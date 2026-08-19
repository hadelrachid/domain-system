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

        // --- MODO DESENVOLVIMENTO (XAMPP SIMULADO) ---
        // Se o usuário está preso na tela de 2FA, gera o código no arquivo txt
        if (isset($_SESSION['pending_2fa_email'])) {
            $user = $this->db->table('users')->where('email', '=', $_SESSION['pending_2fa_email'])->first();
            if ($user && !empty($user['two_factor_secret'])) {
                require_once __DIR__ . '/../GoogleAuthenticator.php';
                $ga = new \PHPGangsta_GoogleAuthenticator();
                $currentCode = $ga->getCode($user['two_factor_secret']);
                
                $tempDir = __DIR__ . '/../../../../temp';
                if (!is_dir($tempDir)) { mkdir($tempDir, 0777, true); }
                file_put_contents($tempDir . '/auth-2fa.txt', "Usuário: {$user['email']}\nCódigo Válido Agora: {$currentCode}\n(Este código expira em 30 segundos)");
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

                require_once __DIR__ . '/../GoogleAuthenticator.php';
                $ga = new \PHPGangsta_GoogleAuthenticator();
                if (!$ga->verifyCode($user['two_factor_secret'], $twofa_code, 2)) {
                    $_SESSION['auth_error'] = "Código do App inválido.";
                    $_SESSION['pending_2fa_email'] = $email;
                    header("Location: " . BASE_URL . "/login");
                    exit;
                }
            }

            // Verifica 2FA via E-MAIL
            if ($two_factor_type === 'email') {
                if (empty($twofa_code)) {
                    // Gera o código e salva no banco
                    $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
                    $expiry = date('Y-m-d H:i:s', strtotime('+5 minutes'));
                    $this->db->table('users')->where('id', '=', $user['id'])->update([
                        'email_2fa_code' => $code,
                        'email_2fa_expiry' => $expiry
                    ]);
                    
                    // Simula o envio de e-mail escrevendo no arquivo
                    $tempDir = __DIR__ . '/../../../../temp';
                    if (!is_dir($tempDir)) { mkdir($tempDir, 0777, true); }
                    file_put_contents($tempDir . '/auth-2fa.txt', "--- SIMULAÇÃO DE E-MAIL ---\nPara: {$user['email']}\nAssunto: Seu código de acesso\n\nSeu código é: {$code}\nExpira em 5 minutos.");

                    $_SESSION['auth_error'] = "Enviamos um código de 6 dígitos para o seu e-mail. Ele expira em 5 minutos.";
                    $_SESSION['pending_2fa_email'] = $email;
                    $_SESSION['pending_2fa_type'] = 'email'; // Para exibir o botão de reenviar na tela
                    $_SESSION['pending_2fa_password_ok'] = true;
                    header("Location: " . BASE_URL . "/login");
                    exit;
                }

                // Tem código digitado, vamos validar
                if ($twofa_code !== $user['email_2fa_code'] || date('Y-m-d H:i:s') > $user['email_2fa_expiry']) {
                    $_SESSION['auth_error'] = "Código inválido ou expirado. Clique em reenviar se necessário.";
                    $_SESSION['pending_2fa_email'] = $email;
                    $_SESSION['pending_2fa_type'] = 'email';
                    header("Location: " . BASE_URL . "/login");
                    exit;
                }
            }

            // Previne falha de segurança de fixação de sessão (Session Fixation)
            session_regenerate_id(true);
            
            // Sucesso! Cria a sessão
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_role'] = $user['role'] ?? 'admin';
            $_SESSION['linked_doctor_id'] = $user['linked_doctor_id'] ?? null;
            
            unset($_SESSION['auth_error']);
            unset($_SESSION['pending_2fa_email']);
            unset($_SESSION['pending_2fa_type']);
            unset($_SESSION['pending_2fa_password_ok']);
            
            header("Location: " . BASE_URL . "/admin");
            exit;
        }

        // Falha!
        unset($_SESSION['pending_2fa_email']);
        unset($_SESSION['pending_2fa_type']);
        unset($_SESSION['pending_2fa_password_ok']);
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
