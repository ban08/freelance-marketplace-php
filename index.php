<?php
require_once __DIR__ . '/templates/bootstrap.php';

// Function to get icon for category
function getCategoryIcon($categoryName) {
    $icons = [
        'Web Development' => 'code',
        'Graphic Design' => 'paint-brush',
        'Writing & Translation' => 'pen-fancy',
        'Digital Marketing' => 'bullhorn',
        'Video & Animation' => 'video',
        'Mobile Development' => 'mobile-alt',
        'Data Science' => 'chart-bar',
        'Photography' => 'camera'
    ];
    
    return $icons[$categoryName] ?? 'star';
}

// Get count of active freelancers (users who have at least one active service)
$activeFreelancersStmt = $pdo->query("
  SELECT COUNT(DISTINCT s.freelancer_id) as active_freelancers
  FROM services s
  WHERE s.status = 'active'
");
$activeFreelancersCount = $activeFreelancersStmt->fetch()['active_freelancers'];

// fetch up to 6 popular categories (by number of services)
$cats = $pdo->query("
  SELECT c.id, c.name, COUNT(s.id) AS cnt
    FROM categories c
    LEFT JOIN services s 
      ON s.category_id = c.id 
     AND s.status = 'active'
   GROUP BY c.id
   ORDER BY cnt DESC, c.name
   LIMIT 6
")->fetchAll();

// fetch 8 latest active services with their first image
$svStmt = $pdo->prepare("
  SELECT 
    s.id,
    s.title,
    s.base_price,
    s.delivery_time_days,
    IFNULL(si.image_path,'img/default.png') AS thumb
  FROM services s
  LEFT JOIN (
    SELECT service_id, image_path
      FROM service_images
     WHERE display_order = 0
  ) si ON si.service_id = s.id
  WHERE s.status = 'active'
  ORDER BY s.created_at DESC
  LIMIT 8
");
$svStmt->execute();
$services = $svStmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt">
<head>
  <meta charset="UTF-8">
  <title>Bem-vindo • Freelance Platform</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link rel="stylesheet" href="css/style.css">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
  <?php include __DIR__ . '/templates/header.php'; ?>

  <section class="hero">
    <div class="hero-content">
      <h1 id="hero-title">Encontre e Contrate os Melhores Freelancers</h1>
      <p id="hero-subtitle">Categorias diversas, preços competitivos e talento à distância de um clique.</p>
      <div class="hero-buttons">
        <a href="pages/browse.php" class="btn-primary" id="explore-btn">
          <i class="fas fa-search"></i> Explorar Serviços
        </a>
        <?php if (empty($_SESSION['user'])): ?>
          <a href="pages/register.php" class="btn-secondary" id="register-btn">
            <i class="fas fa-user-plus"></i> Registar-se
          </a>
        <?php endif; ?>
      </div>
    </div>
  </section>

  <!-- Stats Section -->
  <section class="stats-section animate-on-scroll">
    <div class="stats-container">
      <div class="stat-item">
        <div class="stat-number" data-target="<?= $activeFreelancersCount ?>">0</div>
        <div class="stat-label">Freelancers Ativos</div>
      </div>
      <div class="stat-item">
        <div class="stat-number" data-target="1200">0</div>
        <div class="stat-label">Projetos Concluídos</div>
      </div>
      <div class="stat-item">
        <div class="stat-number" data-target="50">0</div>
        <div class="stat-label">Países</div>
      </div>
    </div>
  </section>

  <section class="categorias-destaque animate-on-scroll">
    <h2>Categorias Populares</h2>
    <div class="categorias-lista">
      <?php foreach ($cats as $c): ?>
      <a href="pages/browse.php?categoria=<?= $c['id'] ?>" class="categoria-item" data-category="<?= $c['id'] ?>">
        <div class="category-icon">
          <i class="fas fa-<?= getCategoryIcon($c['name']) ?>"></i>
        </div>
        <span><?= htmlspecialchars($c['name']) ?></span>
        <small class="category-count"><?= $c['cnt'] ?> serviços</small>
      </a>
      <?php endforeach; ?>
    </div>
  </section>

  <section class="servicos-destaque animate-on-scroll">
    <h2>Serviços em Destaque</h2>
    <div class="servicos-grid" id="services-grid">
      <?php foreach ($services as $s): ?>
      <div class="servico-card" data-service-id="<?= $s['id'] ?>">
        <div class="service-image-container">
          <img src="<?= htmlspecialchars($s['thumb']) ?>" alt="<?= htmlspecialchars($s['title']) ?>" loading="lazy">
          <div class="service-overlay">
            <i class="fas fa-eye"></i>
          </div>
        </div>
        <div class="service-content">
          <h3><?= htmlspecialchars($s['title']) ?></h3>
          <p class="preco">
            <span class="price-amount">€<?= number_format($s['base_price'],2) ?></span>
            <span class="delivery-time">
              <i class="fas fa-clock"></i> <?= intval($s['delivery_time_days']) ?> dias
            </span>
          </p>
          <a href="pages/services.php?id=<?= $s['id'] ?>" class="btn-primary service-btn">
            Ver Detalhes <i class="fas fa-arrow-right"></i>
          </a>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </section>

  <!-- How It Works Section -->
  <section class="how-it-works animate-on-scroll">
    <h2>Como Funciona</h2>
    <div class="steps-container">
      <div class="step-item">
        <div class="step-icon">
          <i class="fas fa-search"></i>
        </div>
        <h3>1. Explore</h3>
        <p>Navegue pelos serviços e encontre o freelancer perfeito para o seu projeto</p>
      </div>
      <div class="step-item">
        <div class="step-icon">
          <i class="fas fa-handshake"></i>
        </div>
        <h3>2. Contrate</h3>
        <p>Contacte o freelancer e discuta os detalhes do seu projeto</p>
      </div>
      <div class="step-item">
        <div class="step-icon">
          <i class="fas fa-check-circle"></i>
        </div>
        <h3>3. Receba</h3>
        <p>Receba o trabalho concluído com qualidade e dentro do prazo</p>
      </div>
    </div>
  </section>

  <?php include __DIR__ . '/templates/footer.php'; ?>

  <script src="js/main.js"></script>
</body>
</html>
