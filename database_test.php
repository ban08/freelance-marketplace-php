<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Database Test</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; }
        .success { color: green; }
        .error { color: red; }
        .info { color: blue; }
        .section { margin: 20px 0; padding: 20px; border: 1px solid #ddd; border-radius: 8px; }
    </style>
</head>
<body>
    <h1>Database Test & Setup</h1>
    
    <?php
    require_once __DIR__ . '/templates/bootstrap.php';
    
    try {
        echo "<div class='section'>";
        echo "<h2>1. Database Connection</h2>";
        echo "<p class='success'>✓ Database connected successfully</p>";
        
        // Test categories
        echo "<h3>Categories:</h3>";
        $cats = $pdo->query("SELECT COUNT(*) as count FROM categories")->fetch();
        echo "<p>Total categories: <strong>" . $cats['count'] . "</strong></p>";
        
        if ($cats['count'] == 0) {
            echo "<p class='info'>Adding sample categories...</p>";
            $pdo->exec("
                INSERT INTO categories (name, description) VALUES 
                ('Web Development', 'Website and web application development'),
                ('Graphic Design', 'Visual design and graphics'),
                ('Writing & Translation', 'Content writing and translation'),
                ('Digital Marketing', 'Online marketing services'),
                ('Video & Animation', 'Video production and animation')
            ");
            echo "<p class='success'>✓ Categories added</p>";
        } else {
            $categories = $pdo->query("SELECT id, name FROM categories")->fetchAll();
            echo "<ul>";
            foreach($categories as $cat) {
                echo "<li>" . htmlspecialchars($cat['name']) . " (ID: " . $cat['id'] . ")</li>";
            }
            echo "</ul>";
        }
        echo "</div>";
        
        // Test users
        echo "<div class='section'>";
        echo "<h3>Users (Freelancers):</h3>";
        $users = $pdo->query("SELECT COUNT(*) as count FROM users WHERE tipo = 'freelancer'")->fetch();
        echo "<p>Total freelancers: <strong>" . $users['count'] . "</strong></p>";
        
        if ($users['count'] == 0) {
            echo "<p class='info'>Adding sample freelancer...</p>";
            $hashedPassword = password_hash('password123', PASSWORD_DEFAULT);
            $pdo->prepare("
                INSERT INTO users (username, password, name, email, tipo) VALUES 
                (?, ?, ?, ?, 'freelancer')
            ")->execute(['john_dev', $hashedPassword, 'John Developer', 'john@example.com']);
            echo "<p class='success'>✓ Sample freelancer added</p>";
        } else {
            $freelancers = $pdo->query("SELECT id, name, username FROM users WHERE tipo = 'freelancer'")->fetchAll();
            echo "<ul>";
            foreach($freelancers as $user) {
                echo "<li>" . htmlspecialchars($user['name']) . " (@" . htmlspecialchars($user['username']) . ") - ID: " . $user['id'] . "</li>";
            }
            echo "</ul>";
        }
        echo "</div>";
        
        // Test services
        echo "<div class='section'>";
        echo "<h3>Services:</h3>";
        $services = $pdo->query("SELECT COUNT(*) as count FROM services")->fetch();
        echo "<p>Total services: <strong>" . $services['count'] . "</strong></p>";
        
        if ($services['count'] == 0) {
            echo "<p class='info'>Adding sample services...</p>";
            
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
                    'Professional Website Development', 
                    'I will create a modern, responsive website for your business with clean design and optimal performance.', 
                    299.99, 
                    7
                ]);
                
                $stmt->execute([
                    $freelancer['id'], 
                    $webCat['id'], 
                    'E-commerce Store Setup', 
                    'Complete e-commerce solution with payment gateway integration, inventory management, and admin dashboard.', 
                    599.99, 
                    14
                ]);
                
                $stmt->execute([
                    $freelancer['id'], 
                    $designCat['id'], 
                    'Custom Logo Design', 
                    'Unique and professional logo design for your brand with multiple concepts and unlimited revisions.', 
                    149.99, 
                    3
                ]);
                
                echo "<p class='success'>✓ Sample services added</p>";
            } else {
                echo "<p class='error'>✗ Could not add services - missing freelancer or categories</p>";
            }
        } else {
            $serviceList = $pdo->query("
                SELECT s.id, s.title, c.name as category, u.name as freelancer, s.base_price, s.status 
                FROM services s 
                JOIN categories c ON s.category_id = c.id 
                JOIN users u ON s.freelancer_id = u.id
                ORDER BY s.created_at DESC
            ")->fetchAll();
            
            echo "<ul>";
            foreach($serviceList as $service) {
                $statusColor = $service['status'] === 'active' ? 'success' : 'error';
                echo "<li>";
                echo "<strong>" . htmlspecialchars($service['title']) . "</strong><br>";
                echo "By: " . htmlspecialchars($service['freelancer']) . " | ";
                echo "Category: " . htmlspecialchars($service['category']) . " | ";
                echo "Price: €" . number_format($service['base_price'], 2) . " | ";
                echo "<span class='{$statusColor}'>Status: " . $service['status'] . "</span>";
                echo "</li>";
            }
            echo "</ul>";
        }
        echo "</div>";
        
        // Test browse query
        echo "<div class='section'>";
        echo "<h3>Browse Query Test:</h3>";
        $browseQuery = "
            SELECT
                s.id,
                s.title,
                s.base_price,
                s.delivery_time_days,
                s.description,
                c.name AS category,
                u.name AS freelancer_name
            FROM services s
            INNER JOIN categories c ON s.category_id = c.id
            INNER JOIN users u ON s.freelancer_id = u.id
            WHERE s.status = 'active'
            ORDER BY s.created_at DESC, s.base_price ASC
        ";
        
        $browseResults = $pdo->query($browseQuery)->fetchAll();
        echo "<p>Browse query results: <strong>" . count($browseResults) . "</strong> services found</p>";
        
        if (count($browseResults) > 0) {
            echo "<ul>";
            foreach($browseResults as $service) {
                echo "<li><strong>" . htmlspecialchars($service['title']) . "</strong> - €" . number_format($service['base_price'], 2) . " (" . htmlspecialchars($service['category']) . ")</li>";
            }
            echo "</ul>";
        }
        echo "</div>";
        
    } catch (Exception $e) {
        echo "<div class='section'>";
        echo "<h2 class='error'>Database Error</h2>";
        echo "<p class='error'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
        echo "</div>";
    }
    ?>
    
    <div class='section'>
        <h2>Navigation</h2>
        <p><a href='pages/browse.php' style='color: blue;'>→ Test Browse Page</a></p>
        <p><a href='pages/browse.php?debug=1' style='color: blue;'>→ Test Browse Page with Debug</a></p>
        <p><a href='index.php' style='color: blue;'>→ Homepage</a></p>
    </div>
</body>
</html>
