<?php
require_once __DIR__ . '/../templates/bootstrap.php';

// se não está logado
if (empty($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

$userId = $_SESSION['user']['id'];
$erro    = "";

// buscar dados atuais
$stmt = $pdo->prepare("
  SELECT username, name, email, bio, profile_picture
    FROM users
   WHERE id = ?
");
$stmt->execute([$userId]);
$dados = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username        = trim($_POST['username'] ?? "");
    $name            = trim($_POST['name']     ?? "");
    $email           = trim($_POST['email']    ?? "");
    $bio              = trim($_POST['bio']      ?? "");

    // validações
    if (!$username || !$name || !$email) {
        $erro = "Nome de utilizador, nome e email são obrigatórios.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erro = "Email inválido.";
    }

    // tratar upload da foto
    $photoPath = $dados['profile_picture'];
    if (empty($erro) && !empty($_FILES['profile_picture']['tmp_name'])) {
        $u = $_FILES['profile_picture'];
        if ($u['error'] === UPLOAD_ERR_OK) {
            $dir = __DIR__ . '/../uploads/profiles/';
            if (!is_dir($dir)) mkdir($dir,0755,true);
            $ext  = pathinfo($u['name'], PATHINFO_EXTENSION);
            $file = uniqid('pf_').".{$ext}";
            if (move_uploaded_file($u['tmp_name'], $dir.$file)) {
                $photoPath = "uploads/profiles/{$file}";
            }
        }
    }

    if (!$erro) {
        // montar UPDATE
        $sql  = "UPDATE users SET username=?, name=?, email=?, bio=?, profile_picture=? WHERE id=?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $username, $name, $email,
            $bio,      $photoPath,
            $userId
        ]);

        // atualizar sessão
        $_SESSION['user']['nome'] = $name;

        header('Location: profile.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
  <meta charset="UTF-8">
  <title>Editar Perfil – ltw07g06</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link rel="stylesheet" href="../css/style.css">
</head>
<body>
  <?php include __DIR__ . '/../templates/header.php'; ?>

  <main class="login-page">
    <h2>Editar Perfil</h2>

    <?php if ($erro): ?>
      <div class="erro"><?= htmlspecialchars($erro) ?></div>
    <?php endif; ?>

    <form method="post" action="edit_profile.php" enctype="multipart/form-data" class="form-login">
      <label for="username">Nome de Utilizador:</label>
      <input id="username" name="username" required
             value="<?= htmlspecialchars($dados['username']) ?>">

      <label for="name">Nome Completo:</label>
      <input id="name" name="name" required
             value="<?= htmlspecialchars($dados['name']) ?>">

      <label for="email">Email:</label>
      <input id="email" type="email" name="email" required
             value="<?= htmlspecialchars($dados['email']) ?>">

      <label for="bio">Biografia:</label>
      <textarea id="bio" name="bio" rows="4"><?= htmlspecialchars($dados['bio']) ?></textarea>

      <label for="profile_picture">Foto de Perfil:</label>
      <?php if ($dados['profile_picture']): ?>
        <img src="../<?= htmlspecialchars($dados['profile_picture']) ?>"
             alt="Atual" style="max-width:100px; display:block; margin-bottom:0.5em;">
      <?php endif; ?>
      <input id="profile_picture" type="file" name="profile_picture" accept="image/*">

      <button type="submit">Guardar Alterações</button>
    </form>
  </main>

  <?php include __DIR__ . '/../templates/footer.php'; ?>
</body>
</html>