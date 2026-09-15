<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($tenantName) ?></title>
    <style>
        body { font-family: sans-serif; text-align: center; padding: 50px; }
        h1 { color: #333; }
        .box { border: 1px solid #ccc; padding: 20px; border-radius: 8px; max-width: 500px; margin: 0 auto; }
    </style>
</head>
<body>
    <div class="box">
        <h1>Bem-vindo à <?= htmlspecialchars($tenantName) ?></h1>
        <p>Este site está sendo gerado pelo motor FlexTheme do nosso SaaS.</p>
        <p>Tenant ID: <code><?= htmlspecialchars($tenantId) ?></code></p>
    </div>
</body>
</html>
