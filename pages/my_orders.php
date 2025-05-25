<?php
require_once __DIR__ . '/../templates/bootstrap.php';
if (empty($_SESSION['user']) || $_SESSION['user']['tipo']!=='cliente') {
    header('Location: login.php'); exit;
}
$clientId = $_SESSION['user']['id'];

$stmt = $pdo->prepare("
  SELECT o.id,
         s.title   AS servico,
         u.name    AS freelancer,
         o.price,
         o.order_date,
         o.status,
         o.completion_date
    FROM orders o
    JOIN services s ON o.service_id = s.id
    JOIN users    u ON s.freelancer_id = u.id
   WHERE o.client_id = ?
   ORDER BY o.order_date DESC
");
$stmt->execute([$clientId]);
$orders = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt">
<head>
  <meta charset="UTF-8">
  <title>Minhas Encomendas – ltw07g06</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link rel="stylesheet" href="../css/style.css">
</head>
<body>
  <?php include __DIR__ . '/../templates/header.php'; ?>
  <main class="dashboard-page">
    <h2 class="dashboard-title">Minhas Encomendas</h2>
    <section class="dashboard-section">
      <?php if ($orders): ?>
        <table class="manage-table">
          <thead>
            <tr>
              <th>Serviço</th><th>Freelancer</th><th>Preço</th>
              <th>Data</th><th>Status</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach($orders as $o): ?>
            <tr>
              <td><?= htmlspecialchars($o['servico']) ?></td>
              <td><?= htmlspecialchars($o['freelancer']) ?></td>
              <td>€<?= number_format($o['price'],2) ?></td>
              <td><?= date('d/m/Y',strtotime($o['order_date'])) ?></td>
              <td>
                <?= ucfirst($o['status']) ?>
                <?php if ($o['status']==='completed'): ?>
                  <br><small>(<?= date('d/m/Y',strtotime($o['completion_date'])) ?>)</small>
                <?php endif; ?>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php else: ?>
        <p>Não tem encomendas ainda. <a href="browse.php" class="btn-primary">Explorar Serviços</a></p>
      <?php endif; ?>
    </section>
    <p class="dashboard-return">
      <a href="dashboard.php" class="btn-primary">← Voltar ao Dashboard</a>
    </p>
  </main>
  <?php include __DIR__ . '/../templates/footer.php'; ?>
</body>
</html>