<?php

namespace Vest\Support\Inertia;

use Vest\Http\Request;

class InertiaRequest
{
    protected $request;

    public function __construct(Request $request = null)
    {
        $this->request = $request ?: new Request();
    }

    public function isInertiaRequest(): bool
    {
        return $this->request->getHeader('X-Inertia') === 'true';
    }

    public function getUrl(): string
    {
        return $this->request->getUri();
    }
}