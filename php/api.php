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

// === Logger ===
if (!class_exists('Logger')) { require_once __DIR__ . '/Logger.php'; } // jeśli klasa w osobnym pliku
$logger = new Logger(__DIR__ . '/log.txt');

// mały helper do logowania JSON
function logj(Logger $logger, string $label, array $arr = []): void {
    $logger->log($label . ' ' . json_encode($arr, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
}

// request baseline
$logger->log(sprintf(
    'REQ method=%s ip=%s uri=%s',
    $_SERVER['REQUEST_METHOD'] ?? '?',
    $_SERVER['REMOTE_ADDR'] ?? '?',
    $_SERVER['REQUEST_URI'] ?? '?'
));


// ===== Helpers: normalizacja, locki, batch storage, dedup maila =====
function safe_id(string $id): string
{
    return preg_replace('/[^a-zA-Z0-9_\-]/', '_', $id);
}

function batch_dir(): string
{
    $d = __DIR__ . '/cache';
    if (!is_dir($d)) @mkdir($d, 0775, true);
    return $d;
}

function batch_path(string $rid): string
{
    return batch_dir() . '/batch_' . safe_id($rid) . '.json';
}

function lock_path(string $rid): string
{
    return sys_get_temp_dir() . '/batch_' . safe_id($rid) . '.lock';
}

function mail_lock_path(string $rid): string
{
    return sys_get_temp_dir() . '/qmail_' . safe_id($rid) . '.lock';
}

function mail_sent_marker(string $rid): string
{
    return sys_get_temp_dir() . '/qmail_' . safe_id($rid) . '.sent';
}

function load_batch(string $rid): array
{
    $p = batch_path($rid);
    if (!is_file($p)) return [];
    $raw = @file_get_contents($p);
    $data = $raw ? json_decode($raw, true) : null;
    return is_array($data) ? $data : [];
}

function save_batch(string $rid, array $data): void
{
    @file_put_contents(batch_path($rid), json_encode($data, JSON_UNESCAPED_UNICODE));
}

function normalize_vehicle_token(string $v): ?string
{
    $l = mb_strtolower(trim($v), 'UTF-8');
    $map = [
        'bus' => 'bus',
        'busik' => 'bus',
        'solówka' => 'solo', 'solowka' => 'solo', 'solo' => 'solo',
        'naczepa' => 'naczepa', 'tir' => 'naczepa'
    ];
    return $map[$l] ?? null;
}

function token_to_calc_type(string $t): string
{
    // Dopasuj do tego, co przyjmuje calculateTransportPrice
    return match ($t) {
        'bus' => 'Bus',
        'solo' => 'solo',
        'naczepa' => 'naczepa',
        default => 'naczepa'
    };
}

function pick_cheapest_alt(array $alts): ?array
{
    // $alts: ['bus'=>result, 'solo'=>result, 'naczepa'=>result]
    if (!$alts) return null;
    $bestKey = null;
    $bestVal = null;
    foreach ($alts as $k => $r) {
        if (isset($r['error'])) continue;
        $price = $r['transport_price_with_margin'] ?? $r['transport_price'] ?? null;
        if (!is_numeric($price)) continue;
        if ($bestVal === null || $price < $bestVal) {
            $bestVal = $price;
            $bestKey = $k;
        }
    }
    if ($bestKey === null) return null;
    return ['token' => $bestKey, 'result' => $alts[$bestKey]];
}

// Dedup maila: 1 wysyłka / request_id / 10 minut
function email_dedup_should_send(string $rid, int $ttl = 600): array
{
    $l = mail_lock_path($rid);
    $m = mail_sent_marker($rid);
    $fp = @fopen($l, 'c+');
    if (!$fp) return [true, null, $m];
    if (!@flock($fp, LOCK_EX | LOCK_NB)) return [false, $fp, $m];
    if (is_file($m) && (time() - @filemtime($m) < $ttl)) {
        @flock($fp, LOCK_UN);
        @fclose($fp);
        return [false, null, $m];
    }
    return [true, $fp, $m];
}

function email_dedup_mark_sent($fp, string $marker): void
{
    @file_put_contents($marker, (string)time());
    if (is_resource($fp)) {
        @flock($fp, LOCK_UN);
        @fclose($fp);
    }
}

function build_email_data(array $params, ?array $bestResult, string $vehicleLabel): array {
    $tp    = $bestResult['transport_price'] ?? null;
    $tp_m  = $bestResult['transport_price_with_margin'] ?? null;
    $avg_m = $bestResult['average_price_with_margin'] ?? null;

    return [
        'prompt'        => $params['prompt'] ?? '',
        'distance'      => $params['distance'] ?? 0,
        'ldm'           => $params['ldm'] ?? 0,
        'weight'        => $params['weight'] ?? 0,
        'route_type'    => $params['route_type'] ?? '',
        'vehicle_type'  => $vehicleLabel,

        // pola używane w szablonie maila
        'transport_price_display'     => is_numeric($tp_m) ? number_format($tp_m, 2, ',', ' ') . ' PLN' : '',
        'total_netto'                 => is_numeric($tp_m) ? number_format($tp_m, 2, ',', ' ') . ' PLN' : '',
        'average_price_with_margin'   => is_numeric($avg_m) ? number_format($avg_m, 2, ',', ' ') . ' PLN' : '',
        'transport_price_with_margin' => is_numeric($tp_m) ? number_format($tp_m, 2, ',', ' ') . ' PLN' : '',
    ];
}

// ===== BATCH MODE: 3 GET-y z query (dowolny) =====
if (isset($_GET['batch']) && (int)$_GET['batch'] === 1) {
    $requestId = $_GET['request_id'] ?? '';
    if (!$requestId) {
        $logger->log('BATCH missing request_id');
        http_response_code(400); echo json_encode(['error'=>'missing request_id']); exit;
    }

    $routeType = $_GET['route_type'] ?? null;
    $distance  = isset($_GET['distance']) ? (float)$_GET['distance'] : null;
    $weight    = isset($_GET['weight']) ? (int)$_GET['weight'] : null;
    $ldm       = isset($_GET['ldm']) ? (float)$_GET['ldm'] : null;
    $email     = $_GET['email'] ?? '';
    $vehicleParam = $_GET['vehicle_type'] ?? '';
    $token = normalize_vehicle_token($vehicleParam);

    logj($logger, 'BATCH start', [
        'rid'=>$requestId,'vehicle_param'=>$vehicleParam,'token'=>$token,
        'rt'=>$routeType,'dist'=>$distance,'w'=>$weight,'ldm'=>$ldm,'email'=>$email
    ]);

    if (!$routeType || $distance === null || $weight === null || $ldm === null || !$token) {
        $logger->log('BATCH missing fields or unknown vehicle_type');
        http_response_code(400);
        echo json_encode(['error'=>'missing fields or unknown vehicle_type','vehicle_type'=>$_GET['vehicle_type'] ?? null]);
        exit;
    }

    // lock batch
    $fp = @fopen(lock_path($requestId), 'c+');
    if (!$fp) {
        $logger->log('BATCH lock failed');
        http_response_code(500); echo json_encode(['error'=>'lock failed']); exit;
    }
    @flock($fp, LOCK_EX);

    // załaduj / zainicjuj stan
    $state = load_batch($requestId);
    if (!$state || !isset($state['started_at']) || (time() - ($state['started_at'] ?? 0) > 600)) {
        $state = array_merge([
            'started_at'    => time(),
            'params'        => [
                'route_type' => $_GET['route_type'] ?? '',
                'distance'   => isset($_GET['distance']) ? (float)$_GET['distance'] : null,
                'weight'     => isset($_GET['weight']) ? (int)$_GET['weight'] : null,
                'ldm'        => isset($_GET['ldm']) ? (float)$_GET['ldm'] : null,
                'email'      => $_GET['email'] ?? '',
                'phone'      => $_GET['phone'] ?? '',
                'prompt'     => $_GET['prompt'] ?? '',
                'urgent'     => $_GET['urgent'] ?? '0',
                'stackable'  => $_GET['stackable'] ?? '0',
                'pickup_date' => $_GET['pickup_date'] ?? '',
                'delivery_date' => $_GET['delivery_date'] ?? '',
                'pickup_postal_code' => $_GET['pickup_postal_code'] ?? '',
                'delivery_postal_code' => $_GET['delivery_postal_code'] ?? '',
                'pickup_city' => $_GET['pickup_city'] ?? '',
                'delivery_city' => $_GET['delivery_city'] ?? ''
            ],
            'results'       => [],
            'finalized'     => false,
            'final_vehicle' => null,
            'mail_status'   => null
        ], $state);
        $logger->log("BATCH init state rid=$requestId");
    } else {
        $logger->log("BATCH load state rid=$requestId");
    }

    // policz
    $calcType = token_to_calc_type($token);
    $result = calculateTransportPrice($calcType, $routeType, $distance, $weight, $ldm);
    logj($logger, 'BATCH result', [
        'rid'=>$requestId,'token'=>$token,
        'price'=>$result['transport_price'] ?? null,
        'price_m'=>$result['transport_price_with_margin'] ?? null
    ]);

    $state['results'][$token] = $result;
    save_batch($requestId, $state);

    // komplet?
    $expected = ['bus','solo','naczepa'];
    $have = array_keys($state['results']);
    $missing = array_values(array_diff($expected, $have));
    logj($logger, 'BATCH progress', ['rid'=>$requestId,'have'=>$have,'missing'=>$missing]);

    $finalPayload = null;

    if (!$state['finalized'] && empty($missing)) {
        $best = pick_cheapest_alt($state['results']);
        if ($best) {
            $state['finalized'] = true;
            $state['final_vehicle'] = $best['token'];

            // zbuduj emailData ZANIM wezwiesz sendMail
            $vehicleLabel = token_to_calc_type($best['token']); // "Bus" | "solo" | "naczepa"
            $emailTo      = $state['params']['email'] ?? '';
            $emailData    = build_email_data($state['params'], $best['result'] ?? null, $vehicleLabel);
            $logger->log('MAIL attempt (batch) to=' . $emailTo . ' keys=' . implode(',', array_keys($emailData)));

            // dedup mail
            list($canSend, $lockMail, $sentMarker) = email_dedup_should_send($requestId, 600);
            $logger->log("BATCH finalize rid=$requestId selected={$best['token']} canSend=" . ($canSend?'1':'0'));

            $mailStatus = 'Pominięto wysyłkę (duplikat request_id)';
            if ($canSend && filter_var($emailTo, FILTER_VALIDATE_EMAIL)) {
                $sendOk = sendMail($emailTo, $emailTo, 'Wstępna wycena transportu', $emailData);
                if ($sendOk === true) {
                    $mailStatus = 'Wysłano e-mail';
                    email_dedup_mark_sent($lockMail, $sentMarker);
                } else {
                    if (is_resource($lockMail)) { @flock($lockMail, LOCK_UN); @fclose($lockMail); }
                    $mailStatus = '❌ Błąd wysyłania e-maila: ' . $sendOk;
                }
            } else {
                if (!$canSend) $logger->log("BATCH mail dedup SKIP rid=$requestId");
                if (!filter_var($emailTo, FILTER_VALIDATE_EMAIL)) $logger->log("BATCH invalid email rid=$requestId email=".$emailTo);
            }
            $state['mail_status'] = $mailStatus;

            // Firmao – 1 wpis po finalizacji
            $firmaoResult = createSalesOpportunityInFirmao([
                'custom6'  => $state['params']['email'],
                'custom8'  => $state['params']['pickup_postal_code'],
                'custom10' => $state['params']['delivery_postal_code'],
                'custom5'  => $state['params']['prompt'],
                'custom7'  => $state['params']['phone'],
                'custom18' => $best['result']['transport_price'] ?? '',
                'custom14' => $state['params']['ldm'],
                'custom12' => $state['params']['distance'],
                'custom17' => $best['result']['transport_price_with_margin'] ?? '',
                'custom16' => $vehicleLabel,
                'custom15' => $state['params']['route_type'],
                'custom13' => $state['params']['weight'],
                'custom9'  => $state['params']['pickup_city'],
                'custom11' => $state['params']['delivery_city']
            ]);
            $state['firmao_result'] = $firmaoResult;
            save_batch($requestId, $state);

            logj($logger, 'BATCH finalized', [
                'rid'=>$requestId,'selected'=>$best['token'],'mail_status'=>$mailStatus,
                'firmao_error'=>$firmaoResult['error'] ?? null
            ]);

            // pełna odpowiedź dla frontu
            $finalPayload = [
                'status'                => 'finalized',
                'request_id'            => $requestId,
                'vehicle_type_selected' => $vehicleLabel,
                'transport_calculation' => $best['result'],
                'alternatives'          => [
                    ['vehicle'=>'Bus',     'result'=>$state['results']['bus'] ?? []],
                    ['vehicle'=>'Solo',    'result'=>$state['results']['solo'] ?? []],
                    ['vehicle'=>'Naczepa', 'result'=>$state['results']['naczepa'] ?? []],
                ],
                'mail_status'           => $state['mail_status'],
                'firmao_creation'       => $firmaoResult,
                'sales_opportunity_id'  => $firmaoResult['salesOpportunityId'] ?? null
            ];
        } else {
            $logger->log("BATCH finalize NO_PRICE rid=$requestId");
            // (opcjonalnie: fallback no_price tutaj)
        }
    }


    @flock($fp, LOCK_UN); @fclose($fp);

    if ($finalPayload) { echo json_encode($finalPayload, JSON_UNESCAPED_UNICODE); exit; }

    echo json_encode([
        'status'     => 'pending',
        'request_id' => $requestId,
        'have'       => $have,
        'missing'    => $missing,
        'received'   => $token,
        'partial'    => $state['results'][$token] ?? []
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Sprawdzenie, czy wszystkie wymagane parametry są obecne
if (isset($_GET['vehicle_type'], $_GET['route_type'], $_GET['distance'], $_GET['weight'], $_GET['ldm'])) {
    
    $vehicleType = $_GET['vehicle_type'];
    $routeType = $_GET['route_type'];
    $distance = (float)$_GET['distance'];
    $weight = (int)$_GET['weight'];
    $ldm = (float)$_GET['ldm'];


    logj($logger, 'SINGLE start', [
        'vt'=>$vehicleType,'rt'=>$routeType,'dist'=>$distance,'w'=>$weight,'ldm'=>$ldm,
        'email'=>$_GET['email'] ?? ''
    ]);

    $result = calculateTransportPrice($vehicleType, $routeType, $distance, $weight, $ldm);

    logj($logger, 'SINGLE result', [
        'price'=>$result['transport_price'] ?? null,
        'price_m'=>$result['transport_price_with_margin'] ?? null,
        'error'=>$result['error'] ?? null
    ]);

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
    $logger->log('SINGLE mail_status=' . (is_bool($mailStatus) ? ($mailStatus ? 'OK' : 'FAIL') : $mailStatus));

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

        $logger->log('SINGLE firmao_status=' . (isset($firmaoResult['error']) ? ('ERR: '.$firmaoResult['error']) : 'OK'));

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
