<?php 
require_once __DIR__ . '/../templates/bootstrap.php';
// Verificar se user está logado
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}
require_once __DIR__ . '/../templates/header.php'; 
require_once __DIR__ . '/../templates/footer.php';

$user = $_SESSION['user'];
?>
<h2>Dashboard</h2>
<p>Bem-vindo, <?= htmlspecialchars($user['nome']) ?>!</p>

<?php if ($user['tipo'] === 'cliente'): ?>
  <section>
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
  <section>
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
  <section>
    <h3>Meus Serviços</h3>
    <p><a href="new_service.php" class="btn">+ Adicionar Novo Serviço</a></p>
    <?php 
      $stmt = $pdo->prepare("SELECT id, titulo, ativo, preco FROM servicos WHERE freelancer_id = ?");
      $stmt->execute([$user['id']]);
      $meusServicos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    ?>
    <?php if ($meusServicos): ?>
      <ul>
        <?php foreach($meusServicos as $sv): ?>
          <li>
            <?= htmlspecialchars($sv['titulo']) ?> 
            (<?= $sv['ativo'] ? 'Ativo' : 'Inativo' ?>) - €<?= htmlspecialchars($sv['preco']) ?>
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

<?php require_once __DIR__ . '/../templates/footer.php'; ?>
