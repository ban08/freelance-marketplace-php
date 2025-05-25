<?php
require_once __DIR__ . '/../templates/bootstrap.php';
if (empty($_SESSION['user']) || $_SESSION['user']['tipo']!=='cliente') {
    header('Location: login.php'); exit;
}
$clientId  = $_SESSION['user']['id'];
$serviceId = filter_input(INPUT_GET,'service',FILTER_VALIDATE_INT);
if (!$serviceId) {
    header('Location: browse.php'); exit;
}
// fetch service + freelancer
$stmt = $pdo->prepare("
  SELECT s.title, s.base_price, s.delivery_time_days,
         u.id AS freelancer_id, u.name AS freelancer_name
    FROM services s
    JOIN users u ON s.freelancer_id = u.id
   WHERE s.id = ? AND s.status='active'
");
$stmt->execute([$serviceId]);
$sv = $stmt->fetch();
if (!$sv) {
    header('Location: browse.php'); exit;
}

$erro = '';
if ($_SERVER['REQUEST_METHOD']==='POST') {
    $card    = trim($_POST['card_number']   ?? '');
    $holder  = trim($_POST['card_holder']   ?? '');
    $expiry  = trim($_POST['card_expiry']   ?? '');
    if (!$card || !$holder || !$expiry) {
        $erro = 'Por favor preencha todos os campos de pagamento.';
    } else {
        // simulate payment success → insert order
        $ins = $pdo->prepare("
          INSERT INTO orders 
            (client_id, service_id, price) 
          VALUES (?,?,?)
        ");
        $ins->execute([$clientId, $serviceId, $sv['base_price']]);
        header('Location: my_orders.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
  <meta charset="UTF-8">
  <title>Checkout – ltw07g06</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link rel="stylesheet" href="../css/style.css">
</head>
<body>
  <?php include __DIR__ . '/../templates/header.php'; ?>
  <main class="login-page" style="max-width:400px; margin:2em auto;">
    <h2>Pagamento: <?= htmlspecialchars($sv['title']) ?></h2>
    <p>Preço: €<?= number_format($sv['base_price'],2) ?> – Entrega em <?= intval($sv['delivery_time_days']) ?> dias</p>
    <?php if ($erro): ?>
      <div class="erro"><?= htmlspecialchars($erro) ?></div>
    <?php endif; ?>
    <form method="post" class="form-login">
      <label for="card_number">Número do Cartão:</label>
      <input id="card_number" name="card_number" required>

      <label for="card_holder">Titular do Cartão:</label>
      <input id="card_holder" name="card_holder" required>

      <label for="card_expiry">Validade (MM/AA):</label>
      <input id="card_expiry" name="card_expiry" placeholder="MM/AA" required>

      <button type="submit">Pagar €<?= number_format($sv['base_price'],2) ?></button>
    </form>
    <p class="dashboard-return">
      <a href="services.php?id=<?= $serviceId ?>" class="btn-primary">← Voltar</a>
    </p>
  </main>
  <?php include __DIR__ . '/../templates/footer.php'; ?>
</body>
</html>