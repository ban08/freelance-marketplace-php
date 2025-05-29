<?php
require_once __DIR__ . '/../templates/bootstrap.php';

if (empty($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

$userId = $_SESSION['user']['id'];

// Try to get user data safely, handling missing columns
try {
    // First, check what columns exist
    $columns = $pdo->query("PRAGMA table_info(users)")->fetchAll();
    $existingColumns = array_column($columns, 'name');
    
    // Build query with only existing columns
    $selectFields = ['id'];
    
    if (in_array('username', $existingColumns)) $selectFields[] = 'username';
    if (in_array('name', $existingColumns)) $selectFields[] = 'name AS full_name';
    if (in_array('email', $existingColumns)) $selectFields[] = 'email';
    if (in_array('tipo', $existingColumns)) $selectFields[] = 'tipo';
    if (in_array('bio', $existingColumns)) $selectFields[] = 'COALESCE(bio, \'\') AS bio';
    if (in_array('profile_picture', $existingColumns)) $selectFields[] = 'COALESCE(profile_picture, \'\') AS profile_picture';
    if (in_array('is_admin', $existingColumns)) $selectFields[] = 'COALESCE(is_admin, 0) AS is_admin';
    if (in_array('joined_date', $existingColumns)) $selectFields[] = 'joined_date';
    
    $query = "SELECT " . implode(', ', $selectFields) . " FROM users WHERE id = ?";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute([$userId]);
    $profile = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Fill in missing fields with defaults
    if (!isset($profile['username'])) $profile['username'] = 'user_' . $userId;
    if (!isset($profile['full_name'])) $profile['full_name'] = $_SESSION['user']['nome'] ?? 'Utilizador';
    if (!isset($profile['email'])) $profile['email'] = $_SESSION['user']['email'] ?? 'N/A';
    if (!isset($profile['tipo'])) $profile['tipo'] = $_SESSION['user']['tipo'] ?? 'cliente';
    if (!isset($profile['bio'])) $profile['bio'] = '';
    if (!isset($profile['profile_picture'])) $profile['profile_picture'] = '';
    if (!isset($profile['is_admin'])) $profile['is_admin'] = $_SESSION['user']['is_admin'] ?? 0;
    if (!isset($profile['joined_date'])) $profile['joined_date'] = date('Y-m-d');
    
} catch (Exception $e) {
    // Fallback: use session data
    $profile = [
        'username' => 'user_' . $userId,
        'full_name' => $_SESSION['user']['nome'] ?? 'Utilizador',
        'email' => $_SESSION['user']['email'] ?? 'N/A',
        'tipo' => $_SESSION['user']['tipo'] ?? 'cliente',
        'bio' => '',
        'profile_picture' => '',
        'is_admin' => $_SESSION['user']['is_admin'] ?? 0,
        'joined_date' => date('Y-m-d')
    ];
}

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
          <li><strong>Tipo de Conta:</strong>
              <?php if ($profile['is_admin']): ?>
                  <span style="color: var(--color-primary); font-weight: bold;">👨‍💼 Administrador</span>
              <?php else: ?>
                  <?= ucfirst(htmlspecialchars($profile['tipo'])) ?>
              <?php endif; ?>
          </li>
          <li><strong>Registado em:</strong>
              <?= date('d/m/Y', strtotime($profile['joined_date'])) ?></li>
        </ul>
      </section>

      <?php if ($profile['is_admin']): ?>
      <section class="profile-section">
        <h3>Acesso Administrativo</h3>
        <p>Tem acesso completo ao painel de administração da plataforma.</p>
        <a href="admin_panel.php" class="btn-primary">Ir para Admin Panel</a>
      </section>
      <?php endif; ?>

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