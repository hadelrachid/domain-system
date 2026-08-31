<?php

namespace DomainSystem\Plugins\auth\Controllers;

use DomainSystem\Core\Theme\ThemeManager;
use DomainSystem\Core\Http\SessionManager;
use DomainSystem\Plugins\auth\Contracts\UserRepositoryInterface;
use DomainSystem\Plugins\auth\Services\TwoFactorService;
use DomainSystem\Core\Contracts\CockpitRegistryInterface;

class AuthController
{
    private ThemeManager $theme;
    private UserRepositoryInterface $userRepo;
    private TwoFactorService $twoFactor;
    private CockpitRegistryInterface $cockpitRegistry;
    private SessionManager $session;

    public function __construct(
        ThemeManager $theme,
        UserRepositoryInterface $userRepo,
        TwoFactorService $twoFactor,
        CockpitRegistryInterface $cockpitRegistry,
        SessionManager $session
    ) {
        $this->theme           = $theme;
        $this->userRepo        = $userRepo;
        $this->twoFactor       = $twoFactor;
        $this->cockpitRegistry = $cockpitRegistry;
        $this->session         = $session;
    }

    public function showLoginForm(\DomainSystem\Core\Http\Request $request)
    {
        if ($this->session->has('user_id')) {
            return \DomainSystem\Core\Http\Response::redirect(\BASE_URL . "/admin");
        }

        if ($this->session->has('pending_2fa_email')) {
            $user = $this->userRepo->findByEmail($this->session->get('pending_2fa_email'));
            if ($user && !empty($user['two_factor_secret'])) {
                $provider = $this->twoFactor->getProvider('app');
                if ($provider) {
                    $provider->challenge($user);
                }
            }
        }

        $error = $this->session->get('auth_error');
        return new \DomainSystem\Core\Http\Response($this->theme->render('login', ['error' => $error]));
    }

    public function authenticate(\DomainSystem\Core\Http\Request $request)
    {
        $email      = $request->input('email', '');
        $password   = $request->input('password', '');
        $twofa_code = $request->input('twofa_code', '');

        $user       = $this->userRepo->findByEmail($email);
        $passwordOk = $this->session->get('pending_2fa_password_ok') === true;

        if ($user && ($passwordOk || password_verify($password, $user['password']))) {

            $two_factor_type = $user['two_factor_type'] ?? 'none';

            if ($two_factor_type !== 'none') {
                $provider = $this->twoFactor->getProvider($two_factor_type);
                if ($provider) {
                    if (empty($twofa_code)) {
                        $provider->challenge($user);
                        $this->session->set('auth_error', ($two_factor_type === 'email')
                            ? "Enviamos um código de 6 dígitos para o seu e-mail. Ele expira em 5 minutos."
                            : "Esta conta exige o Google Authenticator. Digite o código.");
                        $this->session->set('pending_2fa_email', $email);
                        $this->session->set('pending_2fa_type', $two_factor_type);
                        $this->session->set('pending_2fa_password_ok', true);
                        return \DomainSystem\Core\Http\Response::redirect(\BASE_URL . "/login");
                    }
                    if (!$provider->verify($user, $twofa_code)) {
                        $this->session->set('auth_error', "Código inválido ou expirado. Tente novamente.");
                        $this->session->set('pending_2fa_email', $email);
                        $this->session->set('pending_2fa_type', $two_factor_type);
                        return \DomainSystem\Core\Http\Response::redirect(\BASE_URL . "/login");
                    }
                }
            }

            $this->session->regenerate();
            $this->session->set('user_id',   $user['id']);
            $this->session->set('user_name', $user['name']);
            $this->session->set('user_role', $user['role'] ?? 'admin');

            if ($this->session->get('user_role') === 'doctor') {
                $doctor = $this->userRepo->findDoctorByUserId($user['id']);
                $this->session->set('doctor_id', $doctor ? $doctor['id'] : null);
            } else {
                $this->session->set('doctor_id', null);
            }

            $this->session->remove('auth_error');
            $this->session->remove('pending_2fa_email');
            $this->session->remove('pending_2fa_type');
            $this->session->remove('pending_2fa_password_ok');

            $provider = $this->cockpitRegistry->getProviderForRole($this->session->get('user_role'));
            if ($provider) {
                return \DomainSystem\Core\Http\Response::redirect(\BASE_URL . $provider->getDashboardRoute());
            }

            return \DomainSystem\Core\Http\Response::redirect(\BASE_URL . "/admin");
        }

        $this->session->remove('pending_2fa_email');
        $this->session->remove('pending_2fa_type');
        $this->session->remove('pending_2fa_password_ok');
        $this->session->set('auth_error', "Credenciais inválidas. Verifique seu e-mail e senha.");
        return \DomainSystem\Core\Http\Response::redirect(\BASE_URL . "/login");
    }

    public function logout(\DomainSystem\Core\Http\Request $request)
    {
        $this->session->destroy();
        return \DomainSystem\Core\Http\Response::redirect(\BASE_URL . "/login");
    }
}
