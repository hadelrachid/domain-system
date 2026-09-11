<?php

namespace DomainSystem\Plugins\clinic_pack\Theme;

use DomainSystem\Plugins\Database\Connection;
use DomainSystem\Core\Http\SessionManager;
use DomainSystem\Plugins\appointments\Contracts\AppointmentRepositoryInterface;
use DomainSystem\Core\Application;

class CockpitShortcodes
{
    private \PDO $db;
    private SessionManager $session;
    private AppointmentRepositoryInterface $appointmentRepo;

    public function __construct(Connection $conn, SessionManager $session, AppointmentRepositoryInterface $appointmentRepo)
    {
        $this->db = $conn->getPdo();
        $this->session = $session;
        $this->appointmentRepo = $appointmentRepo;
    }

    private function getDoctorIdForCurrentUser(): ?int
    {
        if ($this->session->get('user_role') === 'doctor') {
            $stmt = $this->db->prepare("SELECT linked_doctor_id FROM users WHERE id = ?");
            $stmt->execute([$this->session->get('user_id')]);
            return $stmt->fetchColumn() ?: null;
        }
        return null;
    }

    /**
     * Helper to format names (Name + 3 surnames)
     */
    private function formatName(string $fullName): string
    {
        $parts = explode(' ', trim($fullName));
        if (count($parts) > 4) {
            return htmlspecialchars(implode(' ', array_slice($parts, 0, 4)) . '...');
        }
        return htmlspecialchars(implode(' ', $parts));
    }

    /**
     * Helper to format phones
     */
    private function formatPhone(string $phone): string
    {
        $cleanPhone = preg_replace('/[^0-9]/', '', $phone);
        if (strlen($cleanPhone) == 11) {
            return preg_replace('/(\d{2})(\d{5})(\d{4})/', '($1) $2-$3', $cleanPhone);
        } elseif (strlen($cleanPhone) == 10) {
            return preg_replace('/(\d{2})(\d{4})(\d{4})/', '($1) $2-$3', $cleanPhone);
        }
        return htmlspecialchars($phone);
    }
    
    /**
     * Get clean phone for WhatsApp
     */
    private function getCleanPhone(string $phone): string
    {
        $cleanPhone = preg_replace('/[^0-9]/', '', $phone);
        if (strlen($cleanPhone) == 11 && substr($cleanPhone, 0, 2) != '55') {
            return '55' . $cleanPhone;
        }
        return $cleanPhone;
    }

    public function renderAbaAguardando(array $attributes = []): string
    {
        $appointments = $this->db->query("
            SELECT a.*, d.name as doctor_name, p.name as patient_name, p.phone as patient_phone, p.email as patient_email
            FROM appointments a
            LEFT JOIN doctors d ON a.doctor_id = d.id
            LEFT JOIN patients p ON a.patient_id = p.id
            WHERE a.status IN ('Pendente', 'Aguardando') AND DATE(a.appointment_date) <= CURDATE()
            ORDER BY a.appointment_date ASC, a.appointment_time ASC
        ")->fetchAll(\PDO::FETCH_ASSOC);

        ob_start();
        ?>
        <?php if(empty($appointments)): ?>
            <div style="text-align:center;padding:60px;color:#64748b;background:var(--bg-card);border-radius:8px;">
                <i class="fas fa-calendar-check" style="font-size:48px;margin-bottom:15px;color:#10b981;opacity:0.5;"></i>
                <h2 style="margin:0;">Nenhum paciente aguardando!</h2>
                <p style="color:#94a3b8;">Todos os agendamentos do dia foram confirmados.</p>
            </div>
        <?php else: ?>
            <?php foreach($appointments as $app): 
                $cleanPhone = $this->getCleanPhone($app['patient_phone'] ?? '');
            ?>
            <div class="appointment-card" id="card-<?= $app['id'] ?>">
                <div class="info-group">
                    <span class="info-label">Paciente</span>
                    <span class="info-value"><i class="fas fa-user" style="color:#94a3b8;"></i> <?= $this->formatName($app['patient_name'] ?? '') ?></span>
                    <?php if(!empty($app['patient_phone'])): ?><span style="font-size:12px;color:#64748b;"><?= $this->formatPhone($app['patient_phone'] ?? '') ?></span><?php endif; ?>
                </div>
                <div class="info-group">
                    <span class="info-label">Data &amp; Hora</span>
                    <span class="info-value"><i class="fas fa-clock" style="color:#94a3b8;"></i> <?= date('d/m/Y', strtotime($app['appointment_date'])) ?> às <?= $app['appointment_time'] ?></span>
                </div>
                <div class="info-group">
                    <span class="info-label">Profissional</span>
                    <span class="info-value"><i class="fas fa-user-md" style="color:var(--primary);"></i> <?= htmlspecialchars($app['doctor_name'] ?: 'Não definido') ?></span>
                </div>
                <div class="info-group">
                    <span class="info-label">Tipo</span>
                    <span class="info-value" style="text-transform:capitalize;"><?= htmlspecialchars($app['attendance_type'] ?? 'Particular') ?></span>
                    <?php if(!empty($app['health_insurance'])): ?><span style="font-size:11px;color:#64748b;"><?= htmlspecialchars($app['health_insurance']) ?></span><?php endif; ?>
                </div>
                <div class="info-group">
                    <span class="info-label">Status</span>
                    <span class="status-badge Pendente" id="status-<?= $app['id'] ?>">⟳ Aguardando</span>
                </div>
                <div class="actions" style="display:flex; flex-wrap:wrap; gap:8px; width:100%; align-items:center; justify-content:space-between; margin-top:5px; border-top:1px solid #e2e8f0; padding-top:12px;">
                    <div style="display:flex; gap:6px;">
                        <?php if(!empty($cleanPhone)): ?>
                            <a href="https://wa.me/<?= $cleanPhone ?>" target="_blank" class="btn btn-wa" title="WhatsApp"><i class="fab fa-whatsapp"></i> WhatsApp</a>
                            <a href="https://t.me/+<?= $cleanPhone ?>" target="_blank" class="btn btn-tg" title="Telegram"><i class="fab fa-telegram"></i> Telegram</a>
                        <?php endif; ?>
                        <?php if(!empty($app['patient_email'])): ?>
                            <a href="mailto:<?= htmlspecialchars($app['patient_email']) ?>" class="btn btn-email" title="E-mail"><i class="fas fa-envelope"></i> E-mail</a>
                        <?php endif; ?>
                    </div>
                    <div style="display:flex; gap:8px;">
                        <button onclick="cancelarAgendamento(<?= $app['id'] ?>)" class="btn" style="background:#ef4444;color:white;border:none;padding:8px 12px;font-size:13px;" id="btn-cancel-<?= $app['id'] ?>">
                            <i class="fas fa-user-times"></i> Não Compareceu
                        </button>
                        <button onclick="confirmarAgendamento(<?= $app['id'] ?>)" class="btn btn-confirm" id="btn-confirm-<?= $app['id'] ?>">
                            <i class="fas fa-check"></i> Confirmar
                        </button>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
        <?php
        return ob_get_clean();
    }

    public function renderAbaConfirmadosHoje(array $attributes = []): string
    {
        $role = $attributes['role'] ?? 'secretary';
        $doctorId = $this->getDoctorIdForCurrentUser();

        $sql = "SELECT a.*, d.name as doctor_name, p.name as patient_name, p.phone as patient_phone
                FROM appointments a
                LEFT JOIN doctors d ON a.doctor_id = d.id
                LEFT JOIN patients p ON a.patient_id = p.id
                WHERE a.status = 'Confirmado' AND DATE(a.appointment_date) <= CURDATE()";

        if ($role === 'doctor' && $doctorId) {
            $sql .= " AND a.doctor_id = " . (int)$doctorId;
        }
        $sql .= " ORDER BY a.appointment_date ASC, a.appointment_time ASC";

        $appointments = $this->db->query($sql)->fetchAll(\PDO::FETCH_ASSOC);

        ob_start();
        ?>
        <?php if(empty($appointments)): ?>
            <div style="text-align:center;padding:60px;color:#64748b;background:var(--bg-card);border-radius:8px;">
                <i class="fas fa-user-check" style="font-size:48px;margin-bottom:15px;color:#10b981;opacity:0.4;"></i>
                <h2 style="margin:0;">Nenhum confirmado ainda hoje.</h2>
            </div>
        <?php else: ?>
            <?php foreach($appointments as $app): 
                $cleanPhone = $this->getCleanPhone($app['patient_phone'] ?? '');
            ?>
            <div class="appointment-card status-confirmado" id="card-<?= $app['id'] ?>">
                <div class="info-group">
                    <span class="info-label">Paciente</span>
                    <span class="info-value"><i class="fas fa-user" style="color:#10b981;"></i> <?= $this->formatName($app['patient_name'] ?? '') ?></span>
                    <?php if(!empty($app['patient_phone'])): ?><span style="font-size:12px;color:#64748b;"><?= $this->formatPhone($app['patient_phone'] ?? '') ?></span><?php endif; ?>
                </div>
                <div class="info-group">
                    <span class="info-label">Data &amp; Hora</span>
                    <span class="info-value"><i class="fas fa-clock" style="color:#94a3b8;"></i> <?= date('d/m/Y', strtotime($app['appointment_date'])) ?> às <?= $app['appointment_time'] ?></span>
                </div>
                
                <?php if ($role === 'secretary'): ?>
                <div class="info-group">
                    <span class="info-label">Profissional</span>
                    <span class="info-value"><i class="fas fa-user-md" style="color:var(--primary);"></i> <?= htmlspecialchars($app['doctor_name'] ?: 'Não definido') ?></span>
                </div>
                <?php else: ?>
                <div class="info-group">
                    <span class="info-label">Tipo</span>
                    <span class="info-value" style="text-transform: capitalize;"><?= htmlspecialchars($app['attendance_type'] ?? 'Particular') ?></span>
                    <?php if(!empty($app['health_insurance'])): ?>
                        <span style="font-size: 11px; background: #3b82f6; color: white; padding: 2px 6px; border-radius: 4px; margin-left: 5px;"><?= htmlspecialchars($app['health_insurance']) ?></span>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <div class="info-group">
                    <span class="info-label">Status</span>
                    <span class="status-badge Confirmado"><i class="fas fa-check-circle"></i> Confirmado</span>
                </div>
                
                <div class="actions">
                    <?php if ($role === 'secretary' && !empty($cleanPhone)): ?>
                        <a href="https://wa.me/<?= $cleanPhone ?>" target="_blank" class="btn btn-wa"><i class="fab fa-whatsapp"></i> WhatsApp</a>
                    <?php elseif ($role === 'doctor'): ?>
                        <button class="btn btn-confirm" style="cursor:pointer;" onclick="changeStatus(<?= $app['id'] ?>, 'Atendido', this)">
                            <i class="fas fa-check-double"></i> Concluir
                        </button>
                        <button class="btn-medical" onclick="alert('Funcionalidade de Prontuário em desenvolvimento.')"><i class="fas fa-notes-medical"></i> Abrir Prontuário</button>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
        <?php
        return ob_get_clean();
    }

    public function renderAbaHistorico(array $attributes = []): string
    {
        $role = $attributes['role'] ?? 'secretary';
        $doctorId = ($role === 'doctor') ? $this->getDoctorIdForCurrentUser() : null;
        
        // Histórico de hoje ou Histórico geral dependendo do papel/atributo
        $type = $attributes['type'] ?? 'all'; 
        
        $sql = "SELECT a.*, d.name as doctor_name, p.name as patient_name
                FROM appointments a
                LEFT JOIN doctors d ON a.doctor_id = d.id
                LEFT JOIN patients p ON a.patient_id = p.id
                WHERE a.status IN ('Cancelado', 'Cancelada', 'Concluído', 'Concluido', 'Atendido')";

        if ($doctorId) {
            $sql .= " AND a.doctor_id = " . (int)$doctorId;
        }
        
        if ($type === 'today') {
            $sql .= " AND (DATE(a.appointment_date) = CURDATE() OR DATE(a.updated_at) = CURDATE())";
        }
        
        $sql .= " ORDER BY a.appointment_date DESC, a.appointment_time DESC LIMIT 100";

        $history = $this->db->query($sql)->fetchAll(\PDO::FETCH_ASSOC);

        ob_start();
        ?>
        <?php if(empty($history)): ?>
            <div style="text-align:center;padding:60px;color:#64748b;background:var(--bg-card);border-radius:8px;">
                <i class="fas fa-history" style="font-size:48px;margin-bottom:15px;opacity:0.3;"></i>
                <h2 style="margin:0;">Nenhum registro no histórico.</h2>
            </div>
        <?php else: ?>
            <?php foreach($history as $app): ?>
            <div class="appointment-card" style="border-left-color:<?= strtolower($app['status']) === 'cancelado' ? '#ef4444' : '#64748b' ?>;animation:none;opacity:0.85;">
                <div class="info-group">
                    <span class="info-label">Paciente</span>
                    <span class="info-value"><?= $this->formatName($app['patient_name'] ?? '') ?></span>
                </div>
                <div class="info-group">
                    <span class="info-label">Data &amp; Hora</span>
                    <span class="info-value"><?= date('d/m/Y', strtotime($app['appointment_date'])) ?> às <?= $app['appointment_time'] ?></span>
                </div>
                <div class="info-group">
                    <span class="info-label">Profissional</span>
                    <span class="info-value"><?= htmlspecialchars($app['doctor_name'] ?: 'N/A') ?></span>
                </div>
                <div class="info-group">
                    <span class="info-label">Status Final</span>
                    <span style="padding:4px 10px;border-radius:20px;font-size:11px;font-weight:700;background:<?= strtolower($app['status']) === 'cancelado' ? '#fee2e2' : '#f1f5f9' ?>;color:<?= strtolower($app['status']) === 'cancelado' ? '#b91c1c' : '#475569' ?>;">
                        <?= htmlspecialchars($app['status']) ?>
                    </span>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
        <?php
        return ob_get_clean();
    }

    public function renderAbaPesquisar(array $attributes = []): string
    {
        ob_start();
        ?>
        <div style="background: var(--bg-card); padding: 15px; border-radius: 8px; margin-bottom: 20px; display: flex; gap: 10px; align-items: center; box-shadow: 0 1px 3px rgba(0,0,0,0.08);">
            <input type="text" id="search-name" placeholder="Nome do paciente..." style="flex: 1; padding: 10px; border: 1px solid var(--primary-border); border-radius: 4px; background: var(--bg-body); color: var(--text-main);">
            <input type="date" id="search-date" style="padding: 10px; border: 1px solid var(--primary-border); border-radius: 4px; background: var(--bg-body); color: var(--text-main);">
            <button class="btn btn-save" style="width: auto; margin-top: 0; display: flex; align-items: center; gap: 8px;" onclick="doSearch()">
                <i class="fas fa-search"></i> Buscar
            </button>
        </div>
        <div id="search-results">
            <div style="text-align: center; padding: 50px; color: var(--text-muted); background: var(--bg-card); border-radius: 8px;">
                <i class="fas fa-search" style="font-size: 40px; margin-bottom: 15px; color: var(--primary-border);"></i>
                <h3 style="margin:0 0 10px 0; color:var(--text-main);">Pesquisar Histórico</h3>
                <p style="margin:0;">Busque por nome ou data para ver os atendimentos anteriores.</p>
            </div>
        </div>
        <script>
        function doSearch() {
            const btn = document.querySelector('button[onclick="doSearch()"]');
            const orig = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Buscando...';
            btn.disabled = true;
            
            const name = document.getElementById('search-name').value;
            const date = document.getElementById('search-date').value;
            
            fetch('<?= \BASE_URL ?>/cockpit/search', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
                body: 'name=' + encodeURIComponent(name) + '&date=' + encodeURIComponent(date) + '&csrf_token=<?= $_SESSION["csrf_token"] ?? "" ?>'
            })
            .then(res => res.text())
            .then(html => {
                document.getElementById('search-results').innerHTML = html;
                btn.innerHTML = orig; btn.disabled = false;
            })
            .catch(err => {
                document.getElementById('search-results').innerHTML = '<div style="color:red;padding:20px;">Erro ao buscar. Tente novamente.</div>';
                btn.innerHTML = orig; btn.disabled = false;
            });
        }
        </script>
        <?php
        return ob_get_clean();
    }
}
