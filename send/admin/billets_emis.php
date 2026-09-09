<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user'])) {
    header("Location: index.php");
    exit;
}

$where = "WHERE b.statut IN ('valide', 'embarque')";
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
   💳 FILTRE MODE DE PAIEMENT
========================================= */
if(!empty($_GET['mode_paiement']) && in_array($_GET['mode_paiement'], ['om','wave'])){

    $where .= " AND b.mode_paiement = ?";

    $params[] = $_GET['mode_paiement'];
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

<title>Billets émis</title>

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

/* TOTALS */
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
        🎟️ Billets émis
    </h3>

    <div class="d-flex gap-2 flex-wrap">
 <a href="carabane.php" class="btn btn-info text-white">
        Carabane
    </a>

    <a href="admin_disponibilites.php" class="btn btn-info text-white">
        📊 Disponibilité des places
    </a>

<a href="pax.php" class="btn btn-info text-white">
    👥 PAX Passagers
</a>


    <a href="plan_salle.php" class="btn btn-outline-primary">
        💺 Plan de salle
    </a>

    <a href="billets_send1.php" class="btn btn-primary">
        🧾 Billets par traversée
    </a>

 <a href="details_voyage.php" class="btn btn-outline-info">
                🚢 Détails Voyage
            </a>

<a href="controle.php" class="btn btn-outline-success">
        🎫 Embarquement
    </a>

    <a href="debarquement.php" class="btn btn-outline-dark">
        🚤 Débarquement
    </a>


<a href="recouvrement_redevance_portuaire.php" class="btn btn-outline-dark">
    🏦 Recouvrement Redevance Portuaire
</a>

<a href="debarquements_historique.php" class="btn btn-outline-secondary">
    📜 Historique
</a>


     <a href="reservations_attente.php" class="btn btn-outline-warning">
        ⏳ Réservations en attente / traitées
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
                        name="search"
                        class="form-control"
                        placeholder="🔍 Nom, téléphone ou code billet"
                        value="<?= htmlspecialchars($_GET['search'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                        >

                    </div>

                    <div class="col-md-2">

                        <input
                        type="date"
                        name="date_debut"
                        class="form-control"
                        value="<?= htmlspecialchars($_GET['date_debut'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                        >

                    </div>

                    <div class="col-md-2">

                        <input
                        type="date"
                        name="date_fin"
                        class="form-control"
                        value="<?= htmlspecialchars($_GET['date_fin'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                        >

                    </div>

                    <div class="col-md-3">

                        <select name="mode_paiement" class="form-select">

                            <option value="">
                                Tous les modes de paiement
                            </option>

                            <option value="om" <?= (($_GET['mode_paiement'] ?? '') === 'om') ? 'selected' : '' ?>>
                                📱 Orange Money
                            </option>

                            <option value="wave" <?= (($_GET['mode_paiement'] ?? '') === 'wave') ? 'selected' : '' ?>>
                                🌊 Wave
                            </option>

                        </select>

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

                <h6>💰 Total général</h6>

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
                    <th>Mode paiement</th>
                    <th>Date réservation</th>
                    <th>Date départ</th>
                    <th>Prix billet</th>
                    <th>Frais</th>
                    <th>Total payé</th>
                    <th>Code billet</th>
                    <th>Statut</th>
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

                            <?php if(($b['mode_paiement'] ?? 'om') === 'wave'): ?>

                                <span class="badge" style="background:#1BA0E2;">
                                    🌊 Wave
                                </span>

                            <?php else: ?>

                                <span class="badge" style="background:#ff6600;">
                                    📱 OM
                                </span>

                            <?php endif; ?>

                        </td>

                        <td>
                            <?= date('d/m/Y H:i', strtotime($b['date_reservation'])) ?>
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

                            <span class="text-dark fw-bold">

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

                                <?= htmlspecialchars($b['code_qr'], ENT_QUOTES, 'UTF-8') ?>

                            </span>

                        </td>

                        <td>

                            <?php if($b['statut'] == 'embarque'): ?>
                                <span class="badge bg-primary">
                                    🚢 Embarqué
                                </span>
                            <?php else: ?>
                                <span class="badge bg-success">
                                    ✅ Valide
                                </span>
                            <?php endif; ?>

                        </td>

                    <td>

                            <div class="d-grid gap-1">

                                <!-- MODIFIER -->





                                <!-- REPORT -->

                                <a
                                href="report_billet.php?id=<?= (int) $b['id'] ?>"
                                class="btn btn-warning btn-sm"
                                >

                                🔁 Reporter

                                </a>

                                <!-- PDF -->

                                <a
                                href="/generate_pdf.php?token=<?= urlencode($b['token_acces']) ?>"
class="btn btn-success btn-sm"
target="_blank"
>

📥 PDF

</a>

                            </div>

                        </td>

                    </tr>

                    <?php endforeach; ?>

                <?php else: ?>

                    <tr>

                        <td colspan="15" class="text-center py-4">

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
