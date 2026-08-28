<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page['title']) ?> - Domain System</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; margin: 0; background-color: #f1f5f9; color: #1e293b; }
        .header { background: #1e293b; color: white; padding: 20px; text-align: center; }
        .container { max-width: 900px; margin: 40px auto; background: white; padding: 40px; border-radius: 8px; box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1); }
        .title { border-bottom: 2px solid #e2e8f0; padding-bottom: 10px; margin-top: 0; }
        .content { margin-top: 20px; line-height: 1.6; }
    </style>
</head>
<body>
    <div class="header">
        <h2>Minha Clínica Dinâmica</h2>
    </div>
    
    <div class="container">
        <h1 class="title"><?= htmlspecialchars($page['title']) ?></h1>
        <div class="content">
            <?= $page['content'] ?>
        </div>
    </div>
</body>
</html>
