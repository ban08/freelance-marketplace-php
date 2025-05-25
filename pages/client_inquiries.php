<?php
require_once __DIR__.'/../templates/bootstrap.php';
if (empty($_SESSION['user']) || $_SESSION['user']['tipo']!=='cliente') {
    header('Location: dashboard.php'); exit;
}
$me = $_SESSION['user'];

// fetch distinct freelancers you've messaged
$stmt = $pdo->prepare("
  SELECT
    u.id   AS freelancer_id,
    u.name AS freelancer_name,
    MAX(m.sent_at) AS last_sent
  FROM messages m
  JOIN users u 
    ON (m.sender_id = u.id AND m.receiver_id = ?)
    OR (m.receiver_id = u.id AND m.sender_id = ?)
  WHERE u.tipo = 'freelancer'
  GROUP BY u.id, u.name
  ORDER BY last_sent DESC
");
$stmt->execute([$me['id'],$me['id']]);
$freelancers = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt">
<head>
  <meta charset="UTF-8">
  <title>Mensagens – ltw07g06</title>
  <link rel="stylesheet" href="../css/style.css">
</head>
<body>
  <?php include __DIR__.'/../templates/header.php'; ?>
  <main class="dashboard-page">
    <h2 class="dashboard-title">Freelancers Contactados</h2>
    <?php if($freelancers): ?>
      <table class="manage-table">
        <thead>
          <tr>
            <th>Freelancer</th>
            <th>Última Mensagem</th>
            <th>Ações</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach($freelancers as $f): ?>
          <tr>
            <td><?= htmlspecialchars($f['freelancer_name']) ?></td>
            <td><?= date('d/m/Y H:i',strtotime($f['last_sent'])) ?></td>
            <td>
              <a href="messages.php?client=<?= $f['freelancer_id'] ?>"
                 class="btn-primary">Ver Conversa</a>
              <!-- New custom order button -->
              <a href="request_custom_order.php?service=&freelancer=<?= $f['freelancer_id'] ?>"
                 class="btn-primary"
                 style="background:#0066cc; margin-left:.5em;">
                Novo Pedido Customizado
              </a>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    <?php else: ?>
      <p>Não há conversas. Comece a <a href="browse.php">explorar serviços</a>.</p>
    <?php endif; ?>
  </main>
  <?php include __DIR__.'/../templates/footer.php'; ?>
</body>
</html>