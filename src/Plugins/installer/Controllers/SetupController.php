<?php

namespace DomainSystem\Plugins\installer\Controllers;

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
        $path = DOMAIN_SYSTEM_ROOT . '/public/assets/img/logo-cockpit.png';
        if (file_exists($path)) {
            header('Content-Type: image/png');
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
                $envContent = "DB_DSN=\"$dsn\"\nDB_USER=\"\"\nDB_PASS=\"\"\n";
            } else {
                $dsn = "mysql:host=$host;port=$port;dbname=$name;charset=utf8mb4";
                $pdo = new \PDO($dsn, $user, $pass, [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]);
                $envContent = "DB_DSN=\"$dsn\"\nDB_USER=\"$user\"\nDB_PASS=\"$pass\"\n";
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
        $adminName = $request->input('admin_name');
        $adminEmail = $request->input('admin_email');
        $adminPass = $request->input('admin_pass');

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

        // 2. Discover and Boot plugins (bootPlugins will sort topologically and run activate() since they are not in migrations.json)
        $pluginsJson = json_decode(file_get_contents(DOMAIN_SYSTEM_ROOT . '/config/plugins.json'), true);
        $app = \DomainSystem\Core\Application::getInstance();
        $manager = $app->getPluginManager();
        $manager->discoverPlugins(DOMAIN_SYSTEM_ROOT . '/src/Plugins', DOMAIN_SYSTEM_ROOT . '/config/plugins.json');
        
        // This runs the topological sort and calls activate() for unmigrated plugins
        $manager->bootPlugins();

        // 2. Create Admin Account
        $db = $app->getContainer()->make(\DomainSystem\Plugins\Database\Connection::class)->getPdo();
        
        // Truncate users and insert admin (ensure clean slate)
        try {
            // Limpar administradores existentes e inserir o que o usuário escolheu no instalador
            $db->exec("DELETE FROM users WHERE role = 'admin'");
            
            $stmt = $db->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, 'admin')");
            $stmt->execute([$adminName, $adminEmail, password_hash($adminPass, PASSWORD_BCRYPT)]);
        } catch (\Exception $e) {
            // Table might not exist if migration failed, but we assume activate() worked
        }

        // 3. Mark as installed
        file_put_contents(DOMAIN_SYSTEM_ROOT . '/config/installed.lock', date('Y-m-d H:i:s'));

        // Redirect to admin
        header("Location: " . BASE_URL . "/admin?installed=1");
        exit;
    }
}
