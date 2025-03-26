<?php

function fetchOffersFromApi() {
    // Obliczanie daty sprzed 90 dni
    $date = new DateTime();
    $date->modify('-90 days');
    $baseDate = $date->format('Y-m-d\TH:i:s\Z');
    
    $usr = base64_encode("sevium.api@firmao.pl:5b57038a278e4dbd");
    $url = "https://system.firmao.pl/sevium/svc/v1/offers?creationDate(gt)=" . $baseDate . "&limit=33&sort=creationDate&dir=DESC&mode(eq)=purchase";
    $curl = curl_init($url);
    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "GET");
    curl_setopt($curl, CURLOPT_HEADER, false);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_HTTPHEADER,
    ["Content-type: application/json; charset=UTF-8", "Authorization: Basic $usr"]);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $json);
    curl_setopt ($curl, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt ($curl, CURLOPT_SSL_VERIFYPEER, 0);
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

function fetchTransactionEntriesFromApi($ids) {
    $usr = base64_encode("sevium.api@firmao.pl:5b57038a278e4dbd");
    $customValues = [];

    foreach ($ids as $id => $offerData) {
        $url = "https://system.firmao.pl/sevium/svc/v1/transactionentries?sort=entryOrder&dir=ASC&offer(eq)=" . $id;
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "GET");
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER,
        ["Content-type: application/json; charset=UTF-8", "Authorization: Basic $usr"]);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $json);
        curl_setopt ($curl, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt ($curl, CURLOPT_SSL_VERIFYPEER, 0);
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

        // Iteracja po danych i wyodrębnianie wartości custom4 i custom5
        if (isset($response['data']) && is_array($response['data'])) {
            foreach ($response['data'] as $entry) {
                
                $custom2 = isset($entry['customFields']['custom2']) ? $entry['customFields']['custom2'] : null;
                
                $custom4 = isset($entry['customFields']['custom4']) ? $entry['customFields']['custom4'] : null;
                $custom5 = isset($entry['offer']['customFields']['custom5']) ? $entry['offer']['customFields']['custom5'] : null;
                $custom6 = isset($entry['customFields']['custom6']) ? $entry['customFields']['custom6'] : null;
                $customValues[$id][] = [
                    'custom2' => $custom2,
                    'custom4' => $custom4,
                    'custom5' => $custom5,
                    'custom6' => $custom6
                ];
              
                
            }
        }
    }

    return $customValues;
}

function extractDataFromJson() {
    $data = fetchOffersFromApi();
    $result = [];

    if (isset($data['data']) && is_array($data['data'])) {
        foreach ($data['data'] as $item) {
            if (isset($item['id'])) {
                $result[$item['id']] = [
                    'baseBruttoPrice' => $item['baseBruttoPrice'],
                    'baseNettoPrice' => $item['baseNettoPrice'],
                    'custom5' => isset($item['customFields']['custom5']) ? $item['customFields']['custom5'] : null,
                    'trasa' => $item['customFields']['custom6'],
                
                ];
            }
        }
    } else {
        die("Error: Invalid JSON structure.");
    }

    return $result;
}

function combineOfferAndTransactionData() {
    $offersData = extractDataFromJson();
 
 
    $transactionsData = fetchTransactionEntriesFromApi($offersData);

 
    $combinedData = [];
    foreach ($offersData as $id => $offer) {
    
        $postalCodes = array_column($transactionsData[$id], 'custom4');
        $addresses = array_column($transactionsData[$id], 'custom2');
        $combinedData[] = [
            'id' => $id,
            'baseBruttoPrice' => $offer['baseBruttoPrice'],
            'baseNettoPrice' => $offer['baseNettoPrice'],
            'custom5' => $offer['custom5'],
            'trasa' => $offer['trasa'],
            'postalCode1' => $postalCodes[0] ?? null,
            'postalCode2' => $postalCodes[1] ?? null,
            'adres1' => $addresses[0] ?? null,
            'adres2' => $addresses[1] ?? null
        ];
    }

    return $combinedData;
}


?>

