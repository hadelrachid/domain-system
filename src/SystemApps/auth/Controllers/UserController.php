<?php

namespace DomainSystem\SystemApps\auth\Controllers;

use DomainSystem\Core\Contracts\ThemeManagerInterface;
use DomainSystem\Core\Contracts\SessionManagerInterface;
use DomainSystem\Core\Contracts\PasswordPolicyInterface;

use DomainSystem\Core\Theme\ThemeManager;
use DomainSystem\SystemApps\auth\Contracts\UserRepositoryInterface;
use DomainSystem\SystemApps\auth\Services\TwoFactorService;

class UserController
{
    private ThemeManagerInterface $theme;
    private UserRepositoryInterface $userRepo;
    private TwoFactorService $twoFactor;

    public function __construct(ThemeManagerInterface $theme, UserRepositoryInterface $userRepo, TwoFactorService $twoFactor, SessionManagerInterface $session, PasswordPolicyInterface $passwordPolicy)
    {
        $this->theme = $theme;
        $this->userRepo = $userRepo;
        $this->twoFactor = $twoFactor;
        $this->session = $session;
        $this->passwordPolicy = $passwordPolicy;
    }

    public function index()
    {
        $users = $this->userRepo->getAllUsers();
        
        return $this->theme->render('admin_users', [
            'users' => $users,
            'theme' => $this->theme
        ], __DIR__ . '/../views');
    }

    public function store(\DomainSystem\Core\Http\Request $request)
    {
        $name = $request->input('name') ?? '';
        $email = $request->input('email') ?? '';
        $password = $request->input('password') ?? '';
        $allowedRoles = ['admin', 'manager', 'user', 'subscriber'];
        $role = in_array($request->input('role') ?? '', $allowedRoles) ? $request->input('role') : 'user';

        if (empty($name) || empty($email) || empty($password)) {
            $this->session->setFlash('error', 'Preencha nome, email e senha.');
        } elseif (!$this->passwordPolicy->isAcceptable($password)) {
            $missing = implode(' ', $this->passwordPolicy->getMissingRequirements($password));
            $this->session->setFlash('error', 'A senha não atende aos requisitos de segurança: ' . $missing);
        } else {
            try {
                $this->userRepo->createUser([
                    'name' => $name,
                    'email' => $email,
                    'password' => password_hash($password, PASSWORD_DEFAULT),
                    'role' => $role
                ]);
                $this->session->setFlash('success', 'Usuário criado com sucesso!');
            } catch (\Exception $e) {
                $this->session->setFlash('error', 'Erro (O E-mail já existe?). Detalhes: ' . $e->getMessage());
            }
        }

        header("Location: " . BASE_URL . "/admin/users");
        exit;
    }

    public function generate2fa(\DomainSystem\Core\Http\Request $request)
    {

        
        $user_id = $request->input('id') ?? null;
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
        $appSecret = $appProvider->generateSecret('DomainSystem');
        
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

    public function confirm2fa(\DomainSystem\Core\Http\Request $request)
    {


        $user_id = $request->input('user_id') ?? null;
        $secret = $request->input('secret') ?? null;
        $code = $request->input('code') ?? null;

        if ($user_id && $secret && $code) {
            $appProvider = $this->twoFactor->getProvider('app');
            if ($appProvider->verify(['two_factor_secret' => $secret], $code)) {
                $this->userRepo->updateTwoFactorSecret($user_id, $secret);
                $this->session->setFlash('success', '2FA ativado com sucesso!');
            } else {
                $this->session->setFlash('error', 'Código inválido. Tente novamente.');
            }
        }

        header("Location: " . BASE_URL . "/admin/users");
        exit;
    }

    public function disable2fa(\DomainSystem\Core\Http\Request $request)
    {


        $user_id = $request->input('id') ?? null;
        if ($user_id) {
            $this->userRepo->updateTwoFactorSecret($user_id, null);
            $this->session->setFlash('success', '2FA desativado.');
        }

        header("Location: " . BASE_URL . "/admin/users");
        exit;
    }

    public function change2faType(\DomainSystem\Core\Http\Request $request)
    {


        $user_id = $request->input('user_id') ?? null;
        $two_factor_type = $request->input('two_factor_type') ?? 'none';

        if ($user_id) {
            $this->userRepo->updateTwoFactor($user_id, $two_factor_type, null);
            $this->session->setFlash('success', 'Método de 2FA atualizado!');
        }

        header("Location: " . BASE_URL . "/admin/users");
        exit;
    }

    public function resetPassword(\DomainSystem\Core\Http\Request $request)
    {


        $user_id = $request->input('user_id') ?? null;
        $new_password = $request->input('new_password') ?? '';

        if ($user_id && !empty($new_password)) {
            if (!$this->passwordPolicy->isAcceptable($new_password)) {
                $missing = implode(' ', $this->passwordPolicy->getMissingRequirements($new_password));
                $this->session->setFlash('error', 'A senha não atende aos requisitos de segurança: ' . $missing);
            } else {
                $this->userRepo->updatePassword($user_id, password_hash($new_password, PASSWORD_DEFAULT));
                $this->session->setFlash('success', 'Senha do usuário redefinida com sucesso!');
            }
        }

        header("Location: " . BASE_URL . "/admin/users");
        exit;
    }

    public function delete(\DomainSystem\Core\Http\Request $request)
    {
        $user_id = $request->input('user_id') ?? null;
        
        if ($user_id) {
            $user = $this->userRepo->findById($user_id);
            if ($user) {
                if ($user['role'] === 'admin') {
                    $this->session->setFlash('error', 'Não é permitido excluir um Administrador Geral.');
                } else {
                    try {
                        $this->userRepo->deleteUser($user_id);
                        $this->session->setFlash('success', 'Usuário excluído com sucesso!');
                    } catch (\Exception $e) {
                        $this->session->setFlash('error', 'Erro ao excluir usuário: ' . $e->getMessage());
                    }
                }
            }
        }
        
        header("Location: " . BASE_URL . "/admin/users");
        exit;
    }
}

