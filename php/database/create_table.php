<?php

require_once 'db.php';

try {
    // Tworzenie tabeli transports, jeśli nie istnieje
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS transports (
            id INT AUTO_INCREMENT PRIMARY KEY,
            offer_id INT NOT NULL,
            creation_date DATE NOT NULL,
            address1 VARCHAR(255),
            postal_code1 VARCHAR(20),
            address2 VARCHAR(255),
            postal_code2 VARCHAR(20),
            price DECIMAL(10, 2),
            distance DECIMAL(10, 2),
            transport_type VARCHAR(50),
            route_type VARCHAR(50)
        )
    ");

    // Tworzenie tabeli pricing, jeśli nie istnieje
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS pricing (
            id INT AUTO_INCREMENT PRIMARY KEY,
            price_factor DECIMAL(10, 2) NOT NULL
        )
    ");
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS bus (
            id INT AUTO_INCREMENT PRIMARY KEY,
            max_weight INT NOT NULL,
            min_ldm DECIMAL(10, 2) NOT NULL,
            max_ldm DECIMAL(10, 2) NOT NULL,
            fracht DECIMAL(10, 2) NOT NULL
        )
    ");

    // Tworzenie tabeli solo, jeśli nie istnieje
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS solo (
            id INT AUTO_INCREMENT PRIMARY KEY,
            max_weight INT NOT NULL,
            min_ldm DECIMAL(10, 2) NOT NULL,
            max_ldm DECIMAL(10, 2) NOT NULL,
            fracht DECIMAL(10, 2) NOT NULL
        )
    ");

    // Tworzenie tabeli naczepa, jeśli nie istnieje
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS naczepa (
            id INT AUTO_INCREMENT PRIMARY KEY,
            max_weight INT NOT NULL,
            min_ldm DECIMAL(10, 2) NOT NULL,
            max_ldm DECIMAL(10, 2) NOT NULL,
            fracht DECIMAL(10, 2) NOT NULL
        )
    ");

    echo "Tabele zostały pomyślnie utworzone.";
} catch (PDOException $e) {
    echo "Błąd podczas tworzenia tabel: " . $e->getMessage();
}

?>
