<h1>Módulo de Pacientes</h1>
    
    <?php if (isset($_SESSION['flash_message'])): ?>
        <?php $msg = $_SESSION['flash_message']; unset($_SESSION['flash_message']); ?>
        <div style="background: <?= $msg['type'] === 'error' ? '#fcf0f1' : '#f0f6fc' ?>; border-left: 4px solid <?= $msg['type'] === 'error' ? '#d63638' : '#2271b1' ?>; padding: 10px; margin-bottom: 20px;">
            <?= htmlspecialchars($msg['msg']) ?>
        </div>
    <?php endif; ?>

    <style>
        .flex-container { display: flex; flex-direction: column; gap: 20px; }
        .table-responsive { width: 100%; overflow-x: auto; }
        .form-panel { width: 100%; max-width: 800px; margin-bottom: 20px; }
        @media (max-width: 768px) {
            .flex-container { flex-direction: column; }
            .table-responsive, .form-panel { width: 100%; flex: none; }
        }
    </style>

    <div class="flex-container">
        
        <!-- Formulário de Cadastro -->
        <div class="upload-box form-panel">
            <h2 style="margin-top: 0;">Novo Paciente</h2>
            <form method="POST" action="<?= BASE_URL ?>/admin/patients">
                <label style="display:block; margin-bottom: 5px;">Nome Completo</label>
                <input type="text" name="name" required style="width: 100%; padding: 8px; margin-bottom: 15px; box-sizing: border-box;">

                <label style="display:block; margin-bottom: 5px;">CPF</label>
                <input type="text" name="cpf" required placeholder="Apenas números" style="width: 100%; padding: 8px; margin-bottom: 15px; box-sizing: border-box;">

                <label style="display:block; margin-bottom: 5px;">Data de Nascimento</label>
                <input type="date" name="birthdate" style="width: 100%; padding: 8px; margin-bottom: 15px; box-sizing: border-box;">

                <label style="display:block; margin-bottom: 5px;">E-mail</label>
                <input type="email" name="email" style="width: 100%; padding: 8px; margin-bottom: 15px; box-sizing: border-box;">

                <label style="display:block; margin-bottom: 5px;">Telefone</label>
                <input type="text" name="phone" style="width: 100%; padding: 8px; margin-bottom: 15px; box-sizing: border-box;">

                <div style="border-top: 1px solid #ccc; margin-top: 10px; margin-bottom: 15px; padding-top: 10px;">
                    <strong>Opcionais</strong>
                </div>

                <label style="display:block; margin-bottom: 5px;">Convênio Médico</label>
                <input type="text" name="insurance_number" placeholder="Número da carteirinha" style="width: 100%; padding: 8px; margin-bottom: 15px; box-sizing: border-box;">

                <div style="display:flex; gap:10px;">
                    <div style="flex:1;">
                        <label style="display:block; margin-bottom: 5px;">CEP</label>
                        <input type="text" name="zip_code" id="zip_code" style="width: 100%; padding: 8px; margin-bottom: 15px; box-sizing: border-box;">
                    </div>
                    <div style="flex:2;">
                        <label style="display:block; margin-bottom: 5px;">Estado (UF)</label>
                        <input type="text" name="state" id="state" maxlength="2" style="width: 100%; padding: 8px; margin-bottom: 15px; box-sizing: border-box;">
                    </div>
                </div>

                <label style="display:block; margin-bottom: 5px;">Cidade</label>
                <input type="text" name="city" id="city" style="width: 100%; padding: 8px; margin-bottom: 15px; box-sizing: border-box;">

                <div style="display:flex; gap:10px; margin-bottom: 15px;">
                    <div style="flex:3;">
                        <label style="display:block; margin-bottom: 5px;">Endereço</label>
                        <input type="text" name="address" id="address" style="width: 100%; padding: 8px; box-sizing: border-box;">
                    </div>
                    <div style="flex:1;">
                        <label style="display:block; margin-bottom: 5px;">Nº</label>
                        <input type="text" name="address_number" id="address_number" style="width: 100%; padding: 8px; box-sizing: border-box;">
                    </div>
                </div>

                <label style="display:block; margin-bottom: 5px;">Complemento (Apto, Bloco, etc)</label>
                <input type="text" name="address_complement" id="address_complement" style="width: 100%; padding: 8px; margin-bottom: 15px; box-sizing: border-box;">

                <button type="submit" class="btn btn-activate" style="width: 100%; text-align: center;">Salvar Paciente</button>
            </form>
        </div>

        <!-- Tabela de Pacientes -->
        <div class="table-responsive">
            <table class="wp-list-table">
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>CPF</th>
                        <th>Nascimento</th>
                        <th>Contato</th>
                        <th style="width: 150px;">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($patients)): ?>
                        <tr><td colspan="5" style="text-align: center; padding: 20px;">Nenhum paciente cadastrado ainda.</td></tr>
                    <?php else: ?>
                        <?php foreach ($patients as $p): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($p['name']) ?></strong></td>
                                <td><?= htmlspecialchars($p['cpf']) ?></td>
                                <td><?= !empty($p['birthdate']) ? date('d/m/Y', strtotime($p['birthdate'])) : '-' ?></td>
                                <td>
                                    <?= htmlspecialchars($p['email'] ?? '') ?><br>
                                    <small style="color: #666;"><?= htmlspecialchars($p['phone'] ?? '') ?></small>
                                </td>
                                <td>
                                    <div style="display:flex; gap:5px;">
                                        <a href="<?= BASE_URL ?>/admin/patients/edit?id=<?= $p['id'] ?>" class="btn btn-activate" style="text-decoration:none;">Editar</a>
                                        <form method="POST" action="<?= BASE_URL ?>/admin/patients/delete" onsubmit="return confirm('Tem certeza que deseja excluir o paciente <?= htmlspecialchars($p['name']) ?>?');">
                                            <input type="hidden" name="id" value="<?= $p['id'] ?>">
                                            <button type="submit" class="btn btn-deactivate">Excluir</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
    </div>

    <script>
    // Máscaras e CEP
    document.addEventListener('DOMContentLoaded', function() {
        const masks = {
            cpf: function(value) {
                return value.replace(/\D/g, '')
                            .replace(/(\d{3})(\d)/, '$1.$2')
                            .replace(/(\d{3})(\d)/, '$1.$2')
                            .replace(/(\d{3})(\d{1,2})/, '$1-$2')
                            .replace(/(-\d{2})\d+?$/, '$1');
            },
            phone: function(value) {
                value = value.replace(/\D/g, '');
                if (value.length > 10) {
                    return value.replace(/^(\d{2})(\d{5})(\d{4}).*/, '($1) $2-$3');
                } else if (value.length > 6) {
                    return value.replace(/^(\d{2})(\d{4})(\d{0,4}).*/, '($1) $2-$3');
                } else if (value.length > 2) {
                    return value.replace(/^(\d{2})(\d{0,5})/, '($1) $2');
                }
                return value;
            },
            zip_code: function(value) {
                return value.replace(/\D/g, '')
                            .replace(/(\d{5})(\d)/, '$1-$2')
                            .replace(/(-\d{3})\d+?$/, '$1');
            }
        };

        document.querySelectorAll('input[name="cpf"]').forEach(el => {
            el.addEventListener('input', e => { e.target.value = masks.cpf(e.target.value); });
        });

        document.querySelectorAll('input[name="phone"]').forEach(el => {
            el.addEventListener('input', e => { e.target.value = masks.phone(e.target.value); });
        });

        document.querySelectorAll('input[name="zip_code"]').forEach(el => {
            el.addEventListener('input', e => { e.target.value = masks.zip_code(e.target.value); });
            
            // Busca ViaCEP
            el.addEventListener('blur', function(e) {
                let cep = e.target.value.replace(/\D/g, '');
                if (cep.length === 8) {
                    fetch(`https://viacep.com.br/ws/${cep}/json/`)
                        .then(r => r.json())
                        .then(data => {
                            if (!data.erro) {
                                document.getElementById('address').value = data.logradouro + (data.bairro ? ', ' + data.bairro : '');
                                document.getElementById('city').value = data.localidade;
                                document.getElementById('state').value = data.uf;
                                document.getElementById('address_number').focus();
                            }
                        });
                }
            });
        });
    });
    </script>


