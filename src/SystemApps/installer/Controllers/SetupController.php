<?php

namespace DomainSystem\SystemApps\installer\Controllers;

use DomainSystem\Core\Http\Request;
use DomainSystem\Core\Http\Response;

class SetupController
{
    public function step1(Request $request): Response
    {
        ob_start();
        include dirname(__DIR__) . '/views/step1.php';
        return new Response(ob_get_clean());
    }

    public function logo(Request $request): Response
    {
        $path = DOMAIN_SYSTEM_ROOT . '/public/assets/img/site-home/logo-rd.svg';
        if (file_exists($path)) {
            header('Content-Type: image/svg+xml');
            readfile($path);
            exit;
        }
        return new Response('', 404);
    }

    public function step2_database(Request $request): Response
    {
        $driver = $request->input('db_driver');
        $host = $request->input('db_host', 'localhost');
        $port = $request->input('db_port', '3306');
        $name = $request->input('db_name');
        $user = $request->input('db_user');
        $pass = $request->input('db_pass', '');

        try {
            if ($driver === 'sqlite') {
                $dsn = "sqlite:" . DOMAIN_SYSTEM_ROOT . "/database.sqlite";
                $pdo = new \PDO($dsn);
                $envContent = "DB_DSN=\"$dsn\"\nDB_USER=\"\"\nDB_PASS=\"\"\nACTIVE_THEME=\"rachidd\"\n";
            } else {
                $dsn = "mysql:host=$host;port=$port;dbname=$name;charset=utf8mb4";
                $pdo = new \PDO($dsn, $user, $pass, [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]);
                $envContent = "DB_DSN=\"$dsn\"\nDB_USER=\"$user\"\nDB_PASS=\"$pass\"\nACTIVE_THEME=\"rachidd\"\n";
            }

            // Save .env
            file_put_contents(DOMAIN_SYSTEM_ROOT . '/.env', $envContent);

            ob_start();
            include dirname(__DIR__) . '/views/step2.php';
            return new Response(ob_get_clean());

        } catch (\PDOException $e) {
            $error = "Falha na conexão: " . $e->getMessage();
            ob_start();
            include dirname(__DIR__) . '/views/step1.php';
            return new Response(ob_get_clean());
        }
    }

    public function step3_install(Request $request): Response
    {
        $adminName = trim($request->input('admin_name', ''));
        $adminEmail = trim($request->input('admin_email', ''));
        $adminPass = $request->input('admin_pass', '');

        if (empty($adminName) || empty($adminEmail) || empty($adminPass)) {
            $error = "Preencha todos os campos do administrador.";
            ob_start();
            include dirname(__DIR__) . '/views/step2.php';
            return new Response(ob_get_clean());
        }

        // 1. Apagar rastro de migrações passadas
        $migrationsPath = DOMAIN_SYSTEM_ROOT . '/temp/migrations.json';
        if (file_exists($migrationsPath)) {
            unlink($migrationsPath);
        }

        // 2. Executar migrações manualmente em vez de dar duplo boot
        $app = \DomainSystem\Core\Application::getInstance();
        $manager = $app->getPluginManager();
        $container = $app->getContainer();
        
        $migrated = [];
        foreach ($manager->getPlugins() as $name => $plugin) {
            try {
                $plugin->activate();
                $migrated[] = $name;
            } catch (\Throwable $e) {}
        }
        
        // Salva o log de migrações
        if (!is_dir(dirname($migrationsPath))) {
            mkdir(dirname($migrationsPath), 0755, true);
        }
        file_put_contents($migrationsPath, json_encode($migrated));

        // 2. Create Admin Account
        $db = $app->getContainer()->make(\DomainSystem\SystemApps\Database\Connection::class)->getPdo();
        
        // Truncate users and insert admin (ensure clean slate)
        try {
            // Limpar administradores existentes e inserir o que o usuário escolheu no instalador
            $db->exec("DELETE FROM users WHERE role = 'admin'");
            
            $stmt = $db->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, 'admin')");
            $stmt->execute([$adminName, $adminEmail, password_hash($adminPass, PASSWORD_BCRYPT)]);
            
            $userId = $db->lastInsertId();
            
            // Auto-login
            $session = $app->getContainer()->make(\DomainSystem\Core\Http\SessionManager::class);
            $session->regenerate();
            $session->set('user_id', $userId);
            $session->set('user_name', $adminName);
            $session->set('user_role', 'admin');
            
            $session->remove('auth_error'); // Limpa qualquer erro de login fantasma
        } catch (\Exception $e) {
            // Em vez de engolir o erro, mostre-o para debug!
            die("Erro crítico ao criar usuário: " . $e->getMessage());
        }

        // O redirecionamento após o sucesso fará o kernel reavaliar a existência do Admin no banco.
        
        file_put_contents(DOMAIN_SYSTEM_ROOT . '/config/installed.lock', date('Y-m-d H:i:s'));
        // Redirect to admin
        header("Location: " . BASE_URL . "/admin");
        exit;
    }
}

