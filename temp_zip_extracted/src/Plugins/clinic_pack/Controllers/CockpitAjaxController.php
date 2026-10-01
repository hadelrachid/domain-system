<?php

namespace DomainSystem\Plugins\clinic_pack\Controllers;

use DomainSystem\Core\Http\Request;
use DomainSystem\Core\Http\Response;
use DomainSystem\Core\Http\SessionManager;
use DomainSystem\Plugins\appointments\Contracts\AppointmentRepositoryInterface;
use DomainSystem\Plugins\auth\Contracts\UserRepositoryInterface;
use DomainSystem\Core\Application;

class CockpitAjaxController
{
    public function __construct(
        private SessionManager $session,
        private AppointmentRepositoryInterface $appointmentRepo,
        private UserRepositoryInterface $userRepo
    ) {}

    public function searchHistory(Request $request): Response
    {
        $name = trim($request->input('search_name', $request->input('name', '')));
        $date = trim($request->input('search_date', $request->input('date', '')));
        
        $role = $this->session->get('role');
        $userId = $this->session->get('user_id');
        $doctorId = null;

        if ($role === 'doctor') {
            $user = $this->userRepo->findById($userId);
            $doctorId = $user['linked_doctor_id'] ?? null;
        }

        $results = $this->appointmentRepo->search($name, $date, $doctorId);

        ob_start();
        ?>
        <?php if(empty($results)): ?>
            <div style="text-align:center;padding:40px;color:var(--text-muted);background:var(--bg-card);border-radius:8px;">
                <i class="fas fa-search" style="font-size:36px;margin-bottom:10px;opacity:0.4;"></i>
                <p style="margin:0;">Nenhum atendimento encontrado para os critérios pesquisados.</p>
            </div>
        <?php else: ?>
            <?php foreach($results as $app): ?>
            <div class="appointment-card" style="border-left-color:<?= strtolower($app['status']) === 'cancelado' ? '#ef4444' : (strtolower($app['status']) === 'confirmado' ? '#10b981' : '#64748b') ?>;animation:none;margin-bottom:10px;">
                <div class="info-group">
                    <span class="info-label">Paciente</span>
                    <span class="info-value"><?= htmlspecialchars($app['patient_name'] ?? $app['name'] ?? 'Paciente') ?></span>
                </div>
                <div class="info-group">
                    <span class="info-label">Data &amp; Hora</span>
                    <span class="info-value"><?= date('d/m/Y', strtotime($app['appointment_date'])) ?> às <?= htmlspecialchars($app['appointment_time']) ?></span>
                </div>
                <div class="info-group">
                    <span class="info-label">Profissional</span>
                    <span class="info-value"><?= htmlspecialchars($app['doctor_name'] ?: 'N/A') ?></span>
                </div>
                <div class="info-group">
                    <span class="info-label">Status</span>
                    <span style="padding:4px 10px;border-radius:20px;font-size:11px;font-weight:700;background:<?= strtolower($app['status']) === 'cancelado' ? 'rgba(239, 68, 68, 0.15)' : 'rgba(100, 116, 139, 0.15)' ?>;color:<?= strtolower($app['status']) === 'cancelado' ? '#ef4444' : 'var(--text-main)' ?>;border:1px solid <?= strtolower($app['status']) === 'cancelado' ? 'rgba(239, 68, 68, 0.3)' : 'rgba(100, 116, 139, 0.3)' ?>;">
                        <?= htmlspecialchars($app['status']) ?>
                    </span>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
        <?php
        $html = ob_get_clean();
        return new Response($html);
    }

    public function renderHistoryTabAjax(Request $request): Response
    {
        $role = $this->session->get('role');
        $shortcodeManager = Application::getInstance()->getShortcodeManager();
        
        $viewRole = $request->input('view_role', $role); 
        if (!in_array($viewRole, ['doctor', 'secretary', 'admin'])) {
            $viewRole = 'doctor';
        }
        if ($viewRole === 'admin') $viewRole = 'doctor';
        
        $html = $shortcodeManager->parse('[aba_historico role="' . $viewRole . '" type="today"]');
        return new Response($html);
    }
    
    public function renderTabAjax(Request $request): Response
    {
        $role = $this->session->get('role');
        $shortcodeManager = Application::getInstance()->getShortcodeManager();
        
        $viewRole = $request->input('view_role', $role); 
        if (!in_array($viewRole, ['doctor', 'secretary', 'admin'])) $viewRole = 'doctor';
        if ($viewRole === 'admin') $viewRole = 'doctor';
        
        $tab = $request->input('tab', 'aguardando');
        $html = '';
        if ($tab === 'aguardando') {
            $html = $shortcodeManager->parse('[aba_aguardando role="' . $viewRole . '"]');
        } elseif ($tab === 'confirmados') {
            $html = $shortcodeManager->parse('[aba_confirmados_hoje role="' . $viewRole . '"]');
        } elseif ($tab === 'falta') {
            $html = $shortcodeManager->parse('[aba_nao_compareceu role="' . $viewRole . '"]');
        } elseif ($tab === 'historico') {
            $html = $shortcodeManager->parse('[aba_historico role="' . $viewRole . '" type="today"]');
        }
        
        return new Response($html);
    }

    public function syncPanels(Request $request): Response
    {
        $role = $this->session->get('user_role');
        $userId = $this->session->get('user_id');
        $doctorId = null;

        if ($role === 'doctor') {
            $user = $this->userRepo->findById($userId);
            $doctorId = $user['linked_doctor_id'] ?? null;
        }

        $pending = count($this->appointmentRepo->getPendingQueue($doctorId));
        
        if ($role === 'doctor' && $doctorId) {
            $confirmed = count($this->appointmentRepo->getConfirmedAppointmentsByDoctor($doctorId));
            $historyCount = count($this->appointmentRepo->getHistory($doctorId, '', 'today'));
        } else {
            $confirmed = count($this->appointmentRepo->getAllConfirmedAppointments());
            $historyCount = count($this->appointmentRepo->getHistory(null, '', 'today'));
        }
        
        $missed = 0;
        if (in_array($role, ['secretary', 'receptionist', 'admin'])) {
            $historyFull = $this->appointmentRepo->getHistory(null, '', 'today');
            $missed = count(array_filter($historyFull, fn($a) => strtolower($a['status']) === 'faltou' || strtolower($a['status']) === 'cancelado'));
        }

        return new Response(json_encode([
            'pending' => $pending,
            'confirmed' => $confirmed,
            'missed' => $missed,
            'history' => $historyCount
        ]), 200, ['Content-Type' => 'application/json']);
    }
    
    public function updateAppointmentStatus(Request $request): Response
    {
        $id = $request->input('id');
        $status = $request->input('status');
        
        $this->appointmentRepo->updateStatus($id, $status);
        
        return new Response(json_encode(['success' => true]), 200, ['Content-Type' => 'application/json']);
    }
}
