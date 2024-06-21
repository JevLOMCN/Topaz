<?php
$host = '127.0.0.1'; // The host of your database
$db_mm_game_db = 'eqspifzk_mm_game_db_release'; // The name of your database
$db_mm_user_db = 'eqspifzk_mm_user_db'; // The name of your database
$user = 'eqspifzk_localhost'; // The database user
$pass = 'fc*SZb6QJ8mzfBSS#GG^'; // The password of the database user
$charset = 'utf8mb4'; // The charset you wish to use

$dsn_game = "mysql:host=$host;dbname=$db_mm_game_db;charset=$charset";
$dsn_user = "mysql:host=$host;dbname=$db_mm_user_db;charset=$charset";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
     $pdo_game = new PDO($dsn_game, $user, $pass, $options);
} catch (\PDOException $e) {
     die("Não foi possível se conectar ao banco de dados: " . $e->getMessage());
     throw new \PDOException($e->getMessage(), (int)$e->getCode());
}

try {
     $pdo_user = new PDO($dsn_user, $user, $pass, $options);
} catch (\PDOException $e) {
     die("Não foi possível se conectar ao banco de dados: " . $e->getMessage());
     throw new \PDOException($e->getMessage(), (int)$e->getCode());
}

// PHPMailer settings
$mailHost = 'mail.thelegendofmir.uk'; // SMTP server
$mailPort = 465; // SMTP port
$mailUsername = 'reply@thelegendofmir.uk'; // SMTP username
$mailPassword = 'AdU,&gc{smN0'; // SMTP password
$mailFrom = 'noreply@example.com'; // From email address
$mailFromName = 'MIR4 LOMCN Register'; // From name
$mailIsSMTP = true; // Whether to use SMTP