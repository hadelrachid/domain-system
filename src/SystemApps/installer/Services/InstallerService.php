<?php

namespace DomainSystem\SystemApps\installer\Services;

use DomainSystem\SystemApps\installer\Contracts\InstallerServiceInterface;

class InstallerService implements InstallerServiceInterface
{
    private string $tokenFile;

    public function __construct()
    {
        $this->tokenFile = DOMAIN_SYSTEM_ROOT . '/token/setup.token';
    }

    /**
     * Gera um token de segurança único para o Setup, garantindo que
     * apenas quem tem acesso aos arquivos (Hostinger/FTP) instale.
     */
    public function getOrCreateSetupToken(): string
    {
        if (file_exists($this->tokenFile)) {
            return trim(file_get_contents($this->tokenFile));
        }

        // Garante que a pasta existe
        $tokenDir = dirname($this->tokenFile);
        if (!is_dir($tokenDir)) {
            mkdir($tokenDir, 0755, true);
            file_put_contents($tokenDir . '/.htaccess', 'Deny from all');
        }


        // Gera um token no formato XXXX-XXXX
        $token = strtoupper(bin2hex(random_bytes(2)) . '-' . bin2hex(random_bytes(2)));
        
        // Tenta salvar o token
        if (file_put_contents($this->tokenFile, $token) === false) {
            throw new \RuntimeException("Não foi possível gerar o arquivo de segurança em: temp/setup_token.txt. Verifique as permissões da pasta temp/.");
        }

        return $token;
    }

    public function validateSetupToken(string $inputToken): bool
    {
        if (!file_exists($this->tokenFile)) {
            return false;
        }

        $realToken = trim(file_get_contents($this->tokenFile));
        return hash_equals($realToken, trim(strtoupper($inputToken)));
    }

    public function checkRequirements(): array
    {
        $reqs = [
            'php' => version_compare(PHP_VERSION, '8.0.0', '>='),
            'pdo' => extension_loaded('pdo'),
            'pdo_mysql' => extension_loaded('pdo_mysql'),
            'pdo_sqlite' => extension_loaded('pdo_sqlite'),
            'temp_writable' => is_writable(DOMAIN_SYSTEM_ROOT . '/temp'),
            'config_writable' => is_writable(DOMAIN_SYSTEM_ROOT . '/config') || is_writable(dirname(DOMAIN_SYSTEM_ROOT . '/config')),
        ];

        return $reqs;
    }

    public function testConnection(string $driver, string $host, string $port, string $dbname, string $user, string $pass): array
    {
        if ($driver === 'sqlite') {
            $dsn  = 'sqlite:' . DOMAIN_SYSTEM_ROOT . '/database.sqlite';
            $user = '';
            $pass = '';
        } else {
            // Validações básicas contra injeção de DSN
            if (!preg_match('/^[A-Za-z0-9._:-]+$/', $host)) {
                throw new \InvalidArgumentException("Host inválido.");
            }
            if (!ctype_digit($port)) {
                throw new \InvalidArgumentException("Porta inválida.");
            }
            if (!preg_match('/^[\w$-]+$/', $dbname)) {
                throw new \InvalidArgumentException("Nome do banco inválido.");
            }
            $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4";
        }

        try {
            $pdo = new \PDO($dsn, $user, $pass, [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_TIMEOUT => 5
            ]);
            return ['success' => true, 'pdo' => $pdo, 'dsn' => $dsn];
        } catch (\PDOException $e) {
            throw $e;
        }
    }

    public function writeEnvFile(string $dsn, string $user, string $pass): void
    {
        $envPath = DOMAIN_SYSTEM_ROOT . '/.env';
        
        // Lê as variáveis existentes (para não apagar EMERGENCY_KEY ou APP_KEY)
        $existing = [];
        if (file_exists($envPath)) {
            $parsed = parse_ini_file($envPath, false, INI_SCANNER_RAW) ?: [];
            foreach ($parsed as $k => $v) {
                $existing[$k] = $v;
            }
        }
        
        // Sobrescreve apenas as de banco, estado inicial e tema
        $existing['DB_DSN'] = $dsn;
        $existing['DB_USER'] = $user;
        $existing['DB_PASS'] = $pass;
        $existing['SITE_STATUS'] = $existing['SITE_STATUS'] ?? 'coming_soon';
        $existing['ACTIVE_THEME'] = $existing['ACTIVE_THEME'] ?? '';
        
        // Garante a APP_KEY
        if (empty($existing['APP_KEY'])) {
            $existing['APP_KEY'] = bin2hex(random_bytes(16));
        }

        // Garante a EMERGENCY_KEY para chave de recuperação do sistema
        if (empty($existing['EMERGENCY_KEY'])) {
            $existing['EMERGENCY_KEY'] = bin2hex(random_bytes(24));
        }

        $lines = [];
        foreach ($existing as $k => $v) {
            // Remove aspas internas
            $v = trim($v, '"\'');
            $lines[] = "{$k}=\"{$v}\"";
        }
        
        if (file_put_contents($envPath, implode("\n", $lines) . "\n") === false) {
            throw new \RuntimeException("Falha ao gravar arquivo .env. Verifique permissões na raiz do projeto.");
        }
    }
}
