<?php
require_once __DIR__ . '/templates/bootstrap.php';

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
</head>
<body>
  <?php include __DIR__ . '/templates/header.php'; ?>

  <section class="hero">
    <div class="hero-content">
      <h1>Encontre e Contrate os Melhores Freelancers</h1>
      <p>Categorias diversas, preços competitivos e talento à distância de um clique.</p>
      <div class="hero-buttons">
        <a href="pages/browse.php" class="btn-primary">Explorar Serviços</a>
        <?php if (empty($_SESSION['user'])): ?>
          <a href="pages/register.php" class="btn-secondary">Registar-se</a>
        <?php endif; ?>
      </div>
    </div>
  </section>

  <section class="categorias-destaque">
    <h2>Categorias Populares</h2>
    <div class="categorias-lista">
      <?php foreach ($cats as $c): ?>
      <a href="pages/browse.php?categoria=<?= $c['id'] ?>" class="categoria-item">
        <img src="img/icon-<?= strtolower(str_replace(' ','-', $c['name'])) ?>.png"
             alt="<?= htmlspecialchars($c['name']) ?>">
        <span><?= htmlspecialchars($c['name']) ?> (<?= $c['cnt'] ?>)</span>
      </a>
      <?php endforeach; ?>
    </div>
  </section>

  <section class="servicos-destaque">
    <h2>Serviços em Destaque</h2>
    <div class="servicos-grid">
      <?php foreach ($services as $s): ?>
      <div class="servico-card">
        <img src="<?= htmlspecialchars($s['thumb']) ?>" alt="<?= htmlspecialchars($s['title']) ?>">
        <h3><?= htmlspecialchars($s['title']) ?></h3>
        <p class="preco"><strong>€<?= number_format($s['base_price'],2) ?></strong> – <?= intval($s['delivery_time_days']) ?> dias</p>
        <a href="pages/services.php?id=<?= $s['id'] ?>" class="btn-primary">Ver Detalhes</a>
      </div>
      <?php endforeach; ?>
    </div>
  </section>

  <?php include __DIR__ . '/templates/footer.php'; ?>
</body>
</html>
