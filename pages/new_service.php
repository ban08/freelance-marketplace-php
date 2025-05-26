<?php
require_once __DIR__ . '/../templates/bootstrap.php';

// só freelancers
if (empty($_SESSION['user']) || $_SESSION['user']['tipo'] !== 'freelancer') {
    header('Location: dashboard.php');
    exit;
}
$freelancerId = $_SESSION['user']['id'];
$erro = '';
$sucesso = '';

// buscar categorias
$stmt = $pdo->query("SELECT id, name FROM categories ORDER BY name");
$categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $categoria_id      = intval($_POST['categoria'] ?? 0);
    $titulo            = trim($_POST['titulo'] ?? '');
    $descricao         = trim($_POST['descricao'] ?? '');
    $preco             = floatval($_POST['preco'] ?? 0);
    $delivery_days     = intval($_POST['delivery_days'] ?? 0);

    // validações básicas
    if (!$categoria_id || !$titulo || !$descricao || $preco <= 0 || $delivery_days <= 0) {
        $erro = 'Por favor, preencha todos os campos corretamente.';
    }

    if (!$erro) {
        // inserir serviço
        $stmt = $pdo->prepare(
          "INSERT INTO services 
             (freelancer_id, category_id, title, description, base_price, delivery_time_days) 
           VALUES (?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([
          $freelancerId, 
          $categoria_id, 
          $titulo, 
          $descricao, 
          $preco, 
          $delivery_days
        ]);
        $serviceId = $pdo->lastInsertId();

        // processar uploads
        $uploadDir = __DIR__ . '/../uploads/services/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        foreach ($_FILES['media']['tmp_name'] as $i => $tmp) {
            if (is_uploaded_file($tmp)) {
                $origName = basename($_FILES['media']['name'][$i]);
                $ext      = pathinfo($origName, PATHINFO_EXTENSION);
                $filename = uniqid('svc').".$ext";
                $dest     = $uploadDir . $filename;
                if (move_uploaded_file($tmp, $dest)) {
                    // registar em service_images
                    $stmtImg = $pdo->prepare(
                      "INSERT INTO service_images
                         (service_id, image_path, display_order)
                       VALUES (?, ?, ?)"
                    );
                    $stmtImg->execute([
                      $serviceId, 
                      "uploads/services/$filename", 
                      $i
                    ]);
                }
            }
        }

        $sucesso = 'Serviço criado com sucesso!';
        // opcional: redirecionar para editar ou dashboard
        header("Location: dashboard.php");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
  <meta charset="UTF-8">
  <title>Novo Serviço – ltw07g06</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link rel="stylesheet" href="../css/style.css">
</head>
<body>
  <?php include __DIR__ . '/../templates/header.php'; ?>
  <main class="dashboard-page">
    <h2 class="dashboard-title">Adicionar Novo Serviço</h2>

    <?php if ($erro): ?>
      <div class="erro"><?= htmlspecialchars($erro) ?></div>
    <?php endif; ?>

    <form method="post" action="new_service.php" 
          enctype="multipart/form-data" 
          class="form-login">
      <label for="categoria">Categoria:</label>
      <select id="categoria" name="categoria" required>
        <option value="">– escolha –</option>
        <?php foreach ($categorias as $cat): ?>
          <option value="<?= $cat['id'] ?>"
            <?= ($_POST['categoria'] ?? '') == $cat['id'] ? 'selected' : '' ?>>
            <?= htmlspecialchars($cat['name']) ?>
          </option>
        <?php endforeach; ?>
      </select>

      <label for="titulo">Título do Serviço:</label>
      <input type="text" id="titulo" name="titulo" 
             value="<?= htmlspecialchars($_POST['titulo'] ?? '') ?>" 
             required>

      <label for="descricao">Descrição:</label>
      <textarea id="descricao" name="descricao" rows="5" required>
        <?= htmlspecialchars($_POST['descricao'] ?? '') ?>
      </textarea>

      <label for="preco">Preço (€):</label>
      <input type="number" step="0.01" id="preco" name="preco" 
             value="<?= htmlspecialchars($_POST['preco'] ?? '') ?>" 
             required>

      <label for="delivery_days">Entrega em (dias):</label>
      <input type="number" id="delivery_days" name="delivery_days" 
             value="<?= htmlspecialchars($_POST['delivery_days'] ?? '') ?>" 
             required>

      <label for="media">Imagens/Vídeos:</label>
      <input type="file" id="media" name="media[]" 
             accept="image/*,video/*" multiple>

      <button type="submit" class="btn-primary">Criar Serviço</button>
    </form>

    <?php if ($sucesso): ?>
      <div class="alert alert-success"><?= htmlspecialchars($sucesso) ?></div>
    <?php endif; ?>

    <p class="dashboard-return">
      <a href="dashboard.php" class="btn-primary">← Voltar ao Dashboard</a>
    </p>
  </main>
  <?php include __DIR__ . '/../templates/footer.php'; ?>
</body>
</html>