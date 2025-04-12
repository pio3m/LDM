<?php

require_once 'database/db.php';


function calculateTransportPrice($vehicleType, $routeType, $distance, $weight, $ldm) {
    if ($vehicleType === 'solo') {
        $vehicleType = 'Solówka';
    }
    global $pdo;
    $priceFactor = 0;
    try {
        $stmt = $pdo->query("SELECT price_factor FROM pricing LIMIT 1");
        $priceFactor = $stmt->fetchColumn();
        if ($priceFactor === false) {
            $priceFactor = 0; // Ustawienie domyślnej wartości, jeśli brak wpisu
        }
    } catch (PDOException $e) {
        return ['error' => 'Błąd podczas pobierania price factor: ' . $e->getMessage()];
    }

    // Wybór tabeli na podstawie vehicleType
    $table = '';
    switch ($vehicleType) {
        case 'Bus':
            $table = 'bus';
            break;
        case 'naczepa':
            $table = 'naczepa';
            break;
        case 'Solówka':
            $table = 'solo';
            break;
        default:
            return ['error' => 'Nieprawidłowy typ pojazdu'];
    }

    // Znalezienie wartości fracht
    try {
        $stmt = $pdo->prepare("SELECT fracht FROM $table WHERE max_weight >= :weight AND min_ldm <= :ldm AND max_ldm >= :ldm LIMIT 1");
        $stmt->execute(['weight' => $weight, 'ldm' => $ldm]);
        $fracht = $stmt->fetchColumn();

        if ($fracht === false) {
            return ['error' => 'Nie znaleziono odpowiedniego frachtu'];
        }
    } catch (PDOException $e) {
        return ['error' => 'Błąd podczas pobierania frachtu: ' . $e->getMessage()];
    }

    // Wywołanie funkcji do grupowania danych
    $groupedData = groupByRoute();
    $finalData = groupByVehicleType($groupedData);
    $groupedDataWithAverages = calculateAveragePriceForGroups($finalData);

    // Znalezienie średniej ceny
    $averagePrice = null;
    $averagePriceWithMargin = null;
    $distanceRanges = ['0-100', '100.1-200', '200.1-300', '300.1-400', '400.1-500', '500.1-600', '600+'];

    if (isset($groupedDataWithAverages[$routeType])) {
        
        if ($routeType === 'Krajowy') {
            
            foreach ($distanceRanges as $range) {
                list($min, $max) = explode('-', str_replace('+', '', $range));
                if ($distance >= (float)$min && ($max === '' || $distance <= (float)$max)) {
                    $averagePrice = $groupedDataWithAverages['Krajowy'][$vehicleType][$range]['averagePrice'] ?? null;
                    if ($averagePrice !== null) {
                        $averagePriceWithMargin = $averagePrice * (1 + $priceFactor);
                    }
                    break;
                }
            }
        } else {
            foreach ($distanceRanges as $range) {
                list($min, $max) = explode('-', str_replace('+', '', $range));
                if ($distance >= (float)$min && ($max === '' || $distance <= (float)$max)) {
                    $averagePrice = $groupedDataWithAverages[$routeType][$range]['averagePrice'] ?? null;
                    if ($averagePrice !== null) {
                        $averagePriceWithMargin = $averagePrice * (1 + $priceFactor);
                    }
                    break;
                }
            }
        }
    }

    if ($averagePrice === null) {
        return ['error' => 'Nie znaleziono odpowiedniej średniej ceny'];
    }

    // Obliczenie ceny transportu
    $transportPrice = $distance * $fracht * $averagePrice;
    $transportPriceWithMargin = $distance * $fracht * $averagePriceWithMargin;

    return [
        'average_price' => round($averagePrice, 2),
        'average_price_with_margin' => round($averagePriceWithMargin, 2),
        'transport_price' => round($transportPrice, 2),
        'transport_price_with_margin' => round($transportPriceWithMargin, 2)
    ];
}

?>
