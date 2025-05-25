<?php
require_once __DIR__ . '/../templates/bootstrap.php';
// replace the strict freelancer check with this
if (empty($_SESSION['user'])
  || ! in_array($_SESSION['user']['tipo'], ['freelancer','cliente'], true)
) {
    header('Location: dashboard.php');
    exit;
}
$me       = $_SESSION['user'];
$clientId = filter_input(INPUT_GET,'client',FILTER_VALIDATE_INT);
if (!$clientId) {
    header('Location: inquiries.php'); exit;
}

// Fetch client info
$stmt = $pdo->prepare("SELECT name FROM users WHERE id = ?");
$stmt->execute([$clientId]);
$client = $stmt->fetch();
if (!$client) {
    header('Location: inquiries.php'); exit;
}

// handle reply
if ($_SERVER['REQUEST_METHOD']==='POST') {
    $text = trim($_POST['content'] ?? '');
    $offerPrice = floatval($_POST['offer_price'] ?? 0);
    $offerDays  = intval($_POST['offer_days'] ?? 0);

    if ($offerPrice>0 && $offerDays>0) {
        $text .= "\n\n**Oferta personalizada:** €{$offerPrice} – entrega em {$offerDays} dias.";
    }
    if ($text) {
        $ins = $pdo->prepare("
          INSERT INTO messages
            (sender_id, receiver_id, content)
          VALUES (?,?,?)
        ");
        $ins->execute([$me['id'],$clientId,$text]);
    }
    header("Location: messages.php?client=$clientId");
    exit;
}

// fetch all messages between you and client
$stmt = $pdo->prepare("
  SELECT m.*, 
         CASE WHEN m.sender_id=? THEN 'sent' ELSE 'received' END AS dir,
         u.name AS sender_name
    FROM messages m
    JOIN users u ON u.id = m.sender_id
   WHERE (m.sender_id=? AND m.receiver_id=?)
      OR (m.sender_id=? AND m.receiver_id=?)
   ORDER BY m.sent_at ASC
");
$stmt->execute([$me['id'],$me['id'],$clientId,$clientId,$me['id']]);
$chat = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt">
<head>
  <meta charset="UTF-8">
  <title>Conversa – <?= htmlspecialchars($client['name']) ?></title>
  <link rel="stylesheet" href="../css/style.css">
</head>
<body>
  <?php include __DIR__.'/../templates/header.php'; ?>
  <main class="messages-page">
    <h2 class="dashboard-title">Conversa com <?= htmlspecialchars($client['name']) ?></h2>
    <div class="chat-messages">
      <?php foreach($chat as $m): ?>
      <div class="message <?= $m['dir'] ?>">
        <div class="message-content"><?= nl2br(htmlspecialchars($m['content'])) ?></div>
        <div class="message-time"><?= date('d/m H:i',strtotime($m['sent_at'])) ?></div>
      </div>
      <?php endforeach; ?>
    </div>

    <form method="post" class="chat-input">
      <textarea name="content" rows="2" required placeholder="Escreva a sua resposta…"></textarea>
      <input type="number" step="0.01" name="offer_price" placeholder="€ Oferta" style="width:80px">
      <input type="number" name="offer_days" placeholder="dias" style="width:60px">
      <button type="submit" class="btn-primary">Enviar</button>
    </form>

    <p class="dashboard-return">
      <a href="inquiries.php" class="btn-primary">← Voltar às Consultas</a>
    </p>
  </main>
  <?php include __DIR__.'/../templates/footer.php'; ?>
</body>
</html>