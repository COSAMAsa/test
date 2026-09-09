<?php

$host = '127.0.0.1';
$db   = 'billeterie';
$user = 'root';
$pass = '!Go@#-Bal@GO#Cos2026$$!';
$charset = 'utf8mb4';

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$db;charset=$charset",
        $user,
        $pass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    die("Erreur connexion : " . $e->getMessage());
}
