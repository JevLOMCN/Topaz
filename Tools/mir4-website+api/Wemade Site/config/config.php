<?php
$host = 'http://thelegendofmir.uk/'; // Your database host
$db   = 'mm_user_db'; // The name of your database
$user = 'root'; // The database user
$pass = ''; // The database user's password
$charset = 'utf8mb4'; // The charset you want to use

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
     $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
     throw new \PDOException($e->getMessage(), (int)$e->getCode());
}
