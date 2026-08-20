<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Receituário - <?= htmlspecialchars($appointment['patient_name']) ?></title>
    <style>
        @page { size: A4; margin: 20mm; }
        body {
            font-family: Arial, sans-serif;
            color: #333;
            margin: 0;
            padding: 0;
            background: #fff;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #2c3e50;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .header h1 {
            margin: 0;
            color: #2c3e50;
            font-size: 24px;
            text-transform: uppercase;
        }
        .header p { margin: 5px 0 0; color: #7f8c8d; font-size: 14px; }
        .patient-info {
            margin-bottom: 40px;
            font-size: 16px;
        }
        .patient-info strong { color: #2c3e50; }
        .prescription {
            min-height: 400px;
            font-size: 16px;
            line-height: 1.8;
            white-space: pre-wrap; /* Mantm as quebras de linha que o mdico digitou */
        }
        .footer {
            margin-top: 50px;
            text-align: center;
        }
        .signature-line {
            width: 300px;
            border-top: 1px solid #333;
            margin: 0 auto 10px auto;
        }
        .date {
            margin-top: 40px;
            text-align: right;
            font-size: 14px;
        }
        /* Esconde botes na hora de imprimir */
        @media print {
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>

    <div class="no-print" style="background: #f1c40f; padding: 10px; text-align: center; margin-bottom: 20px; font-weight: bold;">
        Modo de Impresso Ativo. <button onclick="window.print()" style="padding: 5px 15px; cursor:pointer;">Imprimir Novamente</button>
        <button onclick="window.close()" style="padding: 5px 15px; cursor:pointer;">Fechar</button>
    </div>

    <div class="header">
        <h1><?= htmlspecialchars($settings['clinic_name'] ?? 'Clínica Padrão') ?></h1>
        <p><?= htmlspecialchars($settings['clinic_slogan'] ?? '') ?></p>
        <?php if(!empty($settings['clinic_address'])): ?>
            <p style="font-size: 12px; margin-top: 2px;"><?= htmlspecialchars($settings['clinic_address']) ?></p>
        <?php endif; ?>
    </div>

    <div class="patient-info">
        <p><strong>Paciente:</strong> <?= htmlspecialchars($appointment['patient_name']) ?></p>
        <p><strong>CPF:</strong> <?= htmlspecialchars($appointment['cpf']) ?: 'Não informado' ?></p>
    </div>

    <div class="prescription">
<?= htmlspecialchars($prescricao) ?>
    </div>

    <div class="date">
        Data: <?= date('d/m/Y') ?>
    </div>

    <div class="footer">
        <div class="signature-line"></div>
        <strong>Dr(a). <?= htmlspecialchars($appointment['doctor_name']) ?></strong><br>
        <small>Assinatura e Carimbo</small>
    </div>

    <script>
        // Dispara a janela de impresso nativa assim que carregar
        window.onload = function() {
            window.print();
        }
    </script>
</body>
</html>
