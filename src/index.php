<?php
session_start();
require './config/connection.php';

// Lógica para adicionar ao carrinho
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
  $product_id = $_POST['product_id'];
  $quantity = $_POST['quantity'] ?? 1;
  
  // Verifica se o usuário está logado
  if (!isset($_SESSION['user'])) {
      $_SESSION['redirect_to_cart'] = true;
      header('Location: ./auth/login.php');
      exit;
  }
  
  // Verifica se o carrinho existe, se não, cria
  if (!isset($_SESSION['cart_id'])) {
      $stmt = $pdo->prepare("INSERT INTO Cart (Costumer_id) VALUES (?)");
      $stmt->execute([$_SESSION['user']['id']]);
      $_SESSION['cart_id'] = $pdo->lastInsertId();
  }
  
  // Verifica se o produto já está no carrinho
  $stmt = $pdo->prepare("SELECT * FROM CartItem WHERE Cart_ID = ? AND Product_ID = ?");
  $stmt->execute([$_SESSION['cart_id'], $product_id]);
  $existing_item = $stmt->fetch();
  
  if ($existing_item) {
      // Atualiza quantidade
      $new_quantity = $existing_item['QuantityItem'] + $quantity;
      $stmt = $pdo->prepare("UPDATE CartItem SET QuantityItem = ? WHERE ID = ?");
      $stmt->execute([$new_quantity, $existing_item['ID']]);
  } else {
      // Adiciona novo item
      $stmt = $pdo->prepare("INSERT INTO CartItem (Cart_ID, Product_ID, QuantityItem) VALUES (?, ?, ?)");
      $stmt->execute([$_SESSION['cart_id'], $product_id, $quantity]);
  }
  
  // Atualiza contagem total do carrinho
  $stmt = $pdo->prepare("UPDATE Cart SET Itens = (SELECT SUM(QuantityItem) FROM CartItem WHERE Cart_ID = ?) WHERE ID = ?");
  $stmt->execute([$_SESSION['cart_id'], $_SESSION['cart_id']]);
  
  // Atualiza total do carrinho
  $stmt = $pdo->prepare("UPDATE Cart SET Total = (SELECT SUM(ci.QuantityItem * p.Price) FROM CartItem ci JOIN Product p ON ci.Product_ID = p.ID WHERE ci.Cart_ID = ?) WHERE ID = ?");
  $stmt->execute([$_SESSION['cart_id'], $_SESSION['cart_id']]);
  
  $_SESSION['cart_message'] = "Produto adicionado ao carrinho!";
  header('Location: ./cart/index.php');
  exit;
}

// Verifica se o usuário está logado
$is_logged_in = isset($_SESSION['user']);
$user_name = $is_logged_in ? $_SESSION['user']['name'] : '';
$cart_count = 0;
$cart_total = 0;

// Se estiver logado, busca informações do carrinho
if ($is_logged_in && isset($_SESSION['cart_id'])) {
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
        $cart_count = $cart_info['items'] ?? 0;
        $cart_total = $cart_info['total'] ?? 0;
    }
}

// Busca produtos
$search_term = $_GET['search'] ?? '';

if (!empty($search_term)) {
    $stmt = $pdo->prepare("
        SELECT p.*, c.Name as category_name 
        FROM Product p
        JOIN Category c ON p.Category_id = c.ID
        WHERE p.Name LIKE :search OR p.Description LIKE :search
        ORDER BY p.Created_at DESC
    ");
    $stmt->execute(['search' => '%' . $search_term . '%']);
} else {
    $stmt = $pdo->query("
        SELECT p.*, c.Name as category_name 
        FROM Product p
        JOIN Category c ON p.Category_id = c.ID
        ORDER BY p.Created_at DESC
    ");
}
$products = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Portal de Produtos</title>
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
    .navbar {
      background: linear-gradient(135deg, var(--dark-color) 0%, #212529 100%);
      box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    }
    
    .navbar-brand {
      font-weight: 700;
      font-size: 1.5rem;
      letter-spacing: 0.5px;
    }
    
    /* Hero Section */
    .hero-section {
      background: linear-gradient(135deg, var(--secondary-color) 0%, var(--primary-color) 100%);
      color: white;
      padding: 4rem 0;
      margin-bottom: 3rem;
      border-radius: 0 0 20px 20px;
    }
    
    /* Product Cards */
    .product-card {
      border: none;
      border-radius: 10px;
      overflow: hidden;
      box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
      transition: all 0.3s ease;
      height: 100%;
    }
    
    .product-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
    }
    
    .product-card .card-img-top {
      height: 200px;
      object-fit: cover;
      transition: transform 0.5s;
    }
    
    .product-card:hover .card-img-top {
      transform: scale(1.05);
    }
    
    .product-card .card-body {
      display: flex;
      flex-direction: column;
    }
    
    .product-card .card-title {
      font-size: 1.1rem;
      font-weight: 600;
      margin-bottom: 0.5rem;
    }
    
    .product-card .card-text {
      color: #6c757d;
      font-size: 0.9rem;
      flex-grow: 1;
    }
    
    .product-card .price {
      font-size: 1.2rem;
      font-weight: 700;
      color: var(--primary-color);
      margin: 0.5rem 0;
    }
    
    /* Cart Badge */
    .cart-badge {
      position: relative;
    }
    
    .cart-count {
      position: absolute;
      top: -5px;
      right: -5px;
      background-color: #dc3545;
      color: white;
      border-radius: 50%;
      width: 20px;
      height: 20px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 0.7rem;
      font-weight: bold;
    }
    
    /* Footer */
    footer {
      background: linear-gradient(135deg, var(--dark-color) 0%, #212529 100%);
      color: white;
      padding: 2rem 0;
      margin-top: 4rem;
    }
    
    /* Responsive adjustments */
    @media (max-width: 768px) {
      .hero-section {
        padding: 2rem 0;
      }
      
      .hero-section h1 {
        font-size: 1.8rem;
      }
    }
  </style>
</head>
<body>
  <!-- Navbar -->
  <nav class="navbar navbar-expand-lg navbar-dark">
    <div class="container">
      <a class="navbar-brand" href="#">
        <i class="bi bi-cart3"></i> Portal de Produtos
      </a>
      
      <div class="d-flex align-items-center">
        <?php if ($is_logged_in): ?>
          <div class="dropdown me-3">
            <a href="#" class="text-white dropdown-toggle" id="userDropdown" data-bs-toggle="dropdown">
              <i class="bi bi-person-circle"></i> <?= htmlspecialchars($user_name) ?>
            </a>
            <ul class="dropdown-menu dropdown-menu-end">
              <li><a class="dropdown-item" href="./dashboard/client.php"><i class="bi bi-speedometer2"></i> Minha Conta</a></li>
              <li><a class="dropdown-item" href="./orders/index.php"><i class="bi bi-receipt"></i> Meus Pedidos</a></li>
              <li><hr class="dropdown-divider"></li>
              <li><a class="dropdown-item text-danger" href="./auth/logout.php"><i class="bi bi-box-arrow-right"></i> Sair</a></li>
            </ul>
          </div>
        <?php else: ?>
          <a href="./auth/login.php" class="btn btn-outline-light me-2">
            <i class="bi bi-box-arrow-in-right"></i> Login
          </a>
          <a href="./auth/register.php" class="btn btn-primary">
            <i class="bi bi-person-plus"></i> Cadastre-se
          </a>
        <?php endif; ?>
        
        <a href="./cart/index.php" class="btn btn-outline-light ms-3 cart-badge">
          <i class="bi bi-cart3"></i>
          <?php if ($cart_count > 0): ?>
            <span class="cart-count"><?= $cart_count ?></span>
          <?php endif; ?>
        </a>
      </div>
    </div>
  </nav>

  <!-- Hero Section -->
  <section class="hero-section">
    <div class="container text-center">
      <h1 class="display-4 fw-bold mb-3">Bem-vindo ao Portal de Produtos</h1>
      <p class="lead mb-4">Encontre os melhores produtos com os melhores preços</p>
      <form class="d-flex justify-content-center" method="GET" action="">
        <div class="input-group mb-3" style="max-width: 500px;">
          <input type="text" class="form-control" name="search" placeholder="O que você está procurando?" value="<?= isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '' ?>">
          <button class="btn btn-light" type="submit">
            <i class="bi bi-search"></i> Buscar
          </button>
        </div>
      </form>
    </div>
  </section>

  <!-- Product Grid -->
  <div class="container mb-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold">Nossos Produtos</h2>
        <div class="dropdown">
            <button class="btn btn-outline-secondary dropdown-toggle" type="button" id="filterDropdown" data-bs-toggle="dropdown">
                <i class="bi bi-filter"></i> Filtrar
            </button>
            <ul class="dropdown-menu">
                <li><a class="dropdown-item filter-option" href="#" data-filter="recent">Mais recentes</a></li>
                <li><a class="dropdown-item filter-option" href="#" data-filter="price_asc">Menor preço</a></li>
                <li><a class="dropdown-item filter-option" href="#" data-filter="price_desc">Maior preço</a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item filter-option" href="#" data-filter="popular">Mais populares</a></li>
            </ul>
        </div>
    </div>
    
    <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 g-4" id="products-container">
        <?php foreach ($products as $produto): ?>
            <div class="col product-item" data-price="<?= $produto['Price'] ?>" data-date="<?= strtotime($produto['Created_at']) ?>">
                <div class="card product-card">
                    <img src="./uploads/<?= htmlspecialchars($produto['Image']) ?>" class="card-img-top" alt="<?= htmlspecialchars($produto['Name']) ?>">
                    <div class="card-body">
                        <span class="badge bg-secondary mb-2"><?= htmlspecialchars($produto['category_name']) ?></span>
                        <h5 class="card-title"><?= htmlspecialchars($produto['Name']) ?></h5>
                        <p class="card-text"><?= htmlspecialchars(mb_strimwidth($produto['Description'], 0, 100, '...')) ?></p>
                        <div class="price">R$ <?= number_format($produto['Price'], 2, ',', '.') ?></div>
                        <div class="d-flex gap-2 mt-2">
                            <a href="./product/index.php?id=<?= $produto['ID'] ?>" class="btn btn-outline-primary flex-grow-1">
                                <i class="bi bi-eye"></i> Ver
                            </a>
                            <form method="POST" class="flex-grow-1">
                              <input type="hidden" name="product_id" value="<?= $produto['ID'] ?>">
                              <input type="hidden" name="add_to_cart" value="1">
                              <button type="submit" class="btn btn-primary w-100">
                                  <i class="bi bi-cart-plus"></i> Adicionar
                              </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

  <!-- Footer -->
  <footer>
    <div class="container">
      <div class="row">
        <div class="col-md-4 mb-4 mb-md-0">
          <h5><i class="bi bi-cart3"></i> Portal de Produtos</h5>
          <p class="mt-3">A melhor loja online para encontrar o que você precisa.</p>
        </div>
        <div class="col-md-2 mb-4 mb-md-0">
          <h5>Links</h5>
          <ul class="list-unstyled">
            <li class="mb-2"><a href="#" class="text-white">Início</a></li>
            <li class="mb-2"><a href="#" class="text-white">Produtos</a></li>
            <li class="mb-2"><a href="#" class="text-white">Sobre nós</a></li>
            <li><a href="#" class="text-white">Contato</a></li>
          </ul>
        </div>
        <div class="col-md-3 mb-4 mb-md-0">
          <h5>Ajuda</h5>
          <ul class="list-unstyled">
            <li class="mb-2"><a href="#" class="text-white">FAQ</a></li>
            <li class="mb-2"><a href="#" class="text-white">Entregas</a></li>
            <li class="mb-2"><a href="#" class="text-white">Devoluções</a></li>
            <li><a href="#" class="text-white">Pagamentos</a></li>
          </ul>
        </div>
        <div class="col-md-3">
          <h5>Contato</h5>
          <ul class="list-unstyled">
            <li class="mb-2"><i class="bi bi-envelope"></i> contato@portalprodutos.com</li>
            <li class="mb-2"><i class="bi bi-telephone"></i> (11) 99999-9999</li>
            <li><i class="bi bi-geo-alt"></i> São Paulo, SP</li>
          </ul>
        </div>
      </div>
      <hr class="my-4 bg-light">
      <div class="text-center">
        <p class="mb-0">&copy; <?= date('Y') ?> Portal de Produtos. Todos os direitos reservados.</p>
      </div>
    </div>
  </footer>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script>

document.addEventListener('DOMContentLoaded', function() {
    const filterOptions = document.querySelectorAll('.filter-option');
    const productsContainer = document.getElementById('products-container');
    const productItems = Array.from(document.querySelectorAll('.product-item'));

    filterOptions.forEach(option => {
        option.addEventListener('click', function(e) {
            e.preventDefault();
            const filterType = this.getAttribute('data-filter');
            
            // Ordena os produtos conforme o filtro selecionado
            const sortedProducts = [...productItems].sort((a, b) => {
                switch(filterType) {
                    case 'recent':
                        return parseInt(b.getAttribute('data-date')) - parseInt(a.getAttribute('data-date'));
                    case 'price_asc':
                        return parseFloat(a.getAttribute('data-price')) - parseFloat(b.getAttribute('data-price'));
                    case 'price_desc':
                        return parseFloat(b.getAttribute('data-price')) - parseFloat(a.getAttribute('data-price'));
                    case 'popular':
                        // Implemente a lógica de popularidade conforme seu sistema
                        // Por exemplo: data-views="100" nos elementos
                        return Math.random() - 0.5; // Exemplo temporário
                    default:
                        return 0;
                }
            });

            // Remove todos os produtos do container
            while (productsContainer.firstChild) {
                productsContainer.removeChild(productsContainer.firstChild);
            }

            // Adiciona os produtos ordenados de volta ao container
            sortedProducts.forEach(product => {
                productsContainer.appendChild(product);
            });

            // Atualiza o texto do dropdown para mostrar o filtro selecionado
            const dropdownToggle = document.querySelector('#filterDropdown');
            dropdownToggle.innerHTML = `<i class="bi bi-filter"></i> ${this.textContent}`;
        });
    });});
    // Adicionar ao carrinho com AJAX
    document.querySelectorAll('form[action="./cart/add.php"]').forEach(form => {
      form.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        
        try {
          const response = await fetch('./cart/add.php', {
            method: 'POST',
            body: formData
          });
          
          const result = await response.json();
          
          if (result.success) {
            // Atualizar contador do carrinho
            const cartCount = document.querySelector('.cart-count');
            if (cartCount) {
              cartCount.textContent = result.cart_count;
            } else {
              const cartBadge = document.querySelector('.cart-badge');
              cartBadge.innerHTML = `<i class="bi bi-cart3"></i><span class="cart-count">${result.cart_count}</span>`;
            }
            
            // Mostrar notificação
            const toast = document.createElement('div');
            toast.className = 'position-fixed bottom-0 end-0 p-3';
            toast.style.zIndex = '11';
            toast.innerHTML = `
              <div class="toast show" role="alert">
                <div class="toast-header bg-success text-white">
                  <strong class="me-auto">Sucesso!</strong>
                  <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast"></button>
                </div>
                <div class="toast-body">
                  Produto adicionado ao carrinho!
                </div>
              </div>
            `;
            document.body.appendChild(toast);
            
            // Remover notificação após 3 segundos
            setTimeout(() => {
              toast.remove();
            }, 3000);
          }
        } catch (error) {
          console.error('Erro:', error);
        }
      });
    });
  </script>
</body>
</html>