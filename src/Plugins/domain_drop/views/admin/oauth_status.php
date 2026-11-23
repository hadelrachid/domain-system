<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= !empty($success) ? 'AliExpress Conectado!' : 'Atenção na Conexão AliExpress' ?> - DomainDrop</title>
    <style>
        body {
            background-color: #0b1120;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            padding: 20px;
            color: #f8fafc;
            background-image: radial-gradient(circle at center, #1e293b 0%, #0b1120 100%);
            box-sizing: border-box;
        }
        .card {
            background: rgba(30, 41, 59, 0.75);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            padding: 2.5rem 2rem;
            border-radius: 14px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5), 0 0 20px rgba(249, 115, 22, 0.15);
            width: 100%;
            max-width: 520px;
            border: 1px solid rgba(255, 255, 255, 0.08);
            text-align: center;
        }
        .icon {
            font-size: 54px;
            margin-bottom: 16px;
            display: inline-block;
        }
        h1 {
            font-size: 22px;
            margin: 0 0 12px 0;
            font-weight: 700;
            color: #fff;
        }
        p {
            font-size: 14px;
            color: #94a3b8;
            line-height: 1.6;
            margin: 0 0 20px 0;
        }
        .details-box {
            background: rgba(15, 23, 42, 0.7);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 8px;
            padding: 14px 16px;
            text-align: left;
            margin-bottom: 24px;
            font-size: 13px;
        }
        .details-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            gap: 10px;
        }
        .details-row:last-child {
            margin-bottom: 0;
        }
        .details-label {
            color: #64748b;
        }
        .details-val {
            color: #e2e8f0;
            font-weight: 600;
            font-family: monospace;
            word-break: break-all;
        }
        .btn {
            display: inline-block;
            width: 100%;
            padding: 14px 20px;
            background: linear-gradient(135deg, #f97316 0%, #ea580c 100%);
            color: #fff;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 700;
            font-size: 14px;
            box-sizing: border-box;
            box-shadow: 0 4px 15px rgba(249, 115, 22, 0.35);
            transition: all 0.2s ease;
        }
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(249, 115, 22, 0.5);
        }
        .btn-secondary {
            background: rgba(255, 255, 255, 0.08);
            box-shadow: none;
            margin-top: 10px;
            color: #cbd5e1;
        }
        .btn-secondary:hover {
            background: rgba(255, 255, 255, 0.15);
            box-shadow: none;
        }
        .badge-success {
            color: #4ade80;
        }
        .badge-error {
            color: #f87171;
        }
    </style>
</head>
<body>
    <div class="card">
        <?php if (!empty($success)): ?>
            <div class="icon">🎉</div>
            <h1>Conta AliExpress Conectada!</h1>
            <p>O <strong>Access Token OAuth2</strong> e o <strong>Refresh Token</strong> foram gerados pelo AliExpress e gravados com sucesso nas configurações do DomainDrop.</p>
            
            <div class="details-box">
                <div class="details-row">
                    <span class="details-label">Status:</span>
                    <span class="details-val badge-success">● Ativo & Operante</span>
                </div>
                <?php if (!empty($account)): ?>
                <div class="details-row">
                    <span class="details-label">Conta Lojista:</span>
                    <span class="details-val"><?= htmlspecialchars((string)$account, ENT_QUOTES, 'UTF-8') ?></span>
                </div>
                <?php endif; ?>
                <?php if (!empty($expiresInDays)): ?>
                <div class="details-row">
                    <span class="details-label">Validade do Token:</span>
                    <span class="details-val"><?= (int)$expiresInDays ?> dias (Renovação Automática)</span>
                </div>
                <?php endif; ?>
            </div>

            <a href="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/admin/dropship/settings" class="btn">
                ⚙️ Ir para as Configurações do DomainDrop
            </a>
            <a href="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/admin" class="btn btn-secondary">
                🏠 Painel de Controle Principal
            </a>
        <?php else: ?>
            <div class="icon">⚠️</div>
            <h1>Atenção na Autorização OAuth2</h1>
            <p><?= htmlspecialchars((string)($error ?? 'Não foi possível concluir a autorização do AliExpress.'), ENT_QUOTES, 'UTF-8') ?></p>
            
            <div class="details-box">
                <div class="details-row">
                    <span class="details-label">Status:</span>
                    <span class="details-val badge-error">● Requer Ação</span>
                </div>
                <?php if (!empty($codeSnippet)): ?>
                <div class="details-row">
                    <span class="details-label">Código recebido:</span>
                    <span class="details-val"><?= htmlspecialchars((string)$codeSnippet, ENT_QUOTES, 'UTF-8') ?></span>
                </div>
                <?php endif; ?>
            </div>

            <a href="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/admin/dropship/settings" class="btn">
                🔄 Voltar e Tentar Conectar Novamente
            </a>
            <a href="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/admin" class="btn btn-secondary">
                🏠 Painel de Controle Principal
            </a>
        <?php endif; ?>
    </div>
</body>
</html>
