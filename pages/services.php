<?php 
require_once __DIR__ . '/../templates/bootstrap.php';

// pegar ID do serviço
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    echo "<p>Serviço inválido.</p>";
    exit;
}

// buscar serviço + freelancer
$stmt = $pdo->prepare("
  SELECT 
    s.*,
    u.name  AS freelancer_nome,
    u.email AS freelancer_email
  FROM services s
  JOIN users    u ON s.freelancer_id = u.id
  WHERE s.id = ?
");
$stmt->execute([$id]);
$serv = $stmt->fetch();
if (!$serv) {
    echo "<p>Serviço não encontrado.</p>";
    exit;
}

// puxar primeira imagem
$imgStmt = $pdo->prepare("
  SELECT image_path
    FROM service_images
   WHERE service_id = ?
   ORDER BY display_order
   LIMIT 1
");
$imgStmt->execute([$id]);
$img = $imgStmt->fetchColumn() ?: 'img/default.png';

// buscar avaliações
$stmtRev = $pdo->prepare("
  SELECT rv.rating, rv.comment, rv.created_at AS date, u.name AS client_name
    FROM reviews rv
    JOIN users u ON rv.client_id = u.id
   WHERE rv.service_id = ?
   ORDER BY date DESC
");
$stmtRev->execute([$id]);
$reviews = $stmtRev->fetchAll();

// verificar se cliente pode avaliar
$canReview = false;
if (!empty($_SESSION['user']) && $_SESSION['user']['tipo'] === 'cliente') {
    $clientId = $_SESSION['user']['id'];

    // corrigido: usar fetchColumn() no statement, não no PDO
    $stmtOrd = $pdo->prepare("
      SELECT 1
        FROM orders
       WHERE service_id = ?
         AND client_id = ?
         AND status = 'completed'
    ");
    $stmtOrd->execute([$id, $clientId]);
    $didComplete = (bool) $stmtOrd->fetchColumn();

    $stmtChk = $pdo->prepare("
      SELECT 1
        FROM reviews
       WHERE service_id = ?
         AND client_id = ?
    ");
    $stmtChk->execute([$id, $clientId]);
    $didReview = (bool) $stmtChk->fetchColumn();

    $canReview = $didComplete && !$didReview;
}

// tratar POST de avaliação
if ($_SERVER['REQUEST_METHOD']==='POST' && $canReview) {
  $rating = intval($_POST['rating']);
  if ($rating>=1 && $rating<=5) {
    $pdo->prepare("
      INSERT INTO reviews (client_id, service_id, rating, comment)
      VALUES (?,?,?,?)
    ")->execute([$clientId, $id, $rating, '']); // no comment any more
    header("Location: services.php?id=$id");
    exit;
  }
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
  <meta charset="UTF-8">
  <title><?= htmlspecialchars($serv['title']) ?> – ltw07g06</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="../css/style.css">
</head>
<body>
  <?php include __DIR__ . '/../templates/header.php'; ?>

  <main class="service-detail-page">
    <div class="service-detail">
      <div class="service-info">
        <img src="/<?= htmlspecialchars($img) ?>" alt="Imagem do serviço">
        <h1><?= htmlspecialchars($serv['title']) ?></h1>
        <p><?= nl2br(htmlspecialchars($serv['description'])) ?></p>
        <ul>
          <li><strong>Preço:</strong> €<?= number_format($serv['base_price'],2) ?></li>
          <li><strong>Entrega:</strong> <?= intval($serv['delivery_time_days']) ?> dias</li>
        </ul>
      </div>
      <aside class="service-aside">
        <h3>Freelancer</h3>
        <p><?= htmlspecialchars($serv['freelancer_nome']) ?></p>
        <p>Email: 
          <a href="mailto:<?= htmlspecialchars($serv['freelancer_email']) ?>">
            <?= htmlspecialchars($serv['freelancer_email']) ?>
          </a>
        </p>

        <?php if (!empty($_SESSION['user']) && $_SESSION['user']['tipo']==='cliente'): ?>
          <a href="checkout.php?service=<?= $id ?>" class="btn-primary">
            Contratar
          </a>
          <!-- new message button -->
          <a href="messages.php?client=<?= $serv['freelancer_id'] ?>"
             class="btn-secondary"
             style="margin-left:0.5em;">
            💬 Enviar Mensagem
          </a>
        <?php endif; ?>
      </aside>
    </div>

    <section class="service-reviews">
      <h2>Avaliações</h2>
      <?php if ($reviews): ?>
        <?php foreach($reviews as $r): ?>
        <div class="review">
          <p>⭐ <?= $r['rating'] ?> – <?= htmlspecialchars($r['client_name']) ?></p>
          <?php if ($r['comment']): ?>
            <blockquote><?= nl2br(htmlspecialchars($r['comment'])) ?></blockquote>
          <?php endif; ?>
          <small><?= date('d/m/Y H:i',strtotime($r['date'])) ?></small>
        </div>
        <?php endforeach; ?>
      <?php else: ?>
        <p>Nenhuma avaliação ainda.</p>
      <?php endif; ?>
    </section>

    <?php if ($canReview): ?>
    <section class="review-form">
      <h2>Deixe sua avaliação</h2>
      <form method="post">
        <label for="rating">Nota:</label>
        <select id="rating" name="rating" required>
          <option value="">– selecione –</option>
          <?php for($i=5;$i>=1;$i--): ?>
            <option value="<?= $i ?>"><?= $i ?>★</option>
          <?php endfor; ?>
        </select>

        <button type="submit" class="btn-primary">Enviar Avaliação</button>
      </form>
    </section>
    <?php endif; ?>

    <p class="dashboard-return">
      <a href="browse.php" class="btn-primary">← Voltar à Explorar</a>
    </p>
  </main>

  <?php include __DIR__ . '/../templates/footer.php'; ?>
</body>
</html>
