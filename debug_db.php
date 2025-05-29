<?php
try {
    $pdo = new PDO('sqlite:database.sqlite');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo 'Categories in database:' . PHP_EOL;
    $cats = $pdo->query('SELECT COUNT(*) as count FROM categories')->fetch();
    echo 'Total categories: ' . $cats['count'] . PHP_EOL;
    
    $categories = $pdo->query('SELECT id, name FROM categories')->fetchAll();
    foreach($categories as $cat) {
        echo '- ' . $cat['id'] . ': ' . $cat['name'] . PHP_EOL;
    }
    
    echo PHP_EOL . 'Services in database:' . PHP_EOL;
    $services = $pdo->query('SELECT COUNT(*) as count FROM services')->fetch();
    echo 'Total services: ' . $services['count'] . PHP_EOL;
    
    $serviceList = $pdo->query('SELECT s.id, s.title, s.status, c.name as category FROM services s LEFT JOIN categories c ON s.category_id = c.id')->fetchAll();
    foreach($serviceList as $s) {
        echo '- ' . $s['id'] . ': ' . $s['title'] . ' (' . $s['status'] . ') - Category: ' . ($s['category'] ?? 'NULL') . PHP_EOL;
    }
    
    echo PHP_EOL . 'Testing browse.php query:' . PHP_EOL;
    $sql = "
      SELECT
        s.id,
        s.title,
        s.base_price,
        s.delivery_time_days,
        c.name           AS category,
        COALESCE(AVG(r.rating), 0) AS avg_rating,
        COUNT(r.id)              AS reviews_count
      FROM services s
      JOIN categories c   ON s.category_id = c.id
      LEFT JOIN reviews r ON r.service_id  = s.id
      WHERE s.status = 'active'
      GROUP BY s.id
      HAVING COALESCE(AVG(r.rating), 0) >= 0
      ORDER BY COALESCE(AVG(r.rating), 0) DESC, s.base_price ASC
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $browseServices = $stmt->fetchAll();
    
    echo 'Browse query results: ' . count($browseServices) . ' services found' . PHP_EOL;
    foreach($browseServices as $service) {
        echo '- ' . $service['title'] . ' (€' . $service['base_price'] . ') - ' . $service['category'] . PHP_EOL;
    }
    
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
}
?>
