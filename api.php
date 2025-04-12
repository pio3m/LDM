<?php

require_once 'database/db.php';
require_once 'prepare_data_for_pricing.php';
require_once 'calculatePrice.php';

// Ustawienie nagłówka odpowiedzi na JSON
header('Content-Type: application/json');

// Pobieranie price_factor z bazy danych


// Sprawdzenie, czy wszystkie wymagane parametry są obecne
if (isset($_GET['vehicle_type'], $_GET['route_type'], $_GET['distance'], $_GET['weight'], $_GET['ldm'])) {
    
    $vehicleType = $_GET['vehicle_type'];
    $routeType = $_GET['route_type'];
    $distance = (float)$_GET['distance'];
    $weight = (int)$_GET['weight'];
    $ldm = (float)$_GET['ldm'];

    // Obliczenie ceny transportu
    $result = calculateTransportPrice($vehicleType, $routeType, $distance, $weight, $ldm);

    if (isset($result['error'])) {
        echo json_encode(['error' => $result['error']]);
    } else {
        echo json_encode($result);
    }
    exit;
}

// Jeśli nie znaleziono odpowiednich danych, zwróć błąd
http_response_code(404);
echo json_encode(['error' => 'Nie znaleziono odpowiednich danych.']);

?>
