<?php
$host = 'db';
$db = 'ecommerce';
$user = 'user';
$pass = 'secret';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
} catch (PDOException $e) {
    die("Erro de conexão: " . $e->getMessage());
}
?>
