<?php

namespace Vest\Support;

class ViewFactory
{
    protected string $basePath;

    public function __construct(string $basePath)
    {
        $this->basePath = rtrim($basePath, '/');
    }

    /**
     * Renderiza uma view com dados.
     *
     * @param string $view Nome da view
     * @param array $data Dados para a view
     * @return string Conteúdo renderizado
     */
    public function make(string $view, array $data = []): string
    {
        return $this->render($view, $data);
    }

    /**
     * Renderiza uma view.
     *
     * @param string $view Nome da view
     * @param array $data Dados para a view
     * @return string
     */
    protected function render(string $view, array $data): string
    {
        $viewPath = $this->getViewPath($view);

        // Extrai os dados para variáveis
        extract($data);

        ob_start();
        include $viewPath;
        return ob_get_clean();
    }

    /**
     * Obtém o caminho da view.
     *
     * @param string $view Nome da view (ex: 'home.index')
     * @return string Caminho completo para a view
     */
    protected function getViewPath(string $view): string
    {
        return $this->basePath . '/' . str_replace('.', '/', $view) . '.blade.php';
    }
}
