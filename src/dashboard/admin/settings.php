<?php
session_start();
require '../../config/connection.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('Location: ../auth/login.php');
    exit;
}

$perfil_sucesso = '';
$perfil_erro = '';

// Atualizar nome
if (isset($_POST['update_name'])) {
    $novo_nome = trim($_POST['name']);
    $id = $_SESSION['user']['id'];

    $stmt = $pdo->prepare("UPDATE Costumer SET Name = ? WHERE ID = ?");
    if ($stmt->execute([$novo_nome, $id])) {
        $_SESSION['user']['name'] = $novo_nome;
        $perfil_sucesso = "Nome atualizado com sucesso!";
    } else {
        $perfil_erro = "Erro ao atualizar nome.";
    }
}

// Atualizar senha
if (isset($_POST['update_password'])) {
    $nova_senha = password_hash($_POST['password'], PASSWORD_BCRYPT);
    $id = $_SESSION['user']['id'];

    $stmt = $pdo->prepare("UPDATE Costumer SET Password = ? WHERE ID = ?");
    if ($stmt->execute([$nova_senha, $id])) {
        $perfil_sucesso = "Senha atualizada com sucesso!";
    } else {
        $perfil_erro = "Erro ao atualizar senha.";
    }
}

// Obter dados do usuário atualizados
$id = $_SESSION['user']['id'];

$stmt = $pdo->prepare("SELECT Name, Email, updated_at FROM Costumer WHERE ID = ?");
$stmt->execute([$id]);
$admin = $stmt->fetch(PDO::FETCH_ASSOC);

// Atualiza os dados na sessão se quiser manter consistência
if ($admin) {
    $_SESSION['user']['name'] = $admin['Name'];
}
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
      <a class="nav-link" href="../admin.php"><i class="bi bi-speedometer2"></i> Dashboard</a>
    </li>
    <li class="nav-item">
      <a class="nav-link" href="product.php"><i class="bi bi-box-seam"></i> Cadastrar Produtos</a>
    </li>
    <li class="nav-item">
        <a class="nav-link" href="category.php"><i class="bi bi-tags"></i> Criar Categoria</a>
    </li>
    <li class="nav-item">
      <a class="nav-link" href="costumer.php"><i class="bi bi-person-plus"></i> Cadastrar Funcionários</a>
    </li>
    <li class="nav-item">
      <a class="nav-link" href="#"><i class="bi bi-gear"></i> Configurações</a>
    </li>
    <li class="nav-item mt-3">
      <a class="nav-link text-danger" href="../../auth/logout.php"><i class="bi bi-box-arrow-right"></i> Sair</a>
    </li>
  </ul>
</div>

<!-- Seção de Perfil -->
<div class="main-content">
  <div class="container mt-5">
  <h2 class="mb-4">Perfil do Administrador</h2>

  <?php if ($perfil_sucesso): ?>
    <div class="alert alert-success"><?= $perfil_sucesso ?></div>
  <?php elseif ($perfil_erro): ?>
    <div class="alert alert-danger"><?= $perfil_erro ?></div>
  <?php endif; ?>

  <div class="card mb-4 shadow-sm">
    <div class="card-body d-flex align-items-center">
      <img src="https://cdn-icons-png.flaticon.com/512/3135/3135715.png" alt="Perfil" width="100" height="100" class="me-4 rounded-circle border">
      <div>
        <h5 class="card-title mb-1"><?= htmlspecialchars($_SESSION['user']['name']) ?></h5>
        <p class="mb-0 text-muted"><?= htmlspecialchars($admin['Email']) ?></p>
        <p class="mb-0 text-muted">Última atualização: <?= htmlspecialchars($admin['updated_at']) ?></p>
      </div>
    </div>
  </div>

  <!-- Formulário para alterar nome -->
  <form method="POST" class="mb-4">
    <div class="mb-3">
      <label for="name" class="form-label">Alterar Nome</label>
      <input type="text" class="form-control" id="name" name="name" value="<?= htmlspecialchars($_SESSION['user']['name']) ?>" required>
    </div>
    <button type="submit" name="update_name" class="btn btn-primary">Salvar Nome</button>
  </form>

  <!-- Formulário para alterar senha -->
  <form method="POST">
    <div class="mb-3">
      <label for="password" class="form-label">Nova Senha</label>
      <input type="password" class="form-control" id="password" name="password" required placeholder="Digite a nova senha">
    </div>
    <button type="submit" name="update_password" class="btn btn-warning">Alterar Senha</button>
  </form>
</div>

</div>


  <!-- Mobile Menu Button -->
  <button class="btn btn-primary mobile-menu-btn rounded-circle" style="width: 50px; height: 50px;" id="mobileMenuBtn">
    <i class="bi bi-list" style="font-size: 1.5rem;"></i>
  </button>

    <script>
        const mobileMenuBtn = document.getElementById('mobileMenuBtn');
        const sidebar = document.getElementById('sidebar');

        mobileMenuBtn.addEventListener('click', () => {
        sidebar.classList.toggle('show');
        });
    </script>
</body>
</html>