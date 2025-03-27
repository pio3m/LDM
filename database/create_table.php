<?php

require_once 'db.php';

try {
    // Zapytanie SQL do tworzenia tabeli
    $sql = "CREATE TABLE IF NOT EXISTS transports (
        id INT AUTO_INCREMENT PRIMARY KEY,
        offer_id TEXT,
        creation_date DATE,
        address1 TEXT,
        postal_code1 TEXT,
        address2 TEXT,
        postal_code2 TEXT,
        price DECIMAL(10, 2),
        distance DECIMAL(10, 2),
        transport_type TEXT,
        route_type TEXT
    )";

    // Wykonanie zapytania
    $pdo->exec($sql);
    echo "Tabela 'transports' została pomyślnie utworzona.";
} catch (PDOException $e) {
    echo "Błąd podczas tworzenia tabeli: " . $e->getMessage();
}

?>
