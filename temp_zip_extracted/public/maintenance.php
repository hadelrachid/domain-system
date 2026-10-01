<?php
// Página de Manutenção Isolada (Stand-alone)
// Não possui NENHUMA dependência com o framework Domain-System OS.
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Atualização do Sistema</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: #0d1117;
            color: #c9d1d9;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            text-align: center;
        }
        .container {
            max-width: 600px;
            padding: 40px;
            background-color: #161b22;
            border: 1px solid #30363d;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.5);
        }
        h1 {
            color: #58a6ff;
            margin-top: 0;
            font-size: 28px;
        }
        p {
            color: #8b949e;
            line-height: 1.6;
            font-size: 16px;
        }
        .loader {
            width: 40px;
            height: 40px;
            border: 4px solid rgba(88, 166, 255, 0.2);
            border-top-color: #58a6ff;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 30px auto 10px;
        }
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Nave em Manutenção</h1>
        <p>Estamos realizando melhorias na infraestrutura do servidor ou aplicando atualizações críticas de sistema. Não se preocupe, voltaremos online em alguns instantes.</p>
        <div class="loader"></div>
        <p style="font-size: 13px; margin-top: 20px;">Powered by Domain-System OS</p>
    </div>
</body>
</html>
