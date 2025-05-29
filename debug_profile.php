<?php
require_once __DIR__ . '/templates/bootstrap.php';

echo "<h1>Profile Debug</h1>";

// Check what's in the session
echo "<h2>Session Data:</h2>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

if (!empty($_SESSION['user'])) {
    $userId = $_SESSION['user']['id'];
    echo "<h2>User ID: $userId</h2>";
    
    // Check what columns exist in users table
    echo "<h2>Users Table Schema:</h2>";
    try {
        $pragma = $pdo->query("PRAGMA table_info(users)");
        $columns = $pragma->fetchAll();
        echo "<pre>";
        print_r($columns);
        echo "</pre>";
    } catch (Exception $e) {
        echo "<p style='color: red;'>Error getting schema: " . $e->getMessage() . "</p>";
    }
    
    // Try to get user data with different queries
    echo "<h2>User Data Queries:</h2>";
    
    // Query 1: Simple select all
    echo "<h3>Query 1: SELECT * FROM users WHERE id = $userId</h3>";
    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $result = $stmt->fetch();
        echo "<pre>";
        print_r($result);
        echo "</pre>";
    } catch (Exception $e) {
        echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
    }
    
    // Query 2: Only basic columns
    echo "<h3>Query 2: Basic columns only</h3>";
    try {
        $stmt = $pdo->prepare("SELECT id, email FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $result = $stmt->fetch();
        echo "<pre>";
        print_r($result);
        echo "</pre>";
    } catch (Exception $e) {
        echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
    }
    
    // Query 3: Check if user exists at all
    echo "<h3>Query 3: User count check</h3>";
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $result = $stmt->fetch();
        echo "Users with ID $userId: " . $result['count'];
    } catch (Exception $e) {
        echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
    }
    
    // List all users
    echo "<h3>All Users:</h3>";
    try {
        $stmt = $pdo->query("SELECT * FROM users LIMIT 10");
        $users = $stmt->fetchAll();
        echo "<pre>";
        print_r($users);
        echo "</pre>";
    } catch (Exception $e) {
        echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
    }
    
} else {
    echo "<p>No user logged in</p>";
}
?>
    echo "<ul>";
    foreach ($columns as $col) {
        echo "<li>{$col['name']} ({$col['type']})</li>";
    }
    echo "</ul>";
    
    // Check session user
    echo "<h3>Session User:</h3>";
    if (isset($_SESSION['user'])) {
        echo "<pre>";
        print_r($_SESSION['user']);
        echo "</pre>";
    } else {
        echo "<p>No user in session</p>";
    }
    
    // Check database user
    if (isset($_SESSION['user']['id'])) {
        $userId = $_SESSION['user']['id'];
        echo "<h3>Database User (ID: $userId):</h3>";
        
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        
        if ($user) {
            echo "<pre>";
            print_r($user);
            echo "</pre>";
        } else {
            echo "<p>User not found in database!</p>";
        }
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>
