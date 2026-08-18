<?php

namespace DomainSystem\Plugins\Auth\Controllers;

use DomainSystem\Core\Container\Container;
use DomainSystem\Core\Theme\ThemeManager;
use DomainSystem\Plugins\Database\QueryBuilder;

class AuthController
{
    private Container $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    public function showLoginForm()
    {
        // Se já estiver logado, redireciona para o admin
        if (isset($_SESSION['user_id'])) {
            header("Location: " . BASE_URL . "/admin");
            exit;
        }

        /** @var ThemeManager $theme */
        $theme = $this->container->make(ThemeManager::class);
        
        try {
            return $theme->render('auth/login', [
                'error' => $_SESSION['auth_error'] ?? null
            ]);
        } catch (\Exception $e) {
            // Fallback view se o tema não possuir auth/login.php
            return $this->getFallbackLoginView($_SESSION['auth_error'] ?? null);
        }
    }

    public function authenticate()
    {
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';

        /** @var QueryBuilder $db */
        $db = $this->container->make(QueryBuilder::class);
        $user = $db->table('users')->where('email', '=', $email)->first();

        if ($user && password_verify($password, $user['password'])) {
            // Sucesso! Cria a sessão
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            unset($_SESSION['auth_error']);
            
            header("Location: " . BASE_URL . "/admin");
            exit;
        }

        // Falha!
        $_SESSION['auth_error'] = "Credenciais inválidas. Verifique seu e-mail e senha.";
        header("Location: " . BASE_URL . "/login");
        exit;
    }

    public function logout()
    {
        session_destroy();
        header("Location: " . BASE_URL . "/login");
        exit;
    }

    private function getFallbackLoginView($error)
    {
        $errorHtml = $error ? "<div style='color: #d63638; background: #fcf0f1; padding: 10px; margin-bottom: 15px; border-left: 4px solid #d63638;'>{$error}</div>" : '';
        
        return "
        <!DOCTYPE html>
        <html lang='pt-BR'>
        <head>
            <meta charset='UTF-8'>
            <title>Login - Domain System</title>
            <style>
                body { background: #f0f0f1; display: flex; justify-content: center; align-items: center; height: 100vh; font-family: sans-serif; margin: 0; }
                .login-box { background: #fff; padding: 30px; border-radius: 5px; box-shadow: 0 1px 3px rgba(0,0,0,.13); width: 320px; }
                .login-box h1 { margin-top: 0; font-size: 24px; color: #1d2327; text-align: center; }
                .login-box label { display: block; margin-bottom: 5px; color: #1d2327; font-size: 14px; }
                .login-box input[type='email'], .login-box input[type='password'] { width: 100%; padding: 8px; margin-bottom: 15px; border: 1px solid #8c8f94; border-radius: 4px; box-sizing: border-box; }
                .login-box button { width: 100%; background: #2271b1; color: #fff; border: none; padding: 10px; border-radius: 4px; cursor: pointer; font-size: 14px; font-weight: bold; }
                .login-box button:hover { background: #135e96; }
            </style>
        </head>
        <body>
            <div class='login-box'>
                <h1>Autenticação</h1>
                {$errorHtml}
                <form method='POST' action='" . BASE_URL . "/login'>
                    <label>E-mail</label>
                    <input type='email' name='email' required autofocus>
                    
                    <label>Senha</label>
                    <input type='password' name='password' required>
                    
                    <button type='submit'>Entrar no Cockpit</button>
                </form>
            </div>
            <script>
                // Limpa a mensagem de erro da sessão logo após renderizar
                fetch('" . BASE_URL . "/login', {method: 'POST', body: new URLSearchParams({clear_error: 1})}).catch(()=>null);
            </script>
        </body>
        </html>
        ";
    }
}
