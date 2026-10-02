<?php
$f = "src/Plugins/nav_menus/views/admin_menus.php";
$code = file_get_contents($f);

// 1. Remove the local toast container
$code = preg_replace("/<!-- Toast Container -->.*?<\/div>/s", "", $code);

// 2. Remove local showToast JS
$code = preg_replace("/function showToast\(message, type = 'success'\) {.*?},\ 3000\);\s*}/s", "", $code);

// 3. Replace all 'showToast' calls with 'OS.notify'
$code = str_replace("showToast(", "OS.notify(", $code);

file_put_contents($f, $code);
echo "View updated!\n";
