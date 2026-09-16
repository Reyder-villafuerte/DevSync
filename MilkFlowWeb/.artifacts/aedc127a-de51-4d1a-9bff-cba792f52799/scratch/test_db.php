<?php
$host = '127.0.0.1';
$db   = 'milkflow';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
     $pdo = new PDO($dsn, $user, $pass, $options);
     echo "Conexión exitosa a la base de datos '$db'!\n";
} catch (\PDOException $e) {
     echo "Error de conexión: " . $e->getMessage() . "\n";
     if ($e->getCode() == 1049) {
         echo "TIP: La base de datos '$db' no existe. Créala en phpMyAdmin.\n";
     }
}
