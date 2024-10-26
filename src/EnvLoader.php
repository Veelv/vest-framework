<?php
namespace Vest;

class EnvLoader
{
    private static array $data = []; // Armazena as variáveis de ambiente

    /**
     * Carrega as variáveis de ambiente a partir de um arquivo .env.
     *
     * @param string $path O caminho para o arquivo .env
     * @throws \Exception Se o arquivo .env não for encontrado
     */
    public static function load(string $path): void
    {
        if (empty(self::$data)) { 
            if (!file_exists($path)) {
                throw new \Exception("Arquivo .env não encontrado: " . $path);
            }

            $content = file_get_contents($path);

            foreach (explode("\n", $content) as $line) {
                $line = trim($line);

                if (!$line || strpos($line, '#') === 0) {
                    continue;
                }

                [$key, $value] = explode('=', $line, 2);
                self::$data[$key] = $value;
            }
        }
    }

    /**
     * Retorna o valor de uma variável de ambiente, ou um valor padrão se não existir.
     *
     * @param string $key A chave da variável desejada
     * @param string|null $default Valor padrão a ser retornado se a chave não existir
     * @return string|null O valor da variável ou o valor padrão especificado
     */
    public static function getenv(string $key): ?string
    {
        return self::$data[$key];
    }
}
