<?php
require_once 'db.php';

function seedTransportsFromCsv(PDO $pdo, $csvPath) {
    if (!file_exists($csvPath)) {
        die("Plik CSV nie istnieje: $csvPath");
    }

    $pdo->exec("DELETE FROM transports"); // opcjonalne czyszczenie

    $handle = fopen($csvPath, 'r');
    if (!$handle) {
        die("Nie można otworzyć pliku CSV");
    }

    $header = fgetcsv($handle); // Pomijamy nagłówek

    $stmt = $pdo->prepare("
        INSERT INTO transports (
            offer_id, creation_date, address1, postal_code1,
            address2, postal_code2, price, distance,
            transport_type, route_type
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    while (($row = fgetcsv($handle)) !== false) {
        $stmt->execute($row);
    }

    fclose($handle);
    echo "Dane zostały zaimportowane do tabeli transports.";
}

seedTransportsFromCsv($pdo, __DIR__ . '/transports_real.csv');
?>
