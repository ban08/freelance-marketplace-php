<?php
require_once __DIR__ . '/../templates/bootstrap.php';
if (empty($_SESSION['user']) || $_SESSION['user']['tipo']!=='cliente') {
    header('Location: login.php'); exit;
}

$clientId  = $_SESSION['user']['id'];
$serviceId = filter_input(INPUT_GET,'service',FILTER_VALIDATE_INT);
$freelancerId = filter_input(INPUT_GET,'freelancer',FILTER_VALIDATE_INT);
if (!$serviceId && !$freelancerId) {
    header('Location: browse.php'); exit;
}

// Fetch service + freelancer
$stmt = $pdo->prepare("
  SELECT s.title, s.base_price, s.delivery_time_days,
         u.id AS freelancer_id, u.nome AS freelancer_name
    FROM services s 
    JOIN users u ON s.freelancer_id=u.id
   WHERE s.id=? AND s.status='active'
");
$stmt->execute([$serviceId]);
$sv = $stmt->fetch();
if (!$sv) {
    header('Location: browse.php'); exit;
}

$erro = '';
if ($_SERVER['REQUEST_METHOD']==='POST') {
    $price        = floatval($_POST['price'] ?? 0);
    $days         = intval($_POST['days'] ?? 0);
    $requirements = trim($_POST['requirements'] ?? '');

    if ($price <= 0 || $days <= 0 || !$requirements) {
        $erro = 'Por favor preencha todos os campos corretamente.';
    } else {
        // 1) criar ordem pendente
        $ins = $pdo->prepare("
          INSERT INTO orders 
            (client_id, service_id, price, requirements) 
          VALUES (?,?,?,?)
        ");
        $ins->execute([$clientId, $serviceId, $price, $requirements]);
        $orderId = $pdo->lastInsertId();

        // 2) enviar mensagem inicial ao freelancer
        $msg = $pdo->prepare("
          INSERT INTO messages 
            (sender_id,receiver_id,order_id,content) 
          VALUES (?,?,?,?)
        ");
        $content = "Solicitação de pedido personalizado:\n"
                 . "Preço: €{$price}\nDias: {$days}\nRequisitos: {$requirements}";
        $msg->execute([
          $clientId, 
          $sv['freelancer_id'], 
          $orderId, 
          $content
        ]);

        header('Location: orders.php');
        exit;
    }
}

if ($freelancerId) {
  // you came from client_inquiries; offer choice of service
  $svcStmt = $pdo->prepare("
    SELECT id, title 
      FROM services 
     WHERE freelancer_id = ? 
       AND status='active'
  ");
  $svcStmt->execute([$freelancerId]);
  $myServices = $svcStmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
  <meta charset="UTF-8">
  <title>Pedido Personalizado – ltw07g06</title>
  <link rel="stylesheet" href="../css/style.css">
</head>
<body>
  <?php include __DIR__ . '/../templates/header.php'; ?>
  <main class="dashboard-page" style="max-width:500px; margin:2em auto;">
    <h2 class="dashboard-title">Pedido a <?= htmlspecialchars($sv['freelancer_name']) ?></h2>
    <?php if ($erro): ?>
      <div class="erro"><?= htmlspecialchars($erro) ?></div>
    <?php endif; ?>
    <form method="post" class="form-login">
      <?php if (!empty($myServices)): ?>
        <label for="service">Serviço:</label>
        <select id="service" name="service" required>
          <option value="">– escolha –</option>
          <?php foreach($myServices as $s):?>
          <option value="<?= $s['id'] ?>"
            <?= $s['id']==$serviceId?'selected':''?>>
            <?= htmlspecialchars($s['title'])?>
          </option>
          <?php endforeach;?>
        </select>
      <?php endif; ?>

      <label for="price">Preço (€):</label>
      <input id="price" name="price" type="number" step="0.01"
             value="<?= htmlspecialchars($sv['base_price']) ?>" required>

      <label for="days">Entrega em (dias):</label>
      <input id="days" name="days" type="number"
             value="<?= htmlspecialchars($sv['delivery_time_days']) ?>" required>

      <label for="requirements">Detalhes / Requisitos:</label>
      <textarea id="requirements" name="requirements" rows="4" required><?= 
        htmlspecialchars($_POST['requirements'] ?? '') ?></textarea>

      <button type="submit" class="btn-primary">Enviar Solicitação</button>
    </form>
    <p class="dashboard-return">
      <a href="services.php?id=<?= $serviceId ?>" class="btn-primary">
        ← Voltar
      </a>
    </p>
  </main>
  <?php include __DIR__ . '/../templates/footer.php'; ?>
</body>
</html>