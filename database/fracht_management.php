<?php

require_once 'db.php';

// Sprawdzenie, czy żądanie jest metodą POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Funkcja do dodawania wiersza do tabeli
    function addRow($table, $maxWeight, $minLdm, $maxLdm, $fracht) {
        global $pdo;
        try {
            $stmt = $pdo->prepare("INSERT INTO $table (max_weight, min_ldm, max_ldm, fracht) VALUES (:max_weight, :min_ldm, :max_ldm, :fracht)");
            $stmt->execute([
                'max_weight' => $maxWeight,
                'min_ldm' => $minLdm,
                'max_ldm' => $maxLdm,
                'fracht' => $fracht
            ]);
            echo "Dodano nowy wiersz do tabeli $table.";
        } catch (PDOException $e) {
            echo "Błąd podczas dodawania wiersza do tabeli $table: " . $e->getMessage();
        }
    }

    // Funkcja do usuwania wiersza z tabeli
    function deleteRow($table, $id) {
        global $pdo;
        try {
            $stmt = $pdo->prepare("DELETE FROM $table WHERE id = :id");
            $stmt->execute(['id' => $id]);
            echo "Usunięto wiersz z tabeli $table.";
        } catch (PDOException $e) {
            echo "Błąd podczas usuwania wiersza z tabeli $table: " . $e->getMessage();
        }
    }

    // Funkcja do wyświetlania danych z tabeli
    function displayTable($table) {
        global $pdo;
        try {
            $stmt = $pdo->query("SELECT * FROM $table");
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo "<h2>Dane z tabeli $table</h2>";
            echo '<table border="1" cellpadding="5" cellspacing="0">';
            echo '<tr><th>ID</th><th>Max Weight</th><th>Min LDM</th><th>Max LDM</th><th>Fracht</th></tr>';
            foreach ($rows as $row) {
                echo '<tr>';
                echo '<td>' . htmlspecialchars($row['id']) . '</td>';
                echo '<td>' . htmlspecialchars($row['max_weight']) . '</td>';
                echo '<td>' . htmlspecialchars($row['min_ldm']) . '</td>';
                echo '<td>' . htmlspecialchars($row['max_ldm']) . '</td>';
                echo '<td>' . htmlspecialchars($row['fracht']) . '</td>';
                echo '</tr>';
            }
            echo '</table>';
        } catch (PDOException $e) {
            echo "Błąd podczas pobierania danych z tabeli $table: " . $e->getMessage();
        }
    }

    // Obsługa formularzy
    if (isset($_POST['add_bus'])) {
        addRow('bus', $_POST['max_weight'], $_POST['min_ldm'], $_POST['max_ldm'], $_POST['fracht']);
    } elseif (isset($_POST['delete_bus'])) {
        deleteRow('bus', $_POST['id']);
    }

    if (isset($_POST['add_solo'])) {
        addRow('solo', $_POST['max_weight'], $_POST['min_ldm'], $_POST['max_ldm'], $_POST['fracht']);
    } elseif (isset($_POST['delete_solo'])) {
        deleteRow('solo', $_POST['id']);
    }

    if (isset($_POST['add_naczepa'])) {
        addRow('naczepa', $_POST['max_weight'], $_POST['min_ldm'], $_POST['max_ldm'], $_POST['fracht']);
    } elseif (isset($_POST['delete_naczepa'])) {
        deleteRow('naczepa', $_POST['id']);
    }

    // Formularze do dodawania i usuwania wierszy
    echo '<h2>Dodaj/Usuń wiersze w tabeli Bus</h2>';
    echo '<form method="POST">';
    echo '<input type="number" name="max_weight" placeholder="Max Weight" required>';
    echo '<input type="text" name="min_ldm" placeholder="Min LDM" required>';
    echo '<input type="text" name="max_ldm" placeholder="Max LDM" required>';
    echo '<input type="text" name="fracht" placeholder="Fracht" required>';
    echo '<button type="submit" name="add_bus">Dodaj</button>';
    echo '</form>';
    echo '<form method="POST">';
    echo '<input type="number" name="id" placeholder="ID do usunięcia" required>';
    echo '<button type="submit" name="delete_bus">Usuń</button>';
    echo '</form>';

    echo '<h2>Dodaj/Usuń wiersze w tabeli Solo</h2>';
    echo '<form method="POST">';
    echo '<input type="number" name="max_weight" placeholder="Max Weight" required>';
    echo '<input type="text" name="min_ldm" placeholder="Min LDM" required>';
    echo '<input type="text" name="max_ldm" placeholder="Max LDM" required>';
    echo '<input type="text" name="fracht" placeholder="Fracht" required>';
    echo '<button type="submit" name="add_solo">Dodaj</button>';
    echo '</form>';
    echo '<form method="POST">';
    echo '<input type="number" name="id" placeholder="ID do usunięcia" required>';
    echo '<button type="submit" name="delete_solo">Usuń</button>';
    echo '</form>';

    echo '<h2>Dodaj/Usuń wiersze w tabeli Naczepa</h2>';
    echo '<form method="POST">';
    echo '<input type="number" name="max_weight" placeholder="Max Weight" required>';
    echo '<input type="text" name="min_ldm" placeholder="Min LDM" required>';
    echo '<input type="text" name="max_ldm" placeholder="Max LDM" required>';
    echo '<input type="text" name="fracht" placeholder="Fracht" required>';
    echo '<button type="submit" name="add_naczepa">Dodaj</button>';
    echo '</form>';
    echo '<form method="POST">';
    echo '<input type="number" name="id" placeholder="ID do usunięcia" required>';
    echo '<button type="submit" name="delete_naczepa">Usuń</button>';
    echo '</form>';

    // Wyświetlanie danych z tabel
    displayTable('bus');
    displayTable('solo');
    displayTable('naczepa');

} else {
    echo "Dostęp do tej strony jest możliwy tylko przez POST.";
}

?>
