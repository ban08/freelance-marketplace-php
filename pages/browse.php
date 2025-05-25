<?php
require_once __DIR__ . '/../templates/bootstrap.php';

// carregar categorias (para o filtro)
$cats = $pdo
    ->query("SELECT id,name FROM categories ORDER BY name")
    ->fetchAll();

// ler filtros da querystring
$cat       = intval($_GET['categoria']   ?? 0);
$minPrice  = floatval($_GET['min_price'] ?? 0);
$maxPrice  = floatval($_GET['max_price'] ?? 0);
$minRating = floatval($_GET['min_rating']?? 0);

// montar WHERE dinâmico
$where  = ["s.status = 'active'"];
$params = [];
if ($cat)      { $where[] = "s.category_id = ?";   $params[] = $cat; }
if ($minPrice) { $where[] = "s.base_price >= ?";   $params[] = $minPrice; }
if ($maxPrice) { $where[] = "s.base_price <= ?";   $params[] = $maxPrice; }

// consulta com GROUP BY para AVG(r.rating)
$sql = "
  SELECT
    s.id,
    s.title,
    s.base_price,
    s.delivery_time_days,
    c.name           AS category,
    COALESCE(AVG(r.rating),0) AS avg_rating,
    COUNT(r.id)             AS reviews_count
  FROM services s
  JOIN categories c   ON s.category_id = c.id
  LEFT JOIN reviews r ON r.service_id  = s.id
  WHERE ". implode(' AND ', $where) ."
  GROUP BY s.id
  HAVING avg_rating >= ?
  ORDER BY avg_rating DESC, s.base_price ASC
";

$params[] = $minRating;
$stmt     = $pdo->prepare($sql);
$stmt->execute($params);
$services = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt">
<head>
  <meta charset="UTF-8">
  <title>Explorar Serviços – ltw07g06</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link rel="stylesheet" href="../css/style.css">
</head>
<body>
  <?php include __DIR__ . '/../templates/header.php'; ?>

  <main class="servicos-destaque">
    <h2>Explorar Serviços</h2>

    <!-- sua Toolbar de filtros -->
    <div class="search-toolbar">
      <form method="get" class="form-filtros">
        <!-- Categoria -->
        <div class="filter-item">
          <label for="f-cat">Categoria</label>
          <select id="f-cat" name="categoria">
            <option value="">Todas</option>
            <?php foreach($cats as $c): ?>
              <option value="<?= $c['id'] ?>"<?= $c['id']==$cat?' selected':'' ?>>
                <?= htmlspecialchars($c['name']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <!-- Preço mínimo -->
        <div class="filter-item">
          <label for="f-min">Preço ≥</label>
          <input id="f-min" type="number" step="0.01" name="min_price"
                 placeholder="0.00" value="<?= $minPrice ?>">
        </div>
        <!-- Preço máximo -->
        <div class="filter-item">
          <label for="f-max">Preço ≤</label>
          <input id="f-max" type="number" step="0.01" name="max_price"
                 placeholder="0.00" value="<?= $maxPrice ?>">
        </div>
        <!-- Avaliação mínima -->
        <div class="filter-item">
          <label for="f-rating">Avaliação ≥</label>
          <select id="f-rating" name="min_rating">
            <option value="0">Qualquer</option>
            <?php for($r=1;$r<=5;$r++): ?>
              <option value="<?= $r ?>"<?= $r==$minRating?' selected':'' ?>>
                <?= $r ?>★
              </option>
            <?php endfor; ?>
          </select>
        </div>
        <!-- Botões -->
        <div class="filter-actions">
          <button type="submit" class="btn-primary"><span>🔎</span> Filtrar</button>
          <a href="browse.php" class="btn-clear">Limpar</a>
        </div>
      </form>
    </div>

    <!-- grid de serviços -->
    <div class="servicos-grid">
      <?php if ($services): ?>
        <?php foreach($services as $s): ?>
          <div class="servico-card">
            <h3><?= htmlspecialchars($s['title']) ?></h3>
            <p><strong>Categoria:</strong> <?= htmlspecialchars($s['category']) ?></p>
            <p><strong>Preço:</strong> €<?= number_format($s['base_price'],2) ?></p>
            <p><strong>Entrega:</strong> <?= intval($s['delivery_time_days']) ?> dias</p>
            <p>
              <strong>Avaliação:</strong>
              <?= round($s['avg_rating'],1) ?>★ (<?= $s['reviews_count'] ?>)
            </p>
            <a href="services.php?id=<?= $s['id'] ?>" class="btn-primary">
              Ver Serviço
            </a>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <p style="text-align:center;">Nenhum serviço encontrado.</p>
      <?php endif; ?>
    </div>
  </main>

  <?php include __DIR__ . '/../templates/footer.php'; ?>
</body>
</html>