<?php
require_once __DIR__ . '/../templates/bootstrap.php';
if (empty($_SESSION['user']) || $_SESSION['user']['tipo']!=='freelancer') {
    header('Location: dashboard.php');
    exit;
}
$me = $_SESSION['user'];

$stmt = $pdo->prepare("
  SELECT 
    u.id   AS client_id,
    u.name AS client_name,
    MAX(m.sent_at) AS last_sent
  FROM messages m
  JOIN users    u ON m.sender_id = u.id
  WHERE m.receiver_id = ?
  GROUP BY u.id, u.name
  ORDER BY last_sent DESC
");
$stmt->execute([$me['id']]);
$clients = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt">
<head>
  <meta charset="UTF-8">
  <title>Consultas – ltw07g06</title>
  <link rel="stylesheet" href="../css/style.css">
</head>
<body>
  <?php include __DIR__.'/../templates/header.php'; ?>
  <main class="inquiries-page">
    <h2 class="dashboard-title">Consultas dos Clientes</h2>
    <?php if($clients): ?>
      <ul class="inquiry-list">
        <?php foreach($clients as $c): ?>
          <li>
            <a href="messages.php?client=<?= $c['client_id'] ?>">
              <?= htmlspecialchars($c['client_name']) ?>
            </a>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php else: ?>
      <p>Não há consultas novas.</p>
    <?php endif; ?>
    <p class="dashboard-return">
      <a href="dashboard.php" class="btn-primary">← Voltar ao Dashboard</a>
    </p>
  </main>
  <?php include __DIR__.'/../templates/footer.php'; ?>
</body>
</html>