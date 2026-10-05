<?php

namespace DomainSystem\SystemApps\SystemAdmin\Controllers;

use DomainSystem\Core\Contracts\ThemeManagerInterface;
use DomainSystem\Core\Contracts\SessionManagerInterface;
use DomainSystem\SystemApps\auth\Contracts\UserRepositoryInterface;
use DomainSystem\Core\Http\Request;
use DomainSystem\Core\Http\Responses\ViewResponse;
use DomainSystem\Core\Http\Response;

class DevModeController
{
    private ThemeManagerInterface $theme;
    private SessionManagerInterface $session;
    private UserRepositoryInterface $userRepo;

    public function __construct(
        ThemeManagerInterface $theme,
        SessionManagerInterface $session,
        UserRepositoryInterface $userRepo
    ) {
        $this->theme = $theme;
        $this->session = $session;
        $this->userRepo = $userRepo;
    }

    public function prompt(Request $request): \DomainSystem\Core\Contracts\ResponseInterface
    {
        $redirectUrl = $request->input('redirect', BASE_URL . '/admin/plugins');
        
        return new ViewResponse($this->theme->render('dev_mode_prompt', [
            'redirect' => $redirectUrl,
            'error' => $this->session->getFlash('error')
        ], __DIR__ . '/../views'));
    }

    public function authenticate(Request $request): \DomainSystem\Core\Contracts\ResponseInterface
    {
        $password = $request->input('password', '');
        $redirectUrl = $request->input('redirect', BASE_URL . '/admin/plugins');
        
        $userId = $this->session->get('user_id');
        if (!$userId) {
            return Response::redirect(BASE_URL . '/admin/login');
        }

        $user = $this->userRepo->findById($userId);
        
        if ($user && password_verify($password, $user['password'])) {
            // Activa o Dev Mode por 1 hora
            $this->session->set('dev_mode', true);
            $this->session->set('dev_mode_expires', time() + 3600);
            $this->session->setFlash('success', 'Modo Desenvolvedor ativado com sucesso.');
            return Response::redirect($redirectUrl);
        }

        $this->session->setFlash('error', 'Senha incorreta.');
        return Response::redirect(BASE_URL . '/admin/dev-mode?redirect=' . urlencode($redirectUrl));
    }
}
