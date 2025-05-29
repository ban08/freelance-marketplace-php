<?php
require_once __DIR__ . '/templates/bootstrap.php';

echo "<h1>Database Schema Fix</h1>";

try {
    // Check if tipo column exists
    $usersCols = $pdo->query("PRAGMA table_info(users)")->fetchAll();
    $tipoExists = false;
    
    foreach ($usersCols as $col) {
        if ($col['name'] === 'tipo') {
            $tipoExists = true;
            break;
        }
    }
    
    if (!$tipoExists) {
        echo "<p style='color: orange;'>Adding missing 'tipo' column to users table...</p>";
        
        // Add the tipo column
        $pdo->exec("ALTER TABLE users ADD COLUMN tipo TEXT DEFAULT 'cliente'");
        
        echo "<p style='color: green;'>✓ Added 'tipo' column successfully!</p>";
        
        // Set default values for existing users
        $pdo->exec("UPDATE users SET tipo = 'cliente' WHERE tipo IS NULL");
        
        echo "<p style='color: green;'>✓ Set default tipo values for existing users!</p>";
        
    } else {
        echo "<p style='color: green;'>✓ 'tipo' column already exists!</p>";
    }
    
    // Check if services table uses 'price' or 'base_price'
    $servicesCols = $pdo->query("PRAGMA table_info(services)")->fetchAll();
    $hasPriceCol = false;
    $hasBasePriceCol = false;
    
    foreach ($servicesCols as $col) {
        if ($col['name'] === 'price') $hasPriceCol = true;
        if ($col['name'] === 'base_price') $hasBasePriceCol = true;
    }
    
    echo "<h2>Services Table Price Column Check:</h2>";
    echo "<p>Has 'price' column: " . ($hasPriceCol ? 'YES' : 'NO') . "</p>";
    echo "<p>Has 'base_price' column: " . ($hasBasePriceCol ? 'YES' : 'NO') . "</p>";
    
    if (!$hasPriceCol && $hasBasePriceCol) {
        echo "<p style='color: blue;'>Note: Services table uses 'base_price' column name.</p>";
    }
    
    // Test admin panel query
    echo "<h2>Testing Admin Panel Query:</h2>";
    $testQuery = $pdo->query("
        SELECT id, username, name, email, tipo, is_admin, joined_date
        FROM users
        ORDER BY is_admin DESC, name
        LIMIT 5
    ");
    
    $users = $testQuery->fetchAll();
    echo "<p style='color: green;'>✓ Admin panel query works! Found " . count($users) . " users.</p>";
    
    if (count($users) > 0) {
        echo "<h3>Sample Users:</h3>";
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr><th>ID</th><th>Username</th><th>Name</th><th>Email</th><th>Tipo</th><th>Admin</th></tr>";
        foreach ($users as $user) {
            echo "<tr>";
            echo "<td>{$user['id']}</td>";
            echo "<td>{$user['username']}</td>";
            echo "<td>{$user['name']}</td>";
            echo "<td>{$user['email']}</td>";
            echo "<td>{$user['tipo']}</td>";
            echo "<td>" . ($user['is_admin'] ? 'YES' : 'NO') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // Test if any admin users exist
    $adminCount = $pdo->query("SELECT COUNT(*) as count FROM users WHERE is_admin = 1")->fetch();
    echo "<p><strong>Admin users count: {$adminCount['count']}</strong></p>";
    
    if ($adminCount['count'] == 0) {
        echo "<p style='color: orange;'>No admin users found. You may need to create one manually.</p>";
        echo "<p>To create an admin user, you can update an existing user:</p>";
        echo "<code>UPDATE users SET is_admin = 1 WHERE email = 'your-email@example.com';</code>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
    echo "<p>Stack trace:</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

echo "<hr>";
echo "<p><a href='pages/admin_panel.php'>→ Test Admin Panel</a></p>";
echo "<p><a href='pages/login.php'>→ Login Page</a></p>";
?>
