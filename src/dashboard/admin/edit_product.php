<?php
session_start();
require '../../config/connection.php';

// Verificação de admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('Location: ../auth/login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id = $_POST['product_id'] ?? null;
    $action = $_POST['action'] ?? null;

    if (!$product_id || !$action) {
        die('Requisição inválida.');
    }

    // Editar quantidade
    if ($action === 'edit_quantity') {
        $new_quantity = (int) $_POST['new_quantity'];
        if ($new_quantity < 0) {
            die('Quantidade inválida.');
        }
        $stmt = $pdo->prepare("UPDATE Product SET Quantity = ? WHERE ID = ?");
        $stmt->execute([$new_quantity, $product_id]);
    }

    // Editar preço
    elseif ($action === 'edit_price') {
        $new_price = floatval($_POST['new_price']);
        if ($new_price < 0) {
            die('Preço inválido.');
        }
        $stmt = $pdo->prepare("UPDATE Product SET Price = ? WHERE ID = ?");
        $stmt->execute([$new_price, $product_id]);
    }

    // Deletar produto
    elseif ($action === 'delete') {
        // Excluir imagem associada (opcional)
        $stmt_img = $pdo->prepare("SELECT Image FROM Product WHERE ID = ?");
        $stmt_img->execute([$product_id]);
        $img = $stmt_img->fetchColumn();
        if ($img && file_exists("../uploads/$img")) {
            unlink("../uploads/$img");
        }

        $stmt = $pdo->prepare("DELETE FROM Product WHERE ID = ?");
        $stmt->execute([$product_id]);
    }

    // Redireciona de volta
    header('Location: ' . $_SERVER['HTTP_REFERER']);
    exit;
}
?>
