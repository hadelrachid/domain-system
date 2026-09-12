<?php

namespace DomainSystem\Plugins\clinic_pack\Controllers;

use DomainSystem\Core\Theme\ThemeManager;
use DomainSystem\Core\Http\Request;
use DomainSystem\Core\Http\Response;

class CockpitController
{
    private ThemeManager $theme;
    private \DomainSystem\Core\Http\SessionManager $session;

    public function __construct(ThemeManager $theme, \DomainSystem\Core\Http\SessionManager $session)
    {
        $this->theme = $theme;
        $this->session = $session;
    }

    public function renderDoctor(Request $request): Response
    {
        $db = \DomainSystem\Core\Application::getInstance()
            ->getContainer()->make(\DomainSystem\Plugins\Database\Connection::class)->getPdo();
            
        $userId = $this->session->get('user_id');
        $stmt = $db->prepare("SELECT email, profile_image, linked_doctor_id FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(\PDO::FETCH_ASSOC);

        $doctorId = $user['linked_doctor_id'] ?? null;
        
        $appointments = [];
        $schedules = [];
        $doctorName = 'Médico';
        
        if ($doctorId) {
            $stmtDoc = $db->prepare("SELECT name FROM doctors WHERE id = ?");
            $stmtDoc->execute([$doctorId]);
            $doctorName = $stmtDoc->fetchColumn() ?: 'Médico';

            // Buscar apenas os Confirmados (pacientes esperando/agendados)
            $appointments = $db->query("
                SELECT * 
                FROM appointments 
                WHERE doctor_id = " . (int)$doctorId . " 
                  AND status = 'Confirmado'
                ORDER BY appointment_date ASC, appointment_time ASC
            ")->fetchAll(\PDO::FETCH_ASSOC);
            
            // Buscar horários
            $stmtSched = $db->prepare("SELECT * FROM doctor_schedules WHERE doctor_id = ? ORDER BY day_of_week, start_time");
            $stmtSched->execute([$doctorId]);
            $schedules = $stmtSched->fetchAll(\PDO::FETCH_ASSOC);
        }

        $this->theme->setActiveThemePath(__DIR__ . '/../themes/cockpit_doctor');
        $html = $this->theme->render('index', [
            'user_id' => $userId,
            'user_name' => $this->session->get('user_name', $doctorName),
            'user_email' => $user['email'] ?? '',
            'profile_image' => $user['profile_image'] ?? '',
            'two_factor_type' => $user['two_factor_type'] ?? 'none',
            'two_factor_secret' => $user['two_factor_secret'] ?? '',
            'doctor_id' => $doctorId,
            'appointments' => $appointments,
            'schedules' => $schedules
        ]);
        return new Response($html);
    }

    public function renderSecretary(Request $request): Response
    {
        $db = \DomainSystem\Core\Application::getInstance()
            ->getContainer()->make(\DomainSystem\Plugins\Database\Connection::class)->getPdo();
            
        // Buscar agendamentos com o nome do médico
        $appointments = $db->query("
            SELECT a.*, d.name as doctor_name 
            FROM appointments a
            LEFT JOIN doctors d ON a.doctor_id = d.id
            WHERE a.status NOT IN ('Cancelado', 'Cancelada', 'Concluído', 'Concluido')
            ORDER BY a.appointment_date ASC, a.appointment_time ASC
        ")->fetchAll(\PDO::FETCH_ASSOC);
        
        // Buscar lista de médicos para o agendamento manual
        $doctors = $db->query("SELECT id, name, specialty FROM doctors ORDER BY name ASC")->fetchAll(\PDO::FETCH_ASSOC);
        
        // Buscar dados do usuário logado
        $userId = $this->session->get('user_id');
        $stmt = $db->prepare("SELECT email, profile_image FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(\PDO::FETCH_ASSOC);

        $this->theme->setActiveThemePath(__DIR__ . '/../themes/cockpit_secretary');
        $html = $this->theme->render('index', [
            'user_id' => $userId,
            'user_name' => $this->session->get('user_name', 'Secretária'),
            'user_email' => $user['email'] ?? '',
            'profile_image' => $user['profile_image'] ?? '',
            'two_factor_type' => $user['two_factor_type'] ?? 'none',
            'two_factor_secret' => $user['two_factor_secret'] ?? '',
            'appointments' => $appointments,
            'doctors' => $doctors
        ]);
        return new Response($html);
    }

    public function renderNursing(Request $request): Response
    {
        $this->theme->setActiveThemePath(__DIR__ . '/../themes/cockpit_nursing');
        $html = $this->theme->render('index', ['user_name' => $this->session->get('user_name', 'Enfermeiro')]);
        return new Response($html);
    }

    public function renderAdminDashboard(Request $request): Response
    {
        $html = "<h1>Dashboard da Clínica</h1><p>Bem-vindo ao centro de comando da clínica. Selecione uma opção no menu lateral para gerenciar pacientes, médicos, financeiro e agendamentos.</p>";
        return new Response($html);
    }

    public function renderShortcodesCatalog(Request $request): Response
    {
        $app = \DomainSystem\Core\Application::getInstance();
        $shortcodes = $app->getShortcodeManager()->getRegisteredShortcodes();
        
        $html = "<h1>Catálogo de Shortcodes</h1>";
        $html .= "<p>Estes são os componentes visuais que você pode usar para construir novos temas (CockPits).</p>";
        $html .= "<table class='wp-list-table'><thead><tr><th style='width: 250px;'>Tag</th><th>Descrição</th><th>Atributos Suportados</th><th style='width: 100px; text-align: center;'>Ação</th></tr></thead><tbody>";
        foreach ($shortcodes as $tag => $data) {
            $attrs = [];
            foreach ($data['attributes'] as $attr => $desc) {
                $attrs[] = "<strong>{$attr}</strong>: {$desc}";
            }
            $attrsHtml = implode('<br>', $attrs);
            if (empty($attrsHtml)) $attrsHtml = '<em>Nenhum</em>';
            
            $html .= "<tr>";
            $html .= "<td>";
            $html .= "<code id='sc-{$tag}' style='display:inline-block;'>&#91;{$tag}&#93;</code>";
            $html .= "</td>";
            $html .= "<td>{$data['description']}</td>";
            $html .= "<td>{$attrsHtml}</td>";
            $html .= "<td style='text-align: center; vertical-align: middle;'>";
            $html .= "<button class='btn btn-activate' onclick='navigator.clipboard.writeText(\"[\" + \"{$tag}\" + \"]\"); this.innerText=\"Copiado!\"; setTimeout(() => this.innerText=\"Copiar\", 2000);' style='padding: 6px 12px; font-size: 12px; font-weight: 600; white-space: nowrap; width: 100%; max-width: 90px; text-align: center; border-radius: 4px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); border: 1px solid #cbd5e1; cursor: pointer; transition: 0.2s;'>Copiar</button>";
            $html .= "</td>";
            $html .= "</tr>";
        }
        $html .= "</tbody></table>";

        return new Response($html);
    }

    public function searchHistory(Request $request): Response
    {
        $name = trim($request->input('search_name', $request->input('name', '')));
        $date = trim($request->input('search_date', $request->input('date', '')));

        $db = \DomainSystem\Core\Application::getInstance()
            ->getContainer()->make(\DomainSystem\Plugins\Database\Connection::class)->getPdo();

        $role = $this->session->get('role');
        $userId = $this->session->get('user_id');

        $doctorId = null;
        if ($role === 'doctor') {
            $stmt = $db->prepare("SELECT linked_doctor_id FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $doctorId = $stmt->fetchColumn();
        }

        $sql = "SELECT a.*, d.name as doctor_name, p.name as patient_name
                FROM appointments a
                LEFT JOIN doctors d ON a.doctor_id = d.id
                LEFT JOIN patients p ON a.patient_id = p.id
                WHERE 1=1";

        $params = [];

        if ($role === 'doctor' && $doctorId) {
            $sql .= " AND a.doctor_id = ?";
            $params[] = (int)$doctorId;
        }

        if (!empty($name)) {
            $sql .= " AND (p.name LIKE ? OR a.patient_name LIKE ?)";
            $params[] = "%{$name}%";
            $params[] = "%{$name}%";
        }

        if (!empty($date)) {
            $sql .= " AND DATE(a.appointment_date) = ?";
            $params[] = $date;
        }

        $sql .= " ORDER BY a.appointment_date DESC, a.appointment_time DESC LIMIT 100";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $results = $stmt->fetchAll(\PDO::FETCH_ASSOC);

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
        $shortcodeManager = \DomainSystem\Core\Application::getInstance()->getShortcodeManager();
        
        $viewRole = $request->input('view_role', $role); 
        if (!in_array($viewRole, ['doctor', 'secretary', 'admin'])) {
            $viewRole = 'doctor';
        }
        if ($viewRole === 'admin') $viewRole = 'doctor';
        
        $html = $shortcodeManager->parse('[aba_historico role="' . $viewRole . '" type="today"]');
        return new Response($html);
    }
    public function updateAppointmentStatus(Request $request): Response
    {
        $id = $request->input('id');
        $status = $request->input('status');
        
        $db = \DomainSystem\Core\Application::getInstance()
            ->getContainer()->make(\DomainSystem\Plugins\Database\Connection::class)->getPdo();
            
        $stmt = $db->prepare("UPDATE appointments SET status = ? WHERE id = ?");
        $stmt->execute([$status, $id]);
        
        return new Response(json_encode(['success' => true]), 200, ['Content-Type' => 'application/json']);
    }
    
    public function updateProfile(Request $request): Response
    {
        $userId = $this->session->get('user_id');
        $email = $request->input('email');
        $password = $request->input('password');
        
        $db = \DomainSystem\Core\Application::getInstance()
            ->getContainer()->make(\DomainSystem\Plugins\Database\Connection::class)->getPdo();
            
        // Foto de perfil
        $profileImage = null;
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            $ext = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
            $filename = 'profile_' . $userId . '_' . time() . '.' . $ext;
            $dest = dirname(__DIR__, 4) . '/public/uploads/' . $filename;
            
            if (!is_dir(dirname(__DIR__, 4) . '/public/uploads')) {
                mkdir(dirname(__DIR__, 4) . '/public/uploads', 0777, true);
            }
            
            if (move_uploaded_file($_FILES['photo']['tmp_name'], $dest)) {
                $profileImage = '/uploads/' . $filename;
            }
        }
        
        if ($profileImage) {
            $stmt = $db->prepare("UPDATE users SET profile_image = ? WHERE id = ?");
            $stmt->execute([$profileImage, $userId]);
        }
        
        if (!empty($email)) {
            $stmt = $db->prepare("UPDATE users SET email = ? WHERE id = ?");
            $stmt->execute([$email, $userId]);
        }
        
        if (!empty($password)) {
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->execute([$hash, $userId]);
        }
        
        $twoFactorType = $request->input('two_factor_type');
        if ($twoFactorType && in_array($twoFactorType, ['none', 'app', 'email'])) {
            $stmt = $db->prepare("UPDATE users SET two_factor_type = ? WHERE id = ?");
            $stmt->execute([$twoFactorType, $userId]);
        }
        
        $dispatcher = \DomainSystem\Core\Application::getInstance()->getDispatcher();
        $dispatcher->dispatch('cockpit.profile.save', (string)$userId, $request);
        
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            return new \DomainSystem\Core\Http\Response(json_encode(['success' => true, 'message' => 'Configurações salvas com sucesso!']), 200, ['Content-Type' => 'application/json']);
        }
        
        // Redirecionar de volta para a mesma tela
        $referer = $_SERVER['HTTP_REFERER'] ?? (BASE_URL . "/admin");
        $redirectUrl = strpos($referer, '?') !== false ? $referer . '&success=1' : $referer . '?success=1';
        header("Location: " . $redirectUrl);
        exit;
    }
}




