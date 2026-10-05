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
        
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-256-gcm'));
        $tag = '';
        $ciphertext = openssl_encrypt($plaintext, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag);
        
        return base64_encode($iv . $tag . $ciphertext);
    }
}

if (!function_exists('decrypt_string')) {
    function decrypt_string(string $payload): string
    {
        if (empty($payload)) return '';
        $key = getenv('APP_KEY');
        if (!$key) throw new \Exception('APP_KEY não configurada no arquivo .env');
        
        $decoded = base64_decode($payload);
        
        // Backward compatibility for AES-256-CBC
        if (strpos($decoded, '::') !== false) {
            list($iv, $ciphertext) = explode('::', $decoded, 2);
            if (strlen($iv) === openssl_cipher_iv_length('AES-256-CBC')) {
                $plaintext = openssl_decrypt($ciphertext, 'AES-256-CBC', $key, 0, $iv);
                return $plaintext !== false ? $plaintext : '';
            }
        }
        
        $ivLen = openssl_cipher_iv_length('aes-256-gcm');
        $tagLen = 16; // GCM default tag length
        
        if (strlen($decoded) < $ivLen + $tagLen) {
            return '';
        }
        
        $iv = substr($decoded, 0, $ivLen);
        $tag = substr($decoded, $ivLen, $tagLen);
        $ciphertext = substr($decoded, $ivLen + $tagLen);
        
        $plaintext = openssl_decrypt($ciphertext, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag);
        return $plaintext !== false ? $plaintext : '';
    }
}
