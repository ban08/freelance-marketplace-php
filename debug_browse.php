<?php
require_once __DIR__ . '/templates/bootstrap.php';

echo "<h2>Database Debug for Browse Page</h2>";

try {
    // Check categories
    $cats = $pdo->query("SELECT COUNT(*) as count FROM categories")->fetch();
    echo "<p><strong>Categories:</strong> " . $cats['count'] . "</p>";
    
    if ($cats['count'] > 0) {
        $catList = $pdo->query("SELECT id, name FROM categories")->fetchAll();
        echo "<ul>";
        foreach ($catList as $cat) {
            echo "<li>ID: {$cat['id']}, Name: {$cat['name']}</li>";
        }
        echo "</ul>";
    }
    
    // Check users
    $users = $pdo->query("SELECT COUNT(*) as count FROM users")->fetch();
    echo "<p><strong>Total Users:</strong> " . $users['count'] . "</p>";
    
    $freelancers = $pdo->query("SELECT COUNT(*) as count FROM users WHERE tipo = 'freelancer'")->fetch();
    echo "<p><strong>Freelancers:</strong> " . $freelancers['count'] . "</p>";
    
    // Check services
    $services = $pdo->query("SELECT COUNT(*) as count FROM services")->fetch();
    echo "<p><strong>Total Services:</strong> " . $services['count'] . "</p>";
    
    $activeServices = $pdo->query("SELECT COUNT(*) as count FROM services WHERE status = 'active'")->fetch();
    echo "<p><strong>Active Services:</strong> " . $activeServices['count'] . "</p>";
    
    if ($activeServices['count'] > 0) {
        echo "<h3>Active Services:</h3>";
        $serviceList = $pdo->query("
            SELECT s.id, s.title, s.status, c.name as category, u.name as freelancer
            FROM services s 
            LEFT JOIN categories c ON s.category_id = c.id 
            LEFT JOIN users u ON s.freelancer_id = u.id 
            WHERE s.status = 'active'
        ")->fetchAll();
        
        echo "<ul>";
        foreach ($serviceList as $service) {
            echo "<li>ID: {$service['id']}, Title: {$service['title']}, Category: {$service['category']}, Freelancer: {$service['freelancer']}</li>";
        }
        echo "</ul>";
    }
    
    // Test the exact query from browse.php
    echo "<h3>Testing Browse Query:</h3>";
    $sql = "
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
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $results = $stmt->fetchAll();
    
    echo "<p><strong>Query Results:</strong> " . count($results) . " services found</p>";
    
    if (count($results) > 0) {
        echo "<ul>";
        foreach ($results as $result) {
            echo "<li>{$result['title']} - €{$result['base_price']} - {$result['category']} - {$result['freelancer_name']}</li>";
        }
        echo "</ul>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>
