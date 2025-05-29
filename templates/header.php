<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Determine base path based on current file location
$isInPages = strpos($_SERVER['REQUEST_URI'], '/pages/') !== false;
$basePath = $isInPages ? '../' : '';
?>
<header class="topnav">
  <div class="logo">
    <a href="<?= $basePath ?>index.php">Freelance Platform</a>
  </div>
  <nav>
    <ul class="menu">
      <?php if (!empty($_SESSION['user'])): ?>
        <li><a href="<?= $basePath ?>pages/browse.php">Explorar Serviços</a></li>
        <?php if (!empty($_SESSION['user']['is_admin'])): ?>
          <li><a href="<?= $basePath ?>pages/admin_panel.php">Admin Panel</a></li>
        <?php endif; ?>
        <?php if ($_SESSION['user']['tipo'] === 'cliente'): ?>
          <li><a href="<?= $basePath ?>pages/my_orders.php">Minhas Encomendas</a></li>
          <li><a href="<?= $basePath ?>pages/client_inquiries.php">Mensagens</a></li>
        <?php endif; ?>
        <?php if ($_SESSION['user']['tipo'] === 'freelancer'): ?>
          <li><a href="<?= $basePath ?>pages/manage_services.php">Gerir Serviços</a></li>
          <li><a href="<?= $basePath ?>pages/orders.php">Encomendas</a></li>
          <li><a href="<?= $basePath ?>pages/inquiries.php">Mensagens</a></li>
        <?php endif; ?>
        <li><a href="<?= $basePath ?>pages/dashboard.php">Dashboard</a></li>
        <li><a href="<?= $basePath ?>pages/profile.php">Perfil</a></li>
        <li><a href="<?= $basePath ?>pages/logout.php">Logout</a></li>
      <?php else: ?>
        <li><a href="<?= $basePath ?>pages/browse.php">Explorar Serviços</a></li>
        <li><a href="<?= $basePath ?>pages/login.php">Login</a></li>
        <li><a href="<?= $basePath ?>pages/register.php">Registar</a></li>
      <?php endif; ?>
    </ul>
  </nav>
</header>