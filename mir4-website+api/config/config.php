<?php
$host = '127.0.0.1'; // The host of your database
$db = 'mm_user_db'; // The name of your database
$user = 'root'; // The database user
$pass = ''; // The password of the database user
$charset = 'utf8mb4'; // The charset you wish to use

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

// PHPMailer settings
$mailHost = 'mail.thelegendofmir.uk'; // SMTP server
$mailPort = 465; // SMTP port
$mailUsername = 'reply@thelegendofmir.uk'; // SMTP username
$mailPassword = 'AdU,&gc{smN0'; // SMTP password
$mailFrom = 'noreply@example.com'; // From email address
$mailFromName = 'MIR4 LOMCN Register'; // From name
$mailIsSMTP = true; // Whether to use SMTP