<?php
require_once __DIR__.'/../templates/bootstrap.php';
if (empty($_SESSION['user']) || $_SESSION['user']['tipo']!=='freelancer') {
    header('Location: dashboard.php');
    exit;
}
$me = $_SESSION['user'];

// handle “Marcar como concluído”
if ($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['action']??'')==='complete_order') {
    $oid = intval($_POST['order_id']);
    // só se for pedido do próprio freelancer
    $pdo->prepare("
      UPDATE orders
         SET status='completed', completion_date=CURRENT_TIMESTAMP
       WHERE id=? 
         AND service_id IN (
           SELECT id FROM services WHERE freelancer_id=?
         )
    ")->execute([$oid, $me['id']]);
    header('Location: orders.php');
    exit;
}

// buscar encomendas deste freelancer
$stmt = $pdo->prepare("
  SELECT o.id,
         s.title   AS servico,
         u.name    AS cliente,
         o.price,
         o.order_date,
         o.status,
         o.completion_date
    FROM orders o
    JOIN services s  ON o.service_id = s.id
    JOIN users    u  ON o.client_id  = u.id
   WHERE s.freelancer_id = ?
   ORDER BY o.order_date DESC
");
$stmt->execute([$me['id']]);
$orders = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt">
<head>
  <meta charset="UTF-8">
  <title>Encomendas – ltw07g06</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link rel="stylesheet" href="../css/style.css">
</head>
<body>
  <?php include __DIR__.'/../templates/header.php'; ?>
  <main class="dashboard-page">
    <h2 class="dashboard-title">Encomendas Recebidas</h2>
    <section class="dashboard-section">
    <?php if ($orders): ?>
      <table class="manage-table">
        <thead>
          <tr>
            <th>Serviço</th><th>Cliente</th><th>Preço</th>
            <th>Data</th><th>Status</th><th>Ações</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach($orders as $o): ?>
          <tr>
            <td><?= htmlspecialchars($o['servico']) ?></td>
            <td><?= htmlspecialchars($o['cliente']) ?></td>
            <td>€<?= number_format($o['price'],2) ?></td>
            <td><?= date('d/m/Y',strtotime($o['order_date'])) ?></td>
            <td>
              <?= $o['status']==='completed' 
                   ? 'Concluída em '.date('d/m/Y',strtotime($o['completion_date'])) 
                   : ucfirst($o['status']) ?>
            </td>
            <td>
              <?php if ($o['status']!=='completed'): ?>
              <form method="post" style="display:inline">
                <input type="hidden" name="order_id" value="<?= $o['id'] ?>">
                <button type="submit" name="action" value="complete_order"
                        onclick="return confirm('Marcar esta encomenda como concluída?')">
                  Concluir
                </button>
              </form>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    <?php else: ?>
      <p>Não há encomendas para mostrar.</p>
    <?php endif; ?>
    </section>

    <p class="dashboard-return">
      <a href="dashboard.php" class="btn-primary">← Voltar ao Dashboard</a>
    </p>
  </main>
  <?php include __DIR__.'/../templates/footer.php'; ?>
</body>
</html>