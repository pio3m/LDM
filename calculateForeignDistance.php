<?php

function getForeignCoordinates($address) {
    $hereApiKey = "is5aBeCkCm5IsJZh--UffTZtOc2AjopTICTX2xV1VoI";
    // Jeśli nie podano kodu kraju, próbujemy wykryć go na podstawie adresu
    $url = "https://geocode.search.hereapi.com/v1/geocode?q=" . urlencode($address) . "&apiKey=" . $hereApiKey;
    $response = file_get_contents($url);
    $data = json_decode($response, true);
    

    if (!empty($data['items'])) {
        $lat = $data['items'][0]['position']['lat'];
        $lon = $data['items'][0]['position']['lng'];
       
        return ['lat' => $lat, 'lon' => $lon];

        
    }

    return null;
}

function calculateForeignRoadDistance($address1, $address2, $apiKey) {
    // Pobieramy współrzędne dla obu adresów
    $coords1 = getForeignCoordinates($address1);
    $coords2 = getForeignCoordinates($address2);

    
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