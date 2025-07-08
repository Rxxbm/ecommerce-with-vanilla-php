<?php
session_start();
require '../config/connection.php';

if (!isset($_SESSION['user'])) {
    header('Location: ../auth/login.php');
    exit;
}

if ($_SESSION['user']['role'] !== 'user') {
    header('Location: ../dashboard/admin.php');
    exit;
}

$user_id = $_SESSION['user']['id'];
$user_name = $_SESSION['user']['name'];

// Busca os pedidos do cliente
$stmt_orders = $pdo->prepare("
    SELECT o.ID, o.Total, o.Status, o.Created_at, 
           COUNT(oi.ID) as items_count
    FROM `Order` o
    LEFT JOIN OrderItem oi ON o.ID = oi.Order_ID
    WHERE o.Costumer_id = ?
    GROUP BY o.ID
    ORDER BY o.Created_at DESC
    LIMIT 5
");
$stmt_orders->execute([$user_id]);
$orders = $stmt_orders->fetchAll();

// Busca produtos recomendados
$stmt_products = $pdo->query("
    SELECT p.*, c.Name as category_name 
    FROM Product p
    JOIN Category c ON p.Category_id = c.ID
    ORDER BY RAND()
    LIMIT 4
");
$recommended_products = $stmt_products->fetchAll();

// Busca informações do carrinho
$cart_items = 0;
$cart_total = 0;

if (isset($_SESSION['cart_id'])) {
    $stmt_cart = $pdo->prepare("
        SELECT SUM(ci.QuantityItem) as items, 
               SUM(ci.QuantityItem * p.Price) as total
        FROM CartItem ci
        JOIN Product p ON ci.Product_ID = p.ID
        WHERE ci.Cart_ID = ?
    ");
    $stmt_cart->execute([$_SESSION['cart_id']]);
    $cart_info = $stmt_cart->fetch();
    
    if ($cart_info) {
        $cart_items = $cart_info['items'] ?? 0;
        $cart_total = $cart_info['total'] ?? 0;
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Minha Conta - Portal de Produtos</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
  <style>
    :root {
      --primary-color: #2575fc;
      --secondary-color: #6a11cb;
      --dark-color: #343a40;
      --light-color: #f8f9fa;
    }
    
    body {
      background-color: var(--light-color);
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }
    
    /* Navbar */
    .navbar-brand {
      font-weight: 700;
      font-size: 1.5rem;
    }
    
    /* Sidebar */
    .sidebar {
      background: linear-gradient(135deg, var(--dark-color) 0%, #212529 100%);
      color: white;
      min-height: 100vh;
      width: 280px;
      position: fixed;
      box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
      z-index: 1000;
    }
    
    .sidebar-header {
      padding: 20px;
      border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    }
    
    .nav-link {
      color: rgba(255, 255, 255, 0.8);
      border-radius: 5px;
      margin: 5px 15px;
      transition: all 0.3s;
    }
    
    .nav-link:hover, .nav-link.active {
      background: rgba(255, 255, 255, 0.1);
      color: white;
      transform: translateX(5px);
    }
    
    .nav-link i {
      width: 24px;
      text-align: center;
    }
    
    /* Main Content */
    .main-content {
      margin-left: 280px;
      padding: 30px;
      transition: all 0.3s;
    }
    
    @media (max-width: 992px) {
      .sidebar {
        background: linear-gradient(135deg, var(--dark-color) 0%, #212529 100%);
        color: white;
        min-height: 100vh;
        width: 280px;
        position: fixed;
        left: -280px; /* Esconde inicialmente */
        transition: left 0.3s;
        z-index: 1000;
      }

      .sidebar.show {
        left: 0; /* Mostra quando tiver a classe .show */
      }

      .main-content {
        margin-left: 0;
      }
    }
    
    /* Welcome Banner */
    .welcome-banner {
      background: linear-gradient(135deg, var(--secondary-color) 0%, var(--primary-color) 100%);
      color: white;
      border-radius: 10px;
      padding: 30px;
      margin-bottom: 30px;
      box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
    }
    
    /* Cards */
    .card {
      border: none;
      border-radius: 10px;
      box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
      transition: all 0.3s;
      overflow: hidden;
    }
    
    .card:hover {
      transform: translateY(-5px);
      box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
    }
    
    .card-header {
      background-color: white;
      border-bottom: 1px solid rgba(0, 0, 0, 0.05);
      font-weight: 600;
    }
    
    /* Product Cards */
    .product-card .card-img-top {
      height: 180px;
      object-fit: cover;
      transition: transform 0.5s;
    }
    
    .product-card:hover .card-img-top {
      transform: scale(1.05);
    }
    
    .product-card .card-title {
      font-size: 1rem;
      font-weight: 600;
      margin-bottom: 0.5rem;
    }
    
    .product-card .category {
      font-size: 0.8rem;
      color: #6c757d;
    }
    
    /* Status Badges */
    .status-badge {
      padding: 5px 12px;
      border-radius: 20px;
      font-size: 0.75rem;
      font-weight: 600;
      text-transform: capitalize;
    }
    
    .status-pending {
      background-color: #fff3cd;
      color: #856404;
    }
    
    .status-paid {
      background-color: #d4edda;
      color: #155724;
    }
    
    .status-shipped {
      background-color: #cce5ff;
      color: #004085;
    }
    
    .status-delivered {
      background-color: #d1e7dd;
      color: #0f5132;
    }
    
    .status-canceled {
      background-color: #f8d7da;
      color: #842029;
    }
    
    /* Cart Summary */
    .cart-summary {
      background: rgba(0, 0, 0, 0.2);
      padding: 15px;
      border-radius: 8px;
      margin-top: auto;
    }
    
    /* Mobile Menu Button */
    .mobile-menu-btn {
      position: fixed;
      bottom: 20px;
      right: 20px;
      z-index: 999;
      display: none;
    }
    
    @media (max-width: 992px) {
      .mobile-menu-btn {
        display: block;
      }
    }
  </style>
</head>
<body>
  <!-- Sidebar -->
  <div class="sidebar" id="sidebar">
    <div class="sidebar-header text-center">
      <h4>🛒 Portal de Produtos</h4>
    </div>
    
    <ul class="nav flex-column mt-4">
      <li class="nav-item">
        <a class="nav-link active" href="#">
          <i class="bi bi-speedometer2"></i> Dashboard
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link" href="../orders.php">
          <i class="bi bi-receipt"></i> Meus Pedidos
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link" href="./client/settings.php">
          <i class="bi bi-gear"></i> Configurações
        </a>
      </li>
      <li class="nav-item mt-4">
        <div class="cart-summary">
          <h6><i class="bi bi-cart3"></i> Seu Carrinho</h6>
          <p class="mb-1">Itens: <?= $cart_items ?></p>
          <p class="mb-2">Total: R$ <?= number_format($cart_total, 2, ',', '.') ?></p>
          <a href="../cart.php" class="btn btn-sm btn-primary w-100">
            <i class="bi bi-arrow-right"></i> Ver Carrinho
          </a>
        </div>
      </li>
      <li class="nav-item mt-3">
        <a class="nav-link text-danger" href="../auth/logout.php">
          <i class="bi bi-box-arrow-right"></i> Sair
        </a>
      </li>
    </ul>
  </div>

  <!-- Mobile Menu Button -->
  <button class="btn btn-primary mobile-menu-btn rounded-circle" style="width: 50px; height: 50px;" id="mobileMenuBtn">
    <i class="bi bi-list" style="font-size: 1.5rem;"></i>
  </button>

  <!-- Main Content -->
  <div class="main-content">
    <!-- Welcome Banner -->
    <div class="welcome-banner">
      <div class="row align-items-center">
        <div class="col-md-8">
          <h2 class="mb-3">Olá, <?= htmlspecialchars($user_name) ?>!</h2>
          <p class="lead mb-0">Bem-vindo ao seu painel de controle. Aqui você pode acompanhar seus pedidos, lista de desejos e muito mais.</p>
        </div>
        <div class="col-md-4 text-center d-none d-md-block">
          <img src="https://cdn-icons-png.flaticon.com/512/3135/3135715.png" alt="Ícone de usuário" style="height: 120px; filter: drop-shadow(0 0 10px rgba(0,0,0,0.2));">
        </div>
      </div>
    </div>

    <!-- Recent Orders -->
    <div class="card mb-4">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="bi bi-receipt"></i> Pedidos Recentes</h5>
        <a href="../orders.php" class="btn btn-sm btn-outline-primary">Ver Todos</a>
      </div>
      <div class="card-body">
        <?php if (count($orders) > 0): ?>
          <div class="table-responsive">
            <table class="table table-hover">
              <thead class="table-light">
                <tr>
                  <th>Nº Pedido</th>
                  <th>Data</th>
                  <th>Itens</th>
                  <th>Total</th>
                  <th>Status</th>
                  <th>Ações</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($orders as $order): ?>
                  <tr>
                    <td class="fw-bold">#<?= str_pad($order['ID'], 6, '0', STR_PAD_LEFT) ?></td>
                    <td><?= date('d/m/Y', strtotime($order['Created_at'])) ?></td>
                    <td><?= $order['items_count'] ?></td>
                    <td class="fw-bold">R$ <?= number_format($order['Total'], 2, ',', '.') ?></td>
                    <td>
                      <span class="status-badge status-<?= strtolower($order['Status']) ?>">
                        <?= ucfirst($order['Status']) ?>
                      </span>
                    </td>
                    <td>
                      <a href="../order.php?id=<?= $order['ID'] ?>" class="btn btn-sm btn-outline-primary">
                        <i class="bi bi-eye"></i> Detalhes
                      </a>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php else: ?>
          <div class="text-center py-5">
            <i class="bi bi-cart-x" style="font-size: 3rem; color: #adb5bd;"></i>
            <h5 class="mt-3">Você ainda não fez nenhum pedido</h5>
            <p class="text-muted">Quando você fizer pedidos, eles aparecerão aqui.</p>
            <a href="../index.php" class="btn btn-primary">
              <i class="bi bi-bag"></i> Compre Agora
            </a>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- Recommended Products -->
    <div class="card">
      <div class="card-header">
        <h5 class="mb-0"><i class="bi bi-star"></i> Recomendados para Você</h5>
      </div>
      <div class="card-body">
        <div class="row">
          <?php foreach ($recommended_products as $product): ?>
            <div class="col-lg-3 col-md-6 mb-4">
              <div class="card product-card h-100">
                <div class="overflow-hidden" style="height: 180px;">
                  <img src="../uploads/<?= htmlspecialchars($product['Image']) ?>" class="card-img-top w-100 h-100" alt="<?= htmlspecialchars($product['Name']) ?>">
                </div>
                <div class="card-body">
                  <h6 class="card-title"><?= htmlspecialchars($product['Name']) ?></h6>
                  <p class="category text-muted small mb-2"><?= htmlspecialchars($product['category_name']) ?></p>
                  <p class="card-text text-success fw-bold mb-3">R$ <?= number_format($product['Price'], 2, ',', '.') ?></p>
                  <form action="../cart/add.php" method="post" class="d-flex">
                    <input type="hidden" name="product_id" value="<?= $product['ID'] ?>">
                    <input type="number" name="quantity" value="1" min="1" class="form-control form-control-sm me-2" style="width: 60px;">
                    <button type="submit" class="btn btn-sm btn-primary flex-grow-1">
                      <i class="bi bi-cart-plus"></i> Adicionar
                    </button>
                  </form>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script>
        const mobileMenuBtn = document.getElementById('mobileMenuBtn');
        const sidebar = document.getElementById('sidebar');

        mobileMenuBtn.addEventListener('click', () => {
        sidebar.classList.toggle('show');
        });
  </script>
</body>
</html>