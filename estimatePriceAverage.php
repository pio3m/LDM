<?php

require_once 'drawFromApi.php';
require_once 'calculateDistance.php';

$apiKey = '5b3ce3597851110001cf6248091b8d6b6f8949c4b843be3f2a106ad2';
// Predefiniowana tablica z kodami pocztowymi

// Możliwe typy pojazdów
$vehicleTypes = ['solówka', 'bus', 'naczepa', 'inne'];

// Funkcja do obliczania odległości dla danych z Firmao
function calculateDistancesFromFirmaoData($firmaoData, $apiKey) {
    foreach ($firmaoData as &$data) {
        if (!empty($data['postalCode1']) && !empty($data['postalCode2'])) {
            $distance = calculateRoadDistance($data['postalCode1'], $data['postalCode2'], $apiKey);
            $data['distance'] = $distance;
        } else {
            $data['distance'] = null;
        }
    }
    return $firmaoData;
}

// Przypisanie wyniku działania funkcji do zmiennej $firmaoData
$firmaoData = combineOfferAndTransactionData();

// Obliczanie odległości dla danych z Firmao
$firmaoDataWithDistances = calculateDistancesFromFirmaoData($firmaoData, $apiKey);

// Funkcja do grupowania według typu pojazdu i odległości
function groupByVehicleType($firmaoDataWithDistances) {
    $groupedData = [];
    foreach ($firmaoDataWithDistances as $data) {
        $vehicleType = $data['custom5'];
        $distance = $data['distance'];

        if (!isset($groupedData[$vehicleType])) {
            $groupedData[$vehicleType] = [
                '0-100' => [],
                '100.1-200' => [],
                '200.1-300' => [],
                '300.1-400' => [],
                '400.1-500' => [],
                '500.1-600' => [],
                '600+' => []
            ];
        }

        if ($distance <= 100) {
            $groupedData[$vehicleType]['0-100'][] = $data;
        } elseif ($distance <= 200) {
            $groupedData[$vehicleType]['100.1-200'][] = $data;
        } elseif ($distance <= 300) {
            $groupedData[$vehicleType]['200.1-300'][] = $data;
        } elseif ($distance <= 400) {
            $groupedData[$vehicleType]['300.1-400'][] = $data;
        } elseif ($distance <= 500) {
            $groupedData[$vehicleType]['400.1-500'][] = $data;
        } elseif ($distance <= 600) {
            $groupedData[$vehicleType]['500.1-600'][] = $data;
        } else {
            $groupedData[$vehicleType]['600+'][] = $data;
        }
    }
    return $groupedData;
}

// Funkcja do obliczania średniej ceny dla każdej podgrupy
function calculateAveragePriceForGroups($groupedData) {
    foreach ($groupedData as $vehicleType => &$distanceGroups) {
        foreach ($distanceGroups as $distanceRange => &$entries) {
            $totalPrice = 0;
            $totalDistance = 0;
            $count = 0;

            foreach ($entries as $entry) {
                if (!empty($entry['distance'])) {
                    $totalPrice += $entry['baseNettoPrice'];
                    $totalDistance += $entry['distance'];
                    $count++;
                }
            }

            $averagePrice = $count > 0 ? $totalPrice / $totalDistance : 0;
            $entries['averagePrice'] = round($averagePrice, 2);
        }
    }
    return $groupedData;
}

// Wywołanie funkcji groupByVehicleType
$groupedData = groupByVehicleType($firmaoDataWithDistances);

// Obliczanie średniej ceny dla każdej podgrupy
$groupedDataWithAverages = calculateAveragePriceForGroups($groupedData);

// Wyświetlanie wyników


?>
