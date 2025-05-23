<?php

require_once 'database/test_data.php';

// Funkcja do grupowania według trasy
function groupByRoute() {
    $transports = getAllTransports();
   
    $groupedData = [
        'Krajowy' => [],
        'Import' => [],
        'Eksport' => []
    ];

    foreach ($transports as $data) {
        $route = $data['route_type'] ?? 'Inne';
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
        $vehicleType = $data['transport_type'];
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
                    $totalPrice += $entry['price'];
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
                $totalPrice += $entry['price'];
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
                $totalPrice += $entry['price'];
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

?>
