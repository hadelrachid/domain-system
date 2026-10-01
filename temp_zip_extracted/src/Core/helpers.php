<?php

/**
 * Domain-System Helper Functions
 * (Inspirado no WordPress e Laravel)
 */



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
