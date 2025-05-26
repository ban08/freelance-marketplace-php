<?php
require_once __DIR__ . '/../templates/bootstrap.php';
if (empty($_SESSION['user']) || $_SESSION['user']['tipo'] !== 'freelancer') {
    header('Location: dashboard.php');
    exit;
}

$freelancerId = $_SESSION['user']['id'];
$serviceId    = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$serviceId) {
    header('Location: manage_services.php');
    exit;
}

// carregar categorias
$stmt = $pdo->query("SELECT id, name FROM categories ORDER BY name");
$categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);

// carregar dados do serviço
$stmt = $pdo->prepare("
  SELECT * 
    FROM services 
   WHERE id = ? AND freelancer_id = ?
");
$stmt->execute([$serviceId, $freelancerId]);
$service = $stmt->fetch();
if (!$service) {
    header('Location: manage_services.php');
    exit;
}

$erro = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $categoria_id  = intval($_POST['categoria'] ?? 0);
    $titulo        = trim($_POST['titulo'] ?? '');
    $descricao     = trim($_POST['descricao'] ?? '');
    $preco         = floatval($_POST['preco'] ?? 0);
    $delivery_days = intval($_POST['delivery_days'] ?? 0);
    $status        = in_array($_POST['status'] ?? '', ['active','paused'], true)
                     ? $_POST['status'] : $service['status'];

    // validações
    if (!$categoria_id || !$titulo || !$descricao || $preco <= 0 || $delivery_days <= 0) {
        $erro = 'Por favor, preencha todos os campos corretamente.';
    }

    if (!$erro) {
        // update services
        $stmt = $pdo->prepare("
          UPDATE services
             SET category_id = ?, title = ?, description = ?,
                 base_price = ?, delivery_time_days = ?, status = ?, updated_at = CURRENT_TIMESTAMP
           WHERE id = ? AND freelancer_id = ?
        ");
        $stmt->execute([
          $categoria_id, $titulo, $descricao,
          $preco, $delivery_days, $status,
          $serviceId, $freelancerId
        ]);

        // processar novos uploads (anexar apenas)
        $uploadDir = __DIR__ . '/../uploads/services/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

        foreach ($_FILES['media']['tmp_name'] as $i => $tmp) {
            if (is_uploaded_file($tmp)) {
                $ext      = pathinfo($_FILES['media']['name'][$i], PATHINFO_EXTENSION);
                $filename = uniqid('svc').".$ext";
                if (move_uploaded_file($tmp, $uploadDir . $filename)) {
                    $pdo->prepare("
                      INSERT INTO service_images
                        (service_id, image_path, display_order)
                      VALUES (?, ?, ?)
                    ")->execute([
                      $serviceId,
                      "uploads/services/$filename",
                      time() // ou $i
                    ]);
                }
            }
        }

        header('Location: manage_services.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
  <meta charset="UTF-8">
  <title>Editar Serviço – ltw07g06</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link rel="stylesheet" href="../css/style.css">
</head>
<body>
  <?php include __DIR__ . '/../templates/header.php'; ?>
  <main class="dashboard-page">
    <h2 class="dashboard-title">Editar Serviço</h2>

    <?php if ($erro): ?>
      <div class="erro"><?= htmlspecialchars($erro) ?></div>
    <?php endif; ?>

    <form method="post" action="edit_service.php?id=<?= $serviceId ?>"
          enctype="multipart/form-data" class="form-login">
      <label for="categoria">Categoria:</label>
      <select id="categoria" name="categoria" required>
        <?php foreach ($categorias as $cat): ?>
        <option value="<?= $cat['id'] ?>"
          <?= $cat['id']==$service['category_id']?'selected':''?>>
          <?= htmlspecialchars($cat['name']) ?>
        </option>
        <?php endforeach;?>
      </select>

      <label for="titulo">Título:</label>
      <input type="text" id="titulo" name="titulo" required
             value="<?= htmlspecialchars($service['title']) ?>">

      <label for="descricao">Descrição:</label>
      <textarea id="descricao" name="descricao" rows="5" required><?= 
        htmlspecialchars($service['description']) ?></textarea>

      <label for="preco">Preço (€):</label>
      <input type="number" step="0.01" id="preco" name="preco" required
             value="<?= htmlspecialchars($service['base_price']) ?>">

      <label for="delivery_days">Entrega em (dias):</label>
      <input type="number" id="delivery_days" name="delivery_days" required
             value="<?= htmlspecialchars($service['delivery_time_days']) ?>">

      <label>Status:</label>
      <select name="status">
        <option value="active" <?= $service['status']==='active'?'selected':''?>>
          Ativo
        </option>
        <option value="paused" <?= $service['status']==='paused'?'selected':''?>>
          Pausado
        </option>
      </select>

      <label for="media">Adicionar imagens/vídeos:</label>
      <input type="file" id="media" name="media[]"
             accept="image/*,video/*" multiple>

      <button type="submit" class="btn-primary">Guardar Alterações</button>
    </form>

    <p class="dashboard-return">
      <a href="manage_services.php" class="btn-primary">← Voltar à Gestão</a>
    </p>
  </main>
  <?php include __DIR__ . '/../templates/footer.php'; ?>
</body>
</html>