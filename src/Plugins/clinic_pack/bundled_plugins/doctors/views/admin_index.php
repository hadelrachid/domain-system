<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h1 style="margin: 0;">Profissionais / Médicos</h1>
        
        <!-- Botão de Sincronização com o Site Principal -->
        <form method="POST" action="<?= BASE_URL ?>/admin/doctors/sync-wp" style="margin:0;" onsubmit="return confirm('Isso fará o download dos médicos cadastrados no site principal (WordPress). Deseja continuar?');">
    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
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
        .flex-container { display: flex; flex-direction: column; gap: 20px; }
        .table-responsive { width: 100%; overflow-x: auto; }
        .form-panel { width: 100%; max-width: 800px; margin-bottom: 20px; }
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
            <form method="POST" action="<?= BASE_URL ?>/admin/doctors" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                
                <label style="display:block; margin-bottom: 5px;">Nome do Médico</label>
                <input type="text" name="name" placeholder="Ex: Dr. João da Silva" required style="width: 100%; padding: 8px; margin-bottom: 15px; box-sizing: border-box;">

                <label style="display:block; margin-bottom: 5px;">E-mail (Usado para login do painel)</label>
                <input type="email" name="email" placeholder="medico@clinica.com" required style="width: 100%; padding: 8px; margin-bottom: 15px; box-sizing: border-box;">

                <div style="margin-bottom: 15px;">
                    <label style="display:block; margin-bottom: 5px;">Senha Temporária</label>
                    <div style="position: relative;">
                        <input type="password" name="password" id="doc_new_pwd" placeholder="Defina uma senha" required style="width: 100%; padding: 8px; padding-right: 35px; box-sizing: border-box; border: 1px solid #ccc; border-radius: 4px;" oninput="analyzePasswordStrength(this.value, 'doc_new')">
                        <button type="button" onclick="togglePasswordVisibility('doc_new_pwd', this)" style="position: absolute; right: 5px; top: 50%; transform: translateY(-50%); width: 24px; height: 24px; background: transparent; border: none; color: #666; cursor: pointer; padding: 0; display: flex; align-items: center; justify-content: center;" title="Mostrar/Ocultar Senha">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                        </button>
                    </div>
                    
                    <div style="margin-top: 8px;">
                        <button type="button" class="btn" onclick="generatePasswordAndAnalyze('doc_new_pwd', 'doc_new')">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m21.64 3.64-1.28-1.28a1.21 1.21 0 0 0-1.72 0L2.36 18.64a1.21 1.21 0 0 0 0 1.72l1.28 1.28a1.2 1.2 0 0 0 1.72 0L21.64 5.36a1.2 1.2 0 0 0 0-1.72Z"></path><path d="m14 7 3 3"></path><path d="M5 6v4"></path><path d="M19 14v4"></path><path d="M10 2v2"></path><path d="M7 8H3"></path><path d="M21 16h-4"></path><path d="M11 3H9"></path></svg> Gerar Senha Segura
                        </button>
                    </div>
                    
                    <div id="pwd-meter-doc_new" style="display:none; margin-top:8px;">
                         <div style="height:6px; background:#e2e8f0; border-radius:3px; overflow:hidden;">
                              <div id="pwd-bar-doc_new" style="height:100%; width:0%; background:#ef4444; transition: width 0.3s, background 0.3s;"></div>
                         </div>
                         <div style="display:flex; justify-content:space-between; margin-top:4px;">
                             <div id="pwd-hint-doc_new" style="font-size:11px; color:#64748b;">Inclua letras, números e símbolos</div>
                             <div id="pwd-text-doc_new" style="font-size:11px; font-weight:600; text-align:right;">Péssimo</div>
                         </div>
                    </div>
                </div>

                <label style="display:block; margin-bottom: 5px;">CRM / Registro</label>
                <input type="text" name="crm" placeholder="Ex: CRM/SP 12345" style="width: 100%; padding: 8px; margin-bottom: 15px; box-sizing: border-box;">

                <label style="display:block; margin-bottom: 5px;">Especialidade</label>
                <input type="text" name="specialty" placeholder="Ex: Cardiologia" style="width: 100%; padding: 8px; margin-bottom: 15px; box-sizing: border-box;">

                <label style="display:block; margin-bottom: 5px;">Tempo Médio de Consulta (Minutos)</label>
                <input type="number" name="consultation_time" value="30" min="5" step="5" style="width: 100%; padding: 8px; margin-bottom: 15px; box-sizing: border-box;">

                <div style="display:flex; gap:15px; margin-bottom: 20px;">
                    <div style="flex:1;">
                        <label style="display:block; margin-bottom: 5px;">Upload de Foto (Arquivo)</label>
                        <input type="file" name="photo" accept="image/*" style="width: 100%; padding: 6px; box-sizing: border-box; background: #fff; border: 1px solid #ccc; border-radius: 4px;">
                    </div>
                    <div style="flex:1;">
                        <label style="display:block; margin-bottom: 5px;">OU Caminho/URL da Foto</label>
                        <input type="text" name="photo_url" placeholder="Ex: https://..." style="width: 100%; padding: 8px; box-sizing: border-box;">
                    </div>
                </div>

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
                                        <?php $imgUrl = str_starts_with($d['photo_url'], '/') ? BASE_URL . $d['photo_url'] : $d['photo_url']; ?>
                                        <img src="<?= htmlspecialchars($imgUrl) ?>" class="doctor-photo" alt="Foto" style="object-fit:cover; border-radius:50%; width:40px; height:40px;">
                                    <?php else: ?>
                                        <div class="doctor-photo" style="display:inline-block; text-align:center; line-height:40px; color:#aaa; width:40px; height:40px; border-radius:50%; background:#f0f0f1;">👤</div>
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
                                    <div style="display:flex; gap:5px; align-items: center;">
                                        <a href="<?= BASE_URL ?>/admin/doctors/schedule?doctor_id=<?= $d['id'] ?>" class="btn" style="background: #1e293b; color: white; border-color: #1e293b; text-decoration: none;">Horários</a>
                                        <a href="<?= BASE_URL ?>/admin/doctors/edit?id=<?= $d['id'] ?>" class="btn btn-activate" style="text-decoration:none;">Editar</a>
                                        <form method="POST" action="<?= BASE_URL ?>/admin/doctors/delete" onsubmit="return confirm('Tem certeza que deseja excluir <?= htmlspecialchars($d['name']) ?>?');">
    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
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


