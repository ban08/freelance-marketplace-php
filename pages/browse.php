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

if ($search) { 
    $where[] = "(s.title LIKE ? OR s.description LIKE ?)";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
}
if ($cat) { 
    $where[] = "s.category_id = ?";
    $params[] = $cat;
}
if ($minPrice > 0) { 
    $where[] = "s.base_price >= ?";
    $params[] = $minPrice;
}
if ($maxPrice > 0) { 
    $where[] = "s.base_price <= ?";
    $params[] = $maxPrice;
}

// Main query: simplified version
$sql = "
  SELECT
    s.id,
    s.title,
    s.base_price,
    s.delivery_time_days,
    s.description,
    c.name AS category,
    u.name AS freelancer_name,
    (SELECT COALESCE(AVG(CAST(rating AS REAL)), 0) FROM reviews WHERE service_id = s.id) AS avg_rating,
    (SELECT COUNT(*) FROM reviews WHERE service_id = s.id) AS reviews_count
  FROM services s
  INNER JOIN categories c ON s.category_id = c.id
  INNER JOIN users u ON s.freelancer_id = u.id
  WHERE " . implode(' AND ', $where);

// Apply rating filter if specified
if ($minRating > 0) {
    $sql .= " AND (SELECT COALESCE(AVG(CAST(rating AS REAL)), 0) FROM reviews WHERE service_id = s.id) >= ?";
    $params[] = $minRating;
}

$sql .= " ORDER BY s.created_at DESC, s.base_price ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$services = $stmt->fetchAll();

// Debug info (remove in production)
if (isset($_GET['debug'])) {
    echo "<div style='background: #f8f9fa; padding: 20px; margin: 20px; border-radius: 8px;'>";
    echo "<h4>Debug Info:</h4>";
    echo "<p><strong>SQL:</strong> " . str_replace(['  ', "\n"], [' ', ' '], $sql) . "</p>";
    echo "<p><strong>Parameters:</strong> " . implode(', ', $params) . "</p>";
    echo "<p><strong>Services found:</strong> " . count($services) . "</p>";
    
    // Check total services in database
    $totalServices = $pdo->query("SELECT COUNT(*) as count FROM services WHERE status = 'active'")->fetch();
    echo "<p><strong>Total active services in DB:</strong> " . $totalServices['count'] . "</p>";
    
    echo "</div>";
}
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
            Preço ≥ <span id="minPriceValue"><?= max($minPrice,0) ?></span>€
          </label>
          <input
            type="range"
            id="f-price"
            name="min_price"
            min="0"
            max="1000"
            step="10"
            value="<?= max($minPrice,0) ?>">
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
      <?php if (count($services) > 0): ?>
        <?php foreach ($services as $s): ?>
          <div class="servico-card">
            <h3><?= htmlspecialchars($s['title']) ?></h3>
            <p class="service-description"><?= htmlspecialchars(substr($s['description'], 0, 100)) ?>...</p>
            <p><strong>Categoria:</strong> <?= htmlspecialchars($s['category']) ?></p>
            <p><strong>Freelancer:</strong> <?= htmlspecialchars($s['freelancer_name']) ?></p>
            <p><strong>Preço:</strong> €<?= number_format($s['base_price'], 2) ?></p>
            <p><strong>Entrega:</strong> <?= intval($s['delivery_time_days']) ?> dias</p>
            <p><strong>Avaliação:</strong> 
              <?php if ($s['reviews_count'] > 0): ?>
                <?= round($s['avg_rating'], 1) ?>★ (<?= $s['reviews_count'] ?> avaliações)
              <?php else: ?>
                Sem avaliações
              <?php endif; ?>
            </p>
            <a href="services.php?id=<?= $s['id'] ?>" class="btn-primary">Ver Serviço</a>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <div class="no-services-message">
          <h3>Nenhum serviço encontrado</h3>
          <p>Tente ajustar os filtros ou <a href="browse.php">limpar todos os filtros</a>.</p>
        </div>
      <?php endif; ?>
    </div>
  </main>

  <?php include __DIR__.'/../templates/footer.php'; ?>

  <script>
    // Price slider functionality
    document.addEventListener('DOMContentLoaded', function() {
      const priceSlider = document.getElementById('f-price');
      const priceValue = document.getElementById('minPriceValue');
      
      if (priceSlider && priceValue) {
        priceSlider.addEventListener('input', function() {
          priceValue.textContent = this.value;
        });
      }
      
      // Auto-submit form when filters change (optional)
      const filterForm = document.getElementById('filterForm');
      const autoSubmitElements = filterForm.querySelectorAll('select, input[type="range"]');
      
      autoSubmitElements.forEach(element => {
        element.addEventListener('change', function() {
          // Small delay for better UX
          setTimeout(() => {
            filterForm.submit();
          }, 500);
        });
      });
      
      // Search input with enter key and delayed auto-submit
      const searchInput = filterForm.querySelector('input[name="search"]');
      if (searchInput) {
        let searchTimeout;
        searchInput.addEventListener('input', function() {
          clearTimeout(searchTimeout);
          searchTimeout = setTimeout(() => {
            if (this.value.length >= 3 || this.value.length === 0) {
              filterForm.submit();
            }
          }, 1000);
        });
        
        searchInput.addEventListener('keypress', function(e) {
          if (e.key === 'Enter') {
            e.preventDefault();
            filterForm.submit();
          }
        });
      }
    });
  </script>
</body>
</html>