<?php

namespace Vest\Routing;

use Error;
use Psr\Container\ContainerInterface;
use Vest\Debug\Log;
use Vest\Exceptions\MethodNotAllowedException;
use Vest\Exceptions\RouteNotFoundException;
use Vest\Support\ViewFactory;

/**
 * Class Router
 * 
 * Handles routing logic for HTTP requests. Supports route registration, matching, 
 * URL generation, and middleware handling. Also includes route caching functionality.
 */
class Router
{
  /**
   * @var RouteCollection $routeCollection Stores all registered routes.
   */
  protected RouteCollection $routeCollection;

  /**
   * @var ContainerInterface $container Dependency injection container for resolving middleware and actions.
   */
  protected ContainerInterface $container;

  /**
   * @var RouteCache $cache Caches resolved routes to optimize routing performance.
   */
  protected RouteCache $cache;

  /**
   * @var array $middlewareGroups Groups of middleware defined by name.
   */
  protected array $middlewareGroups = [];

  /**
   * @var array $middlewareAliases Alias to middleware mapping for shorthand middleware references.
   */
  protected array $middlewareAliases = [];

  /**
   * Router constructor.
   * 
   * Initializes the route collection, container, and cache.
   * 
   * @param ContainerInterface $container Dependency injection container.
   */
  public function __construct()
  {
    $this->routeCollection = new RouteCollection();
    $this->cache = new RouteCache();
  }

  /**
   * Register a new route.
   * 
   * @param string $method HTTP method (GET, POST, etc.).
   * @param string $uri Route URI pattern.
   * @param mixed $action The action to execute when the route is matched (controller, closure, etc.).
   * @return Route The newly created route.
   */
  public function add(string $method, string $uri, $action): Route
  {
    $route = new Route($method, $uri, $action);
    $this->routeCollection->addRoute($route);
    return $route;
  }

  /**
   * Resolve a route based on the HTTP method and URI.
   * 
   * If the route is cached, returns it directly from cache. Otherwise, attempts to match the route 
   * from the route collection and processes middleware.
   * 
   * @param string $method HTTP method (GET, POST, etc.).
   * @param string $uri Request URI.
   * @param string|null $domain Optional domain for domain-based routing.
   * @return array|null Array with action and parameters or null if no route found.
   * @throws MethodNotAllowedException If the method is not allowed for the URI.
   * 
   */
  public function resolve(string $method, string $uri, ?string $domain = null): ?array {
    // Remover barras duplas e trailing slash
    $uri = '/' . trim($uri, '/');

    // Check if the route is cached
    if ($this->cache->has($method . $uri)) {
        return $this->cache->get($method . $uri);
    }

    try {
        // Try to match the route
        $route = $this->routeCollection->match($method, $uri, $domain);
        if (!$route) {
            $allowedMethods = $this->routeCollection->getAllowedMethods($uri);
            if (!empty($allowedMethods)) {
                // Log the method not allowed exception
                $logger = Log::getInstance();
                $logger->error("Method not allowed for URI: $uri", [
                    'method' => $method,
                    'allowed_methods' => $allowedMethods,
                ]);
                // Return a friendly message
                http_response_code(405); // Method Not Allowed
                return ['error' => 'Method not allowed for this route.'];
            }

            // Log the route not found exception
            $logger = Log::getInstance();
            $logger->error("Route not found: $uri", [
                'method' => $method,
            ]);
            // Return a friendly message
            http_response_code(404); // Not Found
            return ['error' => 'Route not found.'];
        }

        // Get parameters
        $params = $route->getParameters();
        if (!isset($params['id'])) {
            $params['id'] = null;
        }

        // Resolve middleware
        $middlewares = $this->resolveMiddleware($route);
        foreach ($middlewares as $middleware) {
            if ($this->container && $this->container->has($middleware)) {
                $middlewareInstance = $this->container->get($middleware);
                $middlewareInstance->handle();
            }
        }

        // Prepare the resolved route
        $resolvedRoute = [
            'action' => $route->getAction(),
            'parameters' => $params
        ];

        // Cache the route
        $this->cache->put($method . $uri, $resolvedRoute);
        return $resolvedRoute;

    } catch (\Exception $e) {
        // Log any other exceptions
        $logger = Log::getInstance();
        $logger->error("Unexpected exception: " . $e->getMessage(), [
            'method' => $method,
            'uri' => $uri,
            'stack_trace' => $e->getTraceAsString(),
        ]);
        // Return a generic error response
        http_response_code(500); // Internal Server Error
        return ['error' => 'An unexpected error occurred.'];
    }
}

  /**
   * Generate a URL for a named route with optional parameters.
   * 
   * @param string $name The name of the route.
   * @param array $params Optional parameters to replace in the route URI.
   * @return string|null The generated URI or null if the route does not exist.
   */
  public function generateUrl(string $name, array $params = []): ?string
  {
    $route = $this->routeCollection->getRouteByName($name);
    if (!$route) {
      return null;
    }

    // Replace parameters in the URI
    $uri = $route->getUri();
    foreach ($params as $key => $value) {
      $uri = str_replace('{' . $key . '}', $value, $uri);
    }

    return $uri;
  }

  /**
   * Register a group of routes with shared attributes (e.g., middleware).
   * 
   * @param array $attributes Shared attributes for the route group (e.g., middleware).
   * @param callable $callback A callback that registers routes within the group.
   */
  public function group(array $attributes, callable $callback): void
{
    $registrar = new RouteRegistrar($this);
    $registrar->group($attributes, function($router) use ($callback) {
        $callback($router);
    });
}

  /**
   * Register a middleware group by name.
   * 
   * @param string $name The name of the middleware group.
   * @param array $middlewares An array of middleware classes.
   */
  public function addMiddlewareGroup(string $name, array $middlewares): void
  {
    $this->middlewareGroups[$name] = $middlewares;
  }

  /**
   * Register a middleware alias for shorthand references.
   * 
   * @param string $alias The alias name.
   * @param string $middleware The middleware class the alias refers to.
   */
  public function aliasMiddleware(string $alias, string $middleware): void
  {
    $this->middlewareAliases[$alias] = $middleware;
  }

  /**
   * Resolve middleware for a route.
   * 
   * Combines middleware groups and aliases with the route-specific middleware.
   * 
   * @param Route $route The route to resolve middleware for.
   * @return array A unique list of middleware to be executed for the route.
   */
  protected function resolveMiddleware(Route $route): array
  {
    $middlewares = [];
    foreach ($route->getMiddlewares() as $middleware) {
      if (isset($this->middlewareGroups[$middleware])) {
        $middlewares = array_merge($middlewares, $this->middlewareGroups[$middleware]);
      } elseif (isset($this->middlewareAliases[$middleware])) {
        $middlewares[] = $this->middlewareAliases[$middleware];
      } else {
        $middlewares[] = $middleware;
      }
    }
    return array_unique($middlewares);
  }

  public function routeNotFound(): void
  {
    http_response_code(404); // Define o código de status HTTP
    $view = new ViewFactory(); // Instância do ViewFactory
    $data = []; // Dados que você pode passar para a view, se necessário
    echo $view->make('errors.404', $data); // Renderiza a view de erro 404
  }

  /**
   * Clear the route cache.
   * 
   * Clears all cached routes, forcing future requests to resolve routes afresh.
   */
  public function clearCache(): void
  {
    $this->cache->clear();
  }
}
