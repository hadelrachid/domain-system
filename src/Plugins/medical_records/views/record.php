<?php
$pageTitle = "Prontuário: " . htmlspecialchars($appointment['patient_name']);
?>
<style>
    .record-container { display: flex; gap: 20px; align-items: flex-start; }
    
    .patient-panel { width: 300px; background: rgba(30, 41, 59, 0.6); backdrop-filter: blur(8px); border-radius: 12px; border: 1px solid rgba(255,255,255,0.1); padding: 20px; }
    .patient-panel h2 { font-size: 16px; margin: 0 0 15px 0; color: #38bdf8; text-transform: uppercase; letter-spacing: 1px; border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 10px; }
    .patient-info-row { margin-bottom: 15px; }
    .patient-info-label { font-size: 11px; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.5px; display: block; margin-bottom: 3px; }
    .patient-info-value { font-size: 15px; color: #f1f5f9; font-weight: 500; }
    .reception-notes { background: rgba(234, 179, 8, 0.1); border-left: 3px solid #eab308; padding: 10px; margin-top: 15px; font-size: 13px; color: #fde047; font-style: italic; border-radius: 0 4px 4px 0; }
    
    .form-panel { flex-grow: 1; background: rgba(30, 41, 59, 0.6); backdrop-filter: blur(8px); border-radius: 12px; border: 1px solid rgba(255,255,255,0.1); padding: 30px; }
    .form-group { margin-bottom: 25px; }
    .form-label { display: block; font-size: 14px; font-weight: 600; color: #e2e8f0; margin-bottom: 8px; }
    .form-control { width: 100%; background: rgba(15, 23, 42, 0.5); border: 1px solid rgba(255,255,255,0.2); border-radius: 8px; padding: 12px; color: #fff; font-family: inherit; font-size: 14px; resize: vertical; outline: none; transition: all 0.2s; box-sizing: border-box; }
    .form-control:focus { border-color: #38bdf8; box-shadow: 0 0 0 2px rgba(56, 189, 248, 0.2); }
    
    .action-bar { display: flex; justify-content: flex-end; gap: 15px; margin-top: 30px; padding-top: 20px; border-top: 1px solid rgba(255,255,255,0.1); }
    .btn { padding: 10px 20px; border-radius: 8px; font-weight: 600; font-size: 14px; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; justify-content: center; border: none; transition: all 0.2s; }
    .btn-print { background: rgba(139, 92, 246, 0.2); color: #c4b5fd; border: 1px solid rgba(139, 92, 246, 0.4); }
    .btn-print:hover { background: rgba(139, 92, 246, 0.4); }
    .btn-save { background: rgba(255, 255, 255, 0.1); color: #f1f5f9; }
    .btn-save:hover { background: rgba(255, 255, 255, 0.2); }
    .btn-finish { background: #0284c7; color: #fff; }
    .btn-finish:hover { background: #0369a1; }
    
    .success-alert { background: rgba(34, 197, 94, 0.1); border: 1px solid rgba(34, 197, 94, 0.4); color: #86efac; padding: 12px 15px; border-radius: 8px; margin-bottom: 20px; font-weight: 500; }
</style>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
    <h1><?= $pageTitle ?></h1>
    <a href="admin/appointments/history" style="color: #94a3b8; text-decoration: none; font-size: 14px;">&larr; Voltar para a Fila</a>
</div>

<div class="record-container">
    <div class="patient-panel">
        <h2>Dados Clínicos</h2>
        <div class="patient-info-row">
            <span class="patient-info-label">Nome Completo</span>
            <span class="patient-info-value"><?= htmlspecialchars($appointment['patient_name']) ?></span>
        </div>
        <div class="patient-info-row">
            <span class="patient-info-label">Data de Nascimento</span>
            <span class="patient-info-value"><?= htmlspecialchars(date('d/m/Y', strtotime($appointment['patient_dob']))) ?></span>
        </div>
        <div class="patient-info-row">
            <span class="patient-info-label">CPF</span>
            <span class="patient-info-value"><?= htmlspecialchars($appointment['cpf']) ?></span>
        </div>
        <div class="patient-info-row">
            <span class="patient-info-label">Motivo (Triagem)</span>
            <div class="reception-notes">
                "<?= htmlspecialchars($appointment['reception_notes'] ?? 'Sem notas') ?>"
            </div>
        </div>

        <?php if (!empty($pastRecords)): ?>
        <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid rgba(255,255,255,0.1);">
            <h2 style="font-size: 14px; margin-bottom: 10px;">Histórico de Consultas (Timeline)</h2>
            <div style="max-height: 300px; overflow-y: auto; padding-right: 10px;">
                <?php foreach($pastRecords as $past): ?>
                    <div style="background: rgba(15,23,42,0.5); padding: 10px; border-radius: 8px; border-left: 3px solid #38bdf8; margin-bottom: 10px;">
                        <div style="font-size: 11px; color: #94a3b8; font-weight: bold; margin-bottom: 5px;">
                            📅 <?= date('d/m/Y', strtotime($past['appointment_date'])) ?>
                        </div>
                        <?php if(!empty($past['cid_10'])): ?>
                            <div style="font-size: 12px; color: #cbd5e1; margin-bottom: 3px;">
                                <span style="color: #38bdf8;">CID:</span> <?= htmlspecialchars($past['cid_10']) ?>
                            </div>
                        <?php endif; ?>
                        <?php if(!empty($past['evolucao'])): ?>
                            <div style="font-size: 12px; color: #94a3b8; font-style: italic;">
                                "<?= htmlspecialchars(mb_strimwidth($past['evolucao'], 0, 50, '...')) ?>"
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <div class="form-panel">
        <?php if(isset($_GET['success'])): ?>
            <div class="success-alert">✅ Prontuário salvo com sucesso!</div>
        <?php endif; ?>

        <form method="POST" action="admin/appointments/record/<?= $appointment['id'] ?>">
            <div class="form-group">
                <label class="form-label">Anamnese (História Clínica)</label>
                <textarea name="anamnese" rows="4" class="form-control" placeholder="Relato do paciente, início dos sintomas..."><?= htmlspecialchars($record['anamnese']) ?></textarea>
            </div>

            <div class="form-group">
                <label class="form-label">Exame Físico</label>
                <textarea name="exame_fisico" rows="3" class="form-control" placeholder="PA, FC, aspecto geral, dor à palpação..."><?= htmlspecialchars($record['exame_fisico']) ?></textarea>
            </div>

            <div class="form-group">
                <label class="form-label">Hipótese Diagnóstica (CID-10)</label>
                <textarea name="cid_10" rows="2" class="form-control" placeholder="Ex: J03.9 - Amigdalite aguda não especificada..."><?= htmlspecialchars($record['cid_10']) ?></textarea>
            </div>

            <div class="form-group">
                <label class="form-label">Evolução Clínica</label>
                <textarea name="evolucao" rows="3" class="form-control" placeholder="Progresso do tratamento..."><?= htmlspecialchars($record['evolucao']) ?></textarea>
            </div>

            <div class="form-group">
                <label class="form-label">Prescrição Médica</label>
                <textarea name="prescricao" rows="4" class="form-control" placeholder="Medicamentos, dosagem, vias de administração..."><?= htmlspecialchars($record['prescricao']) ?></textarea>
            </div>

            <div class="action-bar">
                <a href="admin/appointments/record/<?= $appointment['id'] ?>/print" target="_blank" class="btn btn-print">🖨️ Imprimir Receita</a>
                <button type="submit" name="salvar" class="btn btn-save">Salvar Rascunho</button>
                <button type="submit" name="finalizar" class="btn btn-finish">Finalizar Atendimento</button>
            </div>
        </form>

        <div style="margin-top: 40px; padding-top: 20px; border-top: 1px solid rgba(255,255,255,0.1);">
            <h2 style="font-size: 16px; margin: 0 0 15px 0; color: #38bdf8;">Exames Anexados</h2>
            
            <?php if (!empty($exams)): ?>
                <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 15px; margin-bottom: 20px;">
                    <?php foreach($exams as $ex): ?>
                        <div style="background: rgba(15,23,42,0.5); padding: 10px; border-radius: 8px; border: 1px solid rgba(255,255,255,0.1); position: relative;">
                            <div style="font-size: 12px; font-weight: bold; margin-bottom: 5px; color: #e2e8f0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="<?= htmlspecialchars($ex['file_name']) ?>">
                                <?= htmlspecialchars($ex['file_name']) ?>
                            </div>
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 10px;">
                                <a href="<?= BASE_URL . $ex['file_path'] ?>" target="_blank" class="btn" style="background: #38bdf8; color: #0f172a; padding: 4px 8px; font-size: 11px;">Visualizar</a>
                                <form method="POST" action="<?= BASE_URL ?>/admin/appointments/record/<?= $appointment['id'] ?>/delete-exam" style="margin: 0;" onsubmit="return confirm('Tem certeza que deseja apagar este exame?')">
                                    <input type="hidden" name="exam_id" value="<?= $ex['id'] ?>">
                                    <button type="submit" class="btn" style="background: #ef4444; color: #fff; padding: 4px 8px; font-size: 11px;">Excluir</button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p style="color: #94a3b8; font-size: 13px;">Nenhum exame anexado a este atendimento.</p>
            <?php endif; ?>

            <form method="POST" action="<?= BASE_URL ?>/admin/appointments/record/<?= $appointment['id'] ?>/upload-exam" enctype="multipart/form-data" style="display: flex; gap: 10px; align-items: center; background: rgba(15,23,42,0.3); padding: 15px; border-radius: 8px;">
                <input type="file" name="exam_file" required style="color: #cbd5e1; font-size: 13px;">
                <button type="submit" class="btn" style="background: #10b981; color: #fff; border: none; padding: 8px 15px;">⬆️ Enviar Arquivo</button>
            </form>
        </div>
    </div>
</div>

