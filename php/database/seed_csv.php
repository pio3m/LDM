<?php
require_once 'db.php';

function seedTransportsFromCsv(PDO $pdo, $csvPath) {
    if (!file_exists($csvPath)) {
        die("Plik CSV nie istnieje: $csvPath");
    }

    // Warto włączyć jawny tryb błędów:
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Opcjonalnie wyczyść tabelę
    $pdo->exec("DELETE FROM transports");

    if (($handle = fopen($csvPath, 'r')) === false) {
        die("Nie można otworzyć pliku CSV");
    }

    // Wczytaj nagłówek (sprawdzamy kolejność kolumn)
    $header = fgetcsv($handle);
    // Oczekujemy: id,offer_id,creation_date,address1,postal_code1,address2,postal_code2,price,distance,transport_type,route_type
    // Jeśli nagłówek się różni, tu można dodać walidację/mapowanie.

    $stmt = $pdo->prepare("
        INSERT INTO transports (
            offer_id, creation_date, address1, postal_code1,
            address2, postal_code2, price, distance,
            transport_type, route_type
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    while (($row = fgetcsv($handle)) !== false) {
        if (count($row) < 11) { continue; } // pomiń niepełne

        // 0: id (pomijamy)
        $offer_id      = $row[1];
        $creation_date = $row[2];
        $address1      = $row[3];
        $postal_code1  = $row[4];
        $address2      = $row[5];
        $postal_code2  = $row[6];
        $price         = $row[7];                       // "350.00" -> ok
        $distance      = ($row[8] === '' ? null : $row[8]); // '' -> NULL
        $transport_type = $row[9];
        $route_type     = $row[10];

        $stmt->execute([
            $offer_id, $creation_date, $address1, $postal_code1,
            $address2, $postal_code2, $price, $distance,
            $transport_type, $route_type
        ]);
    }

    fclose($handle);
    echo "Dane zostały zaimportowane do tabeli transports.";
}


seedTransportsFromCsv($pdo, __DIR__ . '/transports_export_2025-09-14_15-34-24.csv');
?>
