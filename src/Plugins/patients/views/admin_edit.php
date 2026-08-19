<?php ob_start(); ?>

    <div style="display: flex; justify-content: space-between; align-items: center;">
        <h1>Editar Paciente</h1>
        <a href="<?= BASE_URL ?>/admin/patients" class="page-title-action">&larr; Voltar para a lista</a>
    </div>
    
    <?php if (isset($_SESSION['flash_message'])): ?>
        <?php $msg = $_SESSION['flash_message']; unset($_SESSION['flash_message']); ?>
        <div style="background: <?= $msg['type'] === 'error' ? '#fcf0f1' : '#f0f6fc' ?>; border-left: 4px solid <?= $msg['type'] === 'error' ? '#d63638' : '#2271b1' ?>; padding: 10px; margin-bottom: 20px;">
            <?= htmlspecialchars($msg['msg']) ?>
        </div>
    <?php endif; ?>

    <div class="upload-box" style="max-width: 600px; margin-top: 20px;">
        <form method="POST" action="<?= BASE_URL ?>/admin/patients/update">
            <input type="hidden" name="id" value="<?= $patient['id'] ?>">

            <div style="display:flex; gap:15px; margin-bottom: 15px;">
                <div style="flex:2;">
                    <label style="display:block; margin-bottom: 5px;">Nome Completo</label>
                    <input type="text" name="name" value="<?= htmlspecialchars($patient['name'] ?? '') ?>" required style="width: 100%; padding: 8px; box-sizing: border-box;">
                </div>
                <div style="flex:1;">
                    <label style="display:block; margin-bottom: 5px;">CPF</label>
                    <input type="text" name="cpf" value="<?= htmlspecialchars($patient['cpf'] ?? '') ?>" required placeholder="Apenas números" style="width: 100%; padding: 8px; box-sizing: border-box;">
                </div>
            </div>

            <div style="display:flex; gap:15px; margin-bottom: 15px;">
                <div style="flex:1;">
                    <label style="display:block; margin-bottom: 5px;">Data de Nascimento</label>
                    <input type="date" name="birthdate" value="<?= htmlspecialchars($patient['birthdate'] ?? '') ?>" style="width: 100%; padding: 8px; box-sizing: border-box;">
                </div>
                <div style="flex:1;">
                    <label style="display:block; margin-bottom: 5px;">E-mail</label>
                    <input type="email" name="email" value="<?= htmlspecialchars($patient['email'] ?? '') ?>" style="width: 100%; padding: 8px; box-sizing: border-box;">
                </div>
                <div style="flex:1;">
                    <label style="display:block; margin-bottom: 5px;">Telefone</label>
                    <input type="text" name="phone" value="<?= htmlspecialchars($patient['phone'] ?? '') ?>" style="width: 100%; padding: 8px; box-sizing: border-box;">
                </div>
            </div>

            <div style="border-top: 1px solid #ccc; margin-top: 20px; margin-bottom: 20px; padding-top: 10px;">
                <strong>Endereço & Convênio</strong>
            </div>

            <label style="display:block; margin-bottom: 5px;">Convênio Médico</label>
            <input type="text" name="insurance_number" value="<?= htmlspecialchars($patient['insurance_number'] ?? '') ?>" placeholder="Número da carteirinha" style="width: 100%; padding: 8px; margin-bottom: 15px; box-sizing: border-box;">

            <div style="display:flex; gap:15px; margin-bottom: 15px;">
                <div style="flex:1;">
                    <label style="display:block; margin-bottom: 5px;">CEP</label>
                    <input type="text" name="zip_code" id="zip_code" value="<?= htmlspecialchars($patient['zip_code'] ?? '') ?>" style="width: 100%; padding: 8px; box-sizing: border-box;">
                </div>
                <div style="flex:1;">
                    <label style="display:block; margin-bottom: 5px;">Estado (UF)</label>
                    <input type="text" name="state" id="state" value="<?= htmlspecialchars($patient['state'] ?? '') ?>" maxlength="2" style="width: 100%; padding: 8px; box-sizing: border-box;">
                </div>
                <div style="flex:2;">
                    <label style="display:block; margin-bottom: 5px;">Cidade</label>
                    <input type="text" name="city" id="city" value="<?= htmlspecialchars($patient['city'] ?? '') ?>" style="width: 100%; padding: 8px; box-sizing: border-box;">
                </div>
            </div>

            <label style="display:block; margin-bottom: 5px;">Endereço Completo</label>
            <input type="text" name="address" id="address" value="<?= htmlspecialchars($patient['address'] ?? '') ?>" style="width: 100%; padding: 8px; margin-bottom: 20px; box-sizing: border-box;">

            <div style="text-align:right;">
                <button type="submit" class="btn btn-activate" style="padding: 6px 14px; font-size: 14px;">Salvar Alterações</button>
            </div>
        </form>
    </div>

    <script>
    // Máscaras e CEP (reaproveitado)
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

        const applyMasks = () => {
            document.querySelectorAll('input[name="cpf"]').forEach(el => { el.value = masks.cpf(el.value); });
            document.querySelectorAll('input[name="phone"]').forEach(el => { el.value = masks.phone(el.value); });
            document.querySelectorAll('input[name="zip_code"]').forEach(el => { el.value = masks.zip_code(el.value); });
        };
        applyMasks(); // Aplicar on load

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
                            }
                        });
                }
            });
        });
    });
    </script>

<?php 
$content = ob_get_clean();
echo $theme->render('admin/layout', ['content' => $content]);
?>
