<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user'])) {
    header("Location: index.php");
    exit;
}

$where = "WHERE b.statut = 'valide'";
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
    )";

    $search = "%".$_GET['search']."%";

    $params[] = $search;
    $params[] = $search;
    $params[] = $search;
    $params[] = $search;
}

/* =========================================
   📅 FILTRE DATE
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
    p.numero_place,
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
?>

<!DOCTYPE html>
<html lang="fr">

<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Rechercher billet</title>

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

/* BADGE */
.badge{
    font-size:14px;
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
        🔍 Rechercher billet
    </h3>

    <div class="d-flex gap-2">

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

                    <div class="col-md-5">

                        <input
                        type="text"
                        name="search"
                        class="form-control"
                        placeholder="🔍 Nom, téléphone ou code billet"
                        value="<?= $_GET['search'] ?? '' ?>"
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

                    <div class="col-md-1">

                        <button class="btn btn-dark w-100">
                            OK
                        </button>

                    </div>

                </div>

            </form>

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
                    <th>Date réservation</th>
                    <th>Date départ</th>
                    <th>Total payé</th>
                    <th>Code billet</th>
                    <th>Actions</th>

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
    <?= htmlspecialchars(($b['prenom'] . ' ' . $b['nom'])) ?>
</td>

                        <td>
                            <?= htmlspecialchars($b['telephone']) ?>
                        </td>

                        <td>
                            <?= htmlspecialchars($b['cni']) ?>
                        </td>

                        <td>
                            <?= htmlspecialchars($b['type_place']) ?>
                            <?= !empty($b['numero_place']) ? '('.htmlspecialchars($b['numero_place']).')' : '' ?>
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
                            <?= date('d/m/Y H:i', strtotime($b['date_reservation'])) ?>
                        </td>

                        <td>
                            <?= date('d/m/Y H:i', strtotime($b['date_depart'])) ?>
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

                            <div class="d-grid gap-1">

                                <!-- PDF -->

                                
                               <a
                                href="/generate_pdf.php?id=<?= $b['id'] ?>"
                                class="btn btn-success btn-sm"
                                target="_blank"
                                >

                                📥 PDF

                                </a>

                                <!-- CARTE EMBARQUEMENT -->

                                <a
                                href="/generate_embarquement.php?id=<?= $b['id'] ?>"
                                class="btn btn-primary btn-sm"
                                target="_blank"
                                >

                                🛂 Carte embarquement

                                </a>

                            </div>

                        </td>

                    </tr>

                    <?php endforeach; ?>

                <?php else: ?>

                    <tr>

                        <td colspan="11" class="text-center py-4">

                            ❌ Aucun billet trouvé

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
