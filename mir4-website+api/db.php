<?php
$host = 'localhost:3306';
$dbname = 'eqspifzk_mm_user_db'; // Nome da base de dados existente
$username = 'dev';
$password = '1111';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Não foi possível se conectar ao banco de dados: " . $e->getMessage());
}
?>
