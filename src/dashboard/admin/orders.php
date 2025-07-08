<?php
session_start();
require '../../config/connection.php';

// Verifica se o usuário está logado e é admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('Location: ../auth/login.php');
    exit;
}

$erro = '';
$sucesso = '';

// Envia produto para o banco
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $description = $_POST['description'] ?? '';
    $price = floatval($_POST['price']) ?? 0;
    $quantity = intval($_POST['quantity']) ?? 0;
    $category_id = intval($_POST['category_id']) ?? null;

    // Upload da imagem
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $image_name = uniqid() . '.' . $ext;
        $image_path = '../uploads/' . $image_name;

        if (move_uploaded_file($_FILES['image']['tmp_name'], $image_path)) {
            $stmt = $pdo->prepare("
                INSERT INTO Product (Name, Description, Price, Quantity, Image, Category_id)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            if ($stmt->execute([$name, $description, $price, $quantity, $image_name, $category_id])) {
                $sucesso = "Produto cadastrado com sucesso!";
            } else {
                $erro = "Erro ao cadastrar o produto.";
            }
        } else {
            $erro = "Erro ao salvar imagem.";
        }
    } else {
        $erro = "Imagem não enviada corretamente.";
    }
}

// Buscar categorias
$stmt_categories = $pdo->query("SELECT ID, Name FROM Category ORDER BY Name");
$categories = $stmt_categories->fetchAll();
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Minha Conta - Portal de Administração</title>
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
        width: 0;
        overflow: hidden;
        transition: all 0.3s;
      }
      .sidebar.show {
        width: 280px;
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
<div class="sidebar d-none d-lg-block">
  <div class="sidebar-header text-center">
    <h4>🛒 Portal de Produtos</h4>
  </div>
  <ul class="nav flex-column mt-4">
    <li class="nav-item">
      <a class="nav-link" href="../admin.php"><i class="bi bi-speedometer2"></i> Dashboard</a>
    </li>
    <li class="nav-item">
      <a class="nav-link active" href="#"><i class="bi bi-box-seam"></i> Cadastrar Produtos</a>
    </li>
    <li class="nav-item">
      <a class="nav-link" href="create_employee.php"><i class="bi bi-person-plus"></i> Cadastrar Funcionários</a>
    </li>
    <li class="nav-item">
      <a class="nav-link" href="settings.php"><i class="bi bi-gear"></i> Configurações</a>
    </li>
    <li class="nav-item mt-3">
      <a class="nav-link text-danger" href="../../auth/logout.php"><i class="bi bi-box-arrow-right"></i> Sair</a>
    </li>
  </ul>
</div>

<!-- Main Content -->
<div class="main-content">
  <div class="container mt-5">
    <h2 class="mb-4">Cadastrar Novo Produto</h2>

    <?php if ($erro): ?>
      <div class="alert alert-danger"><?= $erro ?></div>
    <?php elseif ($sucesso): ?>
      <div class="alert alert-success"><?= $sucesso ?></div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
      <div class="mb-3">
        <label for="name" class="form-label">Nome do Produto</label>
        <input type="text" class="form-control" id="name" name="name" required>
      </div>

      <div class="mb-3">
        <label for="description" class="form-label">Descrição</label>
        <textarea class="form-control" id="description" name="description" rows="3" required></textarea>
      </div>

      <div class="row mb-3">
        <div class="col-md-4">
          <label for="price" class="form-label">Preço (R$)</label>
          <input type="number" step="0.01" class="form-control" id="price" name="price" required min="0" required>
        </div>
        <div class="col-md-4">
          <label for="quantity" class="form-label">Quantidade</label>
          <input type="number" class="form-control" id="quantity" name="quantity" required min="0" required>
        </div>
        <div class="col-md-4">
          <label for="category_id" class="form-label">Categoria</label>
          <select class="form-select" id="category_id" name="category_id" required>
            <option value="">Selecione...</option>
            <?php foreach ($categories as $cat): ?>
              <option value="<?= $cat['ID'] ?>"><?= htmlspecialchars($cat['Name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <div class="mb-3">
        <label for="image" class="form-label">Imagem do Produto</label>
        <input class="form-control" type="file" id="image" name="image" accept="image/*" required>
      </div>

      <button type="submit" class="btn btn-primary">Cadastrar Produto</button>
    </form>
  </div>
</div>


  <!-- Mobile Menu Button -->
  <button class="btn btn-primary mobile-menu-btn rounded-circle" style="width: 50px; height: 50px;" id="mobileMenuBtn">
    <i class="bi bi-list" style="font-size: 1.5rem;"></i>
  </button>

    <a href="../../auth/logout.php">Sair</a>
</body>
</html>