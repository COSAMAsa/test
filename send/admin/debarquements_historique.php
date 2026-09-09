<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/../config/database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user'])) {
    header("Location: index.php");
    exit;
}

$user = $_SESSION['user'];

if($user['role'] != 'admin' && $user['role'] != 'agent'){
    die("⛔ Accès refusé");
}

$where = "WHERE 1=1";
$params = [];

/* =========================================
   🔎 RECHERCHE
========================================= */
if(!empty($_GET['search'])){

    $where .= " AND (
        b.nom LIKE ?
        OR b.prenom LIKE ?
        OR b.code_qr LIKE ?
        OR b.telephone LIKE ?
        OR d.motif LIKE ?
    )";

    $search = "%".$_GET['search']."%";

    $params[] = $search;
    $params[] = $search;
    $params[] = $search;
    $params[] = $search;
    $params[] = $search;
}

/* =========================================
   📅 FILTRE DATE
========================================= */
if(!empty($_GET['date_debut'])){

    $where .= " AND DATE(d.created_at) >= ?";

    $params[] = $_GET['date_debut'];
}

if(!empty($_GET['date_fin'])){

    $where .= " AND DATE(d.created_at) <= ?";

    $params[] = $_GET['date_fin'];
}

/* =========================================
   📋 RECUPERATION HISTORIQUE
========================================= */
$sql = "

SELECT
    d.*,
    b.nom,
    b.prenom,
    b.telephone,
    b.code_qr,
    b.type_passager,
    b.type_client,
    t.depart,
    t.destination,
    t.date_depart

FROM debarquements d

JOIN billets b
ON d.billet_id = b.id

LEFT JOIN traversees t
ON b.traversee_id = t.id

$where

ORDER BY d.created_at DESC

";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);

$debarquements = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="fr">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Historique des débarquements</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

<style>

body{
    background:#f4f6f9;
}

.card{
    border:none;
    border-radius:18px;
    overflow:hidden;
}

.table th{
    vertical-align:middle;
    white-space:nowrap;
}

.table td{
    vertical-align:middle;
}

.badge{
    font-size:13px;
}

@media(max-width:768px){

    h3{
        font-size:22px;
    }

    .table{
        font-size:14px;
    }

}

</style>

</head>

<body>

<div class="container-fluid mt-4 px-2 px-md-4">

    <!-- HEADER -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap">

        <h3 class="fw-bold">
            🚤 Historique des débarquements
        </h3>

        <div class="d-flex gap-2 flex-wrap">

            <a href="debarquement.php" class="btn btn-dark">
                🚤 Nouveau débarquement
            </a>

            <a href="billets_emis.php" class="btn btn-secondary">
                ⬅️ Retour
            </a>

        </div>

    </div>

    <!-- FILTRE -->
    <div class="card shadow-sm mb-4">

        <div class="card-body">

            <form method="GET">

                <div class="row g-2">

                    <div class="col-md-5">

                        <input
                        type="text"
                        name="search"
                        class="form-control"
                        placeholder="🔍 Nom, téléphone, code billet ou motif"
                        value="<?= htmlspecialchars($_GET['search'] ?? '') ?>"
                        >

                    </div>

                    <div class="col-md-3">

                        <input
                        type="date"
                        name="date_debut"
                        class="form-control"
                        value="<?= htmlspecialchars($_GET['date_debut'] ?? '') ?>"
                        >

                    </div>

                    <div class="col-md-3">

                        <input
                        type="date"
                        name="date_fin"
                        class="form-control"
                        value="<?= htmlspecialchars($_GET['date_fin'] ?? '') ?>"
                        >

                    </div>

                    <div class="col-md-1">

                        <button class="btn btn-dark w-100">
                            OK
                        </button>

                    </div>

                </div>

            </form>

        </div>

    </div>

    <!-- TOTAL -->
    <div class="mb-3">
        <span class="badge bg-dark fs-6">
            🚤 <?= count($debarquements) ?> débarquement(s) trouvé(s)
        </span>
    </div>

    <!-- TABLEAU -->
    <div class="card shadow">

        <div class="card-body table-responsive">

            <table class="table table-bordered table-hover text-center align-middle">

                <thead class="table-dark">

                <tr>
                    <th>Code billet</th>
                    <th>Passager</th>
                    <th>Téléphone</th>
                    <th>Type</th>
                    <th>Voyage</th>
                    <th>Date départ</th>
                    <th>Motif du débarquement</th>
                    <th>Agent</th>
                    <th>Date débarquement</th>
                </tr>

                </thead>

                <tbody>

                <?php if(count($debarquements) > 0): ?>

                    <?php foreach ($debarquements as $d): ?>

                    <tr>

                        <td>
                            <span class="badge bg-secondary">
                                <?= htmlspecialchars($d['code_qr']) ?>
                            </span>
                        </td>

                        <td>
                            <?= htmlspecialchars(trim($d['prenom'] . ' ' . $d['nom'])) ?>
                        </td>

                        <td>
                            <?= htmlspecialchars($d['telephone']) ?>
                        </td>

                        <td>
                            <?= ucfirst($d['type_passager']) ?>
                        </td>

                        <td>
                            <?= htmlspecialchars($d['depart'] ?? '-') ?>
                            →
                            <?= htmlspecialchars($d['destination'] ?? '-') ?>
                        </td>

                        <td>
                            <?= !empty($d['date_depart'])
                                ? date('d/m/Y H:i', strtotime($d['date_depart']))
                                : '-' ?>
                        </td>

                        <td>
                            <span class="badge bg-warning text-dark">
                                <?= htmlspecialchars($d['motif']) ?>
                            </span>
                        </td>

                        <td>
                            <?= htmlspecialchars($d['agent'] ?? '-') ?>
                        </td>

                        <td>
                            <?= date('d/m/Y H:i', strtotime($d['created_at'])) ?>
                        </td>

                    </tr>

                    <?php endforeach; ?>

                <?php else: ?>

                    <tr>

                        <td colspan="9" class="text-center py-4">

                            ❌ Aucun débarquement trouvé

                        </td>

                    </tr>

                <?php endif; ?>

                </tbody>

            </table>

        </div>

    </div>

</div>

</body>

</html>
