<?php 
// pages/login.php
require_once __DIR__ . '/../templates/bootstrap.php';  
require_once __DIR__ . '/../scripts/db.php';   // ajusta para ../scripts/db.php ou ../db/db.php conforme o teu layout

// Inicializa variável para mensagem de erro
$erro = "";
$email = "";  // Inicializa o campo email para preencher o formulário automaticamente

// Processamento do formulário de login quando submetido
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? "");
    $password = $_POST['password'] ?? "";

    if ($email === "" || $password === "") {
        $erro = "Por favor, preencha todos os campos.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) { // apenas aceita email valido
        $erro = "Email inválido.";
    } else {
        // Prepara e executa a query de seleção do utilizador por email
        $stmt = $pdo->prepare("
            SELECT 
              id,
              name     AS nome,
              tipo,               -- agora trazemos o tipo: 'cliente' ou 'freelancer'
              password
            FROM users 
            WHERE email = ?
        ");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        // Verifica se utilizador existe e se a password está correta
        if ($user && password_verify($password, $user['password'])) {
            // Segurança extra: protege contra session fixation
            session_regenerate_id(true);

            // Credenciais válidas -> guardar dados na sessão e redirecionar
            $_SESSION['user'] = [
                'id'   => $user['id'],
                'nome' => $user['nome'],
                'tipo' => $user['tipo']
            ];
            // caminho relativo dentro de /pages
            header("Location: dashboard.php");
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
  <?php include __DIR__ . '/../templates/header.php'; ?>  <!-- Cabeçalho com logo e navegação -->
  <main class="login-page">
    <h2>Iniciar Sessão</h2>

    <?php if ($erro): ?>
      <div class="erro"><?php echo htmlspecialchars($erro); ?></div>
    <?php endif; ?>

    <form method="post" action="login.php" class="form-login">
      <label for="email">Email:</label>
      <!-- Campo repopulado em caso de erro -->
      <input type="email" id="email" name="email" required 
             value="<?php echo htmlspecialchars($email); ?>">

      <label for="password">Password:</label>
      <input type="password" id="password" name="password" required>

      <button type="submit">Entrar</button>
    </form>

    <p>Ainda não tem conta? <a href="register.php">Registe-se</a></p>  <!-- Link de exemplo -->
  </main>
  <?php include __DIR__ . '/../templates/footer.php'; ?>  <!-- Rodapé do site -->
</body>
</html>
