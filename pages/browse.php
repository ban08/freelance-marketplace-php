<?php
// Load application bootstrap (PDO, session, etc.)
require_once __DIR__ . '/../templates/bootstrap.php';

// Fetch all categories for the filter dropdown
$cats = $pdo
    ->query("SELECT id, name FROM categories ORDER BY name")
    ->fetchAll();

// Read filter inputs from query string (with default fallbacks)
$cat       = intval($_GET['categoria']   ?? 0);
$minPrice  = floatval($_GET['min_price'] ?? 0);
$maxPrice  = floatval($_GET['max_price'] ?? 0);
$minRating = floatval($_GET['min_rating']?? 0);
$search     = trim($_GET['search'] ?? '');

// Build dynamic WHERE clauses and collect parameters
$where  = ["s.status = 'active'"];
$params = [];
if ($search)   { $where[] = "s.title LIKE ?";            $params[] = "%{$search}%"; }
if ($cat)      { $where[] = "s.category_id = ?";         $params[] = $cat; }
if ($minPrice) { $where[] = "s.base_price >= ?";         $params[] = $minPrice; }
if ($maxPrice) { $where[] = "s.base_price <= ?";         $params[] = $maxPrice; }

// Main query: join categories, left-join reviews, group by service
$sql = "
  SELECT
    s.id,
    s.title,
    s.base_price,
    s.delivery_time_days,
    c.name           AS category,
    COALESCE(AVG(r.rating), 0) AS avg_rating,
    COUNT(r.id)              AS reviews_count
  FROM services s
  JOIN categories c   ON s.category_id = c.id
  LEFT JOIN reviews r ON r.service_id  = s.id
  WHERE " . implode(' AND ', $where) . "
  GROUP BY s.id
  HAVING COALESCE(AVG(r.rating), 0) >= ?
  ORDER BY COALESCE(AVG(r.rating), 0) DESC, s.base_price ASC
";

// Add minimum rating to parameters and execute
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
  <?php include __DIR__.'/../templates/header.php'; ?>

  <main class="servicos-destaque">
    <!-- Filter toolbar -->
    <div class="search-toolbar">
      <form method="get" id="filterForm" class="form-filtros">
        <!-- Keyword search -->
        <div class="filter-item filter-search">
          <input type="text" name="search" placeholder="🔍 Buscar serviços…" 
                 value="<?= htmlspecialchars($search) ?>">
        </div>

        <!-- Category filter -->
        <div class="filter-item">
          <label for="f-cat">Categoria</label>
          <select id="f-cat" name="categoria">
            <option value="">Todas</option>
            <?php foreach ($cats as $c): ?>
              <option value="<?= $c['id'] ?>"<?= $c['id'] === $cat ? ' selected' : '' ?>>
                <?= htmlspecialchars($c['name']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <!-- Price slider only -->
        <div class="filter-item">
          <label for="f-price">
            Preço ≥ <span id="minPriceValue"><?= max($minPrice,100) ?></span>€
          </label>
          <input
            type="range"
            id="f-price"
            name="min_price"
            min="100"
            max="1000"
            step="10"
            value="<?= max($minPrice,100) ?>">
        </div>

        <!-- Minimum rating filter -->
        <div class="filter-item">
          <label for="f-rating">Avaliação ≥</label>
          <select id="f-rating" name="min_rating">
            <option value="0">Qualquer</option>
            <?php for ($r = 1; $r <= 5; $r++): ?>
              <option value="<?= $r ?>"<?= $r === intval($minRating) ? ' selected' : '' ?>>
                <?= $r ?>★
              </option>
            <?php endfor; ?>
          </select>
        </div>

        <!-- Filter buttons -->
        <div class="filter-actions">
          <button type="submit" class="btn-primary">Aplicar</button>
          <a href="browse.php" class="btn-clear">Limpar</a>
        </div>
      </form>
    </div>

    <!-- Services grid -->
    <div class="servicos-grid">
      <?php if (count($services)): ?>
        <?php foreach ($services as $s): ?>
          <div class="servico-card">
            <h3><?= htmlspecialchars($s['title']) ?></h3>
            <p><strong>Categoria:</strong> <?= htmlspecialchars($s['category']) ?></p>
            <p><strong>Preço:</strong> €<?= number_format($s['base_price'], 2) ?></p>
            <p><strong>Entrega:</strong> <?= intval($s['delivery_time_days']) ?> dias</p>
            <p><strong>Avaliação:</strong> <?= round($s['avg_rating'], 1) ?>★ (<?= $s['reviews_count'] ?>)</p>
            <a href="services.php?id=<?= $s['id'] ?>" class="btn-primary">Ver Serviço</a>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <p style="text-align:center;">Nenhum serviço encontrado.</p>
      <?php endif; ?>
    </div>
  </main>

  <?php include __DIR__.'/../templates/footer.php'; ?>
</body>
</html>