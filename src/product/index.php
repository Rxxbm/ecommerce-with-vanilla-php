<?php
require '../config/connection.php';

if (!isset($_GET['id'])) {
    echo "Produto não encontrado!";
    exit;
}

$id = (int) $_GET['id'];
$stmt = $pdo->prepare("SELECT * FROM Product WHERE ID = ?");
$stmt->execute([$id]);
$produto = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$produto) {
    echo "Produto não encontrado!";
    exit;
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <title><?= htmlspecialchars($produto['Name']) ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    .produto-img {
      max-width: 100%;
      height: auto;
    }
  </style>
</head>
<body>

  <!-- Navbar -->
  <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container">
      <a class="navbar-brand" href="index.php">🛒 Portal de Produtos</a>
      <a class="text-primary" href="./auth/login.php">Area do cliente</a>
    </div>
  </nav>

  <!-- Detalhes do Produto -->
  <div class="container mt-5">
    <div class="row">
      <div class="col-md-6">
        <img src="<?= $produto['Image'] ?>" alt="<?= $produto['Name'] ?>" class="produto-img img-fluid rounded shadow">
      </div>
      <div class="col-md-6">
        <h2><?= htmlspecialchars($produto['Name']) ?></h2>
        <p class="text-muted"><?= htmlspecialchars($produto['Description']) ?></p>
        <h4 class="text-primary">R$ <?= number_format($produto['Price'], 2, ',', '.') ?></h4>
        <p>Estoque: <?= $produto['Quantity'] ?></p>
        <a href="../index.php" class="btn btn-secondary mt-3">Voltar</a>
        <a href="#" class="btn btn-success mt-3">Adicionar ao Carrinho</a>
      </div>
    </div>
  </div>

</body>
</html>
