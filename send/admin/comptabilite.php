<?php
require_once __DIR__ . '/includes/auth.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user'])) {
    header("Location: index.php");
    exit;
}

$user = $_SESSION['user'];

if($user['role'] != 'admin' && $user['role'] != 'finance'){
    die("⛔ Accès refusé");
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Comptabilité</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

<style>

body{
    background:#f4f6f9;
}

.menu-card{
    background:white;
    border-radius:16px;
    padding:40px;
    text-align:center;
    transition:0.3s;
    box-shadow:0 5px 20px rgba(0,0,0,0.08);
}

.menu-card:hover{
    transform:translateY(-5px);
}

a{
    text-decoration:none;
    color:inherit;
}

.icon{
    font-size:45px;
    margin-bottom:15px;
}

</style>
</head>

<body>

<div class="container py-5">

<h2 class="mb-4">💰 Comptabilité</h2>
   <a href="dashboard.php" class="btn btn-secondary">
            ⬅️ Retour
        </a>


<div class="row g-4">

    <div class="col-md-3">
        <a href="etat_ventes.php">
            <div class="menu-card">
                <div class="icon">📊</div>
                <h4>État des ventes</h4>
            </div>
        </a>
    </div>

<div class="col-md-3">
        <a href="etat_vente.php">
            <div class="menu-card">
                <div class="icon">📊</div>
                <h4>Export État des ventes</h4>
            </div>
        </a>
    </div>



    <div class="col-md-3">
        <a href="etat_financier.php">
            <div class="menu-card">
                <div class="icon">💵</div>
                <h4>État financier</h4>
            </div>
        </a>
    </div>

    <div class="col-md-3">
        <a href="remboursements.php">
            <div class="menu-card">
                <div class="icon">💸</div>
                <h4>Remboursements</h4>
            </div>
        </a>
    </div>

    <div class="col-md-3">
        <a href="billets_emis1.php">
            <div class="menu-card">
                <div class="icon">🎟️</div>
                <h4>Billets émis</h4>
            </div>
        </a>
    </div>

    <div class="col-md-3">
        <a href="billets_send.php">
            <div class="menu-card">
                <div class="icon">🧾</div>
                <h4>Billets</h4>
            </div>
        </a>
    </div>

    <div class="col-md-3">
        <a href="encaissements_om.php">
            <div class="menu-card">
                <div class="icon">📱</div>
                <h4>Encaissements Orange Money</h4>
            </div>
        </a>
    </div>

    <div class="col-md-3">
        <a href="encaissements_wave.php">
            <div class="menu-card">
                <div class="icon">🌊</div>
                <h4>Encaissements Wave</h4>
            </div>
        </a>
    </div>

</div>

</div>

</body>
</html>
