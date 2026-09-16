<?php
$pdo = new PDO('mysql:host=127.0.0.1;port=3306', 'root', '');
$pdo->exec('CREATE DATABASE IF NOT EXISTS milkflow_web;');
echo "Database created or already exists.\n";
