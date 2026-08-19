<?php ob_start(); ?>

    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h1 style="margin: 0;">Profissionais / Médicos</h1>
        
        <!-- Botão de Sincronização com o Site Principal -->
        <form method="POST" action="<?= BASE_URL ?>/admin/doctors/sync-wp" style="margin:0;" onsubmit="return confirm('Isso fará o download dos médicos cadastrados no site principal (WordPress). Deseja continuar?');">
            <button type="submit" class="page-title-action" style="background: none; cursor: pointer; padding: 4px 8px; text-decoration: none;">
                Sincronizar via WordPress
            </button>
        </form>
    </div>

    <?php if (isset($_SESSION['flash_message'])): ?>
        <?php $msg = $_SESSION['flash_message']; unset($_SESSION['flash_message']); ?>
        <div style="background: <?= $msg['type'] === 'error' ? '#fcf0f1' : '#f0f6fc' ?>; border-left: 4px solid <?= $msg['type'] === 'error' ? '#d63638' : '#2271b1' ?>; padding: 10px; margin-bottom: 20px;">
            <?= htmlspecialchars($msg['msg']) ?>
        </div>
    <?php endif; ?>

    <style>
        .flex-container { display: flex; gap: 20px; align-items: flex-start; }
        .table-responsive { overflow-x: auto; flex: 2; }
        .form-panel { flex: 1; min-width: 300px; }
        @media (max-width: 768px) {
            .flex-container { flex-direction: column; }
            .table-responsive, .form-panel { width: 100%; flex: none; }
        }
        .doctor-photo { width: 40px; height: 40px; border-radius: 50%; object-fit: cover; vertical-align: middle; margin-right: 10px; background: #eee; }
    </style>

    <div class="flex-container">
        
        <!-- Formulário de Cadastro Manual -->
        <div class="upload-box form-panel">
            <h2 style="margin-top: 0;">Novo Médico</h2>
            <form method="POST" action="<?= BASE_URL ?>/admin/doctors">
                <label style="display:block; margin-bottom: 5px;">Nome do Profissional</label>
                <input type="text" name="name" required style="width: 100%; padding: 8px; margin-bottom: 15px; box-sizing: border-box;">

                <label style="display:block; margin-bottom: 5px;">CRM / Registro</label>
                <input type="text" name="crm" placeholder="Ex: CRM/SP 12345" style="width: 100%; padding: 8px; margin-bottom: 15px; box-sizing: border-box;">

                <label style="display:block; margin-bottom: 5px;">Especialidade</label>
                <input type="text" name="specialty" placeholder="Ex: Cardiologia" style="width: 100%; padding: 8px; margin-bottom: 15px; box-sizing: border-box;">

                <label style="display:block; margin-bottom: 5px;">Tempo Médio de Consulta (Minutos)</label>
                <input type="number" name="consultation_time" value="30" min="5" step="5" style="width: 100%; padding: 8px; margin-bottom: 15px; box-sizing: border-box;">

                <label style="display:block; margin-bottom: 5px;">URL da Foto</label>
                <input type="url" name="photo_url" placeholder="https://..." style="width: 100%; padding: 8px; margin-bottom: 20px; box-sizing: border-box;">

                <button type="submit" class="btn btn-activate" style="width: 100%; text-align: center;">Salvar Médico</button>
            </form>
        </div>

        <!-- Tabela de Médicos -->
        <div class="table-responsive">
            <table class="wp-list-table">
                <thead>
                    <tr>
                        <th>Profissional</th>
                        <th>CRM</th>
                        <th>Especialidade</th>
                        <th>Tempo (Min)</th>
                        <th style="width: 150px;">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($doctors)): ?>
                        <tr><td colspan="5" style="text-align: center; padding: 20px;">Nenhum médico cadastrado. Cadastre manualmente ou sincronize com o site.</td></tr>
                    <?php else: ?>
                        <?php foreach ($doctors as $d): ?>
                            <tr>
                                <td>
                                    <?php if(!empty($d['photo_url'])): ?>
                                        <img src="<?= htmlspecialchars($d['photo_url']) ?>" class="doctor-photo" alt="Foto">
                                    <?php else: ?>
                                        <div class="doctor-photo" style="display:inline-block; text-align:center; line-height:40px; color:#aaa;">👤</div>
                                    <?php endif; ?>
                                    <strong><?= htmlspecialchars($d['name']) ?></strong>
                                    <?php if(!empty($d['wp_id'])): ?>
                                        <span style="font-size:10px; background:#e0f0fa; color:#2271b1; padding:2px 5px; border-radius:3px; margin-left:5px;">Sincronizado WP</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($d['crm'] ?: '-') ?></td>
                                <td><?= htmlspecialchars($d['specialty'] ?: '-') ?></td>
                                <td><?= (int)$d['consultation_time'] ?> min</td>
                                <td>
                                    <div style="display:flex; gap:5px;">
                                        <a href="<?= BASE_URL ?>/admin/doctors/edit?id=<?= $d['id'] ?>" class="btn btn-activate" style="text-decoration:none;">Editar</a>
                                        <form method="POST" action="<?= BASE_URL ?>/admin/doctors/delete" onsubmit="return confirm('Tem certeza que deseja excluir <?= htmlspecialchars($d['name']) ?>?');">
                                            <input type="hidden" name="id" value="<?= $d['id'] ?>">
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

<?php 
$content = ob_get_clean();
echo $theme->render('admin/layout', ['content' => $content]);
?>
