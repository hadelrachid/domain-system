<?php
$files = [
    'src/Plugins/clinic_pack/bundled_plugins/finance/plugin.json' => ['patients'],
    'src/Plugins/clinic_pack/bundled_plugins/medical_records/plugin.json' => ['appointments', 'patients', 'doctors'],
    'src/Plugins/clinic_pack/bundled_plugins/triage/plugin.json' => ['appointments']
];

foreach ($files as $file => $deps) {
    if (file_exists($file)) {
        $json = json_decode(file_get_contents($file), true);
        $json['dependencies'] = $deps;
        file_put_contents($file, json_encode($json, JSON_PRETTY_PRINT));
        echo "Updated $file\n";
    }
}
