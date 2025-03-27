<?php

$host = 'localhost';
$dbname = 'transport';
$user = 'root';
$password = '123';

try {
    // Tworzenie połączenia za pomocą PDO
    $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8";
    $pdo = new PDO($dsn, $user, $password);

    // Ustawienie trybu błędów PDO na wyjątki
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // echo "Połączenie z bazą danych zostało pomyślnie nawiązane."; // Skomentowane
} catch (PDOException $e) {
    echo "Połączenie nieudane: " . $e->getMessage();
}

// Funkcja do pobierania wszystkich rekordów z tabeli transports
function getAllTransports() {
    global $pdo;

    try {
        $stmt = $pdo->query("SELECT * FROM transports");
        $transports = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $transports;
    } catch (PDOException $e) {
        echo "Błąd podczas pobierania danych: " . $e->getMessage();
        return [];
    }
}

?>
