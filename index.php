<?php
require_once __DIR__ . '/templates/bootstrap.php'; 

// Só redireciona se houver sessão E não vier o parâmetro skip=1
if (!isset($_GET['skip']) && isset($_SESSION['user'])) {
    header("Location: pages/dashboard.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
  <meta charset="UTF-8">
  <title>ltw07g06 – Plataforma de Freelancers</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">  <!-- Responsividade -->
  <link rel="stylesheet" href="css/style.css">
</head>
<body>
  <?php include __DIR__ . '/templates/header.php'; ?><!-- Cabeçalho com logo e menu -->

  <!-- Secção Hero / Banner principal -->
  <section class="hero">
    <div class="hero-content">
      <h1>Encontre os melhores freelancers para o seu projeto</h1>
      <p>Plataforma académica de contratação de freelancers.</p>
    </div>
  </section>

  <!-- Secção de Categorias em destaque -->
  <section class="categorias-destaque">
    <h2>Categorias Populares</h2>
    <div class="categorias-lista">
      <div class="categoria-item">
        <img src="img/icon-programacao.png" alt="Programação">
        <span>Programação</span>
      </div>
      <div class="categoria-item">
        <img src="img/icon-design.png" alt="Design Gráfico">
        <span>Design Gráfico</span>
      </div>
      <div class="categoria-item">
        <img src="img/icon-escrita.png" alt="Escrita">
        <span>Escrita</span>
      </div>
    </div>
  </section>

  <!-- Secção de Serviços em destaque -->
  <section class="servicos-destaque">
    <h2>Serviços em Destaque</h2>
    <div class="servicos-grid">
      <!-- Card de serviço 1 -->
      <div class="servico-card">
        <img src="img/website.jpg" alt="Desenvolvimento de Website">
        <h3>Desenvolvimento de Website</h3>
        <p class="descricao">Criação de um website profissional e responsivo.</p>
        <p class="preco"><strong>€500</strong> – por 14 dias</p>
      </div>
      <!-- Card de serviço 2 -->
      <div class="servico-card">
        <img src="img/logo.png" alt="Logotipo Personalizado">
        <h3>Logotipo Personalizado</h3>
        <p class="descricao">Design único de logotipo para a sua marca.</p>
        <p class="preco"><strong>€100</strong> – por 5 dias</p>
      </div>
      <!-- Card de serviço 3 -->
      <div class="servico-card">
        <img src="img/article.png" alt="Artigo para Blog">
        <h3>Artigo para Blog</h3>
        <p class="descricao">Artigo otimizado para SEO (~1000 palavras).</p>
        <p class="preco"><strong>€30</strong> – por 3 dias</p>
      </div>
    </div>
  </section>

  <?php include __DIR__ . '/templates/footer.php'; ?><!-- Rodapé com contactos, etc. -->
</body>
</html>
