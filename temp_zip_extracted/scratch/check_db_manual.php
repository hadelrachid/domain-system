<?php
$db = new PDO('sqlite:D:/xampp/htdocs/domain-system/database.sqlite');
$stmt = $db->query("SELECT * FROM patients WHERE name LIKE '%Carlos%'");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
$stmt = $db->query("SELECT * FROM appointments ORDER BY id DESC LIMIT 10");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
