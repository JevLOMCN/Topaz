<?php
$host = 'localhost:3306';
$dbname = 'mm_user_db'; // Nome da base de dados existente
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Não foi possível se conectar ao banco de dados: " . $e->getMessage());
}
?>
