<?php
require_once 'calculateDistance.php';
require_once 'drawFromApi.php';
require_once 'estimatePriceAverage.php';
require_once 'database/test_data.php';
require_once 'prepare_data_for_pricing.php';
require_once 'database/seed_database.php';
require_once 'database/db.php';

// // Importowanie i wyświetlanie danych z estimatePriceAverage.php
// // $postalCode1 = '00-193';
// // $postalCode2 = '31-200';

// // Twój klucz API OpenRouteService
$apiKey = '5b3ce3597851110001cf6248091b8d6b6f8949c4b843be3f2a106ad2';

// // // Obliczanie odległości po drogach
// // $distance = calculateRoadDistance($postalCode1, $postalCode2, $apiKey);

// Pobieranie aktualnego price_factor z bazy danych
$currentPriceFactor = null;
try {
    $stmt = $pdo->query("SELECT price_factor FROM pricing LIMIT 1");
    $currentPriceFactor = $stmt->fetchColumn();
} catch (PDOException $e) {
    echo "Błąd podczas pobierania price factor: " . $e->getMessage();
}

// Obsługa formularza do zapisu price_factor
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['price_factor'])) {
    $priceFactor = (float)$_POST['price_factor'];

    try {
        // Usunięcie istniejącego wpisu
        $pdo->exec("DELETE FROM pricing");

        // Wstawienie nowego wpisu
        $stmt = $pdo->prepare("INSERT INTO pricing (price_factor) VALUES (:price_factor)");
        $stmt->execute(['price_factor' => $priceFactor]);

        echo "Price factor został zapisany.";
        $currentPriceFactor = $priceFactor; // Aktualizacja zmiennej po zapisie
    } catch (PDOException $e) {
        echo "Błąd podczas zapisywania price factor: " . $e->getMessage();
    }
}

// Formularz do wprowadzenia price_factor
echo '<form method="POST">';
echo '<label for="price_factor">Wprowadź price factor:</label>';
echo '<input type="text" name="price_factor" id="price_factor" value="' . htmlspecialchars($currentPriceFactor) . '" required>';
echo '<button type="submit">Zapisz</button>';
echo '</form>';

// Sprawdzenie, czy formularz został wysłany
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['seed_database'])) {
    // Pobieranie danych z API i obliczanie odległości
    $firmaoData = combineOfferAndTransactionData();
    $firmaoDataWithDistances = calculateDistancesFromFirmaoData($firmaoData, $apiKey);

    // Czyszczenie bazy danych
    cleanOldTransports();

    // Seedowanie bazy danych
    seedDatabaseWithTransports($firmaoDataWithDistances);
}

// Wywołanie funkcji groupByRoute
$groupedData = groupByRoute();

// Grupowanie danych według typu pojazdu i odległości
$finalData = groupByVehicleType($groupedData);

// Obliczanie średniej ceny dla każdej podgrupy
$groupedDataWithAverages = calculateAveragePriceForGroups($finalData);

// Formularz do seedowania bazy danych
echo '<form method="POST">';
echo '<button type="submit" name="seed_database">Seeduj bazę danych</button>';
echo '</form>';

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
    echo "<tr><td>$range</td><td> $averagePrice </td></tr>";
}
echo '</table>';

echo '<h1>Z uwzględnieniem marży</h1>';
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
        echo "<td>" . round($averagePrice * (1 + $priceFactor), 2) . "</td>";
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
    echo "<tr><td>$range</td><td>" . round($averagePrice * (1 + $priceFactor), 2) . "</td></tr>";
}
echo '</table>';

// Wyświetlanie tabeli dla Eksport
echo '<h2>Eksport</h2>';
echo '<table border="1" cellpadding="5" cellspacing="0">';
echo '<tr><th>Zakres odległości</th><th>Średnia cena</th></tr>';
foreach ($distanceRanges as $range) {
    $averagePrice = $groupedDataWithAverages['Eksport'][$range]['averagePrice'] ?? 'Brak danych';
    echo "<tr><td>$range</td><td>" . round($averagePrice * (1 + $priceFactor), 2) . "</td></tr>";
}
echo '</table>';


?>

