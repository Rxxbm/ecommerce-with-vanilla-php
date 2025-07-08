<?php
session_start();
require '../config/connection.php';
require '../config/stripe_config.php'; // Arquivo com \Stripe\Stripe::setApiKey('sua_chave_secreta');

// Verifica se o usuário está logado
if (!isset($_SESSION['user'])) {
    $_SESSION['redirect_to_checkout'] = true;
    header('Location: ../auth/login.php');
    exit;
}

// Verifica se o carrinho existe e tem itens
if (!isset($_SESSION['cart_id']) || empty($_SESSION['cart_id'])) {
    $_SESSION['error_message'] = "Seu carrinho está vazio!";
    header('Location: ./index.php');
    exit;
}

// Obtém informações do carrinho
$stmt = $pdo->prepare("SELECT * FROM Cart WHERE ID = ?");
$stmt->execute([$_SESSION['cart_id']]);
$cart = $stmt->fetch();

// Obtém itens do carrinho
$stmt = $pdo->prepare("
    SELECT ci.ID as cart_item_id, p.ID as product_id, p.Name, p.Price, p.Quantity as stock, ci.QuantityItem
    FROM CartItem ci
    JOIN Product p ON ci.Product_ID = p.ID
    WHERE ci.Cart_ID = ?
");
$stmt->execute([$_SESSION['cart_id']]);
$cart_items = $stmt->fetchAll();

// Verifica se o carrinho está vazio
if (empty($cart_items)) {
    $_SESSION['error_message'] = "Seu carrinho está vazio!";
    header('Location: ./index.php');
    exit;
}

// Verifica disponibilidade de estoque
foreach ($cart_items as $item) {
    if ($item['QuantityItem'] > $item['stock']) {
        $_SESSION['error_message'] = "Desculpe, o produto '{$item['Name']}' não tem estoque suficiente (Disponível: {$item['stock']}).";
        header('Location: ./index.php');
        exit;
    }
}

// Processa o checkout com Stripe
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['stripe_checkout'])) {
    try {
        $pdo->beginTransaction();

        // 1. Cria o pedido no banco de dados
        $stmt = $pdo->prepare("
            INSERT INTO `Order` (Total, Status, Costumer_id) 
            VALUES (?, 'pending', ?)
        ");
        $stmt->execute([$cart['Total'], $_SESSION['user']['id']]);
        $order_id = $pdo->lastInsertId();

        // 2. Prepara os itens para o Stripe
        $line_items = [];
        foreach ($cart_items as $item) {
            $line_items[] = [
                'price_data' => [
                    'currency' => 'brl',
                    'product_data' => [
                        'name' => $item['Name'],
                    ],
                    'unit_amount' => $item['Price'] * 100, // Stripe usa centavos
                ],
                'quantity' => $item['QuantityItem'],
            ];

            // Adiciona ao histórico de itens do pedido
            $stmt = $pdo->prepare("
                INSERT INTO OrderItem (Product_ID, Order_ID, QuantityItem, Price)
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([
                $item['product_id'],
                $order_id,
                $item['QuantityItem'],
                $item['Price']
            ]);

            // Atualiza o estoque
            $stmt = $pdo->prepare("
                UPDATE Product 
                SET Quantity = Quantity - ? 
                WHERE ID = ?
            ");
            $stmt->execute([$item['QuantityItem'], $item['product_id']]);
        }

        // 3. Cria a sessão de checkout no Stripe
        $checkout_session = \Stripe\Checkout\Session::create([
            'payment_method_types' => ['card', 'boleto'],
            'line_items' => $line_items,
            'mode' => 'payment',
            'success_url' => 'http://localhost:8080/cart/order_confirmation.php?session_id={CHECKOUT_SESSION_ID}&order_id='.$order_id,
            'cancel_url' => 'http://localhost:8080/cart/index.php',
            'customer_email' => $_SESSION['user']['email'], // Usa o email do usuário logado
            'metadata' => [
                'order_id' => $order_id,
                'user_id' => $_SESSION['user']['id']
            ],
            'shipping_address_collection' => [
                'allowed_countries' => ['BR'],
            ],
            'phone_number_collection' => [
                'enabled' => true,
            ]
        ]);

        // 4. Limpa o carrinho
        $stmt = $pdo->prepare("DELETE FROM CartItem WHERE Cart_ID = ?");
        $stmt->execute([$_SESSION['cart_id']]);

        $stmt = $pdo->prepare("UPDATE Cart SET Itens = 0, Total = 0 WHERE ID = ?");
        $stmt->execute([$_SESSION['cart_id']]);

        $pdo->commit();

        // Redireciona para o checkout do Stripe
        header("HTTP/1.1 303 See Other");
        header("Location: " . $checkout_session->url);
        exit;

    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['error_message'] = "Erro ao processar o pagamento: " . $e->getMessage();
        echo $e->getMessage();
        exit;
    }
}

// Obtém informações do cliente
$stmt = $pdo->prepare("SELECT * FROM Costumer WHERE ID = ?");
$stmt->execute([$_SESSION['user']['id']]);
$customer = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Finalizar Compra - Portal de Produtos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        .summary-card {
            background-color: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 0 10px rgba(0,0,0,0.05);
        }
        .cart-item-img {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 5px;
        }
        .payment-method {
            cursor: pointer;
            transition: all 0.2s;
        }
        .payment-method:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .payment-method.selected {
            border: 2px solid #0d6efd;
            background-color: #f0f7ff;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="../index.php">🛒 Portal de Produtos</a>
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
        <h2 class="mb-4"><i class="bi bi-credit-card"></i> Finalizar Compra</h2>
        
        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <?= $_SESSION['error_message'] ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['error_message']); ?>
        <?php endif; ?>
        
        <div class="row">
            <div class="col-lg-8">
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">Informações do Cliente</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Nome</label>
                                <input type="text" class="form-control" value="<?= htmlspecialchars($customer['Name']) ?>" readonly>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" class="form-control" value="<?= htmlspecialchars($customer['Email']) ?>" readonly>
                            </div>
                        </div>
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle"></i> Para alterar suas informações, acesse sua conta.
                        </div>
                    </div>
                </div>

                <div class="card shadow-sm">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">Método de Pagamento</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <div class="payment-method card p-3 text-center selected" data-method="card">
                                    <i class="bi bi-credit-card" style="font-size: 2rem;"></i>
                                    <h6>Cartão de Crédito</h6>
                                    <small>Pague com Visa, Mastercard ou outros</small>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="payment-method card p-3 text-center" data-method="boleto">
                                    <i class="bi bi-upc" style="font-size: 2rem;"></i>
                                    <h6>Boleto Bancário</h6>
                                    <small>Pague com boleto (até 3 dias úteis)</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4">
                <div class="summary-card sticky-top" style="top: 20px;">
                    <h5 class="mb-4">Resumo do Pedido</h5>
                    
                    <div class="mb-3">
                        <?php foreach ($cart_items as $item): ?>
                            <div class="d-flex justify-content-between mb-2">
                                <div>
                                    <small><?= htmlspecialchars($item['Name']) ?> (x<?= $item['QuantityItem'] ?>)</small>
                                </div>
                                <div>
                                    <small>R$ <?= number_format($item['Price'] * $item['QuantityItem'], 2, ',', '.') ?></small>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <hr>
                    
                    <div class="d-flex justify-content-between mb-2">
                        <span>Subtotal</span>
                        <span>R$ <?= number_format($cart['Total'], 2, ',', '.') ?></span>
                    </div>
                    
                    <div class="d-flex justify-content-between mb-3">
                        <span>Frete</span>
                        <span>Grátis</span>
                    </div>
                    
                    <hr>
                    
                    <div class="d-flex justify-content-between fw-bold mb-4">
                        <span>Total</span>
                        <span>R$ <?= number_format($cart['Total'], 2, ',', '.') ?></span>
                    </div>
                    
                    <form method="POST" id="payment-form">
                        <input type="hidden" name="stripe_checkout" value="1">
                        <button type="submit" class="btn btn-primary w-100 py-2" id="submit-button">
                            <i class="bi bi-credit-card"></i> Finalizar Pagamento
                        </button>
                    </form>
                    
                    <div class="mt-3 text-center">
                        <a href="./index.php" class="text-decoration-none">
                            <i class="bi bi-arrow-left"></i> Voltar ao carrinho
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Seleção de método de pagamento
        document.querySelectorAll('.payment-method').forEach(method => {
            method.addEventListener('click', () => {
                document.querySelectorAll('.payment-method').forEach(m => {
                    m.classList.remove('selected');
                });
                method.classList.add('selected');
            });
        });

        // Desabilita o botão após o clique para evitar múltiplos envios
        document.getElementById('payment-form').addEventListener('submit', function() {
            document.getElementById('submit-button').disabled = true;
        });
    </script>
</body>
</html>