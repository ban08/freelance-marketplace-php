<?php
require_once __DIR__ . '/../templates/bootstrap.php';

// se não houver login, volta ao form
if (empty($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

$user = $_SESSION['user'];
?>
<!DOCTYPE html>
<html lang="pt">
<head>
  <meta charset="UTF-8">
  <title>Dashboard – ltw07g06</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link rel="stylesheet" href="../css/style.css">
</head>
<body>
  <?php include __DIR__ . '/../templates/header.php'; ?>

  <main class="dashboard-page">
    <h2 class="dashboard-title">Olá, <?= htmlspecialchars($user['nome']) ?>!</h2>

    <?php if ($user['tipo'] === 'cliente'): ?>
      <section class="dashboard-section">
        <h3>Minhas Compras</h3>
        <?php 
          $stmt = $pdo->prepare("SELECT t.data, s.titulo, u.nome AS freelancer
                                  FROM transacoes t 
                                  JOIN servicos s ON t.servico_id = s.id
                                  JOIN utilizadores u ON s.freelancer_id = u.id
                                  WHERE t.cliente_id = ?");
          $stmt->execute([$user['id']]);
          $compras = $stmt->fetchAll(PDO::FETCH_ASSOC);
        ?>
        <?php if ($compras): ?>
          <ul>
            <?php foreach($compras as $compra): ?>
              <li>
                <?= htmlspecialchars($compra['titulo']) ?> – por <?= htmlspecialchars($compra['freelancer']) ?> 
                em <?= htmlspecialchars($compra['data']) ?>
              </li>
            <?php endforeach; ?>
          </ul>
        <?php else: ?>
          <p>Você ainda não contratou nenhum serviço.</p>
        <?php endif; ?>
      </section>
      <section class="dashboard-section">
        <h3>Meus Favoritos</h3>
        <?php 
          $stmt = $pdo->prepare("SELECT s.id, s.titulo, s.preco, u.nome AS freelancer
                                  FROM favoritos f 
                                  JOIN servicos s ON f.servico_id = s.id
                                  JOIN utilizadores u ON s.freelancer_id = u.id
                                  WHERE f.user_id = ?");
          $stmt->execute([$user['id']]);
          $favoritos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        ?>
        <?php if ($favoritos): ?>
          <ul>
            <?php foreach($favoritos as $fav): ?>
              <li>
                <a href="service.php?id=<?= $fav['id'] ?>">
                  <?= htmlspecialchars($fav['titulo']) ?>
                </a> – €<?= htmlspecialchars($fav['preco']) ?> (por <?= htmlspecialchars($fav['freelancer']) ?>)
              </li>
            <?php endforeach; ?>
          </ul>
        <?php else: ?>
          <p>Você não tem serviços favoritos.</p>
        <?php endif; ?>
      </section>
    <?php elseif ($user['tipo'] === 'freelancer'): ?>
      <section class="dashboard-section">
        <h3>Meus Serviços</h3>
        <p><a href="new_service.php" class="btn-primary">+ Novo Serviço</a></p>
        <?php 
          // corrigido para a tabela `services` e colunas em inglês
          $stmt = $pdo->prepare("
            SELECT 
              id,
              title        AS titulo,
              (status = 'active') AS ativo,
              base_price   AS preco
            FROM services 
            WHERE freelancer_id = ?
          ");
          $stmt->execute([$user['id']]);
          $meusServicos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        ?>
        <?php if ($meusServicos): ?>
          <ul>
            <?php foreach($meusServicos as $sv): ?>
              <li>
                <?= htmlspecialchars($sv['titulo']) ?> 
                (<?= $sv['ativo'] ? 'Ativo' : 'Inativo' ?>) 
                - €<?= htmlspecialchars($sv['preco']) ?>
                [<a href="edit_service.php?id=<?= $sv['id'] ?>">Editar</a> | 
                 <a href="delete_service.php?id=<?= $sv['id'] ?>" onclick="return confirm('Tem a certeza?')">Remover</a>]
              </li>
            <?php endforeach; ?>
          </ul>
        <?php else: ?>
          <p>Não tem nenhum serviço registado ainda.</p>
        <?php endif; ?>
      </section>
    <?php endif; ?>

    <!-- Botão para voltar à Página Inicial (AGORA dentro de <main>) -->
    <div class="dashboard-return">
      <a href="../index.php?skip=1" class="btn-primary">← Página Inicial</a>
    </div>
  </main>
</body>
</html>
