<?php
require '../config/connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);
    $role = $_POST['role'] ?? 'user';

    // Verificar se o email já existe
    $check = $pdo->prepare("SELECT COUNT(*) FROM Costumer WHERE Email = ?");
    $check->execute([$email]);
    $exists = $check->fetchColumn();

    if ($exists) {
        $erro = "Já existe uma conta com esse email.";
    } else {
        $stmt = $pdo->prepare("INSERT INTO Costumer (Name, Email, Password, ROLE) VALUES (?, ?, ?, ?)");
        if ($stmt->execute([$name, $email, $password, $role])) {
            header('Location: login.php');
            exit;
        } else {
            $erro = "Erro ao cadastrar. Tente novamente.";
        }
    }
}
?>


<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Cadastro - Portal de Produtos</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body {
      background-color: #f8f9fa;
      display: flex;
      flex-direction: column;
      min-height: 100vh;
    }
    .register-container {
      max-width: 500px;
      margin: auto;
      padding: 2rem;
      background: white;
      border-radius: 10px;
      box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
      margin-top: 3rem;
    }
    .register-header {
      text-align: center;
      margin-bottom: 2rem;
    }
    .register-header h2 {
      color: #343a40;
    }
    .btn-register {
      width: 100%;
      padding: 0.5rem;
      font-weight: 600;
    }
    .footer {
      background-color: #343a40;
      color: white;
      text-align: center;
      padding: 1rem;
      margin-top: auto;
    }
    .error-message {
      color: #dc3545;
      text-align: center;
      margin-bottom: 1rem;
    }
    .form-select {
      margin-bottom: 1rem;
    }
  </style>
</head>
<body>

  <!-- Navbar -->
  <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container">
      <a class="navbar-brand" href="../index.php">🛒 Portal de Produtos</a>
      <a class="text-primary" href="login.php">Já tem conta? Faça login</a>
    </div>
  </nav>

  <!-- Conteúdo Principal -->
  <main class="container">
    <div class="register-container">
      <div class="register-header">
        <h2>Criar Conta</h2>
        <p class="text-muted">Preencha os dados para se cadastrar</p>
      </div>
      
      <?php if (isset($erro)): ?>
        <div class="error-message"><?php echo $erro; ?></div>
      <?php endif; ?>
      
      <form method="POST">
        <div class="mb-3">
          <label for="name" class="form-label">Nome Completo</label>
          <input type="text" class="form-control" id="name" name="name" required placeholder="Digite seu nome">
        </div>
        <div class="mb-3">
          <label for="email" class="form-label">Email</label>
          <input type="email" class="form-control" id="email" name="email" required placeholder="Digite seu email">
        </div>
        <div class="mb-3">
          <label for="password" class="form-label">Senha</label>
          <input type="password" class="form-control" id="password" name="password" required placeholder="Crie uma senha">
        </div>
        <div class="mb-4">
          <label for="role" class="form-label">Tipo de Conta</label>
          <select class="form-select" id="role" name="role">
            <option value="user" selected>Cliente</option>
            <option value="admin">Administrador</option>
          </select>
        </div>
        <button type="submit" class="btn btn-primary btn-register">Cadastrar</button>
      </form>
      
      <div class="text-center mt-3">
        <p>Já possui uma conta? <a href="login.php" class="text-decoration-none">Faça login</a></p>
      </div>
    </div>
  </main>

  <!-- Rodapé -->
  <footer class="footer">
    <div class="container">
      <p>&copy; <?php echo date('Y'); ?> Portal de Produtos - Todos os direitos reservados.</p>
    </div>
  </footer>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>