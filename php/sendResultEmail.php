<?php

require_once 'email/send_mail.php';
require_once 'database/db.php';
require_once 'prepare_data_for_pricing.php';
require_once 'calculatePrice.php';

/**
 * Wysyła email z wynikami kalkulacji transportu
 * 
 * @param array $getParams Parametry z GET
 * @param array $result Wynik kalkulacji
 * @param string $recipientEmail Email odbiorcy
 * @param string $recipientName Nazwa odbiorcy
 * @return bool|string true jeśli wysłano, w przeciwnym razie komunikat błędu
 */
function sendResultEmail($getParams, $result, $recipientEmail, $recipientName = '') {
    
    // Przygotowanie danych do szablonu
    $data = [
        // Parametry z GET
        'vehicle_type' => $getParams['vehicle_type'] ?? 'Nie podano',
        'route_type' => $getParams['route_type'] ?? 'Nie podano',
        'distance' => ($getParams['distance'] ?? 0) . ' km',
        'weight' => ($getParams['weight'] ?? 0) . ' kg',
        'ldm' => $getParams['ldm'] ?? 'Nie podano',
        
        // Wyniki kalkulacji
        'average_price' => isset($result['average_price']) ? number_format($result['average_price'], 2) . ' PLN' : 'N/A',
        'average_price_with_margin' => isset($result['average_price_with_margin']) ? number_format($result['average_price_with_margin'], 2) . ' PLN' : 'N/A',
        'transport_price' => isset($result['transport_price']) ? number_format($result['transport_price'], 2) . ' PLN' : 'N/A',
        'transport_price_with_margin' => isset($result['transport_price_with_margin']) ? number_format($result['transport_price_with_margin'], 2) . ' PLN' : 'N/A',
        
        // Dodatkowe informacje
        'calculation_info' => isset($result['info']) ? $result['info'] : 'Kalkulacja standardowa',
        'error_message' => isset($result['error']) ? $result['error'] : '',
        
        // Statyczne dane dla szablonu
        'quote_number' => 'TRP-' . date('Y') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT),
        'cargo_description' => 'Ładunek o wadze ' . ($getParams['weight'] ?? 0) . ' kg',
        'cargo_weight' => ($getParams['weight'] ?? 0) . ' kg',
        'vehicle_type_display' => $getParams['vehicle_type'] ?? 'Nie określono',
        'pickup_company' => 'Klient',
        'pickup_address' => 'Adres załadunku',
        'pickup_date' => date('d.m.Y'),
        'pickup_time' => '09:00',
        'delivery_company' => 'Klient',
        'delivery_address' => 'Adres rozładunku',
        'delivery_date' => date('d.m.Y', strtotime('+1 day')),
        'delivery_time' => '17:00',
        'distance_display' => ($getParams['distance'] ?? 0) . ' km',
        'transport_price_display' => isset($result['transport_price_with_margin']) ? number_format($result['transport_price_with_margin'], 2) . ' PLN' : 'N/A',
        'insurance_price' => '0.00 PLN',
        'total_netto' => isset($result['transport_price_with_margin']) ? number_format($result['transport_price_with_margin'], 2) . ' PLN' : 'N/A',
        'valid_until' => date('d.m.Y', strtotime('+7 days'))
    ];
    
    // Temat emaila
    $subject = 'Wycena transportu - ' . $data['quote_number'];
    
    // Wywołanie funkcji sendMail
    return sendMail($recipientEmail, $recipientName, $subject, $data);
}

// Przykład użycia - można wywołać bezpośrednio lub z API
if (isset($_GET['test_email'])) {
    // Przykładowe parametry GET
    $testGetParams = [
        'vehicle_type' => 'solo',
        'route_type' => 'Krajowy',
        'distance' => 150,
        'weight' => 2000,
        'ldm' => 3.5
    ];
    
    // Przykładowy wynik kalkulacji
    $testResult = [
        'average_price' => 2.50,
        'average_price_with_margin' => 2.75,
        'transport_price' => 1312.50,
        'transport_price_with_margin' => 1443.75
    ];
    
    $emailResult = sendResultEmail(
        $testGetParams, 
        $testResult, 
        'mwlojewski@gmail.com', 
        'Test User'
    );
    
    if ($emailResult === true) {
        echo '✅ Email z wynikami został wysłany!';
    } else {
        echo '❌ Błąd wysyłania emaila: ' . $emailResult;
    }
}

?>
