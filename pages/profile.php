<?php
require_once __DIR__ . '/../templates/bootstrap.php';

// Se não estiver logado, redireciona para login
if (empty($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

$userId = $_SESSION['user']['id'];

// Buscar dados completos do perfil
$stmt = $pdo->prepare("
    SELECT username, name, email, profile_picture, bio, joined_date
      FROM users
     WHERE id = ?
");
$stmt->execute([$userId]);
$profile = $stmt->fetch(PDO::FETCH_ASSOC);
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
      <?php if (!empty($profile['profile_picture'])): ?>
        <img src="<?= htmlspecialchars($profile['profile_picture']) ?>"
             alt="Foto de Perfil" class="profile-photo">
      <?php else: ?>
        <div class="profile-photo-placeholder"></div>
      <?php endif; ?>

      <h2><?= htmlspecialchars($profile['name']) ?></h2>
      <p class="profile-email"><?= htmlspecialchars($profile['email']) ?></p>

      <?php if (!empty($profile['bio'])): ?>
      <section class="profile-section">
        <h3>Sobre mim</h3>
        <p><?= nl2br(htmlspecialchars($profile['bio'])) ?></p>
      </section>
      <?php endif; ?>

      <section class="profile-section">
        <h3>Detalhes da Conta</h3>
        <ul>
          <li><strong>Nome de Utilizador:</strong> <?= htmlspecialchars($profile['username']) ?></li>
          <li><strong>Registado em:</strong>
              <?= date('d/m/Y', strtotime($profile['joined_date'])) ?></li>
        </ul>
      </section>

      <p><a href="edit_profile.php" class="btn-primary">Editar Perfil</a></p>
    </div>
  </main>

  <?php include __DIR__ . '/../templates/footer.php'; ?>
</body>
</html>