<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agendamento Online - Daher Clínica</title>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Flatpickr (Calendário Customizado) -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://npmcdn.com/flatpickr/dist/l10n/pt.js"></script>
    
    <style>
        :root {
            --primary: #1A365D; /* Azul marinho escuro */
            --secondary: #C5A880; /* Dourado suave */
            --accent: #E2C296; /* Dourado claro */
            --bg-color: #F8F9FA;
            --text-dark: #333333;
            --text-light: #666666;
            --white: #FFFFFF;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Montserrat', sans-serif;
            background-color: var(--bg-color);
            color: var(--text-dark);
            line-height: 1.6;
        }

        /* HEADER / NAVBAR SIMPLES */
        header {
            background-color: var(--primary);
            padding: 15px 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }

        header img {
            max-height: 50px;
        }

        .header-title {
            color: var(--secondary);
            font-family: 'Playfair Display', serif;
            font-size: 1.5rem;
            margin-left: 15px;
        }

        /* CONTAINER DO FORMULÁRIO */
        .container {
            max-width: 800px;
            margin: 40px auto;
            padding: 30px;
            background: var(--white);
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
            border-top: 5px solid var(--secondary);
        }

        .booking-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .booking-header h1 {
            font-family: 'Playfair Display', serif;
            color: var(--primary);
            font-size: 2.2rem;
            margin-bottom: 10px;
        }

        .booking-header p {
            color: var(--text-light);
        }

        /* FORMULÁRIO */
        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--primary);
            font-size: 0.95rem;
        }

        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-family: 'Montserrat', sans-serif;
            font-size: 1rem;
            transition: all 0.3s;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--secondary);
            box-shadow: 0 0 0 3px rgba(197, 168, 128, 0.2);
        }

        select.form-control {
            appearance: none;
            background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%23C5A880' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 15px center;
            background-size: 15px;
        }

        textarea.form-control {
            resize: vertical;
            min-height: 100px;
        }

        .grid-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        /* BOTÃO */
        .btn-submit {
            background-color: var(--primary);
            color: var(--white);
            border: none;
            padding: 15px 30px;
            font-size: 1.1rem;
            font-weight: 600;
            border-radius: 30px;
            cursor: pointer;
            width: 100%;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            font-family: 'Montserrat', sans-serif;
        }

        .btn-submit:hover {
            background-color: #112440;
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(26, 54, 93, 0.2);
        }

        /* MENSAGEM SUCESSO/ERRO */
        #alertMessage {
            display: none;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 600;
            text-align: center;
        }
        
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        /* FOOTER */
        footer {
            text-align: center;
            padding: 20px;
            color: var(--text-light);
            font-size: 0.9rem;
            margin-top: 40px;
        }

        /* RESPONSIVO */
        @media (max-width: 768px) {
            .grid-2 {
                grid-template-columns: 1fr;
                gap: 0;
            }
            .container {
                margin: 20px;
                padding: 20px;
            }
            header {
                flex-direction: column;
            }
            .header-title {
                margin-left: 0;
                margin-top: 10px;
            }
        }
    </style>
</head>
<body>

    <header>
        <div class="header-title">Daher Clínica</div>
    </header>

    <div class="container">
        <div class="booking-header">
            <h1>Agendamento Online</h1>
            <p>Preencha os dados abaixo e entraremos em contato rapidamente para confirmar sua consulta.</p>
        </div>
        
        <div id="alertMessage"></div>

        <form id="bookingForm">
            <div class="form-group">
                <label for="name">Nome Completo *</label>
                <input type="text" id="name" name="name" class="form-control" required placeholder="Seu nome completo">
            </div>

            <div class="grid-2">
                <div class="form-group">
                    <label for="phone">WhatsApp *</label>
                    <input type="text" id="phone" name="phone" class="form-control" required placeholder="(00) 00000-0000">
                </div>
                <div class="form-group">
                    <label for="doctor_id">Médico / Especialidade *</label>
                    <select id="doctor_id" name="doctor_id" class="form-control" required>
                        <option value="">Selecione o Médico</option>
                        <?php foreach($doctors as $doc): ?>
                            <?php 
                                $docName = $doc['name'] ?? 'Médico';
                                $docSpec = $doc['specialty'] ?? '';
                                $label = $docSpec ? "$docName ($docSpec)" : $docName;
                                // Auto-select se bater com o param ?medico= da URL
                                $selected = '';
                                if (!empty($selectedDoctor)) {
                                    if (stripos($docName, $selectedDoctor) !== false || $docName === $selectedDoctor) {
                                        $selected = 'selected';
                                    }
                                }
                            ?>
                            <option value="<?= htmlspecialchars($doc['id']) ?>" <?= $selected ?>><?= htmlspecialchars($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="grid-2">
                <div class="form-group">
                    <label for="date">Data de Preferência *</label>
                    <input type="text" id="date" name="date" class="form-control" required placeholder="Selecione uma data no calendário">
                </div>
                <div class="form-group">
                    <label for="time">Turno de Preferência</label>
                    <select id="time" name="time" class="form-control">
                        <option value="Qualquer">Qualquer horário</option>
                        <option value="Manhã">Manhã (08:00 - 12:00)</option>
                        <option value="Tarde">Tarde (13:00 - 18:00)</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label for="notes">Observações (Opcional)</label>
                <textarea id="notes" name="notes" class="form-control" placeholder="Alguma preferência de horário específico, convênio ou detalhe?"></textarea>
            </div>

            <button type="submit" class="btn-submit" id="btnSubmit">
                <i class="fas fa-calendar-check"></i> Solicitar Agendamento
            </button>
        </form>
    </div>

    <footer>
        &copy; <?= date('Y') ?> Daher Clínica. Todos os direitos reservados.
    </footer>

    <!-- Injeção da Classe Abstrata do Calendário -->
    <script src="<?= BASE_URL ?>/assets/js/doctor-calendar.js"></script>
    <script>
        const doctorDays = <?= json_encode($doctorDays ?? []) ?>;
        
        function validateDateSelection() {
            const docSelect = document.getElementById('doctor_id');
            const dateInput = document.getElementById('date');
            
            if (!docSelect.value || !dateInput.value) return;
            
            const docId = docSelect.value;
            const allowedDays = doctorDays[docId];
            
            if (!allowedDays || allowedDays.length === 0) {
                alert('Este médico ainda não possui horários configurados na agenda.');
                dateInput.value = '';
                return;
            }
            
            const dateStr = dateInput.value.replace(/-/g, '\/');
            const selectedDate = new Date(dateStr);
            const selectedDayOfWeek = selectedDate.getDay();
            
            if (!allowedDays.includes(selectedDayOfWeek)) {
                // Apenas por segurança (o calendário já bloqueia visualmente)
                dateInput.value = '';
            }
        }
        
        // Instancia a classe abstrata!
        new DoctorCalendarPicker('#date', '#doctor_id', doctorDays, function(selectedDates, dateStr, instance, isReset) {
            if (!isReset) {
                validateDateSelection();
            }
        });

        document.getElementById('bookingForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const btn = document.getElementById('btnSubmit');
            const originalText = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processando...';
            btn.disabled = true;

            const formData = new FormData(this);
            const data = Object.fromEntries(formData.entries());

            fetch('/api/agendamento/submit', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(result => {
                const alert = document.getElementById('alertMessage');
                alert.style.display = 'block';
                
                if (result.success) {
                    alert.className = 'alert-success';
                    alert.innerHTML = '<i class="fas fa-check-circle"></i> ' + result.message;
                    this.reset();
                } else {
                    alert.className = 'alert-error';
                    alert.innerHTML = '<i class="fas fa-exclamation-circle"></i> ' + (result.message || 'Erro desconhecido.');
                }
                
                btn.innerHTML = originalText;
                btn.disabled = false;
                
                // Rola para o topo para ver a mensagem
                window.scrollTo({top: 0, behavior: 'smooth'});
            })
            .catch(error => {
                console.error('Error:', error);
                const alert = document.getElementById('alertMessage');
                alert.style.display = 'block';
                alert.className = 'alert-error';
                alert.innerHTML = '<i class="fas fa-exclamation-circle"></i> Ocorreu um erro de rede. Tente novamente.';
                
                btn.innerHTML = originalText;
                btn.disabled = false;
            });
        });
        
        // Colocar a data mínima como hoje
        document.getElementById('date').min = new Date().toISOString().split("T")[0];
    </script>
</body>
</html>
