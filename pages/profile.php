<?php
require_once __DIR__ . '/../templates/bootstrap.php';

if (empty($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

$userId = $_SESSION['user']['id'];

// SELECT apenas as colunas que sabemos que existem
$stmt = $pdo->prepare("
SELECT 
  username,
  name         AS full_name,
  email,
  tipo,
  COALESCE(bio, '')             AS bio,
  COALESCE(profile_picture, '') AS profile_picture,
  joined_date
FROM users
WHERE id = ?
");

$stmt->execute([$userId]);
$profile = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$profile) {
    echo "<p>Perfil não encontrado.</p>";
    exit; 
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
  <meta charset="UTF-8">
  <title>Perfil – ltw07g06</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link rel="stylesheet" href="../css/style.css">
</head>
<body>
  <?php include __DIR__ . '/../templates/header.php'; ?>

  <main class="profile-page">
    <div class="profile-container">
      <?php if ($profile['profile_picture']): ?>
        <img src="/<?= htmlspecialchars($profile['profile_picture']) ?>"
             alt="Foto de Perfil"
             class="profile-photo">
      <?php else: ?>
        <div class="profile-photo-placeholder"></div>
      <?php endif; ?>

      <h2>
        <?= htmlspecialchars($profile['full_name']) ?: 
            htmlspecialchars($_SESSION['user']['nome']) ?>
      </h2>
      <p class="profile-email">
        <?= htmlspecialchars($profile['email']) ?>
      </p>

      <?php if ($profile['bio'] !== ''): ?>
      <section class="profile-section">
        <h3>Sobre mim</h3>
        <p><?= nl2br(htmlspecialchars($profile['bio'])) ?></p>
      </section>
      <?php endif; ?>

      <section class="profile-section">
        <h3>Detalhes da Conta</h3>
        <ul>
          <li><strong>Nome de Utilizador:</strong>
              <?= htmlspecialchars($profile['username']) ?></li>
          <li><strong>Registado em:</strong>
              <?= date('d/m/Y', strtotime($profile['joined_date'])) ?></li>
        </ul>
      </section>

      <p>
        <a href="edit_profile.php" class="btn-primary">
          Editar Perfil
        </a>
      </p>
    </div>
  </main>

  <?php include __DIR__ . '/../templates/footer.php'; ?>
</body>
</html>