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
    u.email AS freelancer_email,
    c.name  AS category
  FROM services s
  JOIN users      u ON s.freelancer_id = u.id
  LEFT JOIN categories c ON s.category_id = c.id
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
$img = $imgStmt->fetchColumn() ?: 'img/default-service.jpg';

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
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
  <?php include __DIR__ . '/../templates/header.php'; ?>

  <main class="service-detail-page">
    <!-- Service Hero Section -->
    <section class="service-hero">
      <div class="container">
        <div class="service-hero-content">
          <div class="service-image-container">
            <img src="../<?= htmlspecialchars($img) ?>" alt="Imagem do serviço" class="service-main-image">
          </div>
          <div class="service-hero-info">
            <div class="service-breadcrumb">
              <a href="../index.php">Início</a> 
              <span class="breadcrumb-separator">></span>
              <a href="browse.php">Explorar</a>
              <span class="breadcrumb-separator">></span>
              <span class="current">Serviço</span>
            </div>
            <h1 class="service-title"><?= htmlspecialchars($serv['title']) ?></h1>
            <div class="service-meta">
              <div class="service-price">
                <i class="fas fa-euro-sign"></i>
                <span class="price-amount">€<?= number_format($serv['base_price'],2) ?></span>
              </div>
              <div class="service-delivery">
                <i class="fas fa-clock"></i>
                <span><?= intval($serv['delivery_time_days']) ?> dias de entrega</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Service Details Section -->
    <section class="service-content">
      <div class="container">
        <div class="service-layout">
          <div class="service-main">
            <div class="service-description-card">
              <h2><i class="fas fa-info-circle"></i> Descrição do Serviço</h2>
              <div class="service-description">
                <?= nl2br(htmlspecialchars($serv['description'])) ?>
              </div>
            </div>
          </div>
          
          <aside class="service-sidebar">
            <div class="freelancer-card">
              <div class="freelancer-header">
                <div class="freelancer-avatar">
                  <i class="fas fa-user-circle"></i>
                </div>
                <div class="freelancer-info">
                  <h3>Freelancer</h3>
                  <p class="freelancer-name"><?= htmlspecialchars($serv['freelancer_nome']) ?></p>
                  <a href="mailto:<?= htmlspecialchars($serv['freelancer_email']) ?>" class="freelancer-email">
                    <i class="fas fa-envelope"></i>
                    <?= htmlspecialchars($serv['freelancer_email']) ?>
                  </a>
                </div>
              </div>
              
              <?php if (!empty($_SESSION['user']) && $_SESSION['user']['tipo']==='cliente'): ?>
                <div class="action-buttons">
                  <a href="checkout.php?service=<?= $id ?>" class="btn btn-primary hire-btn">
                    <i class="fas fa-handshake"></i>
                    Contratar Serviço
                  </a>
                  <a href="messages.php?client=<?= $serv['freelancer_id'] ?>" class="btn btn-secondary message-btn">
                    <i class="fas fa-comments"></i>
                    Enviar Mensagem
                  </a>
                </div>
              <?php endif; ?>
            </div>

            <div class="service-summary-card">
              <h3><i class="fas fa-list-check"></i> Resumo do Serviço</h3>
              <div class="summary-items">
                <div class="summary-item">
                  <div class="summary-label">Preço Base</div>
                  <div class="summary-value price">€<?= number_format($serv['base_price'],2) ?></div>
                </div>
                <div class="summary-item">
                  <div class="summary-label">Tempo de Entrega</div>
                  <div class="summary-value"><?= intval($serv['delivery_time_days']) ?> dias</div>
                </div>
                <div class="summary-item">
                  <div class="summary-label">Status</div>
                  <div class="summary-value"><?= ucfirst($serv['status']) ?></div>
                </div>
              </div>
            </div>
          </aside>
        </div>
      </div>
    </section>

    <!-- Reviews Section -->
    <section class="service-reviews">
      <div class="container">
        <div class="reviews-header">
          <h2><i class="fas fa-star"></i> Avaliações dos Clientes</h2>
          <?php if ($reviews): ?>
            <?php 
            $avgRating = array_sum(array_column($reviews, 'rating')) / count($reviews);
            $reviewCount = count($reviews);
            ?>
            <div class="reviews-summary">
              <div class="average-rating">
                <span class="rating-number"><?= number_format($avgRating, 1) ?></span>
                <div class="rating-stars">
                  <?php for($i = 1; $i <= 5; $i++): ?>
                    <i class="fas fa-star <?= $i <= round($avgRating) ? 'active' : '' ?>"></i>
                  <?php endfor; ?>
                </div>
                <span class="review-count">(<?= $reviewCount ?> avaliação<?= $reviewCount != 1 ? 'ões' : '' ?>)</span>
              </div>
            </div>
          <?php endif; ?>
        </div>
        
        <div class="reviews-grid">
          <?php if ($reviews): ?>
            <?php foreach($reviews as $r): ?>
            <div class="review-card">
              <div class="review-header">
                <div class="reviewer-info">
                  <div class="reviewer-avatar">
                    <i class="fas fa-user-circle"></i>
                  </div>
                  <div class="reviewer-details">
                    <h4 class="reviewer-name"><?= htmlspecialchars($r['client_name']) ?></h4>
                    <div class="review-rating">
                      <?php for($i = 1; $i <= 5; $i++): ?>
                        <i class="fas fa-star <?= $i <= $r['rating'] ? 'active' : '' ?>"></i>
                      <?php endfor; ?>
                    </div>
                  </div>
                </div>
                <div class="review-date">
                  <i class="fas fa-calendar-alt"></i>
                  <?= date('d/m/Y', strtotime($r['date'])) ?>
                </div>
              </div>
              <?php if ($r['comment']): ?>
                <div class="review-content">
                  <?= nl2br(htmlspecialchars($r['comment'])) ?>
                </div>
              <?php endif; ?>
            </div>
            <?php endforeach; ?>
          <?php else: ?>
            <div class="no-reviews">
              <div class="no-reviews-icon">
                <i class="fas fa-star-half-alt"></i>
              </div>
              <h3>Ainda sem avaliações</h3>
              <p>Este serviço ainda não recebeu avaliações. Seja o primeiro a avaliar!</p>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </section>

    <?php if ($canReview): ?>
    <!-- Review Form Section -->
    <section class="review-form-section">
      <div class="container">
        <div class="review-form-card">
          <div class="form-header">
            <h2><i class="fas fa-pen"></i> Deixe sua Avaliação</h2>
            <p>Compartilhe sua experiência com este serviço</p>
          </div>
          <form method="post" class="review-form">
            <div class="form-group">
              <label for="rating"><i class="fas fa-star"></i> Sua Avaliação:</label>
              <div class="rating-select">
                <select id="rating" name="rating" required>
                  <option value="">Selecione uma nota</option>
                  <?php for($i=5;$i>=1;$i--): ?>
                    <option value="<?= $i ?>"><?= $i ?> estrela<?= $i != 1 ? 's' : '' ?></option>
                  <?php endfor; ?>
                </select>
                <i class="fas fa-chevron-down"></i>
              </div>
            </div>
            <div class="form-actions">
              <button type="submit" class="btn btn-primary submit-review">
                <i class="fas fa-paper-plane"></i>
                Enviar Avaliação
              </button>
            </div>
          </form>
        </div>
      </div>
    </section>
    <?php endif; ?>

    <!-- Navigation Section -->
    <section class="service-navigation">
      <div class="container">
        <div class="nav-actions">
          <a href="browse.php" class="btn btn-outline back-btn">
            <i class="fas fa-arrow-left"></i>
            Voltar à Explorar
          </a>
          <a href="../index.php" class="btn btn-outline home-btn">
            <i class="fas fa-home"></i>
            Página Inicial
          </a>
        </div>
      </div>
    </section>
  </main>

  <?php include __DIR__ . '/../templates/footer.php'; ?>
</body>
</html>
