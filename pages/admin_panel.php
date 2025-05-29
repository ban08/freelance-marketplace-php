<?php
require_once __DIR__ . '/../templates/bootstrap.php';

// Check if user is admin
if (empty($_SESSION['user']['is_admin'])) {
    header('Location: dashboard.php');
    exit;
}

$message = '';
$messageType = '';

// Handle POST actions
if ($_SERVER['REQUEST_METHOD']==='POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        if ($action === 'promote') {
            $uid = intval($_POST['user_id']);
            $pdo->prepare("UPDATE users SET is_admin=1 WHERE id = ?")
                ->execute([$uid]);
            $message = 'Utilizador promovido a administrador com sucesso!';
            $messageType = 'success';
        }
        elseif ($action === 'demote') {
            $uid = intval($_POST['user_id']);
            // Don't demote yourself
            if ($uid != $_SESSION['user']['id']) {
                $pdo->prepare("UPDATE users SET is_admin=0 WHERE id = ?")
                    ->execute([$uid]);
                $message = 'Privilégios de administrador removidos com sucesso!';
                $messageType = 'success';
            } else {
                $message = 'Não pode remover os seus próprios privilégios de administrador!';
                $messageType = 'error';
            }
        }
        elseif ($action === 'add_category') {
            $name = trim($_POST['new_category'] ?? '');
            if ($name !== '') {
                // Check if category already exists
                $existing = $pdo->prepare("SELECT id FROM categories WHERE name = ?");
                $existing->execute([$name]);
                if ($existing->fetch()) {
                    $message = 'Esta categoria já existe!';
                    $messageType = 'error';
                } else {
                    $pdo->prepare("INSERT INTO categories (name) VALUES (?)")
                        ->execute([$name]);
                    $message = 'Categoria adicionada com sucesso!';
                    $messageType = 'success';
                }
            } else {
                $message = 'Nome da categoria não pode estar vazio!';
                $messageType = 'error';
            }
        }
        elseif ($action === 'delete_category') {
            $cid = intval($_POST['category_id'] ?? 0);
            // Check if category has services
            $hasServices = $pdo->prepare("SELECT COUNT(*) FROM services WHERE category_id = ?");
            $hasServices->execute([$cid]);
            if ($hasServices->fetchColumn() > 0) {
                $message = 'Não é possível eliminar uma categoria que tem serviços associados!';
                $messageType = 'error';
            } else {
                $pdo->prepare("DELETE FROM categories WHERE id = ?")
                    ->execute([$cid]);
                $message = 'Categoria eliminada com sucesso!';
                $messageType = 'success';
            }
        }
        elseif ($action === 'delete_service') {
            $sid = intval($_POST['service_id']);
            $pdo->prepare("DELETE FROM services WHERE id = ?")
                ->execute([$sid]);
            $message = 'Serviço eliminado com sucesso!';
            $messageType = 'success';
        }
        elseif ($action === 'fix_database') {
            // Add missing columns if needed
            try {
                // Check if tipo column exists
                $usersCols = $pdo->query("PRAGMA table_info(users)")->fetchAll();
                $tipoExists = false;
                
                foreach ($usersCols as $col) {
                    if ($col['name'] === 'tipo') {
                        $tipoExists = true;
                        break;
                    }
                }
                
                if (!$tipoExists) {
                    $pdo->exec("ALTER TABLE users ADD COLUMN tipo TEXT DEFAULT 'cliente'");
                    $pdo->exec("UPDATE users SET tipo = 'cliente' WHERE tipo IS NULL");
                    $message = 'Esquema da base de dados corrigido com sucesso!';
                    $messageType = 'success';
                } else {
                    $message = 'Esquema da base de dados já está correto.';
                    $messageType = 'info';
                }
            } catch (Exception $e) {
                $message = 'Erro ao corrigir base de dados: ' . $e->getMessage();
                $messageType = 'error';
            }
        }
    } catch (Exception $e) {
        $message = 'Erro: ' . $e->getMessage();
        $messageType = 'error';
    }
    
    header('Location: admin_panel.php?msg=' . urlencode($message) . '&type=' . $messageType);
    exit;
}

// Handle message display
if (isset($_GET['msg'])) {
    $message = $_GET['msg'];
    $messageType = $_GET['type'] ?? 'info';
}

// Initialize variables with default values
$stats = [
    'total_users' => 0,
    'total_freelancers' => 0,
    'total_clients' => 0,
    'total_admins' => 0,
    'total_services' => 0,
    'total_categories' => 0
];
$users = [];
$cats = [];
$recent_services = [];

try {
    // Fetch statistics
    $stats['total_users'] = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $stats['total_categories'] = $pdo->query("SELECT COUNT(*) FROM categories")->fetchColumn();
    $stats['total_services'] = $pdo->query("SELECT COUNT(*) FROM services")->fetchColumn();
    $stats['total_admins'] = $pdo->query("SELECT COUNT(*) FROM users WHERE is_admin = 1")->fetchColumn();
    
    // Try to get user types (handle missing tipo column gracefully)
    try {
        $stats['total_freelancers'] = $pdo->query("SELECT COUNT(*) FROM users WHERE tipo = 'freelancer'")->fetchColumn();
        $stats['total_clients'] = $pdo->query("SELECT COUNT(*) FROM users WHERE tipo = 'cliente'")->fetchColumn();
    } catch (Exception $e) {
        // tipo column doesn't exist, set to 0
        $stats['total_freelancers'] = 0;
        $stats['total_clients'] = 0;
    }

    // Fetch users (handle missing tipo column gracefully)
    try {
        $users = $pdo->query("
          SELECT id, username, name, email, tipo, is_admin, joined_date
            FROM users
           ORDER BY is_admin DESC, name
        ")->fetchAll();
    } catch (Exception $e) {
        // Fall back to query without tipo column
        $users = $pdo->query("
          SELECT id, username, name, email, 'cliente' as tipo, is_admin, joined_date
            FROM users
           ORDER BY is_admin DESC, name
        ")->fetchAll();
    }

    // Fetch categories with service count
    $cats = $pdo->query("
      SELECT c.id, c.name, COUNT(s.id) as service_count
        FROM categories c
        LEFT JOIN services s ON c.id = s.category_id
       GROUP BY c.id, c.name
       ORDER BY c.name
    ")->fetchAll();

    // Fetch recent services for management (handle different price column names)
    try {
        $recent_services = $pdo->query("
          SELECT s.id, s.title, s.base_price as price, u.name as freelancer_name, c.name as category_name, s.created_at
            FROM services s
            JOIN users u ON s.freelancer_id = u.id
            LEFT JOIN categories c ON s.category_id = c.id
           ORDER BY s.created_at DESC
           LIMIT 10
        ")->fetchAll();
    } catch (Exception $e) {
        // Try with 'price' column instead
        try {
            $recent_services = $pdo->query("
              SELECT s.id, s.title, s.price, u.name as freelancer_name, c.name as category_name, s.created_at
                FROM services s
                JOIN users u ON s.freelancer_id = u.id
                LEFT JOIN categories c ON s.category_id = c.id
               ORDER BY s.created_at DESC
               LIMIT 10
            ")->fetchAll();
        } catch (Exception $e2) {
            $recent_services = [];
        }
    }

} catch (Exception $e) {
    $message = 'Erro ao carregar dados: ' . $e->getMessage();
    $messageType = 'error';
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
  <meta charset="UTF-8">
  <title>Admin Panel – ltw07g06</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="../css/style.css">
</head>
<body>
  <?php include __DIR__.'/../templates/header.php'; ?>
  
  <main class="admin-panel">
    <div class="admin-container">
      <h1 class="admin-title">
        <i class="admin-icon">⚙️</i>
        Painel de Administração
      </h1>

      <?php if ($message): ?>
        <div class="admin-message admin-message-<?= $messageType ?>">
          <?= htmlspecialchars($message) ?>
        </div>
      <?php endif; ?>

      <!-- Database Status Check -->
      <?php 
      $databaseIssues = false;
      try {
        // Check if tipo column exists
        $pdo->query("SELECT tipo FROM users LIMIT 1");
      } catch (Exception $e) {
        $databaseIssues = true;
      }
      
      if ($databaseIssues): ?>
        <div class="admin-message admin-message-error">
          <strong>⚠️ Problema de Base de Dados Detectado!</strong><br>
          A coluna 'tipo' está em falta na tabela de utilizadores. Isto é necessário para classificar utilizadores como freelancers, clientes, etc.
          <form method="post" style="display: inline; margin-left: 1rem;">
            <button type="submit" name="action" value="fix_database" class="btn-admin btn-add">
              🔧 Corrigir Base de Dados
            </button>
          </form>
        </div>
      <?php endif; ?>

      <!-- Statistics Dashboard -->
      <section class="admin-stats">
        <h2 class="admin-section-title">Estatísticas da Plataforma</h2>
        <div class="stats-grid">
          <div class="stat-card">
            <div class="stat-number"><?= $stats['total_users'] ?></div>
            <div class="stat-label">Total de Utilizadores</div>
          </div>
          <div class="stat-card">
            <div class="stat-number"><?= $stats['total_freelancers'] ?></div>
            <div class="stat-label">Freelancers</div>
          </div>
          <div class="stat-card">
            <div class="stat-number"><?= $stats['total_clients'] ?></div>
            <div class="stat-label">Clientes</div>
          </div>
          <div class="stat-card">
            <div class="stat-number"><?= $stats['total_admins'] ?></div>
            <div class="stat-label">Administradores</div>
          </div>
          <div class="stat-card">
            <div class="stat-number"><?= $stats['total_services'] ?></div>
            <div class="stat-label">Serviços</div>
          </div>
          <div class="stat-card">
            <div class="stat-number"><?= $stats['total_categories'] ?></div>
            <div class="stat-label">Categorias</div>
          </div>
        </div>
      </section>

      <!-- Users Management -->
      <section class="admin-section">
        <h2 class="admin-section-title">Gestão de Utilizadores</h2>
        <div class="admin-table-container">
          <table class="admin-table">
            <thead>
              <tr>
                <th>Nome</th>
                <th>Email</th>
                <th>Tipo</th>
                <th>Admin</th>
                <th>Registado</th>
                <th>Ações</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach($users as $u): ?>
              <tr>
                <td class="user-name">
                  <strong><?= htmlspecialchars($u['name']) ?></strong>
                  <small>@<?= htmlspecialchars($u['username']) ?></small>
                </td>
                <td><?= htmlspecialchars($u['email']) ?></td>
                <td>
                  <span class="user-type user-type-<?= $u['tipo'] ?>">
                    <?= ucfirst($u['tipo']) ?>
                  </span>
                </td>
                <td>
                  <?= $u['is_admin'] ? '<span class="admin-badge">✅ Admin</span>' : '<span class="non-admin">❌</span>' ?>
                </td>
                <td><?= date('d/m/Y', strtotime($u['joined_date'])) ?></td>
                <td class="actions-cell">
                  <?php if (!$u['is_admin']): ?>
                    <form method="post" style="display:inline" onsubmit="return confirm('Elevar este utilizador a administrador?')">
                      <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                      <button type="submit" name="action" value="promote" class="btn-admin btn-promote">
                        Promover
                      </button>
                    </form>
                  <?php elseif ($u['id'] != $_SESSION['user']['id']): ?>
                    <form method="post" style="display:inline" onsubmit="return confirm('Remover privilégios de administrador?')">
                      <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                      <button type="submit" name="action" value="demote" class="btn-admin btn-demote">
                        Remover Admin
                      </button>
                    </form>
                  <?php else: ?>
                    <span class="current-admin">Você</span>
                  <?php endif; ?>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </section>

      <!-- Categories Management -->
      <section class="admin-section">
        <h2 class="admin-section-title">Gestão de Categorias</h2>
        
        <!-- Add new category -->
        <div class="admin-form-section">
          <h3>Adicionar Nova Categoria</h3>
          <form method="post" class="admin-form">
            <div class="form-group">
              <input type="text" name="new_category" required
                     placeholder="Nome da categoria" class="admin-input">
              <button type="submit" name="action" value="add_category" class="btn-admin btn-add">
                <i>➕</i> Adicionar Categoria
              </button>
            </div>
          </form>
        </div>

        <!-- List existing categories -->
        <div class="admin-table-container">
          <table class="admin-table">
            <thead>
              <tr>
                <th>Categoria</th>
                <th>Serviços</th>
                <th>Ações</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach($cats as $c): ?>
              <tr>
                <td><strong><?= htmlspecialchars($c['name']) ?></strong></td>
                <td>
                  <span class="service-count"><?= $c['service_count'] ?> serviços</span>
                </td>
                <td class="actions-cell">
                  <form method="post" style="display:inline" 
                        onsubmit="return confirm('Eliminar esta categoria? <?= $c['service_count'] > 0 ? 'ATENÇÃO: Esta categoria tem ' . $c['service_count'] . ' serviços associados!' : '' ?>')">
                    <input type="hidden" name="category_id" value="<?= $c['id'] ?>">
                    <button type="submit" name="action" value="delete_category" 
                            class="btn-admin btn-delete" <?= $c['service_count'] > 0 ? 'disabled title="Não é possível eliminar categorias com serviços"' : '' ?>>
                      <i>🗑️</i> Eliminar
                    </button>
                  </form>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </section>

      <!-- Recent Services Management -->
      <section class="admin-section">
        <h2 class="admin-section-title">Serviços Recentes</h2>
        <div class="admin-table-container">
          <table class="admin-table">
            <thead>
              <tr>
                <th>Serviço</th>
                <th>Freelancer</th>
                <th>Categoria</th>
                <th>Preço</th>
                <th>Criado</th>
                <th>Ações</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach($recent_services as $s): ?>
              <tr>
                <td><strong><?= htmlspecialchars($s['title']) ?></strong></td>
                <td><?= htmlspecialchars($s['freelancer_name']) ?></td>
                <td><?= htmlspecialchars($s['category_name'] ?: 'Sem categoria') ?></td>
                <td class="price">€<?= number_format($s['price'], 2) ?></td>
                <td><?= date('d/m/Y H:i', strtotime($s['created_at'])) ?></td>
                <td class="actions-cell">
                  <a href="services.php?id=<?= $s['id'] ?>" class="btn-admin btn-view" target="_blank">
                    <i>👁️</i> Ver
                  </a>
                  <form method="post" style="display:inline" 
                        onsubmit="return confirm('Eliminar este serviço permanentemente?')">
                    <input type="hidden" name="service_id" value="<?= $s['id'] ?>">
                    <button type="submit" name="action" value="delete_service" class="btn-admin btn-delete">
                      <i>🗑️</i> Eliminar
                    </button>
                  </form>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </section>

      <!-- Navigation -->
      <div class="admin-navigation">
        <a href="dashboard.php" class="btn-admin btn-back">
          <i>⬅️</i> Voltar ao Dashboard
        </a>
        <a href="profile.php" class="btn-admin btn-profile">
          <i>👤</i> Meu Perfil
        </a>
      </div>
    </div>
  </main>
  
  <?php include __DIR__.'/../templates/footer.php'; ?>
</body>
</html>