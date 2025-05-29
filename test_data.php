<?php
require_once __DIR__ . '/templates/bootstrap.php';

echo "<h2>Database Status Check</h2>";

// Check categories
$cats = $pdo->query("SELECT COUNT(*) as count FROM categories")->fetch();
echo "<p>Categories: " . $cats['count'] . "</p>";

if ($cats['count'] == 0) {
    echo "<p>Adding sample categories...</p>";
    $pdo->exec("
        INSERT INTO categories (name, description) VALUES 
        ('Web Development', 'Website and web application development'),
        ('Graphic Design', 'Visual design and graphics'),
        ('Writing & Translation', 'Content writing and translation'),
        ('Digital Marketing', 'Online marketing services'),
        ('Video & Animation', 'Video production and animation')
    ");
    echo "<p>✓ Categories added</p>";
}

// Check users (freelancers)
$users = $pdo->query("SELECT COUNT(*) as count FROM users WHERE tipo = 'freelancer'")->fetch();
echo "<p>Freelancers: " . $users['count'] . "</p>";

if ($users['count'] == 0) {
    echo "<p>Adding sample freelancer...</p>";
    $hashedPassword = password_hash('password123', PASSWORD_DEFAULT);
    $pdo->prepare("
        INSERT INTO users (username, password, name, email, tipo) VALUES 
        (?, ?, ?, ?, 'freelancer')
    ")->execute(['john_dev', $hashedPassword, 'John Developer', 'john@example.com']);
    echo "<p>✓ Sample freelancer added</p>";
}

// Check services
$services = $pdo->query("SELECT COUNT(*) as count FROM services")->fetch();
echo "<p>Services: " . $services['count'] . "</p>";

if ($services['count'] == 0) {
    echo "<p>Adding sample services...</p>";
    
    // Get freelancer ID
    $freelancer = $pdo->query("SELECT id FROM users WHERE tipo = 'freelancer' LIMIT 1")->fetch();
    
    // Get category IDs
    $webCat = $pdo->query("SELECT id FROM categories WHERE name = 'Web Development'")->fetch();
    $designCat = $pdo->query("SELECT id FROM categories WHERE name = 'Graphic Design'")->fetch();
    
    if ($freelancer && $webCat && $designCat) {
        $stmt = $pdo->prepare("
            INSERT INTO services (freelancer_id, category_id, title, description, base_price, delivery_time_days, status) 
            VALUES (?, ?, ?, ?, ?, ?, 'active')
        ");
        
        $stmt->execute([
            $freelancer['id'], 
            $webCat['id'], 
            'Website Development', 
            'I will create a professional website for your business with modern design and responsive layout.', 
            299.99, 
            7
        ]);
        
        $stmt->execute([
            $freelancer['id'], 
            $webCat['id'], 
            'E-commerce Store', 
            'I will build a complete e-commerce solution with payment integration and admin panel.', 
            599.99, 
            14
        ]);
        
        $stmt->execute([
            $freelancer['id'], 
            $designCat['id'], 
            'Logo Design', 
            'I will design a unique and professional logo for your brand with unlimited revisions.', 
            149.99, 
            3
        ]);
        
        echo "<p>✓ Sample services added</p>";
    }
}

// Final check
echo "<h3>Final Status:</h3>";
$finalServices = $pdo->query("
    SELECT s.title, c.name as category, u.name as freelancer, s.base_price, s.status 
    FROM services s 
    JOIN categories c ON s.category_id = c.id 
    JOIN users u ON s.freelancer_id = u.id
")->fetchAll();

echo "<ul>";
foreach ($finalServices as $service) {
    echo "<li>{$service['title']} by {$service['freelancer']} ({$service['category']}) - €{$service['base_price']} - {$service['status']}</li>";
}
echo "</ul>";

echo "<p><a href='pages/browse.php'>→ Test Browse Page</a></p>";
echo "<p><a href='pages/browse.php?debug=1'>→ Test Browse Page with Debug</a></p>";
?>
