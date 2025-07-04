<?php

// Nagłówki CORS
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once 'database/db.php';
require_once 'prepare_data_for_pricing.php';
require_once 'calculatePrice.php';
require_once 'postToFirmao.php';
require_once 'email/send_mail.php';

// Sprawdzenie, czy wszystkie wymagane parametry są obecne
if (isset($_GET['vehicle_type'], $_GET['route_type'], $_GET['distance'], $_GET['weight'], $_GET['ldm'])) {
    
    $vehicleType = $_GET['vehicle_type'];
    $routeType = $_GET['route_type'];
    $distance = (float)$_GET['distance'];
    $weight = (int)$_GET['weight'];
    $ldm = (float)$_GET['ldm'];

    // Obliczenie ceny transportu
    $result = calculateTransportPrice($vehicleType, $routeType, $distance, $weight, $ldm);
    
    // Przygotowanie danych do szablonu e-maila (po wycenie)
    $emailData = [
        'prompt' => $_GET['prompt'] ?? '',
        'distance' => $distance,
        'ldm' => $ldm,
        'weight' => $weight,
        'route_type' => $routeType,
        'vehicle_type' => $vehicleType,
        'transport_price_display' => isset($result['transport_price_with_margin']) ? number_format($result['transport_price_with_margin'], 2, ',', ' ') . ' PLN' : '',
        'total_netto' => isset($result['transport_price_with_margin']) ? number_format($result['transport_price_with_margin'], 2, ',', ' ') . ' PLN' : '',
        'average_price_with_margin' => isset($result['average_price_with_margin']) ? number_format($result['average_price_with_margin'], 2, ',', ' ') . ' PLN' : '',
        'transport_price_with_margin' => isset($result['transport_price_with_margin']) ? number_format($result['transport_price_with_margin'], 2, ',', ' ') . ' PLN' : '',
    ];

    // Wysyłka e-maila po wycenie
    $emailTo = $_GET['email'] ?? '';
    $emailName = $emailTo;
    $emailSubject = 'Wstępna wycena transportu';
    
    $mailStatus = '';
    if (filter_var($emailTo, FILTER_VALIDATE_EMAIL)) {
        $mailStatus = sendMail($emailTo, $emailName, $emailSubject, $emailData);
    } else {
        $mailStatus = 'Nieprawidłowy adres e-mail.';
    }

    if (isset($result['error'])) {
        echo json_encode([
            'error' => $result['error'],
            'mail_status' => $mailStatus === true ? 'Wysłano e-mail' : $mailStatus
        ]);
        exit;
    } else {
        // Przygotowanie danych do utworzenia szansy sprzedaży w Firmao
        $requestData = [
            'vehicle_type' => $vehicleType,
            'route_type' => $routeType,
            'distance' => $distance,
            'weight' => $weight,
            'ldm' => $ldm,
            'email' => $_GET['email'] ?? '',
            'phone' => $_GET['phone'] ?? '',
            'prompt' => $_GET['prompt'] ?? '',
            'urgent' => $_GET['urgent'] ?? 'false',
            'stackable' => $_GET['stackable'] ?? 'false',
            'pickup_date' => $_GET['pickup_date'] ?? date('Y-m-d'),
            'delivery_date' => $_GET['delivery_date'] ?? date('Y-m-d', strtotime('+1 day')),
            'pickup_postal_code' => $_GET['pickup_postal_code'] ?? '',
            'delivery_postal_code' => $_GET['delivery_postal_code'] ?? '',
            'pickup_city' => $_GET['pickup_city'] ?? '',
            'delivery_city' => $_GET['delivery_city'] ?? ''
        ];

        // Utworzenie szansy sprzedaży w Firmao
        $firmaoResult = createSalesOpportunityInFirmao([
            'custom6' => $requestData['email'], // Adres e-mail
            'custom8' => $requestData['pickup_postal_code'], // Kod pocztowy załadunku
            'custom10' => $requestData['delivery_postal_code'], // Kod pocztowy rozładunku
            'custom5' => $requestData['prompt'], // Prompt
            'custom7' => $requestData['phone'], // Numer telefonu
            'custom18' => $result['transport_price'] ?? '', // Obliczona wycena bez marży
            'custom14' => $requestData['ldm'], // Obliczone LDM
            'custom12' => $requestData['distance'], // Obliczony dystans km
            'custom17' => $result['transport_price_with_margin'] ?? '', // Obliczona wycena z marżą
            'custom16' => $requestData['vehicle_type'], // Typ pojazdu
            'custom15' => $requestData['route_type'], // Typ transportu
            'custom13' => $requestData['weight'], // Waga ładunku
            'custom9' => $requestData['pickup_city'], // Miejscowość załadunku
            'custom11' => $requestData['delivery_city'] // Miejscowość rozładunku
        ]);

        // Przygotowanie odpowiedzi
        $response = [
            'transport_calculation' => $result,
            'firmao_creation' => $firmaoResult,
            'mail_status' => $mailStatus === true ? 'Wysłano e-mail' : $mailStatus
        ];

        if (isset($firmaoResult['error'])) {
            $response['firmao_error'] = $firmaoResult['error'];
        } else {
            $response['sales_opportunity_id'] = $firmaoResult['salesOpportunityId'];
        }

        echo json_encode($response);
    }
    exit;
}

// Jeśli nie znaleziono odpowiednich danych, zwróć błąd
http_response_code(404);
echo json_encode(['error' => 'Nie znaleziono odpowiednich danych.']);

?>
