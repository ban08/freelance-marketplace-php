<?php
require_once __DIR__ . '/templates/bootstrap.php';

echo "<h1>Database Schema Debug</h1>";

try {
    // Check users table structure
    echo "<h2>Users Table Structure:</h2>";
    $usersCols = $pdo->query("PRAGMA table_info(users)")->fetchAll();
    echo "<pre>";
    foreach ($usersCols as $col) {
        echo "Column: {$col['name']} | Type: {$col['type']} | Null: " . ($col['notnull'] ? 'NO' : 'YES') . " | Default: {$col['dflt_value']}\n";
    }
    echo "</pre>";

    // Check if tipo column exists
    $tipoExists = false;
    foreach ($usersCols as $col) {
        if ($col['name'] === 'tipo') {
            $tipoExists = true;
            break;
        }
    }
    
    echo "<p><strong>Tipo column exists: " . ($tipoExists ? 'YES' : 'NO') . "</strong></p>";
    
    if (!$tipoExists) {
        echo "<p style='color: red;'>ERROR: The 'tipo' column is missing from the users table!</p>";
        echo "<p>This column is required for user type classification (freelancer, cliente, admin).</p>";
    }

    // Check categories table
    echo "<h2>Categories Table Structure:</h2>";
    $catsCols = $pdo->query("PRAGMA table_info(categories)")->fetchAll();
    echo "<pre>";
    foreach ($catsCols as $col) {
        echo "Column: {$col['name']} | Type: {$col['type']} | Null: " . ($col['notnull'] ? 'NO' : 'YES') . " | Default: {$col['dflt_value']}\n";
    }
    echo "</pre>";

    // Check services table
    echo "<h2>Services Table Structure:</h2>";
    $servicesCols = $pdo->query("PRAGMA table_info(services)")->fetchAll();
    echo "<pre>";
    foreach ($servicesCols as $col) {
        echo "Column: {$col['name']} | Type: {$col['type']} | Null: " . ($col['notnull'] ? 'NO' : 'YES') . " | Default: {$col['dflt_value']}\n";
    }
    echo "</pre>";

    // Check if services has price column vs base_price
    $priceCol = null;
    foreach ($servicesCols as $col) {
        if (in_array($col['name'], ['price', 'base_price'])) {
            $priceCol = $col['name'];
            break;
        }
    }
    echo "<p><strong>Price column name: " . ($priceCol ?: 'MISSING') . "</strong></p>";

} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>
