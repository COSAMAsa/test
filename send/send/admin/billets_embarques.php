<?php
session_start();

require_once '../config/database.php';

if (!isset($_SESSION['user'])) {
    header("Location: index.php");
    exit;
}

$where = "WHERE b.statut = 'embarque'";
$params = [];

/* =========================================
   🔎 RECHERCHE
========================================= */
if(!empty($_GET['q'])){

    $where .= " AND (
        b.nom LIKE ?
        OR b.code_qr LIKE ?
        OR b.telephone LIKE ?
    )";

    $search = "%".$_GET['q']."%";

    $params[] = $search;
    $params[] = $search;
    $params[] = $search;
}

/* =========================================
   📅 FILTRE PAR DATE
========================================= */
if(!empty($_GET['date_debut'])){

    $where .= " AND DATE(b.date_reservation) >= ?";

    $params[] = $_GET['date_debut'];
}

if(!empty($_GET['date_fin'])){

    $where .= " AND DATE(b.date_reservation) <= ?";

    $params[] = $_GET['date_fin'];
}

/* =========================================
   📋 RECUPERATION BILLETS
========================================= */
$sql = "

SELECT
    b.*,
    p.type_place,
    t.date_depart

FROM billets b

LEFT JOIN places p
ON b.id_place = p.id_place

LEFT JOIN traversees t
ON b.traversee_id = t.id

$where

ORDER BY b.id DESC

";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);

$billets = $stmt->fetchAll();

/* =========================================
   💰 TOTALS
========================================= */
$total_billets = 0;
$total_frais = 0;
$total_general = 0;

foreach($billets as $b){

    $total_billets += $b['prix'];

    $total_frais += $b['frais_service'];

    $total_general += (
        $b['prix'] + $b['frais_service']
    );
}
?>

<!DOCTYPE html>
<html lang="fr">

<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Billets embarqués</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

<style>

body{
    background:#f4f6f9;
}

/* CARD */
.card{
    border:none;
    border-radius:18px;
    overflow:hidden;
}

/* TABLE */
.table th{
    vertical-align:middle;
    white-space:nowrap;
}

.table td{
    vertical-align:middle;
}

/* BADGES */
.badge{
    font-size:14px;
}

/* TOTAL BOX */
.total-box{
    border-radius:15px;
    padding:15px;
    color:white;
}

.bg-blue{
    background:#0d6efd;
}

.bg-green{
    background:#198754;
}

.bg-dark2{
    background:#212529;
}

/* MOBILE */
@media(max-width:768px){

    h3{
        font-size:22px;
    }

    .btn{
        margin-bottom:5px;
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
        🚢 Billets embarqués
    </h3>

    <div class="d-flex gap-2">

        <a href="manifeste_voyage.php" class="btn btn-primary">
            📋 Manifeste par voyage
        </a>

<a
    href="manifeste_carabane.php?traversee_id=<?= $traversee_id ?>"
    target="_blank"
    class="btn btn-warning"
>
    🚢 Manifeste Carabane
</a>

        <a href="dashboard.php" class="btn btn-secondary">
            ⬅️ Retour
        </a>

    </div>

</div>

    <!-- FILTRE -->
    <div class="card shadow-sm mb-4">

        <div class="card-body">

            <form method="GET">

                <div class="row g-2">

                    <div class="col-md-4">

                        <input
                        type="text"
                        name="q"
                        class="form-control"
                        placeholder="🔍 Nom, téléphone ou code billet"
                        value="<?= $_GET['q'] ?? '' ?>"
                        >

                    </div>

                    <div class="col-md-3">

                        <input
                        type="date"
                        name="date_debut"
                        class="form-control"
                        value="<?= $_GET['date_debut'] ?? '' ?>"
                        >

                    </div>

                    <div class="col-md-3">

                        <input
                        type="date"
                        name="date_fin"
                        class="form-control"
                        value="<?= $_GET['date_fin'] ?? '' ?>"
                        >

                    </div>

                    <div class="col-md-2">

                        <button class="btn btn-dark w-100">
                            Rechercher
                        </button>

                    </div>

                </div>

            </form>

        </div>

    </div>

    <!-- TOTALS -->
    <div class="row mb-4">

        <div class="col-md-4 mb-2">

            <div class="total-box bg-blue shadow">

                <h6>🎫 Total billets</h6>

                <h4>
                    <?= number_format($total_billets,0,',',' ') ?> FCFA
                </h4>

            </div>

        </div>

        <div class="col-md-4 mb-2">

            <div class="total-box bg-dark2 shadow">

                <h6>💳 Frais service</h6>

                <h4>
                    <?= number_format($total_frais,0,',',' ') ?> FCFA
                </h4>

            </div>

        </div>

        <div class="col-md-4 mb-2">

            <div class="total-box bg-green shadow">

                <h6>💰 Total embarqué</h6>

                <h4>
                    <?= number_format($total_general,0,',',' ') ?> FCFA
                </h4>

            </div>

        </div>

    </div>

    <!-- TABLEAU -->
    <div class="card shadow">

        <div class="card-body table-responsive">

            <table class="table table-bordered table-hover text-center align-middle">

                <thead class="table-dark">

                <tr>

                    <th>Nom</th>
                    <th>Téléphone</th>
                    <th>CNI</th>
                    <th>Place</th>
                    <th>Type</th>
                    <th>Client</th>
                    <th>Date départ</th>
                    <th>Prix billet</th>
                    <th>Frais</th>
                    <th>Total payé</th>
                    <th>Code billet</th>
                    <th>Statut</th>

                </tr>

                </thead>

                <tbody>

                <?php if(count($billets) > 0): ?>

                    <?php foreach ($billets as $b): ?>

                    <?php
                        $total_paye = (
                            $b['prix'] + $b['frais_service']
                        );
                    ?>

                    <tr>

                      <td>
    <?= htmlspecialchars(trim(($b['prenom'] ?? '') . ' ' . $b['nom'])) ?>
</td>

                        <td>
                            <?= htmlspecialchars($b['telephone']) ?>
                        </td>

                        <td>
                            <?= htmlspecialchars($b['cni']) ?>
                        </td>

                        <td>
                            <?= htmlspecialchars($b['type_place']) ?>
                        </td>

                        <td>
                            <?= ucfirst($b['type_passager']) ?>
                        </td>

                        <td>

                            <?php
                            if($b['type_client'] == 'senegalais'){
                                echo "Sénégalais";
                            }
                            elseif($b['type_client'] == 'resident'){
                                echo "Étranger Résident";
                            }
                            else{
                                echo "Étranger Non Résident";
                            }
                            ?>

                        </td>

                        <td>
                            <?= date('d/m/Y H:i', strtotime($b['date_depart'])) ?>
                        </td>

                        <td>

                            <span class="fw-bold text-primary">

                                <?= number_format($b['prix'],0,',',' ') ?>

                                FCFA

                            </span>

                        </td>

                        <td>

                            <span class="fw-bold text-dark">

                                <?= number_format($b['frais_service'],0,',',' ') ?>

                                FCFA

                            </span>

                        </td>

                        <td>

                            <span class="fw-bold text-success fs-6">

                                <?= number_format($total_paye,0,',',' ') ?>

                                FCFA

                            </span>

                        </td>

                        <td>

                            <span class="badge bg-secondary">

                                <?= $b['code_qr'] ?>

                            </span>

                        </td>

                        <td>

                            <span class="badge bg-success">

                                🚢 Embarqué

                            </span>

                        </td>

                    </tr>

                    <?php endforeach; ?>

                <?php else: ?>

                    <tr>

                        <td colspan="12" class="text-center py-4">

                            ❌ Aucun billet embarqué

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
