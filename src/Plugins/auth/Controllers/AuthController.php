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
        // A simulação agora ocorre silenciosamente dentro do 'challenge' se configurado
        if (isset($_SESSION['pending_2fa_email'])) {
            $user = $this->db->table('users')->where('email', '=', $_SESSION['pending_2fa_email'])->first();
            if ($user && !empty($user['two_factor_secret'])) {
                $provider = $this->twoFactor->getProvider('app');
                if ($provider) {
                    $provider->challenge($user);
                }
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
            
            // Abstração limpa: Encontramos o provedor que o usuário escolheu
            if ($two_factor_type !== 'none') {
                $provider = $this->twoFactor->getProvider($two_factor_type);
                
                if ($provider) {
                    // Passo 1: Desafio (Envia o e-mail, ou apenas levanta o erro de falta de código no app)
                    if (empty($twofa_code)) {
                        $provider->challenge($user);
                        
                        $_SESSION['auth_error'] = ($two_factor_type === 'email') 
                            ? "Enviamos um código de 6 dígitos para o seu e-mail. Ele expira em 5 minutos."
                            : "Esta conta exige o Google Authenticator. Digite o código.";
                            
                        $_SESSION['pending_2fa_email'] = $email;
                        $_SESSION['pending_2fa_type'] = $two_factor_type;
                        $_SESSION['pending_2fa_password_ok'] = true;
                        header("Location: " . BASE_URL . "/login");
                        exit;
                    }

                    // Passo 2: Validação
                    if (!$provider->verify($user, $twofa_code)) {
                        $_SESSION['auth_error'] = "Código inválido ou expirado. Tente novamente.";
                        $_SESSION['pending_2fa_email'] = $email;
                        $_SESSION['pending_2fa_type'] = $two_factor_type;
                        header("Location: " . BASE_URL . "/login");
                        exit;
                    }
                }
            }

            session_regenerate_id(true);
            
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_role'] = $user['role'] ?? 'admin';
            
            // Se for médico, busca e salva o ID do médico na sessão
            if ($_SESSION['user_role'] === 'doctor') {
                $doctor = $this->db->table('doctors')->where('user_id', '=', $user['id'])->first();
                $_SESSION['doctor_id'] = $doctor ? $doctor['id'] : null;
            } else {
                $_SESSION['doctor_id'] = null;
            }
            
            unset($_SESSION['auth_error'], $_SESSION['pending_2fa_email'], $_SESSION['pending_2fa_type'], $_SESSION['pending_2fa_password_ok']);
            
            // Roteamento inteligente baseado no papel
            if ($_SESSION['user_role'] === 'lawyer') {
                header("Location: " . BASE_URL . "/admin/legal");
            } else {
                header("Location: " . BASE_URL . "/admin");
            }
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

