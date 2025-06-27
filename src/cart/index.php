<?php
session_start();
require '../config/connection.php';

// Verifica se o usuário está logado
if (!isset($_SESSION['user'])) {
    $_SESSION['redirect_to_cart'] = true;
    header('Location: ../auth/login.php');
    exit;
}

// Verifica se existe carrinho, se não, cria
if (!isset($_SESSION['cart_id'])) {
    $stmt = $pdo->prepare("INSERT INTO Cart (Costumer_id) VALUES (?)");
    $stmt->execute([$_SESSION['user']['id']]);
    $_SESSION['cart_id'] = $pdo->lastInsertId();
}

// Lógica para remover item do carrinho
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_item'])) {
    $item_id = $_POST['item_id'];
    
    $stmt = $pdo->prepare("DELETE FROM CartItem WHERE ID = ? AND Cart_ID = ?");
    $stmt->execute([$item_id, $_SESSION['cart_id']]);
    
    // Atualiza totais do carrinho
    $stmt = $pdo->prepare("UPDATE Cart SET Itens = (SELECT SUM(QuantityItem) FROM CartItem WHERE Cart_ID = ?) WHERE ID = ?");
    $stmt->execute([$_SESSION['cart_id'], $_SESSION['cart_id']]);
    
    $stmt = $pdo->prepare("UPDATE Cart SET Total = (SELECT SUM(ci.QuantityItem * p.Price) FROM CartItem ci JOIN Product p ON ci.Product_ID = p.ID WHERE ci.Cart_ID = ?) WHERE ID = ?");
    $stmt->execute([$_SESSION['cart_id'], $_SESSION['cart_id']]);
    
    $_SESSION['cart_message'] = "Item removido do carrinho!";
    header('Location: ./index.php');
    exit;
}

// Lógica para atualizar quantidades
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_quantity'])) {
    $item_id = $_POST['item_id'];
    $quantity = $_POST['quantity'];
    
    if ($quantity > 0) {
        $stmt = $pdo->prepare("UPDATE CartItem SET QuantityItem = ? WHERE ID = ? AND Cart_ID = ?");
        $stmt->execute([$quantity, $item_id, $_SESSION['cart_id']]);
    } else {
        $stmt = $pdo->prepare("DELETE FROM CartItem WHERE ID = ? AND Cart_ID = ?");
        $stmt->execute([$item_id, $_SESSION['cart_id']]);
    }
    
    // Atualiza totais do carrinho
    $stmt = $pdo->prepare("UPDATE Cart SET Itens = (SELECT SUM(QuantityItem) FROM CartItem WHERE Cart_ID = ?) WHERE ID = ?");
    $stmt->execute([$_SESSION['cart_id'], $_SESSION['cart_id']]);
    
    $stmt = $pdo->prepare("UPDATE Cart SET Total = (SELECT SUM(ci.QuantityItem * p.Price) FROM CartItem ci JOIN Product p ON ci.Product_ID = p.ID WHERE ci.Cart_ID = ?) WHERE ID = ?");
    $stmt->execute([$_SESSION['cart_id'], $_SESSION['cart_id']]);
    
    $_SESSION['cart_message'] = "Carrinho atualizado!";
    header('Location: ./index.php');
    exit;
}

// Busca itens do carrinho
$stmt = $pdo->prepare("
    SELECT ci.ID as cart_item_id, p.ID as product_id, p.Name, p.Price, p.Image, ci.QuantityItem
    FROM CartItem ci
    JOIN Product p ON ci.Product_ID = p.ID
    WHERE ci.Cart_ID = ?
");
$stmt->execute([$_SESSION['cart_id']]);
$cart_items = $stmt->fetchAll();

// Busca informações do carrinho
$stmt = $pdo->prepare("SELECT * FROM Cart WHERE ID = ?");
$stmt->execute([$_SESSION['cart_id']]);
$cart = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Carrinho - Portal de Produtos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        .cart-item-img {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 5px;
        }
        .quantity-input {
            width: 60px;
            text-align: center;
        }
        .summary-card {
            background-color: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 0 10px rgba(0,0,0,0.05);
        }
    </style>
</head>
<body>
    <!-- Navbar (igual à home) -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="./index.php">🛒 Portal de Produtos</a>
            <div class="d-flex align-items-center">
                <a href="../index.php" class="btn btn-outline-light me-2">
                    <i class="bi bi-house"></i> Home
                </a>
                <a href="./index.php" class="btn btn-outline-light position-relative">
                    <i class="bi bi-cart3"></i>
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                        <?= $cart['Itens'] ?? 0 ?>
                    </span>
                </a>
            </div>
        </div>
    </nav>

    <div class="container my-5">
        <h2 class="mb-4"><i class="bi bi-cart3"></i> Seu Carrinho</h2>
        
        <?php if (isset($_SESSION['cart_message'])): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <?= $_SESSION['cart_message'] ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['cart_message']); ?>
        <?php endif; ?>
        
        <div class="row">
            <div class="col-lg-8">
                <?php if (count($cart_items) > 0): ?>
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Produto</th>
                                            <th>Preço</th>
                                            <th>Quantidade</th>
                                            <th>Total</th>
                                            <th>Ações</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($cart_items as $item): ?>
                                            <tr>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <img src="<?= $item['Image'] ?>" alt="<?= $item['Name'] ?>" class="cart-item-img me-3">
                                                        <div>
                                                            <h6 class="mb-0"><?= htmlspecialchars($item['Name']) ?></h6>
                                                            <small class="text-muted">ID: <?= $item['product_id'] ?></small>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>R$ <?= number_format($item['Price'], 2, ',', '.') ?></td>
                                                <td>
                                                    <form method="POST" class="d-flex">
                                                        <input type="hidden" name="item_id" value="<?= $item['cart_item_id'] ?>">
                                                        <input type="number" name="quantity" value="<?= $item['QuantityItem'] ?>" min="1" class="form-control quantity-input">
                                                        <button type="submit" name="update_quantity" class="btn btn-sm btn-outline-primary ms-2">
                                                            <i class="bi bi-arrow-clockwise"></i>
                                                        </button>
                                                    </form>
                                                </td>
                                                <td>R$ <?= number_format($item['Price'] * $item['QuantityItem'], 2, ',', '.') ?></td>
                                                <td>
                                                    <form method="POST">
                                                        <input type="hidden" name="item_id" value="<?= $item['cart_item_id'] ?>">
                                                        <button type="submit" name="remove_item" class="btn btn-sm btn-outline-danger">
                                                            <i class="bi bi-trash"></i>
                                                        </button>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="card shadow-sm">
                        <div class="card-body text-center py-5">
                            <i class="bi bi-cart-x" style="font-size: 3rem; color: #adb5bd;"></i>
                            <h4 class="mt-3">Seu carrinho está vazio</h4>
                            <p class="text-muted">Adicione produtos ao seu carrinho para continuar</p>
                            <a href="../index.php" class="btn btn-primary">
                                <i class="bi bi-bag"></i> Continuar comprando
                            </a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="col-lg-4 mt-4 mt-lg-0">
                <div class="summary-card sticky-top" style="top: 20px;">
                    <h5 class="mb-4">Resumo do Pedido</h5>
                    
                    <div class="d-flex justify-content-between mb-2">
                        <span>Subtotal</span>
                        <span>R$ <?= number_format($cart['Total'] ?? 0, 2, ',', '.') ?></span>
                    </div>
                    
                    <div class="d-flex justify-content-between mb-3">
                        <span>Frete</span>
                        <span>Grátis</span>
                    </div>
                    
                    <hr>
                    
                    <div class="d-flex justify-content-between fw-bold mb-4">
                        <span>Total</span>
                        <span>R$ <?= number_format($cart['Total'] ?? 0, 2, ',', '.') ?></span>
                    </div>
                    
                    <a href="./checkout.php" class="btn btn-primary w-100 py-2">
                        <i class="bi bi-credit-card"></i> Finalizar Compra
                    </a>
                    
                    <div class="mt-3 text-center">
                        <small class="text-muted">Ou continue comprando</small>
                        <a href="../index.php" class="d-block mt-1">Voltar para a loja</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>