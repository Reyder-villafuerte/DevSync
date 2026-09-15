<?php
$pdo = new PDO('mysql:host=127.0.0.1;port=3306;dbname=milkflow_web', 'root', '');
$stmt = $pdo->query('SHOW TABLES');
while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
    echo $row[0] . "\n";
}
