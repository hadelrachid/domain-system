<?php

namespace DomainSystem\Plugins\auth\Controllers;

use DomainSystem\Core\Theme\ThemeManager;
use DomainSystem\Plugins\Database\QueryBuilder;
use DomainSystem\Plugins\auth\Services\TwoFactorService;

class AuthController
{
    private ThemeManager $theme;
    private QueryBuilder $db;
    private TwoFactorService $twoFactor;

    public function __construct(ThemeManager $theme, QueryBuilder $db, TwoFactorService $twoFactor)
    {
        $this->theme = $theme;
        $this->db = $db;
        $this->twoFactor = $twoFactor;
    }

    public function showLoginForm()
    {
        if (session_status() === PHP_SESSION_NONE) { session_start(); }
        
        if (isset($_SESSION['user_id'])) {
            header("Location: " . BASE_URL . "/admin");
            exit;
        }

        // --- MODO DESENVOLVIMENTO (XAMPP SIMULADO) ---
        if (isset($_SESSION['pending_2fa_email'])) {
            $user = $this->db->table('users')->where('email', '=', $_SESSION['pending_2fa_email'])->first();
            if ($user && !empty($user['two_factor_secret'])) {
                $this->twoFactor->simulateAppCodeGeneration($user);
            }
        }
        // ---------------------------------------------

        $error = $_SESSION['auth_error'] ?? null;
        return $this->theme->render('admin/login', ['error' => $error]);
    }

    public function authenticate()
    {
        if (session_status() === PHP_SESSION_NONE) { session_start(); }
        
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';
        $twofa_code = $_POST['twofa_code'] ?? '';

        $user = $this->db->table('users')->where('email', '=', $email)->first();
        
        $passwordOk = isset($_SESSION['pending_2fa_password_ok']) && $_SESSION['pending_2fa_password_ok'] === true;

        if ($user && ($passwordOk || password_verify($password, $user['password']))) {
            
            $two_factor_type = $user['two_factor_type'] ?? 'none';
            
            // Verifica 2FA via APP
            if ($two_factor_type === 'app' && !empty($user['two_factor_secret'])) {
                if (empty($twofa_code)) {
                    $_SESSION['auth_error'] = "Esta conta exige o Google Authenticator. Digite o código.";
                    $_SESSION['pending_2fa_email'] = $email;
                    $_SESSION['pending_2fa_password_ok'] = true;
                    header("Location: " . BASE_URL . "/login");
                    exit;
                }

                if (!$this->twoFactor->verifyAppCode($user['two_factor_secret'], $twofa_code)) {
                    $_SESSION['auth_error'] = "Código do App inválido.";
                    $_SESSION['pending_2fa_email'] = $email;
                    header("Location: " . BASE_URL . "/login");
                    exit;
                }
            }

            // Verifica 2FA via E-MAIL
            if ($two_factor_type === 'email') {
                if (empty($twofa_code)) {
                    $this->twoFactor->generateAndSendEmailCode($user);
                    
                    $_SESSION['auth_error'] = "Enviamos um código de 6 dígitos para o seu e-mail. Ele expira em 5 minutos.";
                    $_SESSION['pending_2fa_email'] = $email;
                    $_SESSION['pending_2fa_type'] = 'email';
                    $_SESSION['pending_2fa_password_ok'] = true;
                    header("Location: " . BASE_URL . "/login");
                    exit;
                }

                if (!$this->twoFactor->verifyEmailCode($user, $twofa_code)) {
                    $_SESSION['auth_error'] = "Código inválido ou expirado. Clique em reenviar se necessário.";
                    $_SESSION['pending_2fa_email'] = $email;
                    $_SESSION['pending_2fa_type'] = 'email';
                    header("Location: " . BASE_URL . "/login");
                    exit;
                }
            }

            session_regenerate_id(true);
            
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_role'] = $user['role'] ?? 'admin';
            $_SESSION['linked_doctor_id'] = $user['linked_doctor_id'] ?? null;
            
            unset($_SESSION['auth_error'], $_SESSION['pending_2fa_email'], $_SESSION['pending_2fa_type'], $_SESSION['pending_2fa_password_ok']);
            
            header("Location: " . BASE_URL . "/admin");
            exit;
        }

        unset($_SESSION['pending_2fa_email'], $_SESSION['pending_2fa_type'], $_SESSION['pending_2fa_password_ok']);
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
