<?php
$file = 'src/Plugins/clinic_pack/bundled_plugins/appointments/Controllers/AppointmentController.php';
$content = file_get_contents($file);
$content = str_replace(
"        return ob_get_clean();\n    }\n}", 
"        return ob_get_clean();\n    }\n\n    public function renderShortcodeBookingPublic(array \$attributes = []): string\n    {\n        \$db = \DomainSystem\Core\Application::getInstance()\n            ->getContainer()->make(\DomainSystem\Plugins\Database\Connection::class)->getPdo();\n        \n        \$stmt = \$db->query(\"SELECT id, name, specialty FROM doctors ORDER BY name\");\n        \$doctors = \$stmt->fetchAll(\PDO::FETCH_ASSOC);\n\n        \$specialties = [];\n        \$doctorsBySpecialty = [];\n        foreach (\$doctors as \$doc) {\n            \$spec = \$doc['specialty'] ?: 'Clínico geral';\n            if (!in_array(\$spec, \$specialties)) {\n                \$specialties[] = \$spec;\n            }\n            \$doctorsBySpecialty[\$spec][] = \$doc;\n        }\n        sort(\$specialties);\n\n        \$stmtIns = \$db->query(\"SELECT id, name FROM health_insurances WHERE active = 1 ORDER BY id\");\n        \$insurances = \$stmtIns->fetchAll(\PDO::FETCH_ASSOC);\n\n        \$selectedDoctor = \$attributes['medico'] ?? '';\n\n        ob_start();\n        \$isShortcode = true;\n        include dirname(__DIR__, 3) . '/themes/public_booking/index.php';\n        return ob_get_clean();\n    }\n}", 
$content);
file_put_contents($file, $content);
echo "OK\n";
