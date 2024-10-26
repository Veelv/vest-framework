<?php

namespace Vest\Support\Inertia;

use Vest\Http\Response;

class InertiaResponse
{
    protected $response;
    protected $component;
    protected $props;
    protected $url;
    protected $version;

    public function __construct(Response $response = null)
    {
        $this->response = $response ?: new Response();
    }

    public function setComponent($component)
    {
        $this->component = $component;
    }

    public function setProps($props)
    {
        $this->props = $props;
    }

    public function setUrl($url)
    {
        $this->url = $url;
    }

    public function setVersion($version)
    {
        $this->version = $version;
    }

    public function toArray(): array
    {
        return [
            'component' => $this->component,
            'props' => $this->props,
            'url' => $this->url,
            'version' => $this->version,
        ];
    }

    public function toResponse(): Response
    {
        $this->response->setHeader('X-Inertia', 'true');
        $this->response->setHeader('Content-Type', 'application/json');
        $this->response->setBody(json_encode($this->toArray()));
        return $this->response;
    }
}