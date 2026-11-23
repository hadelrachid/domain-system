<?php

declare(strict_types=1);

namespace DomainSystem\SystemApps\SystemAdmin\Controllers;

use DomainSystem\Core\Contracts\SessionManagerInterface;
use DomainSystem\Core\Contracts\ThemeManagerInterface;
use DomainSystem\Core\Http\Request;
use DomainSystem\Core\Http\Responses\ViewResponse;

class EmergencyController
{
    private ThemeManagerInterface $theme;
    private SessionManagerInterface $session;

    public function __construct(ThemeManagerInterface $theme, SessionManagerInterface $session)
    {
        $this->theme = $theme;
        $this->session = $session;
    }

    public function index()
    {
        return new ViewResponse($this->theme->render('emergency', [
            'csrfToken' => (string)$this->session->getCsrfToken(),
        ]), false);
    }

    public function login(Request $request)
    {
        $emergencyKey = getenv('EMERGENCY_KEY');
        $inputKey = (string)$request->input('app_key', '');
        $csrfToken = (string)$request->input('csrf_token', '');
        $expectedCsrf = (string)$this->session->getCsrfToken();

        // Log & Rate-Limit setup
        $tempDir = dirname(__DIR__, 5) . '/temp';
        if (!is_dir($tempDir)) {
            @mkdir($tempDir, 0775, true);
        }
        $logFile = $tempDir . '/emergency_access.log';
        $rateFile = $tempDir . '/emergency_rate_limit.json';
        $ip = (string)($_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN');
        $timestamp = date('Y-m-d H:i:s');

        // 1. Validação CSRF
        if ($csrfToken === '' || $expectedCsrf === '' || !hash_equals($expectedCsrf, $csrfToken)) {
            @file_put_contents($logFile, "[$timestamp] [CSRF_BLOCKED] IP: $ip token CSRF invalido na Escotilha de Emergencia.\n", FILE_APPEND);
            return new ViewResponse($this->theme->render('emergency', [
                'error'     => 'Sessão expirada. Recarregue a página e tente novamente.',
                'csrfToken' => $expectedCsrf,
            ]), false, 403);
        }

        // 2. Proteção contra Brute-Force (máx. 5 tentativas a cada 15 minutos por IP)
        $rateData = file_exists($rateFile) ? (json_decode((string)@file_get_contents($rateFile), true) ?: []) : [];
        $now = time();
        $ipRecord = $rateData[$ip] ?? ['attempts' => 0, 'blocked_until' => 0];
        if (!empty($ipRecord['blocked_until']) && $now < (int)$ipRecord['blocked_until']) {
            @file_put_contents($logFile, "[$timestamp] [RATE_LIMITED] IP: $ip bloqueado por excesso de tentativas.\n", FILE_APPEND);
            return new ViewResponse($this->theme->render('emergency', [
                'error'     => 'Muitas tentativas inválidas. Acesso bloqueado temporariamente por 15 minutos.',
                'csrfToken' => $expectedCsrf,
            ]), false, 429);
        }

        // 3. Impede uso se a chave de emergência não estiver configurada ou for fraca (< 16 chars)
        if (!is_string($emergencyKey) || strlen(trim($emergencyKey)) < 16) {
            @file_put_contents($logFile, "[$timestamp] [BLOCKED] IP: $ip tentou acesso de emergencia, mas EMERGENCY_KEY nao configurada ou fraca no servidor.\n", FILE_APPEND);
            return new ViewResponse($this->theme->render('emergency', [
                'error'     => 'Acesso de Emergência Desativado no Servidor.',
                'csrfToken' => $expectedCsrf,
            ]), false, 403);
        }

        // 4. Mitigação contra Timing Attack usando hash_equals + regeneração de sessão
        if ($inputKey !== '' && hash_equals($emergencyKey, $inputKey)) {
            unset($rateData[$ip]);
            @file_put_contents($rateFile, json_encode($rateData));

            $this->session->regenerate();
            $this->session->set('user_id', 9999);
            $this->session->set('user_role', 'admin');
            $this->session->set('user_name', 'EMERGENCY_ADMIN');

            @file_put_contents($logFile, "[$timestamp] [CRITICAL_ACCESS_GRANTED] IP: $ip ativou a Escotilha de Emergencia.\n", FILE_APPEND);

            return \DomainSystem\Core\Http\Response::redirect(BASE_URL . "/admin/plugins");
        }

        $attempts = (int)($ipRecord['attempts'] ?? 0) + 1;
        $rateData[$ip] = [
            'attempts'      => $attempts,
            'blocked_until' => $attempts >= 5 ? ($now + 900) : 0,
        ];
        @file_put_contents($rateFile, json_encode($rateData));

        @file_put_contents($logFile, "[$timestamp] [FAILED_ATTEMPT] IP: $ip falhou ao tentar abrir a Escotilha de Emergencia (tentativa {$attempts}/5).\n", FILE_APPEND);

        return new ViewResponse($this->theme->render('emergency', [
            'error'     => 'Chave de Emergência Inválida. Acesso Bloqueado.',
            'csrfToken' => $expectedCsrf,
        ]), false, 401);
    }
}
