<?php

namespace DomainSystem\Plugins\auth\Controllers;

use DomainSystem\Core\Theme\ThemeManager;
use DomainSystem\Plugins\Database\QueryBuilder;

class AuthController
{
    private ThemeManager $theme;
    private QueryBuilder $db;

    public function __construct(ThemeManager $theme, QueryBuilder $db)
    {
        $this->theme = $theme;
        $this->db = $db;
    }

    public function showLoginForm()
    {
        if (session_status() === PHP_SESSION_NONE) { session_start(); }
        
        // Se já estiver logado, redireciona
        if (isset($_SESSION['user_id'])) {
            header("Location: " . BASE_URL . "/admin");
            exit;
        }

        $error = $_SESSION['auth_error'] ?? null;
        return $this->theme->render('admin/login', ['error' => $error]);
    }

    public function authenticate()
    {
        if (session_status() === PHP_SESSION_NONE) { session_start(); }
        
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';

        $user = $this->db->table('users')->where('email', '=', $email)->first();

        if ($user && password_verify($password, $user['password'])) {
            // Previne falha de segurança de fixação de sessão (Session Fixation)
            session_regenerate_id(true);
            
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
        if (session_status() === PHP_SESSION_NONE) { session_start(); }
        session_destroy();
        header("Location: " . BASE_URL . "/login");
        exit;
    }
}
