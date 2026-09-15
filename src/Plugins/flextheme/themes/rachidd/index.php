<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($tenantName) ?> - Software Architecture & SaaS</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #0f172a;
            color: #f8fafc;
            margin: 0;
            padding: 0;
            line-height: 1.6;
        }
        .hero {
            text-align: center;
            padding: 100px 20px;
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
            border-bottom: 1px solid #334155;
        }
        h1 {
            font-size: 3rem;
            margin-bottom: 10px;
            background: -webkit-linear-gradient(#38bdf8, #818cf8);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .subtitle {
            font-size: 1.2rem;
            color: #94a3b8;
            max-width: 600px;
            margin: 0 auto 30px auto;
        }
        .btn {
            display: inline-block;
            background-color: #38bdf8;
            color: #0f172a;
            padding: 12px 30px;
            text-decoration: none;
            border-radius: 8px;
            font-weight: bold;
            transition: all 0.3s ease;
        }
        .btn:hover {
            background-color: #7dd3fc;
            transform: translateY(-2px);
        }
        .container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 50px 20px;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
        }
        .card {
            background-color: #1e293b;
            padding: 30px;
            border-radius: 12px;
            border: 1px solid #334155;
        }
        .card h3 {
            color: #38bdf8;
            margin-top: 0;
        }
    </style>
</head>
<body>

    <header class="hero">
        <h1><?= htmlspecialchars($tenantName) ?></h1>
        <p class="subtitle">Bem-vindo à Nave Mãe. Este domínio é servido pelo FlexTheme operando sobre a arquitetura SOLID Multi-Tenant do Motor de Software.</p>
        <a href="#livro" class="btn">Conheça o Livro SOLID</a>
    </header>

    <div class="container">
        <div class="card">
            <h3>📖 O Livro: Entendendo Arquitetura SOLID</h3>
            <p>Em breve, você poderá adquirir o PDF direto por aqui, operado pelo nosso ecossistema de SaaS.</p>
        </div>
        <div class="card">
            <h3>⚙️ Motor SaaS em Ação</h3>
            <p>Você está visualizando o <b>Tenant ID: <?= htmlspecialchars($tenantId) ?></b>. Nosso roteador é capaz de hospedar centenas de sites e sistemas de clínicas a partir do mesmo núcleo.</p>
        </div>
    </div>

</body>
</html>
