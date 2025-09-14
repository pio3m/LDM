<?php

require_once 'db.php';

// Funkcja do pobierania pierwszych 10 rekordów z tabeli transports
function getAllTransportData() {
    global $pdo;

    try {
        $stmt = $pdo->query("SELECT * FROM transports");
        $transports = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return $transports;
    } catch (PDOException $e) {
        echo "Błąd podczas pobierania danych: " . $e->getMessage();
        return [];
    }
}

// Funkcja do eksportu danych do pliku CSV z automatycznym pobieraniem
function exportToCSV() {
    $data = getAllTransportData();
    
    if (empty($data)) {
        echo "Brak danych do eksportu.\n";
        return;
    }
    
    $filename = 'transports_export_' . date('Y-m-d_H-i-s') . '.csv';
    
    // Ustawienie nagłówków HTTP dla pobierania pliku
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: no-cache, must-revalidate');
    header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
    
    // Wyjście bezpośrednio do przeglądarki
    $output = fopen('php://output', 'w');
    
    // Nagłówki kolumn
    $headers = [
        'id',
        'offer_id', 
        'creation_date',
        'address1',
        'postal_code1',
        'address2',
        'postal_code2',
        'price',
        'distance',
        'transport_type',
        'route_type'
    ];
    
    // Zapisanie nagłówków
    fputcsv($output, $headers);
    
    // Zapisanie danych
    foreach ($data as $row) {
        fputcsv($output, [
            $row['id'],
            $row['offer_id'],
            $row['creation_date'],
            $row['address1'],
            $row['postal_code1'],
            $row['address2'],
            $row['postal_code2'],
            $row['price'],
            $row['distance'],
            $row['transport_type'],
            $row['route_type']
        ]);
    }
    
    fclose($output);
    exit; // Zatrzymanie wykonywania skryptu
}

// Wywołanie funkcji eksportu
exportToCSV();

?>
