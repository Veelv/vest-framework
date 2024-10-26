<?php

namespace Vest\Inertia;

use Vest\Http\Response;
use Vest\Support\ControllerFactory;
use Vest\Support\Inertia\InertiaRequest;
use Vest\Support\Inertia\InertiaResponse;
use Vest\Support\ViewFactory;

class Inertia
{
    protected static $sharedProps = [];
    protected static $version = '1.0';
    protected static $rootView = 'home';

    public static function render($component, $props = []): string
{
    // Renderiza o componente no lado do servidor
    $html = self::renderComponent($component, $props);

    return $html;
}

protected static function renderComponent($component, $props = [])
{
    // Crie uma instância do componente
    $componentInstance = new $component();

    // Passe as props para o componente
    $componentInstance->props = $props;

    // Renderize o componente
    $html = $componentInstance->render();

    return $html;
}

    public static function share($key, $value = null)
    {
        if (is_array($key)) {
            self::$sharedProps = array_merge(self::$sharedProps, $key);
        } else {
            self::$sharedProps[$key] = $value;
        }
    }

    public static function setVersion($version)
    {
        self::$version = $version;
    }

    public static function setRootView($view)
    {
        self::$rootView = $view;
    }

    protected static function renderInitialPage(InertiaResponse $response)
    {
        $page = $response->toArray();
        $escapedPage = htmlspecialchars(json_encode($page), ENT_QUOTES, 'UTF-8');

        $viewFactory = new ViewFactory();
        $content = $viewFactory->make(self::$rootView, ['page' => $escapedPage]);

        return $content;
    }
}
