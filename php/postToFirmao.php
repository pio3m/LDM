<?php

function fetchSalesOpportunitiesFromApi() {
    // Identyfikator organizacji
    $organizationId = 'sevium';
    
    // Parametry zapytania - zmienione na sortowanie po dacie utworzenia malejąco
    $start = 0;
    $limit = 1;
    $sort = 'creationDate';
    $dir = 'DESC';
    $dataFormat = 'MEDIUM';
    
    // Tworzenie URL z parametrami
    $url = "https://system.firmao.pl/{$organizationId}/svc/v1/salesopportunities?start={$start}&limit={$limit}&sort={$sort}&dir={$dir}&dataFormat={$dataFormat}";
    
    // Autoryzacja (używając tych samych danych co w drawFromApi.php)
    $usr = base64_encode("sevium.api@firmao.pl:5b57038a278e4dbd");
    
    // Inicjalizacja cURL
    $curl = curl_init($url);
    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "GET");
    curl_setopt($curl, CURLOPT_HEADER, false);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_HTTPHEADER, [
        "Content-type: application/json; charset=UTF-8", 
        "Authorization: Basic $usr"
    ]);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
    
    // Wykonanie zapytania
    $json_response = curl_exec($curl);
    $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);

    // Sprawdzenie błędów
    if ($status != 201 && $status != 200) {
        die("Error: call to URL $url failed with status $status, response $json_response,
        curl_error " . curl_error($curl) . ", curl_errno " . curl_errno($curl));
    }
    curl_close($curl);
    
    // Dekodowanie odpowiedzi JSON
    $response = json_decode($json_response, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        die("Error decoding JSON: " . json_last_error_msg());
    }

    return $response;
}

function extractSalesOpportunitiesData() {
    $data = fetchSalesOpportunitiesFromApi();
    $result = [];

    if (isset($data['data']) && is_array($data['data'])) {
        foreach ($data['data'] as $item) {
            if (isset($item['id'])) {
                // Funkcja pomocnicza do bezpiecznego wyciągania wartości
                $getValue = function($value) {
                    if (is_array($value)) {
                        // Jeśli to tablica z 'id' i 'name', zwróć 'name'
                        if (isset($value['name'])) {
                            return $value['name'];
                        }
                        // Jeśli to tablica z 'id', zwróć 'id'
                        if (isset($value['id'])) {
                            return $value['id'];
                        }
                        // W przeciwnym razie zwróć pierwszy element lub 'Array'
                        return !empty($value) ? reset($value) : 'Array';
                    }
                    return $value;
                };

                $result[] = [
                    'id' => $item['id'],
                    'label' => $item['label'] ?? null,
                    'acquisitionMethod' => $item['acquisitionMethod'] ?? null,
                    'currency' => $item['currency'] ?? null,
                    'salesDate' => $item['salesDate'] ?? null,
                    'salesOpportunityValue' => $item['salesOpportunityValue'] ?? null,
                    'salesOpportunityNetValue' => $item['salesOpportunityNetValue'] ?? null,
                    'customFields' => $item['customFields'] ?? []
                ];
            }
        }
    } else {
        die("Error: Invalid JSON structure or no data found.");
    }

    return $result;
}

/**
 * Wysyła dane do API transportowego i mapuje odpowiedź na customFields Firmao
 * 
 * @param array $requestData Dane z formularza (vehicle_type, route_type, distance, weight, ldm, email, phone, prompt, urgent, stackable, pickup_date, delivery_date)
 * @return array Dane zmapowane na customFields Firmao
 */
function sendTransportRequestToApi($requestData) {
    // URL API transportowego (tymczasowo ignorujemy adres serwera)
    $apiUrl = "http://localhost/api.php";
    
    // Inicjalizacja cURL
    $curl = curl_init($apiUrl);
    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($curl, CURLOPT_HEADER, false);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($requestData));
    curl_setopt($curl, CURLOPT_HTTPHEADER, [
        "Content-type: application/x-www-form-urlencoded; charset=UTF-8"
    ]);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
    
    // Wykonanie zapytania
    $json_response = curl_exec($curl);
    $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);
    
    // Sprawdzenie błędów
    if ($status != 200) {
        return ['error' => "API call failed with status $status: $json_response"];
    }
    
    // Dekodowanie odpowiedzi JSON
    $response = json_decode($json_response, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        return ['error' => "Error decoding JSON: " . json_last_error_msg()];
    }
    
    // Sprawdzenie czy jest błąd w odpowiedzi
    if (isset($response['error'])) {
        return ['error' => $response['error']];
    }
    
    // Mapowanie odpowiedzi na customFields Firmao
    $customFields = [
        'custom6' => $requestData['email'] ?? '', // Adres e-mail
        'custom8' => $requestData['pickup_postal_code'] ?? '', // Kod pocztowy załadunku
        'custom10' => $requestData['delivery_postal_code'] ?? '', // Kod pocztowy rozładunku
        'custom5' => $requestData['prompt'] ?? '', // Prompt
        'custom7' => $requestData['phone'] ?? '', // Numer telefonu
        'custom18' => $response['transport_price'] ?? '', // Obliczona wycena bez marży
        'custom14' => $requestData['ldm'] ?? '', // Obliczone LDM
        'custom12' => $requestData['distance'] ?? '', // Obliczony dystans km
        'custom17' => $response['transport_price_with_margin'] ?? '', // Obliczona wycena z marżą
        'custom16' => $requestData['vehicle_type'] ?? '', // Typ pojazdu
        'custom15' => $requestData['route_type'] ?? '', // Typ transportu
        'custom13' => $requestData['weight'] ?? '', // Waga ładunku
        'custom9' => $requestData['pickup_city'] ?? '', // Miejscowość załadunku
        'custom11' => $requestData['delivery_city'] ?? '' // Miejscowość rozładunku
    ];
    
    return [
        'success' => true,
        'customFields' => $customFields,
        'originalResponse' => $response
    ];
}

/**
 * Tworzy nową szansę sprzedaży w Firmao
 * 
 * @param array $customFields Dane zmapowane na customFields
 * @return array Wynik operacji
 */
function createSalesOpportunityInFirmao($customFields) {
    // Identyfikator organizacji
    $organizationId = 'sevium';
    
    // URL API Firmao
    $url = "https://system.firmao.pl/{$organizationId}/svc/v1/salesopportunities";
    
    // Autoryzacja (używając tych samych danych co w fetchSalesOpportunitiesFromApi)
    $usr = base64_encode("sevium.api@firmao.pl:5b57038a278e4dbd");
    
    // Przygotowanie danych do wysłania zgodnie ze strukturą API
    $postData = [
        'label' => date('Y-m-d H:i:s'), // Data i czas co do sekundy jako string
        'acquisitionMethod' => '1', // ID metody pozyskania "Kalkulator SELVIA"
        'currency' => 'PLN',
        'salesDate' => date('Y-m-d'),
        'salesOpportunityValue' => floatval($customFields['custom17'] ?? 0), // Wartość z marżą
        'salesOpportunityNetValue' => floatval($customFields['custom18'] ?? 0), // Wartość bez marży                                                    
        'customFields.custom1' => null,
        'customFields.custom2' => null,
        'customFields.custom3' => null,
        'customFields.custom4' => null,
        'customFields.custom5' => $customFields['custom5'] ?? null, // Prompt
        'customFields.custom6' => $customFields['custom6'] ?? null, // Email
        'customFields.custom7' => $customFields['custom7'] ?? null, // Telefon
        'customFields.custom8' => $customFields['custom8'] ?? null, // Kod pocztowy załadunku
        'customFields.custom9' => $customFields['custom9'] ?? null, // Miejscowość załadunku
        'customFields.custom10' => $customFields['custom10'] ?? null, // Kod pocztowy rozładunku
        'customFields.custom11' => $customFields['custom11'] ?? null, // Miejscowość rozładunku
        'customFields.custom12' => $customFields['custom12'] ?? null, // Dystans km
        'customFields.custom13' => $customFields['custom13'] ?? null, // Waga ładunku
        'customFields.custom14' => $customFields['custom14'] ?? null, // LDM
        'customFields.custom15' => $customFields['custom15'] ?? null, // Typ transportu
        'customFields.custom16' => $customFields['custom16'] ?? null, // Typ pojazdu
        'customFields.custom17' => $customFields['custom17'] ?? null, // Wycena z marżą
        'customFields.custom18' => $customFields['custom18'] ?? null, // Wycena bez marży
        'customFields.custom19' => null,
        'customFields.custom20' => null
    ];
        
    // Konwersja danych na JSON
    $jsonData = json_encode($postData, JSON_UNESCAPED_UNICODE);
    
    // Inicjalizacja cURL
    $curl = curl_init($url);
    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($curl, CURLOPT_HEADER, false);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $jsonData);
    curl_setopt($curl, CURLOPT_HTTPHEADER, [
        "Content-type: application/json; charset=UTF-8",
        "Authorization: Basic $usr"
    ]);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
    
    // Wykonanie zapytania
    $json_response = curl_exec($curl);
    $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);
    
    // Sprawdzenie błędów
    if ($status != 201 && $status != 200) {
        return ['error' => "Firmao API call failed with status $status: $json_response"];
    }
    
    // Dekodowanie odpowiedzi JSON
    $response = json_decode($json_response, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        return ['error' => "Error decoding JSON: " . json_last_error_msg()];
    }
    
    return [
        'success' => true,
        'salesOpportunityId' => $response['id'] ?? null,
        'response' => $response
    ];
}

/**
 * Kompletna funkcja: pobiera dane z API transportowego i tworzy szansę sprzedaży w Firmao
 * 
 * @param array $requestData Dane z formularza
 * @param string $label Nazwa szansy sprzedaży
 * @param int $customerId ID klienta
 * @param int $responsibleUserId ID odpowiedzialnego użytkownika
 * @return array Wynik operacji
 */
function processTransportRequestAndCreateOpportunity($requestData, $label = 'Nowa szansa transportowa', $customerId = 1, $responsibleUserId = 1) {
    // Krok 1: Pobierz dane z API transportowego
    $transportResult = sendTransportRequestToApi($requestData);
    
    
    if (isset($transportResult['error'])) {
        return ['error' => 'Transport API error: ' . $transportResult['error']];
    }
    
    // Krok 2: Utwórz szansę sprzedaży w Firmao
    $firmaoResult = createSalesOpportunityInFirmao(
        $transportResult['customFields'],
        $label,
        $customerId,
        $responsibleUserId
    );
    
    if (isset($firmaoResult['error'])) {
        return ['error' => 'Firmao API error: ' . $firmaoResult['error']];
    }
    
    return [
        'success' => true,
        'transportData' => $transportResult,
        'firmaoData' => $firmaoResult,
        'salesOpportunityId' => $firmaoResult['salesOpportunityId']
    ];
}

/**
 * Przykład użycia funkcji sendTransportRequestToApi
 */
function testTransportApi() {
    // Przykładowe dane z formularza
    $testData = [
        'vehicle_type' => 'solo',
        'route_type' => 'Krajowy',
        'distance' => 150,
        'weight' => 2000,
        'ldm' => 3.5,
        'email' => 'test@example.com',
        'phone' => '+48123456789',
        'prompt' => 'Testowy prompt',
        'urgent' => 'false',
        'stackable' => 'false',
        'pickup_date' => '2024-01-15',
        'delivery_date' => '2024-01-16'
    ];

    
    $result = sendTransportRequestToApi($testData);
    
    if (isset($result['error'])) {
        echo "Błąd: " . $result['error'] . "\n";
    } else {
        echo "Sukces! CustomFields:\n";
        print_r($result['customFields']);
        echo "\nOryginalna odpowiedź API:\n";
        print_r($result['originalResponse']);
    }
}

// Przykład użycia - można wywołać bezpośrednio
if (isset($_GET['test_sales'])) {
    try {
        $salesOpportunities = extractSalesOpportunitiesData();
        echo "<h2>Szansy sprzedaży z API Firmao:</h2>";
        echo "<pre>";
        print_r($salesOpportunities);
        echo "</pre>";
    } catch (Exception $e) {
        echo "Błąd: " . $e->getMessage();
    }
}

// Test funkcji transport API
if (isset($_GET['test_transport'])) {
    testTransportApi();
}

// Test kompletnej funkcji
if (isset($_GET['test_complete'])) {
    $testData = [
        'vehicle_type' => 'solo',
        'route_type' => 'Krajowy',
        'distance' => 150,
        'weight' => 2000,
        'ldm' => 3.5,
        'email' => 'test@example.com',
        'phone' => '+48123456789',
        'prompt' => 'Testowy prompt transportowy'
    ];
    
    $result = processTransportRequestAndCreateOpportunity($testData, 'Transport testowy');
    
    if (isset($result['error'])) {
        echo "Błąd: " . $result['error'] . "\n";
    } else {
        echo "Sukces! Utworzono szansę sprzedaży ID: " . $result['salesOpportunityId'] . "\n";
        echo "Dane transportowe:\n";
        print_r($result['transportData']);
        echo "\nDane Firmao:\n";
        print_r($result['firmaoData']);
    }
}

?>
