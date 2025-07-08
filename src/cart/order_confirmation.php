<?php
session_start();
require '../config/connection.php';
require '../config/stripe_config.php';

if (!isset($_GET['session_id']) || !isset($_GET['order_id']) || !isset($_SESSION['user'])) {
    header('Location: ../index.php');
    exit;
}

try {
    // Verifica a sessão no Stripe
    $session = \Stripe\Checkout\Session::retrieve($_GET['session_id']);
    
    // Verifica se o pedido pertence ao usuário
    $stmt = $pdo->prepare("SELECT * FROM `Order` WHERE ID = ? AND Costumer_id = ?");
    $stmt->execute([$_GET['order_id'], $_SESSION['user']['id']]);
    $order = $stmt->fetch();
    
    if (!$order) {
        throw new Exception("Pedido não encontrado");
    }
    
    // Atualiza o status conforme o pagamento
    $status = ($session->payment_status === 'paid') ? 'paid' : 'pending';
    $stmt = $pdo->prepare("UPDATE `Order` SET Status = ? WHERE ID = ?");
    $stmt->execute([$status, $_GET['order_id']]);
    
    // Mensagem de sucesso
    $_SESSION['success_message'] = "Pagamento realizado com sucesso! Nº do pedido: ".$order['ID'];
    
    // Redireciona para a página de pedidos
    header('Location: ../orders/index.php');
    exit;

} catch (Exception $e) {
    $_SESSION['error_message'] = "Erro na confirmação: " . $e->getMessage();
    header('Location: ./checkout.php');
    exit;
}