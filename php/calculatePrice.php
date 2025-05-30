<?php

require_once 'database/db.php';
require_once 'Logger.php';

function calculateTransportPrice($vehicleType, $routeType, $distance, $weight, $ldm) {
    global $pdo;
    $logger = new Logger();

    // Sprawdzenie czy dystans jest mniejszy lub równy 50
    if ($distance <= 50) {
        $logger->log("Dystans ($distance) mniejszy lub równy 50km, używam calculateRyczaltPrice");
        return calculateRyczaltPrice($vehicleType, $routeType, $distance, $weight, $ldm);
    }

    if ($vehicleType === 'solo') {
        $vehicleType = 'Solówka';
    }
    if ($routeType === 'export') {
        $routeType = 'Eksport';
    }
    if ($routeType === 'import') {
        $routeType = 'Import';
    }   
    $priceFactor = 0;
    try {
        $stmt = $pdo->query("SELECT price_factor FROM pricing LIMIT 1");
        $priceFactor = $stmt->fetchColumn();
        if ($priceFactor === false) {
            $priceFactor = 0; // Ustawienie domyślnej wartości, jeśli brak wpisu
        }
        $logger->log("Pobrano priceFactor: $priceFactor");
    } catch (PDOException $e) {
        $logger->log("Błąd podczas pobierania price factor: " . $e->getMessage());
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
    
    $logger->log("Sprawdzam, czy istnieje grupa dla routeType: $routeType");
    
    if (isset($groupedDataWithAverages[$routeType])) {
        
        $logger->log("Znaleziono grupę dla routeType: $routeType");
        if ($routeType === 'Krajowy') {
            $logger->log("Przetwarzam trasę krajową, vehicleType: $vehicleType, distance: $distance");
            foreach ($distanceRanges as $range) {
                list($min, $max) = explode('-', str_replace('+', '', $range));
                $logger->log("Sprawdzam zakres: $range (min: $min, max: $max)");
                if ($distance >= (float)$min && ($max === '' || $distance <= (float)$max)) {
                    $logger->log("Dystans $distance pasuje do zakresu $range");
                    $averagePrice = $groupedDataWithAverages['Krajowy'][$vehicleType][$range]['averagePrice'] ?? null;
                    if ($averagePrice !== null) {
                        $averagePriceWithMargin = $averagePrice * (1 + $priceFactor);
                        $logger->log("Znaleziono averagePrice: $averagePrice, averagePriceWithMargin: $averagePriceWithMargin");
                    } else {
                        $logger->log("Nie znaleziono averagePrice dla tego zakresu");
                    }
                    break;
                }
            }
        } else {
            
            $logger->log("Przetwarzam trasę $routeType, distance: $distance");
            foreach ($distanceRanges as $range) {
                list($min, $max) = explode('-', str_replace('+', '', $range));
                $logger->log("Sprawdzam zakres: $range (min: $min, max: $max)");
                if ($distance >= (float)$min && ($max === '' || $distance <= (float)$max)) {
                    $logger->log("Dystans $distance pasuje do zakresu $range");
                    $averagePrice = $groupedDataWithAverages[$routeType][$range]['averagePrice'] ?? null;
                    if ($averagePrice !== null) {
                        $averagePriceWithMargin = $averagePrice * (1 + $priceFactor);
                        $logger->log("Znaleziono averagePrice: $averagePrice, averagePriceWithMargin: $averagePriceWithMargin");
                    } else {
                        $logger->log("Nie znaleziono averagePrice dla tego zakresu");
                    }
                    break;
                }
            }
        }
    } else {
        $logger->log("Nie znaleziono grupy dla routeType: $routeType");
    }

    if ($averagePrice === null) {
        $logger->log("Nie znaleziono odpowiedniej średniej ceny, zwracam błąd");
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

function calculateRyczaltPrice($vehicleType, $routeType, $distance, $weight, $ldm) {
    global $pdo;
    $logger = new Logger();

    if ($vehicleType === 'solo') {
        $vehicleType = 'Solówka';
    }
    if ($vehicleType === 'Solówka') {
        $vehicleType = 'solo';
    }

    try {
        $stmt = $pdo->prepare("
            SELECT price, max_weight 
            FROM ryczalt 
            WHERE vehicle_type = :vehicle_type 
            AND min_ldm <= :ldm 
            AND max_ldm >= :ldm 
            LIMIT 1
        ");
        
        $stmt->execute([
            'vehicle_type' => $vehicleType,
            'ldm' => $ldm
        ]);
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row === false) {
            $logger->log("Nie znaleziono odpowiedniej ceny ryczałtowej dla: vehicle_type=$vehicleType, ldm=$ldm");
            return ['error' => 'Nie znaleziono odpowiedniej ceny ryczałtowej'];
        }

        if ($weight > $row['max_weight']) {
            $logger->log("Przekroczona maksymalna waga dla pojazdu: weight=$weight, max_weight={$row['max_weight']}");
            return ['error' => 'Przekroczona maksymalna waga dla wybranego pojazdu'];
        }

        $logger->log("Znaleziono cenę ryczałtową: {$row['price']} dla: vehicle_type=$vehicleType, ldm=$ldm, weight=$weight");
        
        return [
            'transport_price_with_margin' => $row['price']
        ];

    } catch (PDOException $e) {
        $logger->log("Błąd podczas pobierania ceny ryczałtowej: " . $e->getMessage());
        return ['error' => 'Błąd podczas pobierania ceny ryczałtowej: ' . $e->getMessage()];
    }
}

?>
