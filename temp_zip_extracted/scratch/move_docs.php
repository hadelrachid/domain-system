<?php

function copyDirectory($src, $dst) {
    $dir = opendir($src);
    @mkdir($dst);
    while (false !== ($file = readdir($dir))) {
        if (($file != '.') && ($file != '..')) {
            if (is_dir($src . '/' . $file)) {
                copyDirectory($src . '/' . $file, $dst . '/' . $file);
            } else {
                copy($src . '/' . $file, $dst . '/' . $file);
            }
        }
    }
    closedir($dir);
}

$srcDir = __DIR__ . '/../docs';
$dstDir = __DIR__ . '/../public/docs';

echo "Copiando pasta docs para public/docs...\n";
copyDirectory($srcDir, $dstDir);
echo "Pasta docs copiada com sucesso!\n";
