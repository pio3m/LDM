<?php

require_once 'drawFromApi.php';
require_once 'calculateDistance.php';
require_once 'calculateForeignDistance.php';

$apiKey = '5b3ce3597851110001cf6248091b8d6b6f8949c4b843be3f2a106ad2';
// Predefiniowana tablica z kodami pocztowymi

// Możliwe typy pojazdów
$vehicleTypes = ['solówka', 'bus', 'naczepa', 'inne'];

// Funkcja do obliczania odległości dla danych z Firmao
function calculateDistancesFromFirmaoData($firmaoData, $apiKey) {
    foreach ($firmaoData as &$data) {
        if (!empty($data['trasa']) && ($data['trasa']) === 'Krajowy') {
            if (!empty($data['postalCode1']) && !empty($data['postalCode2'])) {
                $distance = calculateRoadDistance($data['postalCode1'], $data['postalCode2'], $apiKey);
                $data['distance'] = $distance;
            } else {
                $data['distance'] = null;
            }
        } else {
            if (!empty($data['adres1']) && !empty($data['adres2'])) {
                $distance = calculateForeignRoadDistance($data['adres1'], $data['adres2'], $apiKey);
                $data['distance'] = $distance;
            } else {
                $data['distance'] = null;
            }
        }
    }
    return $firmaoData;
}

// Przypisanie wyniku działania funkcji do zmiennej $firmaoData
$firmaoData = combineOfferAndTransactionData();

// Obliczanie odległości dla danych z Firmao
$firmaoDataWithDistances = calculateDistancesFromFirmaoData($firmaoData, $apiKey);

function groupByRoute($firmaoDataWithDistances) {
    $groupedData = [
        'Krajowy' => [],
        'Import' => [],
        'Eksport' => []
    ];

    foreach ($firmaoDataWithDistances as $data) {
        $route = $data['trasa'] ?? 'Inne';
        if (!isset($groupedData[$route])) {
            $groupedData[$route] = [];
        }
        $groupedData[$route][] = $data;
    }

    return $groupedData;
}

// Funkcja do grupowania według typu pojazdu i odległości
function groupByVehicleType($groupedData) {
    $finalData = [
        'Krajowy' => [],
        'Import' => [
            '0-100' => [],
            '100.1-200' => [],
            '200.1-300' => [],
            '300.1-400' => [],
            '400.1-500' => [],
            '500.1-600' => [],
            '600+' => []
        ],
        'Eksport' => [
            '0-100' => [],
            '100.1-200' => [],
            '200.1-300' => [],
            '300.1-400' => [],
            '400.1-500' => [],
            '500.1-600' => [],
            '600+' => []
        ]
    ];

    foreach ($groupedData['Krajowy'] as $data) {
        $vehicleType = $data['custom5'];
        $distance = $data['distance'];

        if (!isset($finalData['Krajowy'][$vehicleType])) {
            $finalData['Krajowy'][$vehicleType] = [
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
            $finalData['Krajowy'][$vehicleType]['0-100'][] = $data;
        } elseif ($distance <= 200) {
            $finalData['Krajowy'][$vehicleType]['100.1-200'][] = $data;
        } elseif ($distance <= 300) {
            $finalData['Krajowy'][$vehicleType]['200.1-300'][] = $data;
        } elseif ($distance <= 400) {
            $finalData['Krajowy'][$vehicleType]['300.1-400'][] = $data;
        } elseif ($distance <= 500) {
            $finalData['Krajowy'][$vehicleType]['400.1-500'][] = $data;
        } elseif ($distance <= 600) {
            $finalData['Krajowy'][$vehicleType]['500.1-600'][] = $data;
        } else {
            $finalData['Krajowy'][$vehicleType]['600+'][] = $data;
        }
    }

    foreach (['Import', 'Eksport'] as $route) {
        foreach ($groupedData[$route] as $data) {
            $distance = $data['distance'];

            if ($distance <= 100) {
                $finalData[$route]['0-100'][] = $data;
            } elseif ($distance <= 200) {
                $finalData[$route]['100.1-200'][] = $data;
            } elseif ($distance <= 300) {
                $finalData[$route]['200.1-300'][] = $data;
            } elseif ($distance <= 400) {
                $finalData[$route]['300.1-400'][] = $data;
            } elseif ($distance <= 500) {
                $finalData[$route]['400.1-500'][] = $data;
            } elseif ($distance <= 600) {
                $finalData[$route]['500.1-600'][] = $data;
            } else {
                $finalData[$route]['600+'][] = $data;
            }
        }
    }

    return $finalData;
}

// Funkcja do obliczania średniej ceny dla każdej podgrupy
function calculateAveragePriceForGroups($finalData) {
    // Rozdzielenie danych na trzy osobne tablice
    $krajowyData = $finalData['Krajowy'];
    $importData = $finalData['Import'];
    $eksportData = $finalData['Eksport'];

    // Obliczanie średniej ceny dla Krajowy
    foreach ($krajowyData as $vehicleType => &$distanceGroups) {
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
            $distanceGroups[$distanceRange]['averagePrice'] = round($averagePrice, 2);
        }
    }

    // Obliczanie średniej ceny dla Import
    foreach ($importData as $distanceRange => &$entries) {
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
        $importData[$distanceRange]['averagePrice'] = round($averagePrice, 2);
    }

    // Obliczanie średniej ceny dla Eksport
    foreach ($eksportData as $distanceRange => &$entries) {
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
        $eksportData[$distanceRange]['averagePrice'] = round($averagePrice, 2);
    }

    // Zwracanie wyników jako osobne tablice
    return [
        'Krajowy' => $krajowyData,
        'Import' => $importData,
        'Eksport' => $eksportData
    ];
}

// Wywołanie funkcji groupByRoute
$groupedData = groupByRoute($firmaoDataWithDistances);

// Wywołanie funkcji groupByVehicleType
$finalData = groupByVehicleType($groupedData);

// Obliczanie średniej ceny dla każdej podgrupy
$groupedDataWithAverages = calculateAveragePriceForGroups($finalData);

// Wyświetlanie wyników

?>
