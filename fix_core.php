<?php
$cores = ['auth', 'pages', 'visual_themes', 'settings', 'nobreak_shield', 'SystemAdmin', 'SystemMonitor', 'nav_menus'];
foreach ($cores as $c) {
    $file = 'D:/xampp/htdocs/domain-system/src/Plugins/' . $c . '/plugin.json';
    if (file_exists($file)) {
        $j = json_decode(file_get_contents($file), true);
        $j['core'] = true;
        unset($j['is_core']);
        file_put_contents($file, json_encode($j, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
}
echo 'Core flags atualizadas!';
