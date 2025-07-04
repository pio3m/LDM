<?php

// Dołącz plik z funkcjami
require_once 'postToFirmao.php';

// Ustawienie nagłówków HTML
header('Content-Type: text/html; charset=utf-8');

// Mapa opisów customFields
$customFieldsDescriptions = [
    'custom5' => 'Prompt',
    'custom6' => 'Email',
    'custom7' => 'Telefon',
    'custom8' => 'Kod pocztowy załadunku',
    'custom9' => 'Miejscowość załadunku',
    'custom10' => 'Kod pocztowy rozładunku',
    'custom11' => 'Miejscowość rozładunku',
    'custom12' => 'Dystans km',
    'custom13' => 'Waga ładunku',
    'custom14' => 'LDM',
    'custom15' => 'Typ transportu',
    'custom16' => 'Typ pojazdu',
    'custom17' => 'Wycena z marżą',
    'custom18' => 'Wycena bez marży',
];

?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test API Firmao - Szanse Sprzedaży</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 20px;
            background-color: #f5f5f5;
            color: #333;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #2c3e50;
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 3px solid #3498db;
            padding-bottom: 10px;
        }
        .status {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            font-weight: bold;
        }
        .success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .info {
            background-color: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }
        .opportunity-card {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            margin-bottom: 20px;
            overflow: hidden;
        }
        .opportunity-header {
            background: #3498db;
            color: white;
            padding: 15px;
            font-weight: bold;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .opportunity-id {
            font-size: 1.2em;
        }
        .opportunity-label {
            font-size: 1.1em;
        }
        .opportunity-content {
            padding: 20px;
        }
        .field-group {
            margin-bottom: 15px;
        }
        .field-label {
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 5px;
            display: block;
        }
        .field-value {
            background: white;
            padding: 8px 12px;
            border-radius: 4px;
            border: 1px solid #ddd;
            min-height: 20px;
        }
        .field-value.null {
            color: #999;
            font-style: italic;
        }
        .field-value.array {
            background: #e8f4fd;
            border-color: #3498db;
        }
        .field-value.object {
            background: #f0f8ff;
            border-color: #5bc0de;
        }
        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 15px;
        }
        .raw-data {
            background: #2c3e50;
            color: #ecf0f1;
            padding: 15px;
            border-radius: 5px;
            margin-top: 20px;
            overflow-x: auto;
        }
        .raw-data pre {
            margin: 0;
            white-space: pre-wrap;
            word-wrap: break-word;
        }
        .toggle-btn {
            background: #6c757d;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 4px;
            cursor: pointer;
            margin-top: 10px;
        }
        .toggle-btn:hover {
            background: #5a6268;
        }
        .hidden {
            display: none;
        }
        .summary {
            background: #e9ecef;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            text-align: center;
            font-size: 1.1em;
        }
        .custom-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .custom-table th, .custom-table td { border: 1px solid #ccc; padding: 6px 10px; text-align: left; }
        .custom-table th { background: #3498db; color: white; }
        .custom-table tr:nth-child(even) { background: #f2f2f2; }
        .section-title { background: #34495e; color: white; padding: 10px; margin: 20px 0 10px 0; border-radius: 5px; font-weight: bold; }
        .boolean-true { background: #d4edda; color: #155724; }
        .boolean-false { background: #f8d7da; color: #721c24; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Test API Firmao - Szanse Sprzedaży</h1>
        
        <?php
        try {
            echo '<div class="status success">✅ Pobieranie danych z API Firmao...</div>';
            
            // Wywołanie funkcji do pobrania danych
            $salesOpportunities = extractSalesOpportunitiesData();
            
            if (empty($salesOpportunities)) {
                echo '<div class="status info">ℹ️ Brak danych do wyświetlenia.</div>';
            } else {
                echo '<div class="summary">📊 Znaleziono <strong>' . count($salesOpportunities) . '</strong> szans sprzedaży</div>';
                
                // Wyświetlenie każdej szansy sprzedaży
                foreach ($salesOpportunities as $index => $opportunity) {
                    
                    echo '<div class="opportunity-card">';
                    echo '<div class="opportunity-header">';
                    echo '<span class="opportunity-id">#' . ($index + 1) . ' (ID: ' . htmlspecialchars($opportunity['id']) . ')</span>';
                    echo '<span class="opportunity-label">' . htmlspecialchars($opportunity['label'] ?? 'Brak nazwy') . '</span>';
                    echo '</div>';
                    
                    echo '<div class="opportunity-content">';
                    
                    // Helper do wyświetlania wartości
                    $displayValue = function($value, $fieldName = '') {
                        if (is_null($value)) {
                            return '<span class="field-value null">Brak danych</span>';
                        } elseif (is_bool($value)) {
                            $class = $value ? 'boolean-true' : 'boolean-false';
                            return '<span class="field-value ' . $class . '">' . ($value ? 'Tak' : 'Nie') . '</span>';
                        } elseif (is_array($value)) {
                            if (isset($value['label'])) {
                                return '<span class="field-value">' . htmlspecialchars($value['label']) . '</span>';
                            } elseif (isset($value['id'])) {
                                return '<span class="field-value">ID: ' . htmlspecialchars($value['id']) . '</span>';
                            } else {
                                $display = '[' . implode(', ', array_map(function($item) {
                                    return is_array($item) ? json_encode($item) : $item;
                                }, $value)) . ']';
                                return '<span class="field-value array">' . htmlspecialchars($display) . '</span>';
                            }
                        } elseif (is_numeric($value) && strpos($fieldName, 'Value') !== false) {
                            return '<span class="field-value">' . number_format($value, 2, ',', ' ') . ' PLN</span>';
                        } else {
                            return '<span class="field-value">' . htmlspecialchars($value) . '</span>';
                        }
                    };
                    
                    // Helper do wyświetlania pola
                    $displayField = function($label, $value, $fieldName = '') use ($displayValue) {
                        echo '<div class="field-group">';
                        echo '<span class="field-label">' . htmlspecialchars($label) . '</span>';
                        echo $displayValue($value, $fieldName);
                        echo '</div>';
                    };
                    
                    // Podstawowe informacje
                    echo '<div class="section-title">📋 Podstawowe informacje</div>';
                    echo '<div class="grid">';
                    $displayField('ID', $opportunity['id'] ?? null);
                    $displayField('Nazwa szansy', $opportunity['label'] ?? null);
                    $displayField('Metoda pozyskania', $opportunity['acquisitionMethod'] ?? null);
                    $displayField('Waluta', $opportunity['currency'] ?? null);
                    $displayField('Data sprzedaży', $opportunity['salesDate'] ?? null);
                    $displayField('Wartość szansy', $opportunity['salesOpportunityValue'] ?? null, 'salesOpportunityValue');
                    $displayField('Wartość netto szansy', $opportunity['salesOpportunityNetValue'] ?? null, 'salesOpportunityNetValue');
                    echo '</div>';
                    
                    // Custom fields
                    if (isset($opportunity['customFields']) && is_array($opportunity['customFields'])) {
                        echo '<div class="section-title">🔧 Custom Fields</div>';
                        echo '<table class="custom-table">';
                        echo '<tr><th>Pole</th><th>Opis</th><th>Wartość</th></tr>';
                        foreach ($opportunity['customFields'] as $key => $val) {
                            $desc = $customFieldsDescriptions[$key] ?? '';
                            echo '<tr><td>' . htmlspecialchars($key) . '</td><td>' . htmlspecialchars($desc) . '</td><td>' . htmlspecialchars($val) . '</td></tr>';
                        }
                        echo '</table>';
                    }
                    
                    echo '</div>'; // opportunity-content
                    echo '</div>'; // opportunity-card
                }
                
                // Przycisk do pokazania/ukrycia surowych danych
                echo '<button class="toggle-btn" onclick="toggleRawData()">📋 Pokaż/Ukryj surowe dane JSON</button>';
                echo '<div id="rawData" class="raw-data hidden">';
                echo '<pre>' . htmlspecialchars(json_encode($salesOpportunities, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) . '</pre>';
                echo '</div>';
            }
            
        } catch (Exception $e) {
            echo '<div class="status error">❌ Błąd podczas pobierania danych: ' . htmlspecialchars($e->getMessage()) . '</div>';
        }
        ?>
    </div>
    
    <script>
        function toggleRawData() {
            const rawData = document.getElementById('rawData');
            rawData.classList.toggle('hidden');
        }
    </script>
</body>
</html>
