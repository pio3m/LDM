<?php

require_once 'database/db.php';
require_once 'prepare_data_for_pricing.php';

// Ustawienie nagłówka odpowiedzi na JSON
header('Content-Type: application/json');

// Pobieranie price_factor z bazy danych
$priceFactor = 0;
try {
    $stmt = $pdo->query("SELECT price_factor FROM pricing LIMIT 1");
    $priceFactor = $stmt->fetchColumn();
    if ($priceFactor === false) {
        $priceFactor = 0; // Ustawienie domyślnej wartości, jeśli brak wpisu
    }
} catch (PDOException $e) {
    echo json_encode(['error' => 'Błąd podczas pobierania price factor: ' . $e->getMessage()]);
    exit;
}

// Sprawdzenie, czy wszystkie wymagane parametry są obecne
if (isset($_GET['vehicle_type'], $_GET['route_type'], $_GET['distance'])) {
    
    $vehicleType = $_GET['vehicle_type'];
    $routeType = $_GET['route_type'];
    $distance = (float)$_GET['distance'];
   
    // Wywołanie funkcji do grupowania danych
    $groupedData = groupByRoute();
    $finalData = groupByVehicleType($groupedData);
    $groupedDataWithAverages = calculateAveragePriceForGroups($finalData);
    
    // Sprawdzenie, czy podany route_type istnieje
    if (isset($groupedDataWithAverages[$routeType])) {
        if ($routeType === 'Krajowy') {
        
            // Sprawdzenie, czy podany vehicle_type istnieje
            if (isset($groupedDataWithAverages['Krajowy'][$vehicleType])) {
                
                // Znalezienie odpowiedniego zakresu odległości
                $distanceRanges = ['0-100', '100.1-200', '200.1-300', '300.1-400', '400.1-500', '500.1-600', '600+'];
                foreach ($distanceRanges as $range) {
                    list($min, $max) = explode('-', str_replace('+', '', $range));
                    if ($distance >= (float)$min && ($max === '' || $distance <= (float)$max)) {
                        $averagePrice = $groupedDataWithAverages['Krajowy'][$vehicleType][$range]['averagePrice'] ?? null;
                        if ($averagePrice !== null) {
                            $averagePriceWithMargin = $averagePrice * (1 + $priceFactor);
                            echo json_encode([
                                'average_price' => $averagePrice,
                                'average_price_with_margin' => round($averagePriceWithMargin, 2)
                            ]);
                            exit;
                        }
                    }
                }
            }
        } else {
            // Dla Import i Eksport
            $distanceRanges = ['0-100', '100.1-200', '200.1-300', '300.1-400', '400.1-500', '500.1-600', '600+'];
            foreach ($distanceRanges as $range) {
                list($min, $max) = explode('-', str_replace('+', '', $range));
                if ($distance >= (float)$min && ($max === '' || $distance <= (float)$max)) {
                    $averagePrice = $groupedDataWithAverages[$routeType][$range]['averagePrice'] ?? null;
                    if ($averagePrice !== null) {
                        $averagePriceWithMargin = $averagePrice * (1 + $priceFactor);
                        echo json_encode([
                            'average_price' => $averagePrice,
                            'average_price_with_margin' => round($averagePriceWithMargin, 2)
                        ]);
                        exit;
                    }
                }
            }
        }
    }
}

// Jeśli nie znaleziono odpowiednich danych, zwróć błąd
http_response_code(404);
echo json_encode(['error' => 'Nie znaleziono odpowiednich danych.']);

?>
