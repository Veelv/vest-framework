<?php

namespace Vest\Support;

use Exception;

class ViewFactory
{
    protected string $baseNamespace = APP_PATH. 'app\Views'; // Definindo o namespace base para as views

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
     * @throws Exception
     */
    protected function render(string $view, array $data): string
    {
        $viewPath = $this->getViewPath($view);

        // Verifica se a view existe
        if (!file_exists($viewPath)) {
            throw new Exception("View [$viewPath] not found.");
        }

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
        return $this->baseNamespace . '/' . str_replace('.', '/', $view) . '.php';
    }
}
