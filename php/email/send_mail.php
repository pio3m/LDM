<?php

// Pokazuj błędy (tylko w fazie testów – usuń lub schowaj na produkcji)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Wczytaj dane z .env
require_once __DIR__ . '/config.php';
load_env();

// Dołącz PHPMailera
require_once __DIR__ . '/PHPMailer/PHPMailer.php';
require_once __DIR__ . '/PHPMailer/SMTP.php';
require_once __DIR__ . '/PHPMailer/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * Wysyła e-mail przez SMTP z szablonem HTML
 *
 * @param string $toEmail    Adres odbiorcy
 * @param string $toName     Nazwa odbiorcy (opcjonalnie)
 * @param string $subject    Temat wiadomości
 * @param array  $data       Dane do wstawienia w szablon (opcjonalnie)
 * @return bool|string       true jeśli wysłano, w przeciwnym razie komunikat błędu
 */
function sendMail(string $toEmail, string $toName, string $subject, array $data = []) {
    
    $mail = new PHPMailer(true);
    $mail->CharSet = 'UTF-8';
    $mail->Encoding = 'base64';
    
    try {
        // Konfiguracja SMTP
        $mail->isSMTP();
        $mail->Host       = $_ENV['SMTP_HOST'];
        $mail->SMTPAuth   = true;
        $mail->Username   = $_ENV['SMTP_USER'];
        $mail->Password   = $_ENV['SMTP_PASS'];
        $mail->SMTPSecure = $_ENV['SMTP_SECURE'];
        $mail->Port       = $_ENV['SMTP_PORT'];

        // Dane nadawcy i odbiorcy
        $mail->setFrom($_ENV['SMTP_USER'], 'Sevium Logistics');
        $mail->addAddress($toEmail, $toName ?: $toEmail);

        // Temat
        $mail->Subject = $subject;
        $mail->isHTML(true);

        // Wczytaj szablon HTML
        $templatePath = __DIR__ . '/template.html';
        if (!file_exists($templatePath)) {
            throw new Exception('Szablon HTML nie został znaleziony: ' . $templatePath);
        }

        $body = file_get_contents($templatePath);
        
        // Zastąp dane w szablonie (opcjonalnie)
        if (!empty($data)) {
            foreach ($data as $key => $value) {
                $body = str_replace('{{' . $key . '}}', $value, $body);
            }
        }

        $mail->Body = $body;
        $mail->AltBody = strip_tags($body);

        $mail->send();
        return true;

    } catch (Exception $e) {
        return 'Błąd wysyłki: ' . $mail->ErrorInfo;
    }
}



?>
