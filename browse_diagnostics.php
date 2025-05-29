<?php
require_once __DIR__ . '/templates/bootstrap.php';

echo "<h1>Browse Page Diagnostics</h1>";

// Test database structure
echo "<h2>1. Database Structure Check</h2>";
try {
    $tables = $pdo->query("SELECT name FROM sqlite_master WHERE type='table'")->fetchAll();
    echo "<p><strong>Tables:</strong> " . implode(', ', array_column($tables, 'name')) . "</p>";
    
    // Check users table structure
    $userColumns = $pdo->query("PRAGMA table_info(users)")->fetchAll();
    echo "<h3>Users table columns:</h3><ul>";
    foreach ($userColumns as $col) {
        echo "<li>{$col['name']} ({$col['type']})</li>";
    }
    echo "</ul>";
    
    // Check services table structure
    $serviceColumns = $pdo->query("PRAGMA table_info(services)")->fetchAll();
    echo "<h3>Services table columns:</h3><ul>";
    foreach ($serviceColumns as $col) {
        echo "<li>{$col['name']} ({$col['type']})</li>";
    }
    echo "</ul>";
    
    // Check categories table structure
    $catColumns = $pdo->query("PRAGMA table_info(categories)")->fetchAll();
    echo "<h3>Categories table columns:</h3><ul>";
    foreach ($catColumns as $col) {
        echo "<li>{$col['name']} ({$col['type']})</li>";
    }
    echo "</ul>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error checking structure: " . $e->getMessage() . "</p>";
}

// Test data counts
echo "<h2>2. Data Counts</h2>";
try {
    $counts = [];
    $counts['users'] = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $counts['categories'] = $pdo->query("SELECT COUNT(*) FROM categories")->fetchColumn();
    $counts['services'] = $pdo->query("SELECT COUNT(*) FROM services")->fetchColumn();
    $counts['active_services'] = $pdo->query("SELECT COUNT(*) FROM services WHERE status = 'active'")->fetchColumn();
    
    foreach ($counts as $table => $count) {
        echo "<p><strong>{$table}:</strong> {$count}</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>Error counting data: " . $e->getMessage() . "</p>";
}

// Test the exact browse query step by step
echo "<h2>3. Testing Browse Query Components</h2>";

try {
    // Test basic services query
    echo "<h3>Basic services query:</h3>";
    $basicQuery = "SELECT id, title, status FROM services";
    $basicResults = $pdo->query($basicQuery)->fetchAll();
    echo "<p>Found " . count($basicResults) . " services</p>";
    if (count($basicResults) > 0) {
        echo "<ul>";
        foreach ($basicResults as $service) {
            echo "<li>ID: {$service['id']}, Title: {$service['title']}, Status: {$service['status']}</li>";
        }
        echo "</ul>";
    }
    
    // Test with category join
    echo "<h3>Services with category join:</h3>";
    $catJoinQuery = "SELECT s.id, s.title, s.status, c.name as category 
                     FROM services s 
                     INNER JOIN categories c ON s.category_id = c.id";
    $catJoinResults = $pdo->query($catJoinQuery)->fetchAll();
    echo "<p>Found " . count($catJoinResults) . " services with categories</p>";
    
    // Test with users join
    echo "<h3>Services with users join:</h3>";
    $userJoinQuery = "SELECT s.id, s.title, s.status, u.name as freelancer 
                      FROM services s 
                      INNER JOIN users u ON s.freelancer_id = u.id";
    $userJoinResults = $pdo->query($userJoinQuery)->fetchAll();
    echo "<p>Found " . count($userJoinResults) . " services with users</p>";
    
    // Test full query
    echo "<h3>Full browse query:</h3>";
    $fullQuery = "
      SELECT
        s.id,
        s.title,
        s.base_price,
        s.delivery_time_days,
        s.description,
        c.name AS category,
        u.name AS freelancer_name,
        (SELECT COALESCE(AVG(CAST(rating AS REAL)), 0) FROM reviews WHERE service_id = s.id) AS avg_rating,
        (SELECT COUNT(*) FROM reviews WHERE service_id = s.id) AS reviews_count
      FROM services s
      INNER JOIN categories c ON s.category_id = c.id
      INNER JOIN users u ON s.freelancer_id = u.id
      WHERE s.status = 'active'
      ORDER BY s.created_at DESC, s.base_price ASC
    ";
    
    $fullResults = $pdo->query($fullQuery)->fetchAll();
    echo "<p>Found " . count($fullResults) . " active services with full query</p>";
    
    if (count($fullResults) > 0) {
        echo "<ul>";
        foreach ($fullResults as $service) {
            echo "<li><strong>{$service['title']}</strong> by {$service['freelancer_name']} ({$service['category']}) - €{$service['base_price']}</li>";
        }
        echo "</ul>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error testing queries: " . $e->getMessage() . "</p>";
    echo "<p>SQL Error Details: " . print_r($pdo->errorInfo(), true) . "</p>";
}

echo "<hr>";
echo "<p><a href='pages/browse.php'>→ Go to Browse Page</a></p>";
echo "<p><a href='test_data.php'>→ Setup Test Data</a></p>";
?>
