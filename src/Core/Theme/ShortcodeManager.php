<?php

namespace DomainSystem\Core\Theme;

class ShortcodeManager
{
    /**
     * @var array Registros de shortcodes no formato:
     * [
     *     'tag' => [
     *         'callback' => callable,
     *         'description' => 'Descrição do que faz',
     *         'attributes' => ['attr1' => 'descrição do attr1']
     *     ]
     * ]
     */
    private array $shortcodes = [];
    private ?\DomainSystem\Core\Container\Container $container;

    public function __construct(?\DomainSystem\Core\Container\Container $container = null)
    {
        $this->container = $container;
    }

    /**
     * Registra um novo shortcode.
     */
    public function add(string $tag, $callback, string $description = '', array $attributes = []): void
    {
        $this->shortcodes[$tag] = [
            'callback' => $callback,
            'description' => $description,
            'attributes' => $attributes
        ];
    }

    /**
     * Retorna a lista de todos os shortcodes registrados e suas documentações.
     * Ideal para criar um "Catálogo de Shortcodes" no painel admin.
     *
     * @return array
     */
    public function getRegisteredShortcodes(): array
    {
        return $this->shortcodes;
    }

    /**
     * Procura shortcodes em uma string e os substitui pelo resultado de seus callbacks.
     *
     * @param string $content O conteúdo bruto (HTML do Tema).
     * @return string O conteúdo processado com as injeções feitas.
     */
    public function parse(string $content): string
    {
        if (empty($this->shortcodes)) {
            return $content;
        }

        // Padrão de regex inspirado no WordPress para encontrar [shortcode attr="val"]
        $tags = implode('|', array_map('preg_quote', array_keys($this->shortcodes)));
        
        $pattern = '/\[(' . $tags . ')([^\]]*)\]/';

        return preg_replace_callback($pattern, [$this, 'processTag'], $content);
    }

    /**
     * Callback interno usado pelo preg_replace_callback para processar uma tag encontrada.
     */
    private function processTag(array $matches): string
    {
        $tag = $matches[1];
        $attributesString = $matches[2];
        
        // Parse attributes
        $attributes = $this->parseAttributes($attributesString);

        if (isset($this->shortcodes[$tag])) {
            $callback = $this->shortcodes[$tag]['callback'];
            
            // Suporte para resolução via DI Container para [Controller::class, 'method']
            if (is_array($callback) && is_string($callback[0])) {
                if ($this->container) {
                    $callback[0] = $this->container->make($callback[0]);
                }
            }

            // Executa o plugue (plugin) e retorna o HTML gerado!
            return call_user_func($callback, $attributes);
        }

        return $matches[0]; // Retorna a tag original se não achar o callback (falha de segurança)
    }

    /**
     * Função auxiliar para transformar 'color="dark" id="12"' num array associativo.
     */
    private function parseAttributes(string $text): array
    {
        $attributes = [];
        $pattern = '/(\w+)\s*=\s*"([^"]*)"(?:\s|$)|(\w+)\s*=\s*\'([^\']*)\'(?:\s|$)|(\w+)\s*=\s*([^\s\'"]+)(?:\s|$)|"([^"]*)"(?:\s|$)|(\S+)(?:\s|$)/';
        
        if (preg_match_all($pattern, $text, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                if (!empty($match[1])) {
                    $attributes[strtolower($match[1])] = stripcslashes($match[2]);
                } elseif (!empty($match[3])) {
                    $attributes[strtolower($match[3])] = stripcslashes($match[4]);
                } elseif (!empty($match[5])) {
                    $attributes[strtolower($match[5])] = stripcslashes($match[6]);
                }
            }
        }
        return $attributes;
    }
}
