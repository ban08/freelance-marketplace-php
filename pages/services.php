<?php 
require_once __DIR__ . '/../templates/bootstrap.php';
require_once __DIR__ . '/../templates/header.php';

// Obter e validar ID do serviço da query string
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    echo "<p>ID de serviço inválido.</p>";
    require_once __DIR__ . '/../templates/footer.php';
    exit;
}

// Buscar serviço e freelancer
$stmt = $pdo->prepare("SELECT s.*, u.nome AS freelancer_nome, u.email AS freelancer_email
                       FROM servicos s 
                       JOIN utilizadores u ON s.utilizador_id = u.id
                       WHERE s.id = ?");
$stmt->execute([$id]);
$serv = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$serv) {
    echo "<h2>Serviço não encontrado.</h2>";
    require_once __DIR__ . '/../templates/footer.php';
    exit;
}

// (Opcional) Buscar avaliações do serviço atual
$stmt2 = $pdo->prepare("SELECT a.rating, a.comentario, a.data, u.nome AS cliente_nome 
                        FROM avaliacoes a 
                        JOIN utilizadores u ON a.cliente_id = u.id
                        WHERE a.servico_id = ?
                        ORDER BY a.data DESC");
$stmt2->execute([$id]);
$reviews = $stmt2->fetchAll(PDO::FETCH_ASSOC);

// Calcular média de avaliações
$media = 0;
$totalReviews = count($reviews);
if ($totalReviews > 0) {
    $soma = 0;
    foreach($reviews as $rev) {
        $soma += $rev['rating'];
    }
    $media = round($soma / $totalReviews, 1);
}

// Verificar se o utilizador pode avaliar
$jaAvaliou = false;
$usuarioLogado = $_SESSION['user']['id'] ?? null; // Ajuste conforme a sua lógica de sessão
if ($usuarioLogado) {
    $stmtCheck = $pdo->prepare("SELECT id FROM avaliacoes WHERE servico_id = ? AND cliente_id = ?");
    $stmtCheck->execute([$id, $usuarioLogado]);
    $jaAvaliou = (bool) $stmtCheck->fetch();
}

// Verificar se o logado é um cliente que comprou este serviço
$comprou = false;
if ($usuarioLogado && ($_SESSION['user']['tipo'] === 'cliente')) {
    $stmtEncomenda = $pdo->prepare("SELECT id FROM encomendas 
                                    WHERE servico_id = ? 
                                      AND cliente_id = ? 
                                      AND status = 'concluida'");
    $stmtEncomenda->execute([$id, $usuarioLogado]);
    $comprou = (bool) $stmtEncomenda->fetch();
}

// Processar envio de nova avaliação
if (isset($_POST['submit_review']) && $usuarioLogado && ($_SESSION['user']['tipo'] === 'cliente')) {
    $rating = intval($_POST['rating'] ?? 0);
    $comentario = trim($_POST['comentario'] ?? '');
    if ($rating >= 1 && $rating <= 5 && $comprou && !$jaAvaliou) {
        $stmtInsert = $pdo->prepare("INSERT INTO avaliacoes(servico_id, cliente_id, rating, comentario, data) 
                                     VALUES (?, ?, ?, ?, datetime('now'))");
        $stmtInsert->execute([$id, $usuarioLogado, $rating, $comentario]);
        // Redirecionar para recarregar e evitar reenvio do form
        header("Location: services.php?id=$id");
        exit;
    }
}
?>
<div class="service-detail-page">
  <h2><?= htmlspecialchars($serv['titulo']) ?></h2>
  <div class="service-detail">
    <div class="service-info">
      <img src="<?= htmlspecialchars($serv['imagem']) ?>" alt="Imagem do serviço">
      <p><?= nl2br(htmlspecialchars($serv['descricao'])) ?></p>
      <p><strong>Preço:</strong> €<?= htmlspecialchars($serv['preco']) ?></p>
      <p><strong>Duração:</strong> <?= htmlspecialchars($serv['duracao']) ?> horas</p>
    </div>
    <aside class="service-aside">
      <h3>Freelancer</h3>
      <p><?= htmlspecialchars($serv['freelancer_nome']) ?></p>
      <p>Email: <a href="mailto:<?= htmlspecialchars($serv['freelancer_email']) ?>">
                  <?= htmlspecialchars($serv['freelancer_email']) ?></a></p>
      <button onclick="alert('Funcionalidade de contratação a implementar')">
        Contratar
      </button>
    </aside>
  </div>

  <!-- Média e lista de avaliações -->
  <div class="service-reviews">
    <?php if ($totalReviews > 0): ?>
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

  <p><a href="services.php">← Voltar aos serviços</a></p>
</div>
<?php require_once __DIR__ . '/../templates/footer.php'; ?>
