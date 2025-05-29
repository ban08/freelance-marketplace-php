<?php
session_start();

// Simple admin login for testing
if (isset($_POST['become_admin'])) {
    $_SESSION['user'] = [
        'id' => 1,
        'username' => 'admin',
        'name' => 'Administrator',
        'email' => 'admin@test.com',
        'tipo' => 'admin',
        'is_admin' => 1
    ];
    header('Location: pages/admin_panel.php');
    exit;
}

if (isset($_POST['logout'])) {
    session_destroy();
    header('Location: admin_test.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Admin Panel Test</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .test-container {
            max-width: 600px;
            margin: 2rem auto;
            padding: 2rem;
            background: white;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .test-btn {
            display: inline-block;
            padding: 1rem 2rem;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            border: none;
            cursor: pointer;
            font-size: 1rem;
            margin: 0.5rem;
        }
        .test-btn:hover {
            background: #5a67d8;
        }
        .logout-btn {
            background: #dc3545;
        }
        .logout-btn:hover {
            background: #c82333;
        }
    </style>
</head>
<body>
    <div class="test-container">
        <h1>Admin Panel Test</h1>
        
        <?php if (empty($_SESSION['user'])): ?>
            <p>To test the admin panel, you need to be logged in as an admin user.</p>
            <form method="post">
                <button type="submit" name="become_admin" class="test-btn">
                    🔑 Login as Test Admin
                </button>
            </form>
            
            <hr>
            <h3>Alternative: Login with existing account</h3>
            <p><a href="pages/login.php" class="test-btn">Go to Login Page</a></p>
            
        <?php else: ?>
            <p>Welcome, <strong><?= htmlspecialchars($_SESSION['user']['name']) ?></strong>!</p>
            <p>Admin status: <?= $_SESSION['user']['is_admin'] ? '✅ Yes' : '❌ No' ?></p>
            
            <div>
                <a href="pages/admin_panel.php" class="test-btn">🔧 Admin Panel</a>
                <a href="pages/dashboard.php" class="test-btn">📊 Dashboard</a>
                <a href="pages/profile.php" class="test-btn">👤 Profile</a>
                <a href="fix_database.php" class="test-btn">🔨 Fix Database</a>
            </div>
            
            <form method="post" style="margin-top: 1rem;">
                <button type="submit" name="logout" class="test-btn logout-btn">
                    🚪 Logout
                </button>
            </form>
        <?php endif; ?>
        
        <hr>
        <h3>Direct Links:</h3>
        <p><a href="debug_schema.php" class="test-btn">🔍 Debug Database Schema</a></p>
        <p><a href="database_test.php" class="test-btn">🧪 Database Test</a></p>
    </div>
</body>
</html>
