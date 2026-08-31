<?php

namespace DomainSystem\Plugins\auth\Controllers;

use DomainSystem\Core\Theme\ThemeManager;
use DomainSystem\Plugins\auth\Contracts\UserRepositoryInterface;
use DomainSystem\Plugins\auth\Services\TwoFactorService;

class UserController
{
    private ThemeManager $theme;
    private UserRepositoryInterface $userRepo;
    private TwoFactorService $twoFactor;

    public function __construct(ThemeManager $theme, UserRepositoryInterface $userRepo, TwoFactorService $twoFactor)
    {
        $this->theme = $theme;
        $this->userRepo = $userRepo;
        $this->twoFactor = $twoFactor;
    }

    public function index()
    {

        
        $users = $this->userRepo->getAllUsers();
        $doctors = $this->userRepo->getAllDoctors();
        
        return $this->theme->render('admin_users', [
            'users' => $users,
            'doctors' => $doctors,
            'theme' => $this->theme
        ], __DIR__ . '/../views');
    }

    public function store()
    {


        $name = $_POST['name'] ?? '';
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';
        $role = $_POST['role'] ?? 'receptionist';
        $linked_doctor_id = !empty($_POST['linked_doctor_id']) ? $_POST['linked_doctor_id'] : null;

        if (empty($name) || empty($email) || empty($password)) {
            $_SESSION['flash_message'] = ['type' => 'error', 'msg' => 'Preencha nome, email e senha.'];
        } else {
            try {
                $this->userRepo->createUser([
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

        
        $user_id = $_GET['id'] ?? null;
        if (!$user_id) {
            header("Location: " . BASE_URL . "/admin/users");
            exit;
        }

        $user = $this->userRepo->findById($user_id);
        if (!$user) {
            header("Location: " . BASE_URL . "/admin/users");
            exit;
        }

        $appProvider = $this->twoFactor->getProvider('app');
        $appSecret = $appProvider->generateSecret('DaherClinica');
        
        // Simulação p/ Dev Mode
        $user['two_factor_secret'] = $appSecret['secret'];
        $appProvider->challenge($user);

        return $this->theme->render('admin_2fa', [
            'user' => $user,
            'qrCodeUrl' => $appSecret['qrCodeUrl'],
            'secret' => $appSecret['secret'],
            'theme' => $this->theme
        ], __DIR__ . '/../views');
    }

    public function confirm2fa()
    {


        $user_id = $_POST['user_id'] ?? null;
        $secret = $_POST['secret'] ?? null;
        $code = $_POST['code'] ?? null;

        if ($user_id && $secret && $code) {
            $appProvider = $this->twoFactor->getProvider('app');
            if ($appProvider->verify(['two_factor_secret' => $secret], $code)) {
                $this->userRepo->updateTwoFactorSecret($user_id, $secret);
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


        $user_id = $_GET['id'] ?? null;
        if ($user_id) {
            $this->userRepo->updateTwoFactorSecret($user_id, null);
            $_SESSION['flash_message'] = ['type' => 'success', 'msg' => '2FA desativado.'];
        }

        header("Location: " . BASE_URL . "/admin/users");
        exit;
    }

    public function change2faType()
    {


        $user_id = $_POST['user_id'] ?? null;
        $two_factor_type = $_POST['two_factor_type'] ?? 'none';

        if ($user_id) {
            $this->userRepo->updateTwoFactor($user_id, $two_factor_type, null);
            $_SESSION['flash_message'] = ['type' => 'success', 'msg' => 'Método de 2FA atualizado!'];
        }

        header("Location: " . BASE_URL . "/admin/users");
        exit;
    }

    public function resetPassword()
    {


        $user_id = $_POST['user_id'] ?? null;
        $new_password = $_POST['new_password'] ?? '';

        if ($user_id && !empty($new_password)) {
            $this->userRepo->updatePassword($user_id, password_hash($new_password, PASSWORD_DEFAULT));
            $_SESSION['flash_message'] = ['type' => 'success', 'msg' => 'Senha do usuário redefinida com sucesso!'];
        }

        header("Location: " . BASE_URL . "/admin/users");
        exit;
    }
}
