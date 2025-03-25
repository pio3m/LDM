<?php
function getCoordinates($address, $countryCode = null) {
    // Jeśli nie podano kodu kraju, próbujemy wykryć go na podstawie adresu
    $url = "https://nominatim.openstreetmap.org/search?q=" . urlencode($address);

    // Jeśli podano countryCode, dodajemy go do zapytania
    if ($countryCode) {
        $url .= "&countrycodes=" . urlencode($countryCode);
    }

    $url .= "&format=json";

    $options = [
        "http" => [
            "header" => "User-Agent: PHP"
        ]
    ];

    $context = stream_context_create($options);
    $response = file_get_contents($url, false, $context);
    $data = json_decode($response, true);

    if (!empty($data)) {
        return [
            'lat' => $data[0]['lat'],
            'lon' => $data[0]['lon']
        ];
    }

    return null;
}
function calculateRoadDistance($address1, $address2, $apiKey) {
    // Pobieramy współrzędne dla obu adresów
    $coords1 = getCoordinates($address1);
    $coords2 = getCoordinates($address2);

    // Sprawdzamy, czy udało się pobrać współrzędne dla obu adresów
    if ($coords1 && $coords2) {
        // Budowanie URL do OpenRouteService
        $url = "https://api.openrouteservice.org/v2/directions/driving-car?api_key=$apiKey&start={$coords1['lon']},{$coords1['lat']}&end={$coords2['lon']},{$coords2['lat']}";

        // Opcje dla nagłówka
        $options = [
            "http" => [
                "header" => "User-Agent: PHP"
            ]
        ];

        $context = stream_context_create($options);
        // Wysyłamy zapytanie do OpenRouteService
        $response = file_get_contents($url, false, $context);
        $data = json_decode($response, true);

        // Jeśli mamy dane o odległości, zwracamy ją (w kilometrach)
        if (!empty($data['features'][0]['properties']['segments'][0]['distance'])) {
            return round($data['features'][0]['properties']['segments'][0]['distance'] / 1000, 2);
        }
    }

    // Jeśli coś poszło nie tak, zwracamy null
    return null;
}