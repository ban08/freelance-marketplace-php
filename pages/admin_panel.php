<?php
require_once __DIR__ . '/../templates/bootstrap.php';
if (empty($_SESSION['user']['is_admin'])) {
    header('Location: dashboard.php');
    exit;
}

// Promote user
if ($_SERVER['REQUEST_METHOD']==='POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'promote') {
        $uid = intval($_POST['user_id']);
        $pdo->prepare("UPDATE users SET is_admin=1 WHERE id = ?")
            ->execute([$uid]);
    }
    elseif ($action === 'add_category') {
        $name = trim($_POST['new_category'] ?? '');
        if ($name !== '') {
            $pdo->prepare("INSERT INTO categories (name) VALUES (?)")
                ->execute([$name]);
        }
    }
    elseif ($action === 'delete_category') {
        $cid = intval($_POST['category_id'] ?? 0);
        $pdo->prepare("DELETE FROM categories WHERE id = ?")
            ->execute([$cid]);
    }
    header('Location: admin_panel.php');
    exit;
}

// Fetch users
$users = $pdo->query("
  SELECT id, username, name, email, tipo, is_admin
    FROM users
   ORDER BY name
")->fetchAll();

// Fetch categories
$cats = $pdo->query("
  SELECT id, name
    FROM categories
   ORDER BY name
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt">
<head>
  <meta charset="UTF-8">
  <title>Admin Panel – ltw07g06</title>
  <link rel="stylesheet" href="../css/style.css">
</head>
<body>
  <?php include __DIR__.'/../templates/header.php'; ?>
  <main class="dashboard-page">

    <!-- Users Table (existing) -->
    <h2 class="dashboard-title">Admin Panel: Utilizadores</h2>
    <table class="manage-table">
      <thead>
        <tr>
          <th>Nome</th><th>Email</th><th>Tipo</th><th>Admin?</th><th>Ações</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach($users as $u): ?>
        <tr>
          <td><?= htmlspecialchars($u['name']) ?></td>
          <td><?= htmlspecialchars($u['email']) ?></td>
          <td><?= htmlspecialchars($u['tipo']) ?></td>
          <td><?= $u['is_admin'] ? '✅' : '❌' ?></td>
          <td>
            <?php if (!$u['is_admin']): ?>
            <form method="post" style="display:inline">
              <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
              <button type="submit" name="action" value="promote"
                      onclick="return confirm('Elevar a administrador este utilizador?')">
                Elevar a Administrador
              </button>
            </form>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>

    <!-- Categories Management -->
    <section style="margin-top:2em;">
      <h2 class="dashboard-title">Categorias de Serviços</h2>

      <!-- Add new category -->
      <form method="post" class="form-inline" style="margin-bottom:1em;">
        <input type="text" name="new_category" required
               placeholder="Nova categoria" style="margin-right:.5em;">
        <button type="submit" name="action" value="add_category">
          Adicionar Categoria
        </button>
      </form>

      <!-- List existing categories -->
      <table class="manage-table">
        <thead>
          <tr><th>Categoria</th><th>Ações</th></tr>
        </thead>
        <tbody>
          <?php foreach($cats as $c): ?>
          <tr>
            <td><?= htmlspecialchars($c['name']) ?></td>
            <td>
              <form method="post" style="display:inline">
                <input type="hidden" name="category_id" value="<?= $c['id'] ?>">
                <button type="submit" name="action" value="delete_category"
                        onclick="return confirm('Eliminar esta categoria?')">
                  Apagar
                </button>
              </form>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </section>

    <p class="dashboard-return">
      <a href="dashboard.php" class="btn-primary">← Voltar ao Dashboard</a>
    </p>

  </main>
  <?php include __DIR__.'/../templates/footer.php'; ?>
</body>
</html>