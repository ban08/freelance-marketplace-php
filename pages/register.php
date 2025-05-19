<?php 
require_once __DIR__ . '/../templates/bootstrap.php';
require __DIR__ . '/../templates/header.php'; 

$erro = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Obter e limpar os dados
    $nome     = trim($_POST['nome']);
    $email    = trim($_POST['email']);
    $password = $_POST['password'];
    $tipo     = $_POST['tipo'] ?? '';

    // Validações simples
    if (!$nome || !$email || !$password || !$tipo) {
        $erro = "Por favor preencha todos os campos.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erro = "Email inválido.";
    } elseif ($tipo !== 'cliente' && $tipo !== 'freelancer') {
        $erro = "Tipo de utilizador inválido.";
    } else {
        // Verificar duplicação de email
        $stmt = $pdo->prepare("SELECT id FROM utilizadores WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $erro = "Já existe uma conta com este email.";
        }
    }

    // Se não há erros até agora, proceder com inserção
    if (empty($erro)) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO utilizadores (nome, email, password, tipo) VALUES (?, ?, ?, ?)");
        $stmt->execute([$nome, $email, $hash, $tipo]);

        // Redirecionar para login (ou fazer login automático)
        header("Location: login.php?registrado=1");
        exit;
    }
}
?>

<div class="register-container">
  <h2>Criar Nova Conta</h2>
  <?php if($erro): ?>
    <p class="erro"><?= htmlspecialchars($erro) ?></p>
  <?php endif; ?>
  <form method="POST" action="register.php">
    <label>Nome: <input type="text" name="nome" value="<?= htmlspecialchars($nome ?? '') ?>" required></label><br>
    <label>Email: <input type="email" name="email" value="<?= htmlspecialchars($email ?? '') ?>" required></label><br>
    <label>Password: <input type="password" name="password" required></label><br>
    <label>Tipo de Utilizador:
      <select name="tipo">
        <option value="cliente" <?= (isset($tipo)&&$tipo=='cliente')?'selected':''; ?>>Cliente</option>
        <option value="freelancer" <?= (isset($tipo)&&$tipo=='freelancer')?'selected':''; ?>>Freelancer</option>
      </select>
    </label><br>
    <button type="submit">Registar</button>
  </form>
  <p>Já tem conta? <a href="login.php">Inicie sessão aqui</a>.</p>
</div>

<?php require '/../templates/footer.php'; ?>
