<?php
$sourceDir = __DIR__ . '/../docs';
$targetDir = __DIR__ . '/../src/Plugins/flextheme/themes/rachidd/templates/docs';

if (!is_dir($targetDir)) {
    mkdir($targetDir, 0777, true);
}

$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($sourceDir));

foreach ($iterator as $fileInfo) {
    if ($fileInfo->isFile() && $fileInfo->getExtension() === 'html') {
        $file = $fileInfo->getPathname();
        $content = file_get_contents($file);
        
        // Pega o caminho relativo (ex: architecture/01-ciclo)
        $relativePath = str_replace([$sourceDir . '/', $sourceDir . '\\'], '', $fileInfo->getPathname());
        $relativePath = str_replace('\\', '/', $relativePath); // Padroniza
        $slug = preg_replace('/\.html$/', '', $relativePath);
        
        // Cria a pasta de destino se for subdiretório
        $targetFile = $targetDir . '/' . $slug . '.php';
        $targetSubdir = dirname($targetFile);
        if (!is_dir($targetSubdir)) {
            mkdir($targetSubdir, 0777, true);
        }
    
    // Extract <style>
    $style = '';
    if (preg_match('/<style[^>]*>(.*?)<\/style>/is', $content, $matches)) {
        $style = "<style>\n" . $matches[1] . "\n</style>\n";
    }
    
    // Extract <body> inner content
    $body = '';
    if (preg_match('/<body[^>]*>(.*?)<\/body>/is', $content, $matches)) {
        $body = $matches[1];
    }
    
    // Remove translations switch logic if we want, but let's keep it
    
    // Fix relative links like href="tutorial-dev.html" -> href="/docs/tutorial-dev"
    // We can use [base_url]/docs/tutorial-dev
    $body = preg_replace('/href="([^"]+)\.html"/', 'href="<?= defined(\'BASE_URL\') ? BASE_URL : \'\' ?>/docs/$1"', $body);
    
    // Fix image paths: src="images/logo.svg" -> src="[base_url]/docs/images/logo.svg"
    // Since images are in public/docs/images
    $body = preg_replace('/src="images\/([^"]+)"/', 'src="<?= defined(\'BASE_URL\') ? BASE_URL : \'\' ?>/docs/images/$1"', $body);

    $finalContent = $style . $body;
    
    file_put_contents($targetDir . '/' . $slug . '.php', $finalContent);
    echo "Convertido: $relativePath -> templates/docs/$slug.php\n";
    }
}

echo "Migração das documentações concluída!\n";
