<?php

namespace DomainSystem\Tests\Unit;

use PHPUnit\Framework\TestCase;

class HelpersTest extends TestCase
{
    protected function setUp(): void
    {
        // Define APP_KEY se não estiver definida
        if (!getenv('APP_KEY')) {
            putenv('APP_KEY=12345678901234567890123456789012'); // 32 bytes key
        }
        
        // Garante que os helpers estão carregados
        require_once __DIR__ . '/../../src/Core/helpers.php';
    }

    public function testEncryptAndDecryptGCM()
    {
        $plaintext = "Dados muito secretos para o ring 0";
        $encrypted = encrypt_string($plaintext);
        
        $this->assertNotEmpty($encrypted);
        $this->assertNotEquals($plaintext, $encrypted);
        
        $decrypted = decrypt_string($encrypted);
        $this->assertEquals($plaintext, $decrypted);
    }

    public function testGcmDetectsTampering()
    {
        $plaintext = "Dados originais";
        $encrypted = encrypt_string($plaintext);
        
        // Decodifica, altera um byte do ciphertext e re-encoda
        $raw = base64_decode($encrypted);
        $raw[strlen($raw) - 1] = chr(ord($raw[strlen($raw) - 1]) ^ 1); // Flip last bit
        $tampered = base64_encode($raw);
        
        // Com GCM, deve falhar a autenticação e retornar string vazia
        $decrypted = decrypt_string($tampered);
        $this->assertEquals('', $decrypted);
    }

    public function testBackwardCompatibilityCbc()
    {
        $key = getenv('APP_KEY');
        $plaintext = "Dados legado em CBC";
        
        // Simula a criação de um payload legado em AES-256-CBC
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('AES-256-CBC'));
        $ciphertext = openssl_encrypt($plaintext, 'AES-256-CBC', $key, 0, $iv);
        $legacyPayload = base64_encode($iv . '::' . $ciphertext);
        
        // Deve decifrar com sucesso usando a lógica de fallback
        $decrypted = decrypt_string($legacyPayload);
        $this->assertEquals($plaintext, $decrypted);
    }
}
