<?php

namespace DomainSystem\Plugins\auth\Controllers;

use DomainSystem\Core\Theme\ThemeManager;
use DomainSystem\Plugins\Database\QueryBuilder;

class UserController
{
    private ThemeManager $theme;
    private QueryBuilder $db;

    public function __construct(ThemeManager $theme, QueryBuilder $db)
    {
        $this->theme = $theme;
        $this->db = $db;
    }

    public function index()
    {
        if (session_status() === PHP_SESSION_NONE) { session_start(); }
        
        $users = $this->db->table('users')->get();
        $doctors = $this->db->table('doctors')->get();
        
        $theme = $this->theme;
        
        ob_start();
        include __DIR__ . '/../views/admin_users.php';
        return ob_get_clean();
    }

    public function store()
    {
        if (session_status() === PHP_SESSION_NONE) { session_start(); }

        $name = $_POST['name'] ?? '';
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';
        $role = $_POST['role'] ?? 'receptionist';
        $linked_doctor_id = !empty($_POST['linked_doctor_id']) ? $_POST['linked_doctor_id'] : null;

        if (empty($name) || empty($email) || empty($password)) {
            $_SESSION['flash_message'] = ['type' => 'error', 'msg' => 'Preencha nome, email e senha.'];
        } else {
            try {
                $this->db->table('users')->insert([
                    'name' => $name,
                    'email' => $email,
                    'password' => password_hash($password, PASSWORD_DEFAULT),
                    'role' => $role,
                    'linked_doctor_id' => $linked_doctor_id
                ]);
                $_SESSION['flash_message'] = ['type' => 'success', 'msg' => 'Usuário criado com sucesso!'];
            } catch (\Exception $e) {
                $_SESSION['flash_message'] = ['type' => 'error', 'msg' => 'Erro (O E-mail já existe?). Detalhes: ' . $e->getMessage()];
            }
        }

        header("Location: " . BASE_URL . "/admin/users");
        exit;
    }

    public function generate2fa()
    {
        if (session_status() === PHP_SESSION_NONE) { session_start(); }
        
        $user_id = $_GET['id'] ?? null;
        if (!$user_id) {
            header("Location: " . BASE_URL . "/admin/users");
            exit;
        }

        $user = $this->db->table('users')->where('id', '=', $user_id)->first();
        if (!$user) {
            header("Location: " . BASE_URL . "/admin/users");
            exit;
        }

        require_once __DIR__ . '/../GoogleAuthenticator.php';
        $ga = new \PHPGangsta_GoogleAuthenticator();
        
        $secret = $ga->createSecret();
        $qrCodeUrl = $ga->getQRCodeGoogleUrl('DaherClinica', $secret);

        // --- MODO DESENVOLVIMENTO (XAMPP SIMULADO) ---
        // Salva o código de 6 dígitos atual em temp/auth-2fa.txt para facilitar os testes sem celular
        $currentCode = $ga->getCode($secret);
        $tempDir = __DIR__ . '/../../../../temp';
        if (!is_dir($tempDir)) { mkdir($tempDir, 0777, true); }
        file_put_contents($tempDir . '/auth-2fa.txt', "Usuário: {$user['email']}\nSecret: {$secret}\nCódigo Válido Agora: {$currentCode}\n(Este código expira em 30 segundos)");
        // ---------------------------------------------

        $theme = $this->theme;
        ob_start();
        include __DIR__ . '/../views/admin_2fa.php';
        return ob_get_clean();
    }

    public function confirm2fa()
    {
        if (session_status() === PHP_SESSION_NONE) { session_start(); }

        $user_id = $_POST['user_id'] ?? null;
        $secret = $_POST['secret'] ?? null;
        $code = $_POST['code'] ?? null;

        if ($user_id && $secret && $code) {
            require_once __DIR__ . '/../GoogleAuthenticator.php';
            $ga = new \PHPGangsta_GoogleAuthenticator();
            
            $checkResult = $ga->verifyCode($secret, $code, 2); // 2 = 2*30sec clock tolerance
            
            if ($checkResult) {
                $this->db->table('users')->where('id', '=', $user_id)->update([
                    'two_factor_secret' => $secret
                ]);
                $_SESSION['flash_message'] = ['type' => 'success', 'msg' => '2FA ativado com sucesso!'];
            } else {
                $_SESSION['flash_message'] = ['type' => 'error', 'msg' => 'Código inválido. Tente novamente.'];
            }
        }

        header("Location: " . BASE_URL . "/admin/users");
        exit;
    }

    public function disable2fa()
    {
        if (session_status() === PHP_SESSION_NONE) { session_start(); }

        $user_id = $_GET['id'] ?? null;
        if ($user_id) {
            $this->db->table('users')->where('id', '=', $user_id)->update([
                'two_factor_secret' => null
            ]);
            $_SESSION['flash_message'] = ['type' => 'success', 'msg' => '2FA desativado.'];
        }

        header("Location: " . BASE_URL . "/admin/users");
        exit;
    }
}
