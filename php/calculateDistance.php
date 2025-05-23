<?php

function getCoordinates($postalCode) {
    $url = "https://nominatim.openstreetmap.org/search?postalcode=" . urlencode($postalCode) . "&format=json&countrycodes=pl";
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

function calculateRoadDistance($postalCode1, $postalCode2, $apiKey) {
    $coords1 = getCoordinates($postalCode1);
    $coords2 = getCoordinates($postalCode2);

    if ($coords1 && $coords2) {
        $url = "https://api.openrouteservice.org/v2/directions/driving-car?api_key=$apiKey&start={$coords1['lon']},{$coords1['lat']}&end={$coords2['lon']},{$coords2['lat']}";
        
        $options = [
            "http" => [
                "header" => "User-Agent: PHP"
            ]
        ];
        $context = stream_context_create($options);
        $response = file_get_contents($url, false, $context);
        $data = json_decode($response, true);

        if (!empty($data['features'][0]['properties']['segments'][0]['distance'])) {
            // Odległość w metrach, konwersja na kilometry
            return round($data['features'][0]['properties']['segments'][0]['distance'] / 1000, 2);
        }
    }

    return null;
}


