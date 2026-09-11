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
        $html .= "<table class='wp-list-table'><thead><tr><th style='width: 250px;'>Tag</th><th>Descrição</th><th>Atributos Suportados</th></tr></thead><tbody>";
        foreach ($shortcodes as $tag => $data) {
            $attrs = [];
            foreach ($data['attributes'] as $attr => $desc) {
                $attrs[] = "<strong>{$attr}</strong>: {$desc}";
            }
            $attrsHtml = implode('<br>', $attrs);
            if (empty($attrsHtml)) $attrsHtml = '<em>Nenhum</em>';
            
            $html .= "<tr>";
            $html .= "<td>";
            $html .= "<code id='sc-{$tag}' style='display:inline-block; margin-right: 8px;'>&#91;{$tag}&#93;</code>";
            $html .= "<button class='btn btn-activate' onclick='navigator.clipboard.writeText(\"[\" + \"{$tag}\" + \"]\"); this.innerText=\"Copiado!\"; setTimeout(() => this.innerText=\"Copiar\", 2000);' style='padding: 2px 8px; font-size: 11px; white-space: nowrap;'>Copiar</button>";
            $html .= "</td>";
            $html .= "<td>{$data['description']}</td>";
            $html .= "<td>{$attrsHtml}</td>";
            $html .= "</tr>";
        }
        $html .= "</tbody></table>";

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




