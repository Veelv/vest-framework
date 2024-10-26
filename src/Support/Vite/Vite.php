<?php
namespace Vest\Support\Vite;

use Vest\EnvLoader;

class Vite
{
    private bool $isEnabled;

    public function __construct(bool $isEnabled)
    {
        $this->isEnabled = $isEnabled;
    }

    public function load(array $entries): string
    {
        // Se o Vite não estiver habilitado, retorna uma implementação padrão.
        if (!$this->isEnabled) {
            // Aqui você pode incluir scripts padrão ou retornar uma implementação básica
            // Por exemplo, retornar o CSS e JS diretamente do servidor
            return $this->loadFallback($entries);
        }

        $devServerIsRunning = false;

        if (EnvLoader::getenv('APP_MODE') === 'development') {
            $devServerIsRunning = @fsockopen("localhost", 5175);
        }

        if ($devServerIsRunning) {
            $html = '<script type="module" src="http://localhost:5175/@vite/client"></script>';
            $html .= <<<HTML
            <script type="module">
                import RefreshRuntime from 'http://localhost:5175/@react-refresh';
                RefreshRuntime.injectIntoGlobalHook(window);
                window.\$RefreshReg\$ = () => {};
                window.\$RefreshSig\$ = () => (type) => type;
                window.__vite_plugin_react_preamble_installed__ = true;
            </script>
            HTML;
            foreach ($entries as $entry) {
                $html .= '<script type="module" src="http://localhost:5175/' . $entry . '"></script>';
            }
            return $html;
        } else {
            $manifestPath = APP_PATH . 'public/resources/.vite/manifest.json';

            if (file_exists($manifestPath)) {
                $manifest = json_decode(file_get_contents($manifestPath), true);
                $html = '';
                foreach ($entries as $entry) {
                    if (isset($manifest[$entry])) {
                        $file = $manifest[$entry]['file'];
                        if (pathinfo($file, PATHINFO_EXTENSION) === 'css') {
                            $html .= '<link rel="stylesheet" href="' . base_url('resources/' . $file) . '">';
                        } elseif (pathinfo($file, PATHINFO_EXTENSION) === 'js') {
                            $html .= '<script type="module" src="' . base_url('resources/' . $file) . '"></script>';
                        }
                    }
                }
                return $html;
            } else {
                return ''; // Handle missing manifest as needed
            }
        }
    }

    private function loadFallback(array $entries): string
    {
        $html = '';
        foreach ($entries as $entry) {
            // Aqui você pode incluir arquivos CSS e JS diretamente se Vite não estiver habilitado
            if (pathinfo($entry, PATHINFO_EXTENSION) === 'css') {
                $html .= '<link rel="stylesheet" href="' . base_url('resources/' . $entry) . '">';
            } elseif (pathinfo($entry, PATHINFO_EXTENSION) === 'js') {
                $html .= '<script src="' . base_url('resources/' . $entry) . '"></script>';
            }
        }
        return $html;
    }
}
