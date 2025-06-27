<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('Location: ../auth/login.php');
    exit;
}
echo "Bem-vindo, ADMIN " . $_SESSION['user']['name'] . "! Esta é sua área administrativa.";
?>
<a href="../auth/logout.php">Sair</a>
