<?php
session_start();
require_once '../config/database.php';
require_once __DIR__ . '/includes/auth.php';

if($_SESSION['user']['role'] != 'admin' && $_SESSION['user']['role'] != 'Exploitation'){
    die("⛔ Accès refusé");
}
// 🔐 sécurité
if (!isset($_SESSION['user'])) {
    header("Location: index.php");
    exit;
}

$id = $_GET['id'];

try {

    // 🔥 supprimer billets (si tu veux éviter erreur FK)
    $stmt = $pdo->prepare("DELETE FROM billets WHERE traversee_id=?");
    $stmt->execute([$id]);

    // 🔥 supprimer places
    $stmt = $pdo->prepare("DELETE FROM places WHERE traversee_id=?");
    $stmt->execute([$id]);

    // 🔥 supprimer traversée
    $stmt = $pdo->prepare("DELETE FROM traversees WHERE id=?");
    $stmt->execute([$id]);

    header("Location: liste_traversees.php?success=deleted");
    exit;

} catch (Exception $e) {
    die("❌ Erreur : " . $e->getMessage());
}
