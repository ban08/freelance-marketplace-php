<?php
require_once __DIR__ . '/../templates/bootstrap.php';
if (empty($_SESSION['user']) || $_SESSION['user']['tipo'] !== 'freelancer') {
    header('Location: dashboard.php');
    exit;
}
$freelancerId = $_SESSION['user']['id'];

// Handle status changes or deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $serviceId = intval($_POST['service_id'] ?? 0);
    $action    = $_POST['action'] ?? '';
    if ($serviceId && in_array($action, ['activate','pause','delete'], true)) {
        if ($action === 'delete') {
            $stmt = $pdo->prepare("DELETE FROM services WHERE id = ? AND freelancer_id = ?");
            $stmt->execute([$serviceId, $freelancerId]);
        } else {
            $newStatus = $action === 'activate' ? 'active' : 'paused';
            $stmt = $pdo->prepare("UPDATE services SET status = ? WHERE id = ? AND freelancer_id = ?");
            $stmt->execute([$newStatus, $serviceId, $freelancerId]);
        }
    }
    header('Location: manage_services.php');
    exit;
}

// Fetch all services for this freelancer
$stmt = $pdo->prepare("
    SELECT s.id, s.title, c.name AS category,
           s.base_price, s.delivery_time_days, s.status
      FROM services s
      JOIN categories c ON s.category_id = c.id
     WHERE s.freelancer_id = ?
     ORDER BY s.created_at DESC
");
$stmt->execute([$freelancerId]);
$services = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt">
<head>
  <meta charset="UTF-8">
  <title>Gerir Serviços – ltw07g06</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link rel="stylesheet" href="../css/style.css">
</head>
<body>
  <?php include __DIR__ . '/../templates/header.php'; ?>

  <main class="dashboard-page">
    <h2 class="dashboard-title">Gerir Serviços</h2>
    <section class="dashboard-section">
      <?php if ($services): ?>
        <table class="manage-table">
          <thead>
            <tr>
              <th>Título</th>
              <th>Categoria</th>
              <th>Preço (€)</th>
              <th>Entrega (dias)</th>
              <th>Status</th>
              <th>Ações</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($services as $sv): ?>
            <tr>
              <td><?= htmlspecialchars($sv['title']) ?></td>
              <td><?= htmlspecialchars($sv['category']) ?></td>
              <td><?= number_format($sv['base_price'],2) ?></td>
              <td><?= intval($sv['delivery_time_days']) ?></td>
              <td><?= $sv['status'] === 'active' ? 'Ativo' : 'Pausado' ?></td>
              <td>
                <form method="post" style="display:inline">
                  <input type="hidden" name="service_id" value="<?= $sv['id'] ?>">
                  <?php if ($sv['status'] === 'active'): ?>
                    <button type="submit" name="action" value="pause">⏸ Pausar</button>
                  <?php else: ?>
                    <button type="submit" name="action" value="activate">▶️ Ativar</button>
                  <?php endif; ?>
                </form>
                <a href="edit_service.php?id=<?= $sv['id'] ?>">✏️ Editar</a>
                <form method="post" style="display:inline" 
                      onsubmit="return confirm('Remover serviço?');">
                  <input type="hidden" name="service_id" value="<?= $sv['id'] ?>">
                  <button type="submit" name="action" value="delete">🗑 Remover</button>
                </form>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php else: ?>
        <p>Não existem serviços registados. <a href="new_service.php" class="btn-primary">+ Criar Novo Serviço</a></p>
      <?php endif; ?>
    </section>

    <p class="dashboard-return">
      <a href="dashboard.php" class="btn-primary">← Voltar ao Dashboard</a>
    </p>
  </main>

  <?php include __DIR__ . '/../templates/footer.php'; ?>
</body>
</html>