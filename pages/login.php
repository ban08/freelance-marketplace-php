<?php 
// pages/login.php
session_start();
require_once __DIR__ . '/../scripts/db.php';  // inclui conexão PDO ($db)
require_once __DIR__ . '/../templates/bootstrap.php';

// Inicializa variável para mensagem de erro
$erro = "";

// Processamento do formulário de login quando submetido
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? "");       // obtem email do formulário
    $password = $_POST['password'] ?? "";

    if ($email === "" || $password === "") {
        $erro = "Por favor, preencha todos os campos.";
    } else {
        // Prepara e executa query de seleção do utilizador por email
        $stmt = $pdo->prepare("SELECT * FROM utilizadores WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // Verifica se utilizador existe e se a password está correta
        if ($user && password_verify($password, $user['password'])) {
            // Credenciais válidas -> guardar dados na sessão e redirecionar
            $_SESSION['user_id']   = $user['id'];
            $_SESSION['user_nome'] = $user['nome'];
            $_SESSION['user_tipo'] = $user['tipo'];
            header("Location: /pages/dashboard.php");
            exit;
        } else {
            $erro = "Email ou password incorretos.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
  <meta charset="UTF-8">
  <title>Login - ltw07g06</title>
  <link rel="stylesheet" href="../css/style.css">
</head>
<body>
  <?php include __DIR__ . '/../templates/header.php'; ?>  <!-- Cabeçalho com logo e nav -->
  <main class="login-page">
    <h2>Iniciar Sessão</h2>
    <?php if ($erro): ?>
      <div class="erro"><?php echo htmlspecialchars($erro); ?></div>
    <?php endif; ?>
    <form method="post" action="login.php" class="form-login">
      <label for="email">Email:</label>
      <input type="email" id="email" name="email" required>

      <label for="password">Password:</label>
      <input type="password" id="password" name="password" required>

      <button type="submit">Entrar</button>
    </form>
    <p>Ainda não tem conta? <a href="#">Registe-se</a></p>  <!-- Link de exemplo para página de registo -->
  </main>
  <?php include __DIR__ . '/../templates/footer.php'; ?>  <!-- Rodapé do site -->
</body>
</html>
