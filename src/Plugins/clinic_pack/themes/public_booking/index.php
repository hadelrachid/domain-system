<?php
if (!defined('DOMAIN_SYSTEM_ROOT')) exit;

/**
 * Theme: Agendamento Online (Público)
 * 
 * Variáveis disponíveis (injetadas pelo BookingController):
 *   $doctors        - array de médicos [{id, name, specialty}, ...]
 *   $selectedDoctor - string com nome do médico pré-selecionado (via ?medico=)
 *   $theme          - instância do ThemeManager
 */

// Garante variáveis
$doctors = $doctors ?? [];
$selectedDoctor = $selectedDoctor ?? '';
?>
<?php 
if(empty($isShortcode)) {
    include __DIR__ . '/header.php';
} else {
    // Modo Shortcode: Injeta apenas o CSS
    $cssFile = __DIR__ . '/assets/css/booking.css';
    if (file_exists($cssFile)) {
        echo "<style>\n" . file_get_contents($cssFile) . "\n</style>";
    }
}
?>

    <!-- Main Content -->
    <div class="booking-widget-container">
    <main class="booking-main">
        <div class="booking-container">
            
            <!-- Título -->
            <div class="booking-title">
                <i class="fas fa-calendar-check icon-calendar"></i>
                <h1>Agendamento Online</h1>
                <p>Escolha o médico, a data e o horário disponível. Nosso sistema confirmará sua consulta em instantes.</p>
            </div>

            <!-- Alertas -->
            <div id="alertMessage" class="alert"></div>

            <!-- Formulário -->
            <form id="bookingForm" novalidate>
                
                <!-- Nome Completo -->
                <div class="form-group">
                    <label for="name">Nome Completo <span class="required">*</span></label>
                    <input type="text" id="name" name="name" class="form-control" 
                           required placeholder="Seu nome completo" 
                           autocomplete="name">
                </div>

                <!-- WhatsApp + E-mail -->
                <div class="form-row">
                    <div class="form-group">
                        <label for="phone">WhatsApp <span class="required">*</span></label>
                        <input type="tel" id="phone" name="phone" class="form-control" 
                               required placeholder="(21) 99999-9999" 
                               autocomplete="tel"
                               inputmode="tel">
                    </div>
                    <div class="form-group">
                        <label for="email">E-mail</label>
                        <input type="email" id="email" name="email" class="form-control" 
                               placeholder="seu@email.com" 
                               autocomplete="email"
                               inputmode="email"
                               pattern="[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,}$"
                               title="Digite um e-mail válido contendo @ e domínio.">
                    </div>
                </div>

                <!-- Especialidade -->
                <div class="form-group">
                    <label for="specialty">Especialidade <span class="required">*</span></label>
                    <select id="specialty" name="specialty" class="form-control" required>
                        <option value="">Selecione a Especialidade</option>
                        <?php foreach ($specialties as $spec): ?>
                            <option value="<?= htmlspecialchars($spec) ?>"><?= htmlspecialchars($spec) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Médico -->
                <div class="form-group" id="doctor-group" style="display: none;">
                    <label for="doctor_id">Médico Especialista <span class="required">*</span></label>
                    <select id="doctor_id" name="doctor_id" class="form-control" required disabled>
                        <option value="">Selecione a especialidade primeiro</option>
                    </select>
                </div>

                <!-- Data -->
                <div class="form-group">
                    <label for="date">Data da Consulta <span class="required">*</span></label>
                    <input type="date" id="date" name="date" class="form-control" required>
                </div>

                <!-- Slots de Horário (dinâmicos via JS) -->
                <div class="slots-container" id="slotsContainer">
                    <label>Horário Disponível <span class="required">*</span></label>
                    <div class="slots-hint">
                        <i class="fas fa-info-circle"></i>
                        Selecione um médico e uma data para ver os horários disponíveis.
                    </div>
                </div>
                <input type="hidden" id="selectedTime" name="time" value="">

                <!-- Convênio -->
                <div class="form-row">
                    <div class="form-group">
                        <label for="attendance_type">Tipo de Atendimento <span class="required">*</span></label>
                        <select id="attendance_type" name="attendance_type" class="form-control" required>
                            <option value="particular">Particular</option>
                            <option value="conveniado">Conveniado</option>
                        </select>
                    </div>
                    <div class="form-group" id="health_insurance_group" style="display: none;">
                        <label for="health_insurance">Plano de Saúde</label>
                        <select id="health_insurance" name="health_insurance" class="form-control">
                            <option value="">-- Selecione o Plano --</option>
                            <?php if (isset($insurances)): ?>
                                <?php foreach ($insurances as $ins): ?>
                                    <option value="<?= htmlspecialchars($ins['name']) ?>"><?= htmlspecialchars($ins['name']) ?></option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                </div>

                <!-- Observações -->
                <div class="form-group">
                    <label for="notes">Observações <small>(opcional)</small></label>
                    <textarea id="notes" name="notes" class="form-control" 
                              placeholder="Convênio, preferência de horário, ou detalhes adicionais..."></textarea>
                </div>

                <!-- Botão Submit -->
                <button type="submit" class="btn-submit" id="btnSubmit">
                    <i class="fas fa-calendar-check"></i> Solicitar Agendamento
                </button>

                <!-- Trust Badge -->
                <div class="trust-badge">
                    <i class="fas fa-shield-alt"></i>
                    Seus dados estão seguros e protegidos.
                </div>
            </form>

        </div>
    </main>
    </div>

<?php if(empty($isShortcode)) include __DIR__ . '/footer.php'; ?>

    <script>
    (function() {
        'use strict';

        const doctorsBySpecialty = <?= json_encode($doctorsBySpecialty ?? []) ?>;

        const specialtySelect = document.getElementById('specialty');
        const doctorSelect = document.getElementById('doctor_id');
        const doctorGroup = document.getElementById('doctor-group');
        const dateInput = document.getElementById('date');
        const slotsContainer = document.getElementById('slotsContainer');
        const selectedTimeInput = document.getElementById('selectedTime');
        const form = document.getElementById('bookingForm');
        const btnSubmit = document.getElementById('btnSubmit');
        const alertBox = document.getElementById('alertMessage');

        // Lógica de Especialidade -> Médico
        specialtySelect.addEventListener('change', function() {
            const spec = this.value;
            doctorSelect.innerHTML = '<option value="">Selecione o Médico</option>';
            
            if (spec && doctorsBySpecialty[spec]) {
                doctorsBySpecialty[spec].forEach(doc => {
                    const opt = document.createElement('option');
                    opt.value = doc.id;
                    opt.textContent = doc.name;
                    doctorSelect.appendChild(opt);
                });
                doctorSelect.disabled = false;
                doctorGroup.style.display = 'block';
            } else {
                doctorSelect.disabled = true;
                doctorGroup.style.display = 'none';
            }
            
            // Reset slots
            loadSlots();
        });

        // Data mínima = hoje
        const today = new Date().toISOString().split('T')[0];
        dateInput.min = today;

        function renderSlotsHint() {
            const message = dateInput.value 
                ? 'Selecione a especialidade e o médico para ver os horários.' 
                : 'Selecione um médico e uma data para ver os horários disponíveis.';
            const label = '<label>Horário Disponível <span class="required">*</span></label>';
            slotsContainer.innerHTML = label + 
                '<div class="slots-hint">' +
                '<i class="fas fa-info-circle"></i> ' + message +
                '</div>';
        }

        // Máscara simples de telefone
        const phoneInput = document.getElementById('phone');
        phoneInput.addEventListener('input', function() {
            let v = this.value.replace(/\D/g, '');
            if (v.length > 11) v = v.substring(0, 11);
            if (v.length > 6) {
                this.value = '(' + v.substring(0, 2) + ') ' + v.substring(2, 7) + '-' + v.substring(7);
            } else if (v.length > 2) {
                this.value = '(' + v.substring(0, 2) + ') ' + v.substring(2);
            } else if (v.length > 0) {
                this.value = '(' + v;
            }
        });

        // === CARREGAR SLOTS ===
        function loadSlots() {
            const doctorId = doctorSelect.value;
            const date = dateInput.value;

            // Reset
            selectedTimeInput.value = '';

            if (!doctorId || !date) {
                renderSlotsHint();
                return;
            }

            renderSlotsLoading();

            fetch('<?= \BASE_URL ?>/api/agendamento/slots?doctor_id=' + encodeURIComponent(doctorId) + '&date=' + encodeURIComponent(date))
                .then(r => r.json())
                .then(data => {
                    if (data.slots && data.slots.length > 0) {
                        renderSlots(data.slots);
                    } else {
                        renderSlotsEmpty(data.message || 'Nenhum horário disponível para esta data.');
                    }
                })
                .catch(() => {
                    renderSlotsEmpty('Erro ao carregar horários. Tente novamente.');
                });
        }



        function renderSlotsLoading() {
            const label = '<label>Horário Disponível <span class="required">*</span></label>';
            slotsContainer.innerHTML = label + 
                '<div class="slots-loading">' +
                '<i class="fas fa-spinner fa-spin"></i> Carregando horários...' +
                '</div>';
        }

        function renderSlotsEmpty(message) {
            const label = '<label>Horário Disponível <span class="required">*</span></label>';
            slotsContainer.innerHTML = label + 
                '<div class="slots-empty">' +
                '<i class="fas fa-calendar-times" style="font-size:1.5rem; display:block; margin-bottom:8px;"></i>' +
                message +
                '</div>';
        }

        function renderSlots(slots) {
            const label = '<label>Horário Disponível <span class="required">*</span></label>';
            let html = label + '<div class="slots-grid">';
            slots.forEach(function(slot) {
                html += '<button type="button" class="slot-btn" data-time="' + slot + '">' + slot + '</button>';
            });
            html += '</div>';
            slotsContainer.innerHTML = html;

            // Bind clique nos slots
            slotsContainer.querySelectorAll('.slot-btn').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    // Remove seleção anterior
                    slotsContainer.querySelectorAll('.slot-btn').forEach(function(b) {
                        b.classList.remove('selected');
                    });
                    // Seleciona este
                    this.classList.add('selected');
                    selectedTimeInput.value = this.dataset.time;
                });
            });
        }

        // Eventos para recarregar slots
        doctorSelect.addEventListener('change', loadSlots);
        dateInput.addEventListener('change', loadSlots);

        // Se veio com médico pré-selecionado, disparar
        if (doctorSelect.value) {
            // Pequeno delay para garantir que a página renderizou
            setTimeout(function() {
                if (dateInput.value) loadSlots();
            }, 100);
        }

        const attType = document.getElementById('attendance_type');
        const insGroup = document.getElementById('health_insurance_group');
        
        attType.addEventListener('change', function() {
            if (this.value === 'conveniado') {
                insGroup.style.display = 'block';
            } else {
                insGroup.style.display = 'none';
                document.getElementById('health_insurance').value = '';
            }
        });

        // === ENVIAR FORMULÁRIO ===
        form.addEventListener('submit', function(e) {
            e.preventDefault();

            // Validação
            const name = document.getElementById('name').value.trim();
            const phone = phoneInput.value.trim();
            const doctorId = doctorSelect.value;
            const date = dateInput.value;
            const time = selectedTimeInput.value;
            const attendanceType = attType.value;
            const insurance = document.getElementById('health_insurance').value;
            const email = document.getElementById('email').value.trim();

            if (!name || !phone || !doctorId || !date) {
                showAlert('error', '<i class="fas fa-exclamation-circle"></i> Preencha todos os campos obrigatórios.');
                return;
            }

            if (email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                showAlert('error', '<i class="fas fa-exclamation-circle"></i> Por favor, insira um e-mail válido com @ e domínio.');
                return;
            }

            if (!time) {
                showAlert('error', '<i class="fas fa-clock"></i> Selecione um horário disponível.');
                return;
            }

            if (attendanceType === 'conveniado' && !insurance) {
                showAlert('error', '<i class="fas fa-exclamation-circle"></i> Selecione o seu plano de saúde.');
                return;
            }

            // Desabilitar botão
            const originalText = btnSubmit.innerHTML;
            btnSubmit.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processando...';
            btnSubmit.disabled = true;

            const payload = {
                name: name,
                phone: phone,
                email: email,
                doctor_id: doctorId,
                date: date,
                time: time,
                attendance_type: attendanceType,
                health_insurance: insurance,
                notes: document.getElementById('notes').value.trim()
            };

            fetch('<?= \BASE_URL ?>/api/agendamento/submit', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            })
            .then(r => r.json())
            .then(result => {
                if (result.success) {
                    showAlert('success', '<i class="fas fa-check-circle"></i> ' + result.message);
                    form.reset();
                    selectedTimeInput.value = '';
                    renderSlotsHint();
                    
                    // Se o formulário estiver rodando dentro do CockPit (shortcode)
                    if (typeof window.forceSync === 'function') {
                        window.forceSync();
                        if (typeof window.switchTab === 'function') {
                            setTimeout(() => {
                                window.switchTab('pendentes');
                            }, 1500); // Aguarda 1.5s para a pessoa ler o alerta de sucesso e depois move de aba
                        }
                    }
                } else {
                    showAlert('error', '<i class="fas fa-exclamation-circle"></i> ' + (result.message || 'Erro desconhecido.'));
                }
                btnSubmit.innerHTML = originalText;
                btnSubmit.disabled = false;
                window.scrollTo({ top: 0, behavior: 'smooth' });
            })
            .catch(function() {
                showAlert('error', '<i class="fas fa-exclamation-circle"></i> Erro de rede. Tente novamente.');
                btnSubmit.innerHTML = originalText;
                btnSubmit.disabled = false;
            });
        });

        function showAlert(type, html) {
            alertBox.style.display = 'block';
            alertBox.className = 'alert alert-' + type;
            alertBox.innerHTML = html;
        }

    })();
    </script>
