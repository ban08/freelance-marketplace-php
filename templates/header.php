<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<header class="topnav">
  <div class="logo">
    <a href="/index.php?skip=1">Freelance Platform</a>
  </div>
  <nav>
    <ul class="menu">
      <?php if (!empty($_SESSION['user'])): ?>
        <?php if ($_SESSION['user']['tipo'] === 'cliente'): ?>
          <li><a href="/pages/browse.php">Explorar Serviços</a></li>
          <li><a href="/pages/my_orders.php">Minhas Encomendas</a></li>
        <?php endif; ?>
        <li><a href="/pages/dashboard.php">Dashboard</a></li>
        <li><a href="/pages/profile.php">Perfil</a></li>
        <li><a href="/pages/logout.php">Logout</a></li>
      <?php else: ?>
        <li><a href="/pages/login.php">Login</a></li>
        <li><a href="/pages/register.php">Registar</a></li>
      <?php endif; ?>
    </ul>
  </nav>
</header>