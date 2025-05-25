<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<header class="topnav">
  <div class="logo">
    <a href="/index.php">Freelance Platform</a>
  </div>
  <nav>
    <ul class="menu">
      <?php if (!empty($_SESSION['user'])): ?>
        <li>
          <a href="/pages/profile.php">
            <span class="avatar">
              <?= strtoupper(substr($_SESSION['user']['nome'], 0, 1)) ?>
            </span>
            <?= htmlspecialchars($_SESSION['user']['nome']) ?>
          </a>
        </li>
        <li><a href="/pages/dashboard.php">Dashboard</a></li>
        <li><a href="/pages/logout.php">Logout</a></li>
      <?php else: ?>
        <li><a href="/pages/login.php">Login</a></li>
        <li><a href="/pages/register.php">Registar</a></li>
      <?php endif; ?>
    </ul>
  </nav>
</header>