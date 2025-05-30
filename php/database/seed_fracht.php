<?php

require_once 'db.php'; // zakłada, że masz tutaj połączenie PDO jako $pdo

function seedFreightTables(PDO $pdo) {
    // Dane do tabeli `bus` ze zrzutu
    $busData = [
        [500, 0.80, 1.20, 0.40],
        [800, 1.21, 2.40, 0.60],
        [1200, 2.41, 3.60, 0.80],
        [1500, 3.61, 4.80, 1.00]
    ];

    // Dane do tabeli `solo` ze zrzutu
    $soloData = [
        [2250, 0.80, 2.00, 0.40],
        [4500, 2.01, 4.00, 0.60],
        [7500, 4.01, 6.00, 0.80],
        [9000, 6.01, 7.70, 1.00]
    ];

    // Dane do tabeli `naczepa` ze zrzutu
    $naczepaData = [
        [6000, 0.80, 3.50, 0.40],
        [12000, 3.51, 7.00, 0.60],
        [18000, 7.01, 10.50, 0.80],
        [24000, 10.51, 13.60, 1.00]
    ];

    // Dane do tabeli `ryczalt`
    $ryczaltData = [
        // [typ_pojazdu, max_weight, min_ldm, max_ldm, fracht, price]
        // Przykładowa struktura:
        ['bus', 800, 0.80, 2.40, 0.70, 350],
        ['bus', 1500, 2.41, 4.80, 1, 500],
        ['solo', 9000, 0.80, 7.7, 1, 800],
        ['naczepa', 24000, 0.80, 13.6, 1, 1000],

    ];

    try {
        $pdo->beginTransaction();

        // Czyszczenie danych
        $pdo->exec("DELETE FROM bus");
        $pdo->exec("DELETE FROM solo");
        $pdo->exec("DELETE FROM naczepa");
        $pdo->exec("DELETE FROM ryczalt");

        // Seedowanie danych do tabeli `bus`
        $stmt = $pdo->prepare("INSERT INTO bus (max_weight, min_ldm, max_ldm, fracht) VALUES (?, ?, ?, ?)");
        foreach ($busData as $row) {
            $stmt->execute($row);
        }

        // Seedowanie danych do tabeli `solo`
        $stmt = $pdo->prepare("INSERT INTO solo (max_weight, min_ldm, max_ldm, fracht) VALUES (?, ?, ?, ?)");
        foreach ($soloData as $row) {
            $stmt->execute($row);
        }

        // Seedowanie danych do tabeli `naczepa`
        $stmt = $pdo->prepare("INSERT INTO naczepa (max_weight, min_ldm, max_ldm, fracht) VALUES (?, ?, ?, ?)");
        foreach ($naczepaData as $row) {
            $stmt->execute($row);
        }

        // Seedowanie danych do tabeli `ryczalt`
        $stmt = $pdo->prepare("INSERT INTO ryczalt (vehicle_type, max_weight, min_ldm, max_ldm, fracht, price) VALUES (?, ?, ?, ?, ?, ?)");
        foreach ($ryczaltData as $row) {
            $stmt->execute($row);
        }

        $pdo->commit();
        echo "Tabele zostały pomyślnie zasilone danymi.\n";
    } catch (PDOException $e) {
        $pdo->rollBack();
        echo "Błąd przy seedowaniu danych: " . $e->getMessage();
    }
}

// Uruchomienie funkcji
seedFreightTables($pdo);

?>
