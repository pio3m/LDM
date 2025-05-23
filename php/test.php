<?php

$test1 = 12247; // Przykładowe ID oferty
$test2 = 12232; // Przykładowe ID oferty

function fetchOfferData($id1, $id2) {
    

    // Funkcja do pobierania danych z API
    function getApiData($id) {
        $usr = base64_encode("sevium.api@firmao.pl:5b57038a278e4dbd");
        $url = "https://system.firmao.pl/sevium/svc/v1/offers/" . $id;
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "GET");
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER,
        ["Content-type: application/json; charset=UTF-8", "Authorization: Basic $usr"]);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
        $json_response = curl_exec($curl);
        $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        if ($status != 201 && $status != 200) {
            die("Error: call to URL $url failed with status $status, response $json_response,
            curl_error " . curl_error($curl) . ", curl_errno " . curl_errno($curl));
        }
        curl_close($curl);
        $response = json_decode($json_response, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            die("Error decoding JSON: " . json_last_error_msg());
        }

        return $response;
    }

    // Pobieranie danych dla obu ID
    $data1 = getApiData($id1);
    $data2 = getApiData($id2);

    // Wyświetlanie danych w formacie JSON
    echo '<pre>';
    echo "Data for ID $id1:\n";
    print_r($data1);
    echo "\n\nData for ID $id2:\n";
    print_r($data2);
    echo '</pre>';
}

// Wywołanie funkcji z przykładowymi ID
fetchOfferData($test1, $test2);

?>
