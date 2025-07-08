<?php
session_start();
require '../config/connection.php';

// Verifica se o usuário está logado
if (!isset($_SESSION['user'])) {
    header('Location: ../auth/login.php');
    exit;
}

// Obtém o ID do usuário logado
$user_id = $_SESSION['user']['id'];

// Verifica se foi solicitado um pedido específico
if (isset($_GET['order_id'])) {
    // Mostrar detalhes de um pedido específico
    $order_id = $_GET['order_id'];
    
    // Verifica se o pedido pertence ao usuário
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
        header('Location: ./orders.php');
        exit;
    }
    
    // Obtém os itens do pedido
    $stmt = $pdo->prepare("
        SELECT oi.*, p.Name, p.Image, p.Description 
        FROM OrderItem oi
        JOIN Product p ON oi.Product_ID = p.ID
        WHERE oi.Order_ID = ?
    ");
    $stmt->execute([$order_id]);
    $order_items = $stmt->fetchAll();
    
    // Calcula o total para verificação
    $calculated_total = 0;
    foreach ($order_items as $item) {
        $calculated_total += $item['Price'] * $item['QuantityItem'];
    }
} else {
    // Mostrar lista de todos os pedidos do usuário
    $stmt = $pdo->prepare("
        SELECT o.ID, o.Total, o.Status, o.Created_at, 
               COUNT(oi.ID) as item_count
        FROM `Order` o
        LEFT JOIN OrderItem oi ON o.ID = oi.Order_ID
        WHERE o.Costumer_id = ?
        GROUP BY o.ID
        ORDER BY o.Created_at DESC
    ");
    $stmt->execute([$user_id]);
    $orders = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meus Pedidos - Portal de Produtos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        .order-card {
            transition: all 0.3s ease;
            border-left: 4px solid;
        }
        .order-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .order-status {
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.8rem;
            letter-spacing: 0.5px;
        }
        .status-pending {
            border-left-color: #ffc107;
            color: #ffc107;
        }
        .status-paid {
            border-left-color: #17a2b8;
            color: #17a2b8;
        }
        .status-shipped {
            border-left-color: #007bff;
            color: #007bff;
        }
        .status-delivered {
            border-left-color: #28a745;
            color: #28a745;
        }
        .status-canceled {
            border-left-color: #dc3545;
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
            <h2><i class="bi bi-receipt"></i> Meus Pedidos</h2>
            <a href="../index.php" class="btn btn-outline-primary">
                <i class="bi bi-arrow-left"></i> Continuar Comprando
            </a>
        </div>

        <?php if (isset($order)): ?>
            <!-- Detalhes de um pedido específico -->
            <div class="row">
                <div class="col-lg-8">
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-light d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Pedido #<?= $order['ID'] ?></h5>
                            <span class="order-status status-<?= $order['Status'] ?>">
                                <?php 
                                $status_labels = [
                                    'pending' => 'Pendente',
                                    'paid' => 'Pago',
                                    'shipped' => 'Enviado',
                                    'delivered' => 'Entregue',
                                    'canceled' => 'Cancelado'
                                ];
                                echo $status_labels[$order['Status']] ?? $order['Status'];
                                ?>
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
                            
                            <div class="d-flex justify-content-between fw-bold mb-3">
                                <span>Total</span>
                                <span>R$ <?= number_format($order['Total'], 2, ',', '.') ?></span>
                            </div>
                            
                            <div class="alert alert-info">
                                <i class="bi bi-info-circle"></i> O código de rastreamento será enviado por email quando o pedido for despachado.
                            </div>
                            
                            <a href="./orders.php" class="btn btn-outline-secondary w-100">
                                <i class="bi bi-arrow-left"></i> Voltar para Meus Pedidos
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <!-- Lista de todos os pedidos -->
            <?php if (empty($orders)): ?>
                <div class="card shadow-sm">
                    <div class="card-body text-center py-5">
                        <i class="bi bi-receipt" style="font-size: 3rem; color: #adb5bd;"></i>
                        <h4 class="mt-3">Você ainda não fez nenhum pedido</h4>
                        <p class="text-muted">Quando você fizer um pedido, ele aparecerá aqui</p>
                        <a href="../index.php" class="btn btn-primary">
                            <i class="bi bi-bag"></i> Ir para a Loja
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <div class="row">
                    <?php foreach ($orders as $order): ?>
                        <div class="col-md-6 mb-4">
                            <div class="card shadow-sm order-card status-<?= $order['Status'] ?>">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <div>
                                            <h5 class="mb-1">Pedido #<?= $order['ID'] ?></h5>
                                            <small class="text-muted"><?= date('d/m/Y H:i', strtotime($order['Created_at'])) ?></small>
                                        </div>
                                        <span class="badge bg-<?= 
                                            $order['Status'] == 'pending' ? 'warning' : 
                                            ($order['Status'] == 'paid' ? 'info' : 
                                            ($order['Status'] == 'shipped' ? 'primary' : 
                                            ($order['Status'] == 'delivered' ? 'success' : 'danger')))
                                        ?> text-capitalize">
                                            <?= $status_labels[$order['Status']] ?? $order['Status'] ?>
                                        </span>
                                    </div>
                                    
                                    <div class="d-flex justify-content-between mb-3">
                                        <div>
                                            <small class="text-muted"><?= $order['item_count'] ?> item(s)</small>
                                        </div>
                                        <div>
                                            <span class="fw-bold">R$ <?= number_format($order['Total'], 2, ',', '.') ?></span>
                                        </div>
                                    </div>
                                    
                                    <div class="d-grid">
                                        <a href="./order_details.php?order_id=<?= $order['ID'] ?>" class="btn btn-outline-primary btn-sm">
                                            <i class="bi bi-eye"></i> Ver Detalhes
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>