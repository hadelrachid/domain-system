<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SYSTEM EMERGENCY HATCH</title>
    <style>
        body {
            background-color: #000;
            color: #0f0;
            font-family: 'Courier New', Courier, monospace;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            overflow: hidden;
        }
        .terminal {
            border: 2px solid #0f0;
            padding: 30px;
            width: 100%;
            max-width: 600px;
            box-shadow: 0 0 20px rgba(0, 255, 0, 0.2);
            background: rgba(0, 20, 0, 0.9);
        }
        h1 {
            color: #f00;
            text-align: center;
            text-shadow: 0 0 10px #f00;
            text-transform: uppercase;
            letter-spacing: 2px;
            margin-top: 0;
        }
        .warning {
            color: #ff0;
            margin-bottom: 20px;
            font-weight: bold;
            line-height: 1.5;
        }
        .error {
            color: #f00;
            margin-bottom: 15px;
            font-weight: bold;
            animation: blink 1s infinite;
        }
        input[type="password"] {
            background: #000;
            border: 1px solid #0f0;
            color: #0f0;
            padding: 10px;
            width: 100%;
            font-family: 'Courier New', Courier, monospace;
            font-size: 16px;
            box-sizing: border-box;
            outline: none;
            margin-bottom: 15px;
        }
        input[type="password"]:focus {
            box-shadow: 0 0 10px #0f0;
        }
        button {
            background: #0f0;
            color: #000;
            border: none;
            padding: 12px 20px;
            font-family: 'Courier New', Courier, monospace;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            width: 100%;
            transition: all 0.2s;
        }
        button:hover {
            background: #fff;
            box-shadow: 0 0 15px #fff;
        }
        @keyframes blink {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
        .scanline {
            width: 100%;
            height: 100px;
            z-index: 9999;
            position: absolute;
            pointer-events: none;
            background: linear-gradient(0deg, rgba(0,0,0,0) 0%, rgba(0,255,0,0.1) 50%, rgba(0,0,0,0) 100%);
            animation: scanline 6s linear infinite;
        }
        @keyframes scanline {
            0% { top: -100px; }
            100% { top: 100vh; }
        }
    </style>
</head>
<body>
    <div class="scanline"></div>
    <div class="terminal">
        <h1>[ FATAL SECURITY FAULT ]</h1>
        <div class="warning">
            > KERNEL PANIC: NO AUTHENTICATION MODULE DETECTED.<br>
            > ACCESS TO /admin HAS BEEN BLOCKED BY THE EMERGENCY PROTOCOL.<br>
            > ENTER SYSTEM APP_KEY TO MOUNT RECOVERY CONSOLE.
        </div>
        
        <?php if (!empty($error)): ?>
            <div class="error">> ERROR: <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form action="<?= BASE_URL ?>/admin/emergency" method="POST">
            <div>
                <label>> INPUT MASTER_KEY (APP_KEY):</label>
                <input type="password" name="app_key" autofocus autocomplete="off" required>
            </div>
            <button type="submit">> INITIATE OVERRIDE</button>
        </form>
        <div style="margin-top:20px; font-size:12px; opacity:0.7; text-align:center;">
            Domain System // Core Protection Service v2.0
        </div>
    </div>
</body>
</html>
