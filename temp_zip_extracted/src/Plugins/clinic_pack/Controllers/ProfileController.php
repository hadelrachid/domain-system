<?php

namespace DomainSystem\Plugins\clinic_pack\Controllers;

use DomainSystem\Core\Http\Request;
use DomainSystem\Core\Http\Response;
use DomainSystem\Core\Http\SessionManager;
use DomainSystem\Plugins\clinic_pack\Contracts\UserProfileServiceInterface;
use DomainSystem\Core\Events\EventDispatcher;

class ProfileController
{
    public function __construct(
        private SessionManager $session,
        private UserProfileServiceInterface $userProfileService,
        private EventDispatcher $events
    ) {}

    public function updateProfile(Request $request): Response
    {
        $userId = (int)$this->session->get('user_id');
        
        try {
            $result = $this->userProfileService->updateProfile($userId, $request->all(), $_FILES);
            
            // Dispara evento para plugins que escutam salvamento de perfil (ex: visual_themes)
            $this->events->dispatch('cockpit.profile.save', (string)$userId, $request);
            
            $isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
            if ($isAjax) {
                return new Response(json_encode(['success' => true, 'message' => 'Configurações salvas com sucesso!']), 200, ['Content-Type' => 'application/json']);
            }
            
            $referer = $_SERVER['HTTP_REFERER'] ?? '/admin';
            $redirectUrl = strpos($referer, '?') !== false ? $referer . '&success=1' : $referer . '?success=1';
            return Response::redirect($redirectUrl);

        } catch (\Exception $e) {
            $isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
            if ($isAjax) {
                return new Response(json_encode(['success' => false, 'error' => $e->getMessage()]), 400, ['Content-Type' => 'application/json']);
            }
            
            $referer = $_SERVER['HTTP_REFERER'] ?? '/admin';
            $redirectUrl = strpos($referer, '?') !== false ? $referer . '&error=' . urlencode($e->getMessage()) : $referer . '?error=' . urlencode($e->getMessage());
            return Response::redirect($redirectUrl);
        }
    }
    
    public function generate2fa(Request $request): Response
    {
        // Placeholder para futura implementação
        return new Response("2FA Generate Placeholder");
    }
    
    public function confirm2fa(Request $request): Response
    {
        // Placeholder para futura implementação
        return new Response("2FA Confirm Placeholder");
    }
}
