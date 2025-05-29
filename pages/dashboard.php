<?php
require_once __DIR__ . '/../templates/bootstrap.php';

// se não houver login, volta ao form
if (empty($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

$user = $_SESSION['user'];

// inline delete from dashboard
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete_service') {
    $sid = intval($_POST['service_id']);
    $pdo->prepare("DELETE FROM services WHERE id=? AND freelancer_id=?")
        ->execute([$sid, $user['id']]);
    header('Location: dashboard.php');
    exit;
}

// Fetch additional stats for freelancers
if ($user['tipo'] === 'freelancer') {
    // Get service statistics
    $statsStmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_services,
            SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_services,
            SUM(CASE WHEN status = 'paused' THEN 1 ELSE 0 END) as paused_services
        FROM services 
        WHERE freelancer_id = ?
    ");
    $statsStmt->execute([$user['id']]);
    $serviceStats = $statsStmt->fetch();
    
    // Get recent orders
    $ordersStmt = $pdo->prepare("
        SELECT COUNT(*) as total_orders,
               SUM(CASE WHEN o.status = 'pending' THEN 1 ELSE 0 END) as pending_orders,
               SUM(CASE WHEN o.status = 'completed' THEN 1 ELSE 0 END) as completed_orders
        FROM orders o
        JOIN services s ON o.service_id = s.id
        WHERE s.freelancer_id = ?
    ");
    $ordersStmt->execute([$user['id']]);
    $orderStats = $ordersStmt->fetch();
    
    // Get recent messages count
    $msgStmt = $pdo->prepare("
        SELECT COUNT(DISTINCT sender_id) as unread_conversations
        FROM messages 
        WHERE receiver_id = ?
    ");
    $msgStmt->execute([$user['id']]);
    $messageStats = $msgStmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="pt">

<head>
    <meta charset="UTF-8">
    <title>Dashboard – ltw07g06</title>
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body>
    <?php include __DIR__ . '/../templates/header.php'; ?>
    
    <main class="dashboard-main">
        <!-- Dashboard Hero Section -->
        <section class="dashboard-hero">
            <div class="container">
                <div class="dashboard-welcome">
                    <div class="welcome-content">
                        <h1 class="welcome-title">
                            <i class="fas fa-tachometer-alt"></i>
                            Olá, <?= htmlspecialchars($user['name'] ?? $user['nome']) ?>!
                        </h1>
                        <p class="welcome-subtitle">
                            <?php if ($user['tipo'] === 'freelancer'): ?>
                                Gerencie os seus serviços 
                            <?php else: ?>
                                Explore serviços incríveis e acompanhe seus pedidos
                            <?php endif; ?>
                        </p>
                    </div>
                    <?php if ($user['tipo'] === 'freelancer'): ?>
                        <div class="quick-actions">
                            <a href="new_service.php" class="quick-action-btn primary">
                                <i class="fas fa-plus"></i>
                                Novo Serviço
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </section>

        <div class="container">
            <?php if ($user['tipo'] === 'freelancer') : ?>
                <!-- Statistics Overview -->
                <section class="dashboard-stats">
                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-briefcase"></i>
                            </div>
                            <div class="stat-content">
                                <div class="stat-number"><?= $serviceStats['total_services'] ?? 0 ?></div>
                                <div class="stat-label">Total de Serviços</div>
                            </div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <div class="stat-content">
                                <div class="stat-number"><?= $serviceStats['active_services'] ?? 0 ?></div>
                                <div class="stat-label">Serviços Ativos</div>
                            </div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-shopping-cart"></i>
                            </div>
                            <div class="stat-content">
                                <div class="stat-number"><?= $orderStats['total_orders'] ?? 0 ?></div>
                                <div class="stat-label">Total de Pedidos</div>
                            </div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-envelope"></i>
                            </div>
                            <div class="stat-content">
                                <div class="stat-number"><?= $messageStats['unread_conversations'] ?? 0 ?></div>
                                <div class="stat-label">Conversas</div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Navigation Cards -->
                <section class="dashboard-navigation">
                    <div class="nav-cards-grid">
                        <a href="manage_services.php" class="nav-card">
                            <div class="nav-card-icon">
                                <i class="fas fa-cogs"></i>
                            </div>
                            <div class="nav-card-content">
                                <h3>Gerir Serviços</h3>
                                <p>Editar, ativar ou pausar seus serviços existentes</p>
                            </div>
                            <div class="nav-card-arrow">
                                <i class="fas fa-arrow-right"></i>
                            </div>
                        </a>
                        
                        <a href="orders.php" class="nav-card">
                            <div class="nav-card-icon">
                                <i class="fas fa-box"></i>
                            </div>
                            <div class="nav-card-content">
                                <h3>Encomendas</h3>
                                <p>Acompanhe e gerencie todas as suas encomendas</p>
                            </div>
                            <div class="nav-card-arrow">
                                <i class="fas fa-arrow-right"></i>
                            </div>
                        </a>
                        
                        <a href="profile.php" class="nav-card">
                            <div class="nav-card-icon">
                                <i class="fas fa-user"></i>
                            </div>
                            <div class="nav-card-content">
                                <h3>Perfil</h3>
                                <p>Atualize suas informações pessoais e profissionais</p>
                            </div>
                            <div class="nav-card-arrow">
                                <i class="fas fa-arrow-right"></i>
                            </div>
                        </a>
                    </div>
                </section>

                <!-- Recent Services Overview -->
                <section class="dashboard-services">
                    <div class="section-header">
                        <h2>
                            <i class="fas fa-list"></i>
                            Meus Serviços
                        </h2>
                        <a href="manage_services.php" class="view-all-btn">
                            Ver Todos <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                    
                    <div class="services-overview">
                        <?php
                        $stmt = $pdo->prepare("
                            SELECT id, title, status, base_price, created_at
                            FROM services
                            WHERE freelancer_id = ?
                            ORDER BY created_at DESC
                            LIMIT 5
                        ");
                        $stmt->execute([$user['id']]);
                        $recentServices = $stmt->fetchAll();
                        ?>
                        
                        <?php if ($recentServices) : ?>
                            <div class="services-list">
                                <?php foreach ($recentServices as $service) : ?>
                                    <div class="service-item">
                                        <div class="service-info">
                                            <h4 class="service-title"><?= htmlspecialchars($service['title']) ?></h4>
                                            <div class="service-details">
                                                <span class="service-price">€<?= htmlspecialchars($service['base_price']) ?></span>
                                                <span class="service-status status-<?= $service['status'] ?>">
                                                    <?= ucfirst($service['status']) ?>
                                                </span>
                                            </div>
                                        </div>
                                        <div class="service-actions">
                                            <a href="edit_service.php?id=<?= $service['id'] ?>" class="action-btn edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="services.php?id=<?= $service['id'] ?>" class="action-btn view">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else : ?>
                            <div class="empty-state">
                                <div class="empty-icon">
                                    <i class="fas fa-briefcase"></i>
                                </div>
                                <h3>Nenhum serviço ainda</h3>
                                <p>Comece criando seu primeiro serviço para atrair clientes</p>
                                <a href="new_service.php" class="btn-primary">
                                    <i class="fas fa-plus"></i>
                                    Criar Primeiro Serviço
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </section>
            <?php else : ?>
                <!-- Client Dashboard -->
                <section class="dashboard-navigation">
                    <div class="nav-cards-grid client-nav">
                        <a href="browse.php" class="nav-card">
                            <div class="nav-card-icon">
                                <i class="fas fa-search"></i>
                            </div>
                            <div class="nav-card-content">
                                <h3>Explorar Serviços</h3>
                                <p>Descubra serviços incríveis de freelancers talentosos</p>
                            </div>
                            <div class="nav-card-arrow">
                                <i class="fas fa-arrow-right"></i>
                            </div>
                        </a>
                        
                        <a href="my_orders.php" class="nav-card">
                            <div class="nav-card-icon">
                                <i class="fas fa-shopping-bag"></i>
                            </div>
                            <div class="nav-card-content">
                                <h3>Minhas Encomendas</h3>
                                <p>Acompanhe o progresso dos seus pedidos</p>
                            </div>
                            <div class="nav-card-arrow">
                                <i class="fas fa-arrow-right"></i>
                            </div>
                        </a>
                        
                        <a href="profile.php" class="nav-card">
                            <div class="nav-card-icon">
                                <i class="fas fa-user"></i>
                            </div>
                            <div class="nav-card-content">
                                <h3>Meu Perfil</h3>
                                <p>Gerencie suas informações pessoais</p>
                            </div>
                            <div class="nav-card-arrow">
                                <i class="fas fa-arrow-right"></i>
                            </div>
                        </a>
                    </div>
                </section>

                <section class="dashboard-section client-orders">
                    <div class="section-header">
                        <h2>
                            <i class="fas fa-clock"></i>
                            Atividade Recente
                        </h2>
                    </div>
                    
                    <?php
                    $stmt = $pdo->prepare("
                        SELECT o.id, s.title, u.name AS freelancer, o.order_date, o.status
                        FROM orders o 
                        JOIN services s ON o.service_id = s.id
                        JOIN users u ON s.freelancer_id = u.id
                        WHERE o.client_id = ?
                        ORDER BY o.order_date DESC
                        LIMIT 5
                    ");
                    $stmt->execute([$user['id']]);
                    $recentOrders = $stmt->fetchAll();
                    ?>
                    
                    <?php if ($recentOrders): ?>
                        <div class="orders-list">
                            <?php foreach($recentOrders as $order): ?>
                                <div class="order-item">
                                    <div class="order-info">
                                        <h4><?= htmlspecialchars($order['title']) ?></h4>
                                        <p>por <?= htmlspecialchars($order['freelancer']) ?></p>
                                        <span class="order-date"><?= date('d/m/Y', strtotime($order['order_date'])) ?></span>
                                    </div>
                                    <div class="order-status status-<?= $order['status'] ?>">
                                        <?= ucfirst($order['status']) ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <div class="empty-icon">
                                <i class="fas fa-shopping-bag"></i>
                            </div>
                            <h3>Nenhuma encomenda ainda</h3>
                            <p>Explore nossos serviços e faça seu primeiro pedido</p>
                            <a href="browse.php" class="btn-primary">
                                <i class="fas fa-search"></i>
                                Explorar Serviços
                            </a>
                        </div>
                    <?php endif; ?>
                </section>
            <?php endif; ?>
        </div>


    </main>
    <?php include __DIR__ . '/../templates/footer.php'; ?>
</body>

</html>
