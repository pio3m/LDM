<?php

require_once 'db.php';

//Funkcja do zapisywania logów
function logMessage($message) {
    $logFile = 'log.txt'; // Nazwa pliku logu
    $currentDate = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$currentDate] $message\n", FILE_APPEND);
}

function seedDatabaseWithTransports($firmaoDataWithDistances) {
    global $pdo;

    try {
        foreach ($firmaoDataWithDistances as $data) {
            // Sprawdzenie, czy wpis już istnieje
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM transports WHERE offer_id = :offer_id");
            $stmt->execute(['offer_id' => $data['id']]);
            $exists = $stmt->fetchColumn();

            if (!$exists) {
                // Przygotowanie zapytania do wstawienia nowego wpisu
                $insertStmt = $pdo->prepare("
                    INSERT INTO transports (offer_id, creation_date, address1, postal_code1, address2, postal_code2, price, distance, transport_type, route_type)
                    VALUES (:offer_id, :creation_date, :address1, :postal_code1, :address2, :postal_code2, :price, :distance, :transport_type, :route_type)
                ");

                // Wykonanie zapytania
                $insertStmt->execute([
                    'offer_id' => $data['id'],
                    'creation_date' => date('Y-m-d', strtotime($data['creationDate'])),
                    'address1' => $data['adres1'],
                    'postal_code1' => $data['postalCode1'],
                    'address2' => $data['adres2'],
                    'postal_code2' => $data['postalCode2'],
                    'price' => $data['baseNettoPrice'],
                    'distance' => $data['distance'],
                    'transport_type' => $data['custom5'],
                    'route_type' => $data['trasa']
                ]);

                logMessage("Dodano nowy wpis dla offer_id: " . $data['id']);
            } else {
                logMessage("Wpis dla offer_id: " . $data['id'] . " już istnieje.");
            }
        }
    } catch (PDOException $e) {
        logMessage("Błąd podczas zapisywania danych: " . $e->getMessage());
    }
}

?>
