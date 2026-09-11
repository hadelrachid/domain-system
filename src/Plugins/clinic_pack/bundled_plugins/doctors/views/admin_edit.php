<div style="display: flex; justify-content: space-between; align-items: center;">
        <h1>Configurações do Médico</h1>
        <a href="<?= BASE_URL ?>/admin/doctors" class="page-title-action">&larr; Voltar para a lista</a>
    </div>

    <style>
        .nav-tabs { display: flex; border-bottom: 1px solid #dcdcde; margin-top: 15px; margin-bottom: 20px; gap: 20px; }
        .nav-tabs a { text-decoration: none; padding: 10px 5px; color: #50575e; border-bottom: 3px solid transparent; font-size: 14px; font-weight: 600; }
        .nav-tabs a:hover { color: #2271b1; }
        .nav-tabs a.active { color: #2271b1; border-bottom: 3px solid #2271b1; }
    </style>

    <div class="nav-tabs">
        <a href="<?= BASE_URL ?>/admin/doctors/edit?id=<?= $doctor['id'] ?>" class="active"><i class="fas fa-user-md"></i> Informações Gerais</a>
        <a href="<?= BASE_URL ?>/admin/doctors/schedule?doctor_id=<?= $doctor['id'] ?>"><i class="fas fa-calendar-alt"></i> Agenda de Horários</a>
    </div>
    
    <?php if (isset($_SESSION['flash_message'])): ?>
        <?php $msg = $_SESSION['flash_message']; unset($_SESSION['flash_message']); ?>
        <div style="background: <?= $msg['type'] === 'error' ? '#fcf0f1' : '#f0f6fc' ?>; border-left: 4px solid <?= $msg['type'] === 'error' ? '#d63638' : '#2271b1' ?>; padding: 10px; margin-bottom: 20px;">
            <?= htmlspecialchars($msg['msg']) ?>
        </div>
    <?php endif; ?>

    <div class="upload-box" style="max-width: 600px; margin-top: 20px;">
        <form method="POST" action="<?= BASE_URL ?>/admin/doctors/update" enctype="multipart/form-data">
    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
            <input type="hidden" name="id" value="<?= $doctor['id'] ?>">

            <div style="display:flex; gap:15px; margin-bottom: 15px;">
                <div style="flex:2;">
                    <label style="display:block; margin-bottom: 5px;">Nome do Profissional</label>
                    <input type="text" name="name" value="<?= htmlspecialchars($doctor['name'] ?? '') ?>" required style="width: 100%; padding: 8px; box-sizing: border-box;">
                </div>
                <div style="flex:1;">
                    <label style="display:block; margin-bottom: 5px;">CRM / Registro</label>
                    <input type="text" name="crm" value="<?= htmlspecialchars($doctor['crm'] ?? '') ?>" style="width: 100%; padding: 8px; box-sizing: border-box;">
                </div>
            </div>

            <div style="display:flex; gap:15px; margin-bottom: 15px;">
                <div style="flex:2;">
                    <label style="display:block; margin-bottom: 5px;">Especialidade</label>
                    <input type="text" name="specialty" value="<?= htmlspecialchars($doctor['specialty'] ?? '') ?>" style="width: 100%; padding: 8px; box-sizing: border-box;">
                </div>
                <div style="flex:1;">
                    <label style="display:block; margin-bottom: 5px;">Tempo (Minutos)</label>
                    <input type="number" name="consultation_time" value="<?= (int)($doctor['consultation_time'] ?? 30) ?>" min="5" step="5" style="width: 100%; padding: 8px; box-sizing: border-box;">
                </div>
            </div>

            <div style="display:flex; gap:15px; margin-bottom: 20px;">
                <div style="flex:1;">
                    <label style="display:block; margin-bottom: 5px;">Fazer Upload de Nova Foto (Local)</label>
                    <input type="file" name="photo" accept="image/*" style="width: 100%; padding: 6px; box-sizing: border-box; background: #fff; border: 1px solid #ccc; border-radius: 4px;">
                </div>
                <div style="flex:1;">
                    <label style="display:block; margin-bottom: 5px;">OU Fornecer Caminho/URL da Foto</label>
                    <input type="text" name="photo_url" value="<?= htmlspecialchars($doctor['photo_url'] ?? '') ?>" placeholder="Ex: https://..." style="width: 100%; padding: 8px; box-sizing: border-box;">
                </div>
            </div>

            <div style="text-align:right;">
                <button type="submit" class="btn btn-activate" style="padding: 6px 14px; font-size: 14px;">Salvar Alterações</button>
            </div>
        </form>
    </div>


