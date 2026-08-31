<?php

namespace DomainSystem\Plugins\auth\Controllers;

use DomainSystem\Core\Theme\ThemeManager;
use DomainSystem\Plugins\Database\QueryBuilder;
use DomainSystem\Plugins\auth\Services\TwoFactorService;
use DomainSystem\Core\Contracts\CockpitRegistryInterface;

class AuthController
{
    private ThemeManager $theme;
    private QueryBuilder $db;
    private TwoFactorService $twoFactor;
    private CockpitRegistryInterface $cockpitRegistry;

    public function __construct(ThemeManager $theme, QueryBuilder $db, TwoFactorService $twoFactor, CockpitRegistryInterface $cockpitRegistry)
    {
        $this->theme = $theme;
        $this->db = $db;
        $this->twoFactor = $twoFactor;
        $this->cockpitRegistry = $cockpitRegistry;
    }

    public function showLoginForm(\DomainSystem\Core\Http\Request $request)
    {
        if (session_status() === PHP_SESSION_NONE) { session_start(); }
        
        if (isset($_SESSION['user_id'])) {
            return \DomainSystem\Core\Http\Response::redirect(\BASE_URL . "/admin");
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
        return new \DomainSystem\Core\Http\Response($this->theme->render('login', ['error' => $error]));
    }

    public function authenticate(\DomainSystem\Core\Http\Request $request)
    {
        if (session_status() === PHP_SESSION_NONE) { session_start(); }
        
        $email = $request->input('email', '');
        $password = $request->input('password', '');
        $twofa_code = $request->input('twofa_code', '');

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
                        return \DomainSystem\Core\Http\Response::redirect(\BASE_URL . "/login");
                    }

                    // Passo 2: Validação
                    if (!$provider->verify($user, $twofa_code)) {
                        $_SESSION['auth_error'] = "Código inválido ou expirado. Tente novamente.";
                        $_SESSION['pending_2fa_email'] = $email;
                        $_SESSION['pending_2fa_type'] = $two_factor_type;
                        return \DomainSystem\Core\Http\Response::redirect(\BASE_URL . "/login");
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
            
            // Roteamento inteligente baseado no papel através do CockpitRegistry (SOLID)
            $provider = $this->cockpitRegistry->getProviderForRole($_SESSION['user_role']);
            if ($provider) {
                return \DomainSystem\Core\Http\Response::redirect(\BASE_URL . $provider->getDashboardRoute());
            }

            // Fallback para o Master Admin
            return \DomainSystem\Core\Http\Response::redirect(\BASE_URL . "/admin");
        }

        unset($_SESSION['pending_2fa_email'], $_SESSION['pending_2fa_type'], $_SESSION['pending_2fa_password_ok']);
        $_SESSION['auth_error'] = "Credenciais inválidas. Verifique seu e-mail e senha.";
        return \DomainSystem\Core\Http\Response::redirect(\BASE_URL . "/login");
    }

    public function logout(\DomainSystem\Core\Http\Request $request)
    {
        if (session_status() === PHP_SESSION_NONE) { session_start(); }
        session_destroy();
        return \DomainSystem\Core\Http\Response::redirect(\BASE_URL . "/login");
    }
}

