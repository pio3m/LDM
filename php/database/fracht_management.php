<?php

require_once 'db.php';

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

    // Funkcja do dodawania wiersza do tabeli ryczalt
    function addRyczaltRow($vehicleType, $maxWeight, $minLdm, $maxLdm, $fracht, $price) {
        global $pdo;
        try {
            $stmt = $pdo->prepare("INSERT INTO ryczalt (vehicle_type, max_weight, min_ldm, max_ldm, fracht, price) 
                                 VALUES (:vehicle_type, :max_weight, :min_ldm, :max_ldm, :fracht, :price)");
            $stmt->execute([
                'vehicle_type' => $vehicleType,
                'max_weight' => $maxWeight,
                'min_ldm' => $minLdm,
                'max_ldm' => $maxLdm,
                'fracht' => $fracht,
                'price' => $price
            ]);
            echo "Dodano nowy wiersz do tabeli ryczalt.";
        } catch (PDOException $e) {
            echo "Błąd podczas dodawania wiersza do tabeli ryczalt: " . $e->getMessage();
        }
    }

    // Funkcja do wyświetlania danych z tabeli ryczalt
    function displayRyczaltTable() {
        global $pdo;
        try {
            $stmt = $pdo->query("SELECT * FROM ryczalt");
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo "<h2>Dane z tabeli ryczalt</h2>";
            echo '<table border="1" cellpadding="5" cellspacing="0">';
            echo '<tr><th>ID</th><th>Typ pojazdu</th><th>Max Weight</th><th>Min LDM</th><th>Max LDM</th><th>Fracht</th><th>Cena</th></tr>';
            foreach ($rows as $row) {
                echo '<tr>';
                echo '<td>' . htmlspecialchars($row['id']) . '</td>';
                echo '<td>' . htmlspecialchars($row['vehicle_type']) . '</td>';
                echo '<td>' . htmlspecialchars($row['max_weight']) . '</td>';
                echo '<td>' . htmlspecialchars($row['min_ldm']) . '</td>';
                echo '<td>' . htmlspecialchars($row['max_ldm']) . '</td>';
                echo '<td>' . htmlspecialchars($row['fracht']) . '</td>';
                echo '<td>' . htmlspecialchars($row['price']) . '</td>';
                echo '</tr>';
            }
            echo '</table>';
        } catch (PDOException $e) {
            echo "Błąd podczas pobierania danych z tabeli ryczalt: " . $e->getMessage();
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

    // Obsługa formularzy dla tabeli ryczalt
    if (isset($_POST['add_ryczalt'])) {
        addRyczaltRow(
            $_POST['vehicle_type'],
            $_POST['max_weight'],
            $_POST['min_ldm'],
            $_POST['max_ldm'],
            $_POST['fracht'],
            $_POST['price']
        );
    } elseif (isset($_POST['delete_ryczalt'])) {
        deleteRow('ryczalt', $_POST['id']);
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

    // Dodaj formularz dla tabeli ryczalt (przed wyświetlaniem tabel)
    echo '<h2>Dodaj/Usuń wiersze w tabeli Ryczałt</h2>';
    echo '<form method="POST">';
    echo '<input type="text" name="vehicle_type" placeholder="Typ pojazdu" required>';
    echo '<input type="number" name="max_weight" placeholder="Max Weight" required>';
    echo '<input type="text" name="min_ldm" placeholder="Min LDM" required>';
    echo '<input type="text" name="max_ldm" placeholder="Max LDM" required>';
    echo '<input type="text" name="fracht" placeholder="Fracht" required>';
    echo '<input type="number" name="price" placeholder="Cena" required>';
    echo '<button type="submit" name="add_ryczalt">Dodaj</button>';
    echo '</form>';
    echo '<form method="POST">';
    echo '<input type="number" name="id" placeholder="ID do usunięcia" required>';
    echo '<button type="submit" name="delete_ryczalt">Usuń</button>';
    echo '</form>';

    // Wyświetlanie danych z tabel
    displayTable('bus');
    displayTable('solo');
    displayTable('naczepa');
    displayRyczaltTable();

?>
