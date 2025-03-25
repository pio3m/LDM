<?php
require_once 'calculateDistance.php';

// // Importowanie i wyświetlanie danych z estimatePriceAverage.php
require_once 'estimatePriceAverage.php';

// // Ustawienie zmiennych dla kodów pocztowych
// // $postalCode1 = '00-193';
// // $postalCode2 = '31-200';

// // Twój klucz API OpenRouteService
$apiKey = '5b3ce3597851110001cf6248091b8d6b6f8949c4b843be3f2a106ad2';

// // // Obliczanie odległości po drogach
// // $distance = calculateRoadDistance($postalCode1, $postalCode2, $apiKey);

// // // Wyświetlanie wyniku
// // if ($distance !== null) {
// //     echo "Odległość po drogach między $postalCode1 a $postalCode2 wynosi: " . $distance . " km";
// // } else {
// //     echo "Nie można obliczyć odległości po drogach między $postalCode1 a $postalCode2.";
// // }

// // Pobieranie danych z funkcji
// $postalDataArray = generatePostalDataArray(55);

// // Obliczanie odległości dla każdego elementu tablicy
// $postalDataArrayWithDistances = calculateDistancesForPostalData($postalDataArray, $apiKey);

// // Grupowanie danych według typu pojazdu
// $groupedData = groupByVehicleType($postalDataArrayWithDistances);

// // Obliczanie średniej ceny dla każdej podgrupy
// $groupedDataWithAverages = calculateAveragePriceForGroups($groupedData);

// // // Definiowanie typów pojazdów i zakresów odległości
// // $vehicleTypes = ['solówka', 'bus', 'naczepa', 'inne'];
// // $distanceRanges = ['0-100', '100.1-200', '200.1-300', '300.1-400', '400.1-500', '500.1-600', '600+'];
$vehicleTypes = array_keys($groupedDataWithAverages);
$distanceRanges = ['0-100', '100.1-200', '200.1-300', '300.1-400', '400.1-500', '500.1-600', '600+'];

// Generowanie tabeli HTML
echo '<table border="1" cellpadding="5" cellspacing="0">';
echo '<tr><th>Zakres odległości</th>';
foreach ($vehicleTypes as $vehicleType) {
    echo "<th>$vehicleType</th>";
}
echo '</tr>';

foreach ($distanceRanges as $range) {
    echo "<tr><td>$range</td>";
    foreach ($vehicleTypes as $vehicleType) {
        $averagePrice = isset($groupedDataWithAverages[$vehicleType][$range]['averagePrice']) ? $groupedDataWithAverages[$vehicleType][$range]['averagePrice'] : 'Brak danych';
        echo "<td>$averagePrice</td>";
    }
    echo '</tr>';
}

echo '</table>';

// Wyświetlanie zawartości groupedDataWithAverages
echo '<pre>';
print_r($groupedDataWithAverages);
echo '</pre>';

// require_once 'drawFromApi.php';
// require_once 'test.php';



?>

