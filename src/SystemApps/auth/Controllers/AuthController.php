<?php

declare(strict_types=1);

namespace DomainSystem\SystemApps\auth\Controllers;

use DomainSystem\Core\Theme\ThemeManager;
use DomainSystem\Core\Security\SessionManager;
use DomainSystem\Core\Security\SafeRedirectValidator;
use DomainSystem\Core\Contracts\SafeRedirectValidatorInterface;
use DomainSystem\SystemApps\auth\Contracts\LoginThrottleInterface;
use DomainSystem\SystemApps\auth\Contracts\UserRepositoryInterface;
use DomainSystem\SystemApps\auth\Services\TwoFactorService;
use DomainSystem\Core\Contracts\CockpitRegistryInterface;
use DomainSystem\Core\Security\IdentityManager;

class AuthController
{
    private ThemeManager $theme;
    private UserRepositoryInterface $userRepo;
    private TwoFactorService $twoFactor;
    private CockpitRegistryInterface $cockpitRegistry;
    private SessionManager $session;
    private SafeRedirectValidatorInterface $redirectValidator;
    private ?LoginThrottleInterface $throttle;
    private ?IdentityManager $identity;

    public function __construct(
        ThemeManager $theme,
        UserRepositoryInterface $userRepo,
        TwoFactorService $twoFactor,
        CockpitRegistryInterface $cockpitRegistry,
        SessionManager $session,
        ?SafeRedirectValidatorInterface $redirectValidator = null,
        ?LoginThrottleInterface $throttle = null,
        ?IdentityManager $identity = null
    ) {
        $this->theme           = $theme;
        $this->userRepo        = $userRepo;
        $this->twoFactor       = $twoFactor;
        $this->cockpitRegistry = $cockpitRegistry;
        $this->session         = $session;
        $this->redirectValidator = $redirectValidator ?? new SafeRedirectValidator();
        $this->throttle        = $throttle;
        $this->identity        = $identity;
    }

    public function showLoginForm(\DomainSystem\Core\Http\Request $request)
    {
        $baseUrl = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';
        if ($this->session->has('user_id')) {
            $userRole = (string)$this->session->get('user_role', 'admin');
            $provider = $this->cockpitRegistry->getProviderForRole($userRole);
            if ($provider) {
                return \DomainSystem\Core\Http\Response::redirect($baseUrl . $provider->getDashboardRoute());
            }
            if (in_array(strtolower($userRole), ['admin', 'manager', 'superadmin'], true)) {
                return \DomainSystem\Core\Http\Response::redirect($baseUrl . "/admin");
            }
            // Sessão comum não autorizada para o Cockpit: limpa para permitir tela de login limpa
            $this->session->destroy();
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
        $this->session->remove('auth_error');
        return new \DomainSystem\Core\Http\Response($this->theme->render('login', ['error' => $error]));
    }

    public function authenticate(\DomainSystem\Core\Http\Request $request)
    {
        $baseUrl    = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';
        $email      = trim($request->input('email', ''));
        $password   = $request->input('password', '');
        $twofa_code = $request->input('twofa_code', '');

        // T14: Throttle anti-força bruta por chave de e-mail e IP
        $clientIp = (string)($request->server['REMOTE_ADDR'] ?? '127.0.0.1');
        $throttleKey = strtolower($email) . '|' . $clientIp;

        if ($this->throttle !== null && $this->throttle->tooManyAttempts($throttleKey)) {
            $this->session->set('auth_error', "Muitas tentativas de login incorretas. Aguarde alguns minutos antes de tentar novamente.");
            return \DomainSystem\Core\Http\Response::redirect($baseUrl . "/login");
        }

        $user       = $this->userRepo->findByEmail($email);
        $passwordOk = ($this->session->get('pending_2fa_password_ok') === true) && ($this->session->get('pending_2fa_email') === $email);

        // T21: Mitigação de Timing Attack via dummy hash de custo equivalente (CWE-208)
        $dummyHash = '$2y$10$abcdefghijklmnopqrstuuABCDEFGHIJKLMNOPQRSTUVWXYZ012';
        $hashToVerify = ($user && !empty($user['password'])) ? (string)$user['password'] : $dummyHash;
        $passwordMatches = password_verify($password, $hashToVerify);

        if ($user && ($passwordOk || $passwordMatches)) {
            $this->throttle?->clear($throttleKey);

            $baseUrl = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';
            $userStatus = (string)($user['status'] ?? 'active');
            if ($userStatus === 'banned') {
                $this->session->remove('pending_2fa_email');
                $this->session->remove('pending_2fa_type');
                $this->session->remove('pending_2fa_password_ok');
                $this->session->set('auth_error', "Esta conta foi banida por violação das diretrizes da plataforma.");
                return \DomainSystem\Core\Http\Response::redirect($baseUrl . "/login");
            }
            if ($userStatus === 'suspended') {
                $this->session->remove('pending_2fa_email');
                $this->session->remove('pending_2fa_type');
                $this->session->remove('pending_2fa_password_ok');
                $this->session->set('auth_error', "Sua conta está temporariamente suspensa. Entre em contato com o suporte.");
                return \DomainSystem\Core\Http\Response::redirect($baseUrl . "/login");
            }

            // =========================================================================
            // CATRACA DO COCKPIT (Acesso Exclusivo a Administradores / Gestores)
            // =========================================================================
            // Usuários comuns, clientes e alunos não possuem permissão de acesso ao
            // Cockpit administrativo. Devem ser rejeitados com erro de credenciais.
            $rawRole = strtolower((string)($user['role'] ?? 'user'));
            $isRoleAdmin = in_array($rawRole, ['admin', 'manager', 'superadmin'], true);
            $hasProvider = $this->cockpitRegistry->getProviderForRole($rawRole) !== null;
            $hasAclAdmin = false;
            if ($this->identity !== null) {
                $hasAclAdmin = $this->identity->hasRole((int)$user['id'], 'admin')
                    || $this->identity->hasRole((int)$user['id'], 'manager')
                    || $this->identity->userCan((int)$user['id'], '*');
            }

            if (!$isRoleAdmin && !$hasProvider && !$hasAclAdmin) {
                $this->throttle?->hit($throttleKey);
                $this->session->remove('pending_2fa_email');
                $this->session->remove('pending_2fa_type');
                $this->session->remove('pending_2fa_password_ok');
                $this->session->set('auth_error', "Credenciais inválidas. Verifique seu e-mail e senha.");
                $redirectParam = (string)$request->input('redirect', '');
                return \DomainSystem\Core\Http\Response::redirect($baseUrl . "/login" . ($redirectParam !== '' ? '?redirect=' . urlencode($redirectParam) : ''));
            }

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
            $this->session->remove('pending_2fa_email');
            $this->session->remove('pending_2fa_type');
            $this->session->remove('pending_2fa_password_ok');
            $this->session->set('user_id',   $user['id']);
            $this->session->set('user_name', $user['name']);
            $this->session->set('user_role', $user['role'] ?? 'admin');

            // Isolamento rigoroso: elimina qualquer resquício ou eco de sessões/flashes de aluno
            $this->session->remove('academy_student_id');
            $this->session->remove('academy_student_name');
            $this->session->remove('academy_student_username');
            $this->session->remove('academy_student_email');
            $this->session->remove('academy_student_flash');
            $this->session->remove('flash_message');

            $this->session->remove('auth_error');
            $this->session->remove('pending_2fa_email');
            $this->session->remove('pending_2fa_type');
            $this->session->remove('pending_2fa_password_ok');

            $redirectParam = (string)$request->input('redirect', '');
            if ($redirectParam !== '') {
                $safeTarget = $this->redirectValidator->sanitize($redirectParam, '');
                if ($safeTarget !== '') {
                    $targetUrl = (str_starts_with($safeTarget, 'http://') || str_starts_with($safeTarget, 'https://'))
                        ? $safeTarget
                        : ($baseUrl !== '' && !str_starts_with($safeTarget, $baseUrl) ? $baseUrl . $safeTarget : $safeTarget);
                    return \DomainSystem\Core\Http\Response::redirect($targetUrl);
                }
            }

            $userRole = (string)$this->session->get('user_role', 'admin');
            $provider = $this->cockpitRegistry->getProviderForRole($userRole);
            if ($provider) {
                return \DomainSystem\Core\Http\Response::redirect($baseUrl . $provider->getDashboardRoute());
            }

            if (in_array(strtolower($userRole), ['admin', 'manager', 'superadmin'], true)) {
                return \DomainSystem\Core\Http\Response::redirect($baseUrl . "/admin");
            }

            return \DomainSystem\Core\Http\Response::redirect($baseUrl !== '' ? $baseUrl : "/");
        }

        $this->throttle?->hit($throttleKey);
        $this->session->remove('pending_2fa_email');
        $this->session->remove('pending_2fa_type');
        $this->session->remove('pending_2fa_password_ok');
        $this->session->set('auth_error', "Credenciais inválidas. Verifique seu e-mail e senha.");
        return \DomainSystem\Core\Http\Response::redirect($baseUrl . "/login");
    }

    public function logout(\DomainSystem\Core\Http\Request $request)
    {
        $this->session->destroy();
        return \DomainSystem\Core\Http\Response::redirect(\BASE_URL . "/login");
    }
}
