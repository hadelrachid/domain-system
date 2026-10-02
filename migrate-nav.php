<?php
try {
    $db = new PDO('mysql:host=localhost;dbname=domain_system', 'root', '');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $sql = "
    CREATE TABLE IF NOT EXISTS theme_menus (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        location VARCHAR(255) UNIQUE,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    );

    CREATE TABLE IF NOT EXISTS theme_menu_items (
        id INT AUTO_INCREMENT PRIMARY KEY,
        menu_id INT NOT NULL,
        title VARCHAR(255) NOT NULL,
        url VARCHAR(255),
        page_id INT NULL,
        order_index INT DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    );
    ";

    $db->exec($sql);
    echo "Tabelas criadas com sucesso no MySQL do XAMPP!\n";
} catch (PDOException $e) {
    echo "Erro: " . $e->getMessage() . "\n";
}
