<?php

require_once 'db.php';

function cleanOldTransports() {
    global $pdo;

    try {
        // Obliczanie daty sprzed 90 dni
        $dateThreshold = new DateTime();
        $dateThreshold->modify('-90 days');
        $formattedDate = $dateThreshold->format('Y-m-d');

        // Zapytanie SQL do usuwania starych wpisów
        $sql = "DELETE FROM transports WHERE creation_date < :dateThreshold";

        // Przygotowanie i wykonanie zapytania
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['dateThreshold' => $formattedDate]);

        echo "Usunięto wpisy starsze niż 90 dni.";
    } catch (PDOException $e) {
        echo "Błąd podczas usuwania danych: " . $e->getMessage();
    }
}

?>
