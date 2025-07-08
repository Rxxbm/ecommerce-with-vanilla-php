<?php
session_start();
require '../config/connection.php';
require '../config/stripe_config.php';

// Verifica se o usuário está logado
if (!isset($_SESSION['user'])) {
    header('Location: ../auth/login.php');
    exit;
}

// Obtém o ID do pedido da URL
if (!isset($_GET['order_id'])) {
    $_SESSION['error_message'] = "Pedido não especificado!";
    header('Location: ./index.php');
    exit;
}

$order_id = $_GET['order_id'];
$user_id = $_SESSION['user']['id'];

// Busca os detalhes do pedido
$stmt = $pdo->prepare("
    SELECT o.*, c.Name as customer_name, c.Email 
    FROM `Order` o
    JOIN Costumer c ON o.Costumer_id = c.ID
    WHERE o.ID = ? AND o.Costumer_id = ?
");
$stmt->execute([$order_id, $user_id]);
$order = $stmt->fetch();

if (!$order) {
    $_SESSION['error_message'] = "Pedido não encontrado ou não pertence a você!";
    header('Location: ./index.php');
    exit;
}

// Busca os itens do pedido
$stmt = $pdo->prepare("
    SELECT oi.*, p.Name, p.Image, p.Description 
    FROM OrderItem oi
    JOIN Product p ON oi.Product_ID = p.ID
    WHERE oi.Order_ID = ?
");
$stmt->execute([$order_id]);
$order_items = $stmt->fetchAll();

// Processar pagamento para pedidos pendentes
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['pay_order']) && $order['Status'] == 'pending') {
    try {
        // Prepara os itens para o Stripe
        $line_items = [];
        foreach ($order_items as $item) {
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
        }

        // Configuração da URL base
        $base_url = "http://localhost:8080";
        
        // Cria sessão de checkout no Stripe
        $checkout_session = \Stripe\Checkout\Session::create([
            'payment_method_types' => ['card', 'boleto'],
            'line_items' => $line_items,
            'mode' => 'payment',
            'success_url' => $base_url . '/cart/order_confirmation.php?session_id={CHECKOUT_SESSION_ID}&order_id='.$order_id,
            'cancel_url' => $base_url . '/orders/order_details.php?order_id='.$order_id,
            'customer_email' => $order['Email'],
            'metadata' => [
                'order_id' => $order_id,
                'user_id' => $user_id
            ],
            'shipping_address_collection' => [
                'allowed_countries' => ['BR'],
            ]
        ]);

        // Armazena o session_id na sessão para verificação posterior
        $_SESSION['stripe_session_id'] = $checkout_session->id;
        
        // Redireciona para o checkout do Stripe
        header("Location: " . $checkout_session->url);
        exit;

    } catch (Exception $e) {
        $_SESSION['error_message'] = "Erro ao processar o pagamento: " . $e->getMessage();
        header("Location: order_details.php?order_id=".$order_id);
        exit;
    }
}

// Calcula o total para verificação
$calculated_total = 0;
foreach ($order_items as $item) {
    $calculated_total += $item['Price'] * $item['QuantityItem'];
}

// Labels de status
$status_labels = [
    'pending' => 'Pendente',
    'paid' => 'Pago',
    'shipped' => 'Enviado',
    'delivered' => 'Entregue',
    'canceled' => 'Cancelado'
];
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalhes do Pedido #<?= $order_id ?> - Portal de Produtos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        .order-status {
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.8rem;
            letter-spacing: 0.5px;
        }
        .status-pending {
            color: #ffc107;
        }
        .status-paid {
            color: #17a2b8;
        }
        .status-shipped {
            color: #007bff;
        }
        .status-delivered {
            color: #28a745;
        }
        .status-canceled {
            color: #dc3545;
        }
        .product-img {
            width: 70px;
            height: 70px;
            object-fit: cover;
            border-radius: 5px;
        }
        .timeline {
            position: relative;
            padding-left: 30px;
        }
        .timeline::before {
            content: '';
            position: absolute;
            left: 10px;
            top: 0;
            bottom: 0;
            width: 2px;
            background: #e9ecef;
        }
        .timeline-item {
            position: relative;
            padding-bottom: 20px;
        }
        .timeline-item::before {
            content: '';
            position: absolute;
            left: -30px;
            top: 0;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background: #fff;
            border: 3px solid;
        }
        .timeline-pending::before {
            border-color: #ffc107;
        }
        .timeline-paid::before {
            border-color: #17a2b8;
        }
        .timeline-shipped::before {
            border-color: #007bff;
        }
        .timeline-delivered::before {
            border-color: #28a745;
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
                <a href="../dashboard/client.php" class="btn btn-outline-light me-2">
                    <i class="bi bi-person"></i> Minha Conta
                </a>
                <a href="../cart/index.php" class="btn btn-outline-light position-relative">
                    <i class="bi bi-cart3"></i>
                </a>
            </div>
        </div>
    </nav>

    <div class="container my-5">
        <!-- Mensagens de erro/sucesso -->
        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <?= $_SESSION['error_message'] ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['error_message']); ?>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <?= $_SESSION['success_message'] ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['success_message']); ?>
        <?php endif; ?>

        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="bi bi-receipt"></i> Detalhes do Pedido #<?= $order_id ?></h2>
            <a href="./index.php" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Voltar para Pedidos
            </a>
        </div>

        <div class="row">
            <div class="col-lg-8">
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-light d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Informações do Pedido</h5>
                        <span class="order-status status-<?= $order['Status'] ?>">
                            <?= $status_labels[$order['Status']] ?? $order['Status'] ?>
                        </span>
                    </div>
                    <div class="card-body">
                        <!-- Linha do tempo do pedido -->
                        <div class="timeline mb-4">
                            <div class="timeline-item <?= $order['Status'] == 'pending' ? 'timeline-pending' : '' ?>">
                                <h6>Pedido Realizado</h6>
                                <small class="text-muted"><?= date('d/m/Y H:i', strtotime($order['Created_at'])) ?></small>
                            </div>
                            
                            <?php if ($order['Status'] == 'paid' || $order['Status'] == 'shipped' || $order['Status'] == 'delivered'): ?>
                            <div class="timeline-item timeline-paid">
                                <h6>Pagamento Confirmado</h6>
                                <small class="text-muted"><?= date('d/m/Y H:i', strtotime($order['Created_at']) + 3600) ?></small>
                            </div>
                            <?php endif; ?>
                            
                            <?php if ($order['Status'] == 'shipped' || $order['Status'] == 'delivered'): ?>
                            <div class="timeline-item timeline-shipped">
                                <h6>Pedido Enviado</h6>
                                <small class="text-muted"><?= date('d/m/Y H:i', strtotime($order['Created_at']) + 86400) ?></small>
                            </div>
                            <?php endif; ?>
                            
                            <?php if ($order['Status'] == 'delivered'): ?>
                            <div class="timeline-item timeline-delivered">
                                <h6>Pedido Entregue</h6>
                                <small class="text-muted"><?= date('d/m/Y H:i', strtotime($order['Created_at']) + 172800) ?></small>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <hr>
                        
                        <h5 class="mb-3">Itens do Pedido</h5>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Produto</th>
                                        <th>Preço Unitário</th>
                                        <th>Quantidade</th>
                                        <th>Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($order_items as $item): ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <img src="../uploads/<?= htmlspecialchars($item['Image']) ?>" alt="<?= htmlspecialchars($item['Name']) ?>" class="product-img me-3">
                                                    <div>
                                                        <h6 class="mb-0"><?= htmlspecialchars($item['Name']) ?></h6>
                                                        <small class="text-muted"><?= substr(htmlspecialchars($item['Description']), 0, 50) ?>...</small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>R$ <?= number_format($item['Price'], 2, ',', '.') ?></td>
                                            <td><?= $item['QuantityItem'] ?></td>
                                            <td>R$ <?= number_format($item['Price'] * $item['QuantityItem'], 2, ',', '.') ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4">
                <div class="card shadow-sm sticky-top" style="top: 20px;">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">Resumo do Pedido</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-2">
                            <span>Número do Pedido</span>
                            <span>#<?= $order['ID'] ?></span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Data do Pedido</span>
                            <span><?= date('d/m/Y', strtotime($order['Created_at'])) ?></span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Status</span>
                            <span class="fw-bold">
                                <?= $status_labels[$order['Status']] ?? $order['Status'] ?>
                            </span>
                        </div>
                        
                        <hr>
                        
                        <div class="d-flex justify-content-between mb-2">
                            <span>Subtotal</span>
                            <span>R$ <?= number_format($calculated_total, 2, ',', '.') ?></span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Frete</span>
                            <span>Grátis</span>
                        </div>
                        
                        <hr>
                        
                        <div class="d-flex justify-content-between fw-bold mb-4">
                            <span>Total</span>
                            <span>R$ <?= number_format($order['Total'], 2, ',', '.') ?></span>
                        </div>
                        
                        <?php if ($order['Status'] == 'pending'): ?>
                        <form method="POST" id="payment-form">
                            <button type="submit" name="pay_order" class="btn btn-success w-100 py-2" id="submit-btn">
                                <i class="bi bi-credit-card"></i> Pagar Agora
                            </button>
                        </form>
                        <?php endif; ?>
                        
                        <div class="mt-3">
                            <?php if ($order['Status'] == 'paid' || $order['Status'] == 'shipped'): ?>
                            <div class="alert alert-info">
                                <i class="bi bi-info-circle"></i> Seu pedido está sendo processado. Enviaremos atualizações por email.
                            </div>
                            <?php elseif ($order['Status'] == 'delivered'): ?>
                            <div class="alert alert-success">
                                <i class="bi bi-check-circle"></i> Pedido entregue com sucesso!
                            </div>
                            <?php endif; ?>
                            
                            <a href="./index.php" class="btn btn-outline-secondary w-100 mt-2">
                                <i class="bi bi-arrow-left"></i> Voltar para Pedidos
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>