<?php
require_once __DIR__ . '/../templates/bootstrap.php';

// Termina sessão
session_unset();
session_destroy();
?>
<!DOCTYPE html>
<html lang="pt">
<head>
  <meta charset="UTF-8">
  <title>Logout – ltw07g06</title>
  <link rel="stylesheet" href="../css/style.css">
</head>
<body>
  <?php include __DIR__ . '/../templates/header.php'; ?>

  <main class="logout-page">
    <div class="logout-message">
      <h2>Até breve!</h2>
      <p>Terminou a sessão com sucesso.</p>
      <p>
        <a href="../index.php" class="btn-primary">Página Inicial</a>
        <a href="login.php"     class="btn-primary">Iniciar Sessão</a>
      </p>
    </div>
  </main>

  <?php include __DIR__ . '/../templates/footer.php'; ?>
</body>
</html>