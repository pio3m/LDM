<?php
require_once 'calculateDistance.php';
require_once 'drawFromApi.php';
require_once 'estimatePriceAverage.php';

// // Importowanie i wyświetlanie danych z estimatePriceAverage.php
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

// Pobieranie danych z API i obliczanie odległości
$firmaoData = combineOfferAndTransactionData();
$firmaoDataWithDistances = calculateDistancesFromFirmaoData($firmaoData, $apiKey);

// Grupowanie danych według trasy
$groupedData = groupByRoute($firmaoDataWithDistances);

// Grupowanie danych według typu pojazdu i odległości
$finalData = groupByVehicleType($groupedData);

// Obliczanie średniej ceny dla każdej podgrupy
$groupedDataWithAverages = calculateAveragePriceForGroups($finalData);

// Wyświetlanie tabeli dla Krajowy
echo '<h2>Krajowy</h2>';
echo '<table border="1" cellpadding="5" cellspacing="0">';
echo '<tr><th>Zakres odległości</th>';
foreach (array_keys($groupedDataWithAverages['Krajowy']) as $vehicleType) {
    echo "<th>$vehicleType</th>";
}
echo '</tr>';

$distanceRanges = ['0-100', '100.1-200', '200.1-300', '300.1-400', '400.1-500', '500.1-600', '600+'];
foreach ($distanceRanges as $range) {
    echo "<tr><td>$range</td>";
    foreach ($groupedDataWithAverages['Krajowy'] as $vehicleType => $distances) {
        $averagePrice = $distances[$range]['averagePrice'] ?? 'Brak danych';
        echo "<td>$averagePrice</td>";
    }
    echo '</tr>';
}
echo '</table>';

// Wyświetlanie tabeli dla Import
echo '<h2>Import</h2>';
echo '<table border="1" cellpadding="5" cellspacing="0">';
echo '<tr><th>Zakres odległości</th><th>Średnia cena</th></tr>';
foreach ($distanceRanges as $range) {
    $averagePrice = $groupedDataWithAverages['Import'][$range]['averagePrice'] ?? 'Brak danych';
    echo "<tr><td>$range</td><td>$averagePrice</td></tr>";
}
echo '</table>';

// Wyświetlanie tabeli dla Eksport
echo '<h2>Eksport</h2>';
echo '<table border="1" cellpadding="5" cellspacing="0">';
echo '<tr><th>Zakres odległości</th><th>Średnia cena</th></tr>';
foreach ($distanceRanges as $range) {
    $averagePrice = $groupedDataWithAverages['Eksport'][$range]['averagePrice'] ?? 'Brak danych';
    echo "<tr><td>$range</td><td>$averagePrice</td></tr>";
}
echo '</table>';

// Wyświetlanie zawartości groupedDataWithAverages

// require_once 'drawFromApi.php';
// require_once 'test.php';



?>

