<?php 
require_once __DIR__ . '/../templates/bootstrap.php';
require_once __DIR__ . '/../templates/header.php';

// pegar ID do serviço
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    echo "<p>Serviço inválido.</p>";
    exit;
}

// buscar serviço + freelancer nas tabelas corretas
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

// puxar primeira imagem (se existir)
$imgStmt = $pdo->prepare("
  SELECT image_path
    FROM service_images
   WHERE service_id = ?
   ORDER BY display_order
   LIMIT 1
");
$imgStmt->execute([$id]);
$img = $imgStmt->fetchColumn() ?: 'img/default.png';

// buscar avaliações, etc… (mantém seu código de reviews)
$stmtRev = $pdo->prepare("
  SELECT rv.rating, rv.comment, rv.created_at AS date, u.name AS client_name
    FROM reviews rv
    JOIN users u ON rv.client_id = u.id
   WHERE rv.service_id = ?
   ORDER BY date DESC
");
$stmtRev->execute([$id]);
$reviews = $stmtRev->fetchAll();

// —–– Can the current client review? —––
$canReview    = false;
if (!empty($_SESSION['user']) && $_SESSION['user']['tipo'] === 'cliente') {
    $clientId    = $_SESSION['user']['id'];
    // did they complete an order?
    $stmtOrd     = $pdo->prepare("
      SELECT 1 FROM orders 
       WHERE service_id = ? 
         AND client_id = ? 
         AND status = 'completed'
    ");
    $stmtOrd->execute([$id, $clientId]);
    $didComplete = (bool)$stmtOrd->fetch();
    // have they already reviewed?
    $stmtChk     = $pdo->prepare("
      SELECT 1 FROM reviews 
       WHERE service_id = ? 
         AND client_id = ?
    ");
    $stmtChk->execute([$id, $clientId]);
    $didReview   = (bool)$stmtChk->fetch();
    $canReview   = $didComplete && !$didReview;
}

// —–– Handle review POST —––
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['rating']) && $canReview) {
    $rating  = intval($_POST['rating']);
    $comment = trim($_POST['comment'] ?? '');
    if ($rating >= 1 && $rating <= 5) {
        $ins = $pdo->prepare("
          INSERT INTO reviews (client_id, service_id, rating, comment)
          VALUES (?, ?, ?, ?)
        ");
        $ins->execute([$clientId, $id, $rating, $comment]);
        // Redirecionar para evitar reenvio do formulário
        header("Location: services.php?id=$id");
        exit;
    }
}
?>
<div class="service-detail-page">
  <h2><?= htmlspecialchars($serv['title']) ?></h2>
  <div class="service-detail">
    <div class="service-info">
      <img src="/<?= htmlspecialchars($img) ?>" 
           alt="Imagem do serviço" style="max-width:100%">
      <p><?= nl2br(htmlspecialchars($serv['description'])) ?></p>
      <p><strong>Preço:</strong> €<?= number_format($serv['base_price'],2) ?></p>
      <p><strong>Entrega:</strong> <?= intval($serv['delivery_time_days']) ?> dias</p>
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
      <?php endif; ?>
    </aside>
  </div>

  <!-- Média e lista de avaliações -->
  <div class="service-reviews">
    <?php if (count($reviews) > 0): ?>
      <p class="servico-rating">🌟 <strong><?= $media ?></strong> de 5 (<?= $totalReviews ?> avaliações)</p>
    <?php else: ?>
      <p class="servico-rating">Este serviço ainda não tem avaliações.</p>
    <?php endif; ?>

    <h3>Avaliações dos Clientes</h3>
    <?php foreach($reviews as $rev): ?>
      <div class="review">
        <p class="review-rating"><strong>Nota:</strong> <?= $rev['rating'] ?>/5</p>
        <p class="review-comment">"<?= htmlspecialchars($rev['comentario']) ?>"</p>
        <p class="review-meta">por <strong><?= htmlspecialchars($rev['cliente_nome']) ?></strong> em 
                               <?= date('d/m/Y', strtotime($rev['data'])) ?></p>
      </div>
    <?php endforeach; ?>
  </div>

  <!-- Formulário de avaliação, se aplicável -->
  <?php if ($usuarioLogado && ($_SESSION['user']['tipo'] === 'cliente') && $comprou && !$jaAvaliou): ?>
  <div class="review-form">
    <h3>Deixe a sua Avaliação</h3>
    <form method="POST">
      <label>Classificação:</label>
      <select name="rating" required>
        <option value="">--Escolha--</option>
        <option value="5">5 – Excelente</option>
        <option value="4">4 – Bom</option>
        <option value="3">3 – Médio</option>
        <option value="2">2 – Fraco</option>
        <option value="1">1 – Terrível</option>
      </select><br>
      <label>Comentário:</label><br>
      <textarea name="comentario" rows="3" maxlength="500"></textarea><br>
      <button type="submit" name="submit_review">Enviar Avaliação</button>
    </form>
  </div>
  <?php endif; ?>

  <!-- Botão para solicitar pedido personalizado -->
  <?php if (!empty($_SESSION['user']) && $_SESSION['user']['tipo']==='cliente'): ?>
    <a href="request_custom_order.php?service=<?= $id ?>"
       class="btn-primary"
       style="margin-top:1em; display:inline-block;">
      📩 Solicitar Pedido Personalizado
    </a>
  <?php endif; ?>

  <p><a href="browse.php" class="btn-primary">← Voltar à Explorar</a></p>
</div>
<?php require __DIR__.'/../templates/footer.php'; ?>
