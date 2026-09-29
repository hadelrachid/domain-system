<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($siteName) ?> | Portal OS</title>
    
    <!-- Modern Font -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --bg-color: #0b0c10;
            --surface: rgba(31, 40, 51, 0.7);
            --primary: #45f3ff;
            --primary-glow: rgba(69, 243, 255, 0.6);
            --text-main: #c5c6c7;
            --text-muted: #8b8c8d;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            background-color: var(--bg-color);
            color: var(--text-main);
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            overflow: hidden;
            position: relative;
        }

        /* Cyberpunk Grid Background */
        body::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background-image: 
                linear-gradient(rgba(69, 243, 255, 0.04) 1px, transparent 1px),
                linear-gradient(90deg, rgba(69, 243, 255, 0.04) 1px, transparent 1px);
            background-size: 40px 40px;
            z-index: 0;
            pointer-events: none;
        }

        /* Glow effect in center */
        body::after {
            content: '';
            position: absolute;
            width: 60vw;
            height: 60vw;
            background: radial-gradient(circle, var(--primary-glow) 0%, transparent 60%);
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            opacity: 0.12;
            z-index: 0;
            pointer-events: none;
        }

        .container {
            position: relative;
            z-index: 1;
            background: var(--surface);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid rgba(69, 243, 255, 0.15);
            padding: 3.5rem;
            border-radius: 20px;
            text-align: center;
            max-width: 650px;
            width: 90%;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.5),
                        inset 0 0 20px rgba(69, 243, 255, 0.05);
            animation: fadeIn 1.2s cubic-bezier(0.16, 1, 0.3, 1) forwards;
            opacity: 0;
            transform: translateY(30px);
        }

        .badge {
            display: inline-block;
            background: rgba(69, 243, 255, 0.1);
            color: var(--primary);
            padding: 0.5rem 1.2rem;
            border-radius: 50px;
            font-size: 0.75rem;
            font-weight: 800;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            border: 1px solid rgba(69, 243, 255, 0.3);
            margin-bottom: 2rem;
            box-shadow: 0 0 15px rgba(69, 243, 255, 0.2);
        }

        h1 {
            color: #fff;
            font-size: 3rem;
            font-weight: 800;
            margin-bottom: 1.2rem;
            letter-spacing: -1px;
            text-shadow: 0 0 25px rgba(255, 255, 255, 0.15);
        }

        p {
            font-size: 1.15rem;
            line-height: 1.7;
            color: var(--text-muted);
            margin-bottom: 3rem;
            padding: 0 1rem;
        }

        .btn-glow {
            display: inline-block;
            background: transparent;
            color: var(--primary);
            text-decoration: none;
            padding: 1.2rem 3rem;
            font-size: 1rem;
            font-weight: 600;
            border: 2px solid var(--primary);
            border-radius: 12px;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
            position: relative;
            overflow: hidden;
            box-shadow: 0 0 20px rgba(69, 243, 255, 0.2),
                        inset 0 0 15px rgba(69, 243, 255, 0.05);
        }

        .btn-glow:hover {
            background: var(--primary);
            color: #0b0c10;
            box-shadow: 0 0 40px rgba(69, 243, 255, 0.6),
                        inset 0 0 20px rgba(255, 255, 255, 0.6);
            transform: translateY(-2px);
        }

        @keyframes fadeIn {
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="badge">FlexTheme OS 2.0</div>
        <h1><?= htmlspecialchars($siteName) ?></h1>
        <p>A arquitetura web do futuro está ativa. Este portal está sendo renderizado nativamente pelo Domain-System OS com integração direta e segura ao Kernel.</p>
        <a href="<?= BASE_URL ?>/login" class="btn-glow">Acessar Painel</a>
    </div>
</body>
</html>
