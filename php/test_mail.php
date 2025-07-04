<?php
require_once __DIR__ . '/email/send_mail.php';

// Przykładowe dane do wstawienia w szablon
$data = [
    'quote_number' => 'TRP-2024-0892',
    'cargo_description' => 'Palety z towarem',
    'cargo_weight' => '1.5 tony',
    'vehicle_type' => 'Ciężarówka do 3.5t z windą załadunkową',
    'pickup_company' => 'Firma XYZ',
    'pickup_address' => 'ul. Przykładowa 12, 00-123 Warszawa',
    'pickup_date' => '20 września 2024',
    'pickup_time' => '09:00',
    'delivery_company' => 'Firma ABC',
    'delivery_address' => 'ul. Testowa 34, 12-345 Kraków',
    'delivery_date' => '21 września 2024',
    'delivery_time' => '14:00',
    'distance' => '290 km',
    'transport_price' => '1,450.00 PLN',
    'insurance_price' => '0.00 PLN',
    'total_netto' => '1,450.00 PLN',
    'vat_amount' => '333.50 PLN',
    'total_brutto' => '1,783.50 PLN',
    'valid_until' => '27 września 2024'
];

$result = sendMail(
    'mwlojewski@gmail.com',        // <- Wpisz swój prawdziwy adres e-mail!
    'Tester',
    'Wycena transportu #TRP-2024-0892',
    $data
);

if ($result === true) {
    echo '✅ Testowy e-mail został wysłany!';
} else {
    echo '❌ Coś poszło nie tak: ' . $result;
}