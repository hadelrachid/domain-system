<?php

/**
 * Domain-System Helper Functions
 * (Inspirado no WordPress e Laravel)
 */

if (!function_exists('add_shortcode')) {
    /**
     * Registra um novo shortcode.
     *
     * @param string $tag A tag do shortcode (ex: 'agendamento_form')
     * @param callable $callback A função que irá processar e retornar o HTML
     * @param string $description Descrição do que o shortcode faz
     * @param array $attributes Atributos que ele aceita
     */
    function add_shortcode(string $tag, $callback, string $description = '', array $attributes = []): void
    {
        $app = \DomainSystem\Core\Application::getInstance();
        if ($app) {
            $app->getShortcodeManager()->add($tag, $callback, $description, $attributes);
        }
    }
}

if (!function_exists('do_shortcode')) {
    /**
     * Processa uma string procurando e executando os shortcodes registrados.
     *
     * @param string $content Conteúdo bruto, geralmente HTML
     * @return string O conteúdo com os shortcodes processados
     */
    function do_shortcode(string $content): string
    {
        $app = \DomainSystem\Core\Application::getInstance();
        if ($app) {
            return $app->getShortcodeManager()->parse($content);
        }
        return $content; // Se não tiver kernel, retorna sem processar
    }
}

if (!function_exists('encrypt_string')) {
    function encrypt_string(string $plaintext): string
    {
        if (empty($plaintext)) return '';
        $key = getenv('APP_KEY');
        if (!$key) throw new \Exception('APP_KEY não configurada no arquivo .env');
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('AES-256-CBC'));
        $ciphertext = openssl_encrypt($plaintext, 'AES-256-CBC', $key, 0, $iv);
        return base64_encode($iv . '::' . $ciphertext);
    }
}

if (!function_exists('decrypt_string')) {
    function decrypt_string(string $payload): string
    {
        if (empty($payload)) return '';
        $key = getenv('APP_KEY');
        if (!$key) throw new \Exception('APP_KEY não configurada no arquivo .env');
        $decoded = base64_decode($payload);
        if (strpos($decoded, '::') === false) return $payload; // Backward compatibility
        
        list($iv, $ciphertext) = explode('::', $decoded, 2);
        if (strlen($iv) !== openssl_cipher_iv_length('AES-256-CBC')) return $payload;
        
        $plaintext = openssl_decrypt($ciphertext, 'AES-256-CBC', $key, 0, $iv);
        return $plaintext !== false ? $plaintext : '';
    }
}
