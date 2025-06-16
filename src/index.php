<?php require './config/connection.php'; ?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <title>Portal de Produtos</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body {
      background-color: #f8f9fa;
    }
    .card-img-top {
      height: 200px;
      object-fit: cover;
    }
    footer {
      background-color: #343a40;
      color: white;
      text-align: center;
      padding: 1rem;
      margin-top: 3rem;
    }
  </style>
</head>
<body>

  <!-- Navbar -->
  <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container">
      <a class="navbar-brand" href="#">🛒 Portal de Produtos</a>
    </div>
  </nav>

  <!-- Conteúdo -->
  <div class="container mt-5">
    <h2 class="text-center mb-4">Confira nossos produtos</h2>
    <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 g-4">
      <?php
      $stmt = $pdo->query("SELECT * FROM Product");

      while ($produto = $stmt->fetch(PDO::FETCH_ASSOC)) {
      ?>
        <div class="col">
          <div class="card h-100 shadow-sm">
            <img src="<?= $produto['Image'] ?>" class="card-img-top" alt="<?= $produto['Name'] ?>">
            <div class="card-body d-flex flex-column">
              <h5 class="card-title"><?= htmlspecialchars($produto['Name']) ?></h5>
              <p class="card-text"><?= htmlspecialchars($produto['Description']) ?></p>
              <p class="mt-auto fw-bold text-primary">R$ <?= number_format($produto['Price'], 2, ',', '.') ?></p>
              <a href="./product/index.php?id=<?= $produto['ID'] ?>" class="btn btn-success mt-2">Comprar</a>
            </div>
          </div>
        </div>
      <?php } ?>
    </div>
  </div>

  <!-- Rodapé -->
  <footer>
    <div class="container">
      <p>&copy; <?= date('Y') ?> Portal de Produtos - Todos os direitos reservados.</p>
    </div>
  </footer>

</body>
</html>
