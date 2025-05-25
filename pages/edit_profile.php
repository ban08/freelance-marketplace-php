<?php
require_once __DIR__ . '/../templates/bootstrap.php';

// se não está logado
if (empty($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

$userId = $_SESSION['user']['id'];
$erro    = "";
$sucesso = "";

// buscar dados atuais
$stmt = $pdo->prepare("SELECT username, name, email FROM users WHERE id = ?");
$stmt->execute([$userId]);
$dados = $stmt->fetch(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username        = trim($_POST['username'] ?? "");
    $name            = trim($_POST['name']     ?? "");
    $email           = trim($_POST['email']    ?? "");
    $newPassword     = $_POST['new_password']      ?? "";
    $confirmPassword = $_POST['confirm_password']  ?? "";

    // validações
    if (!$username || !$name || !$email) {
        $erro = "Nome de utilizador, nome e email são obrigatórios.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erro = "Email inválido.";
    } else {
        // unicidade username
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
        $stmt->execute([$username, $userId]);
        if ($stmt->fetch()) {
            $erro = "Esse nome de utilizador já está em uso.";
        }
        // unicidade email
        if (!$erro) {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $stmt->execute([$email, $userId]);
            if ($stmt->fetch()) {
                $erro = "Esse email já está registado.";
            }
        }
    }

    // se trocar password, valida confirmação
    if (!$erro && $newPassword) {
        if (strlen($newPassword) < 6) {
            $erro = "A password deve ter pelo menos 6 caracteres.";
        } elseif ($newPassword !== $confirmPassword) {
            $erro = "A confirmação da password não coincide.";
        }
    }

    if (!$erro) {
        // montar UPDATE
        $fields = ["username = ?", "name = ?", "email = ?"];
        $params = [$username, $name, $email];
        if ($newPassword) {
            $fields[]  = "password = ?";
            $params[]  = password_hash($newPassword, PASSWORD_DEFAULT);
        }
        $params[] = $userId;

        $sql = "UPDATE users SET " . implode(", ", $fields) . " WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        // atualizar sessão
        $_SESSION['user']['nome'] = $name;

        // 🚀 redirecionar para o perfil
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
    <?php elseif ($sucesso): ?>
      <div class="alert alert-success"><?= htmlspecialchars($sucesso) ?></div>
    <?php endif; ?>

    <form method="post" action="edit_profile.php" class="form-login">
      <label for="username">Nome de Utilizador:</label>
      <input type="text" id="username" name="username" required
             value="<?= htmlspecialchars($dados['username'] ?? '') ?>">

      <label for="name">Nome Completo:</label>
      <input type="text" id="name" name="name" required
             value="<?= htmlspecialchars($dados['name'] ?? '') ?>">

      <label for="email">Email:</label>
      <input type="email" id="email" name="email" required
             value="<?= htmlspecialchars($dados['email'] ?? '') ?>">

      <label for="new_password">Nova Password: <small>(deixe em branco para manter)</small></label>
      <input type="password" id="new_password" name="new_password">

      <label for="confirm_password">Confirmar Password:</label>
      <input type="password" id="confirm_password" name="confirm_password">

      <button type="submit">Guardar Alterações</button>
    </form>
  </main>

  <?php include __DIR__ . '/../templates/footer.php'; ?>
</body>
</html>