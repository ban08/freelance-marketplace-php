<?php
require_once __DIR__ . '/../templates/bootstrap.php';
?>
<!DOCTYPE html>
<html lang="pt">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Registar – ltw07g06</title>
  <link rel="stylesheet" href="../css/style.css">
</head>
<body>
  <?php include __DIR__ . '/../templates/header.php'; ?>

  <main class="register-page">
    <div class="register-container">
      <h2>Criar Nova Conta</h2>
      <?php if (!empty($erro)): ?>
        <div class="erro"><?= htmlspecialchars($erro) ?></div>
      <?php endif; ?>
      <form method="POST" action="register.php" class="form-register">
        <label for="nome">Nome:</label>
        <input type="text" id="nome" name="nome"
               value="<?= htmlspecialchars($nome ?? '') ?>" required>

        <label for="email">Email:</label>
        <input type="email" id="email" name="email"
               value="<?= htmlspecialchars($email ?? '') ?>" required>

        <label for="password">Password:</label>
        <input type="password" id="password" name="password" required>

        <label for="tipo">Tipo de Utilizador:</label>
        <select id="tipo" name="tipo" required>
          <option value="">– Selecione –</option>
          <option value="cliente" <?= ($tipo ?? '')==='cliente'?'selected':'' ?>>Cliente</option>
          <option value="freelancer" <?= ($tipo ?? '')==='freelancer'?'selected':'' ?>>Freelancer</option>
        </select>

        <button type="submit">Registar</button>
      </form>
      <p class="alt-action">
        Já tem conta? <a href="login.php">Inicie sessão aqui</a>.
      </p>
    </div>
  </main>

  <?php include __DIR__ . '/../templates/footer.php'; ?>
</body>
</html>
