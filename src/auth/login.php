<?php
session_start();
require '../config/connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM Costumer WHERE Email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['Password'])) {
        $_SESSION['user'] = [
            'id' => $user['ID'],
            'name' => $user['Name'],
            'role' => $user['ROLE']
        ];
        if ($user['ROLE'] === 'admin') {
            header('Location: ../dashboard/admin.php');
        } else {
            header('Location: ../dashboard/client.php');
        }
        exit;
    } else {
        $erro = "Login inválido";
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login - Portal de Produtos</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body {
      background-color: #f8f9fa;
      display: flex;
      flex-direction: column;
      min-height: 100vh;
    }
    .login-container {
      max-width: 400px;
      margin: auto;
      padding: 2rem;
      background: white;
      border-radius: 10px;
      box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
      margin-top: 5rem;
    }
    .login-header {
      text-align: center;
      margin-bottom: 2rem;
    }
    .login-header h2 {
      color: #343a40;
    }
    .btn-login {
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
  </style>
</head>
<body>

  <!-- Navbar -->
  <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container">
      <a class="navbar-brand" href="../index.php">🛒 Portal de Produtos</a>
      <a class="text-primary" href="register.php">Criar conta</a>
    </div>
  </nav>

  <!-- Conteúdo Principal -->
  <main class="container">
    <div class="login-container">
      <div class="login-header">
        <h2>Login</h2>
        <p class="text-muted">Acesse sua conta para continuar</p>
      </div>
      
      <?php if (isset($erro)): ?>
        <div class="error-message"><?php echo $erro; ?></div>
      <?php endif; ?>
      
      <form method="POST">
        <div class="mb-3">
          <label for="email" class="form-label">Email</label>
          <input type="email" class="form-control" id="email" name="email" required placeholder="Digite seu email">
        </div>
        <div class="mb-4">
          <label for="password" class="form-label">Senha</label>
          <input type="password" class="form-control" id="password" name="password" required placeholder="Digite sua senha">
        </div>
        <button type="submit" class="btn btn-primary btn-login">Entrar</button>
      </form>
      
      <div class="text-center mt-3">
        <a href="register.php" class="text-decoration-none">Não tem uma conta? Cadastre-se</a>
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