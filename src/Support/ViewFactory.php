<?php

namespace Vest\Support;

class ViewFactory
{
    protected $viewPaths = [];

    public function __construct(array $viewPaths = [])
    {
        $this->viewPaths = $viewPaths;
    }

    public function addPath($path)
    {
        $this->viewPaths[] = rtrim($path, '/') . '/';
    }

    public function render($view, $data = [])
    {
        $viewFile = $this->findViewFile($view);
        if ($viewFile === false) {
            throw new \Exception("View [$view] not found.");
        }

        ob_start();
        extract($data);
        include $viewFile;
        return ob_get_clean();
    }

    protected function findViewFile($view)
    {
        foreach ($this->viewPaths as $path) {
            $viewFile = $path . $view . '.php';
            if (file_exists($viewFile)) {
                return $viewFile;
            }
        }

        return false;
    }
}
