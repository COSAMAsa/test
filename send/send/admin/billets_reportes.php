<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user'])) {
    header("Location: index.php");
    exit;
}

// Sécurité rôle
$role = $_SESSION['user']['role'] ?? '';
if (!in_array($role, ['admin', 'exploitation', 'agent'])) {
    header("Location: dashboard.php");
    exit;
}

$where = "WHERE b.report_effectue = 1";
$params = [];

/* =========================================
   🔎 RECHERCHE
========================================= */
if (!empty($_GET['search'])) {

    $where .= " AND (
        b.nom LIKE ?
        OR b.prenom LIKE ?
        OR b.code_qr LIKE ?
        OR b.telephone LIKE ?
    )";

    $search = "%" . $_GET['search'] . "%";

    $params[] = $search;
    $params[] = $search;
    $params[] = $search;
    $params[] = $search;
}

/* =========================================
   📅 FILTRE DATE (date de réservation)
========================================= */
if (!empty($_GET['date_debut'])) {
    $where .= " AND DATE(b.date_reservation) >= ?";
    $params[] = $_GET['date_debut'];
}

if (!empty($_GET['date_fin'])) {
    $where .= " AND DATE(b.date_reservation) <= ?";
    $params[] = $_GET['date_fin'];
}

/* =========================================
   📋 RECUPERATION DES BILLETS REPORTES
========================================= */
$sql = "

SELECT
    b.*,
    p.type_place,
    p.numero_place,
    t.date_depart,
    t.reference_voyage,
    t.depart AS depart_traversee,
    t.destination AS destination_traversee,

    ta.date_depart AS ancienne_date_depart,
    ta.reference_voyage AS ancienne_reference_voyage,
    ta.depart AS ancien_depart_traversee,
    ta.destination AS ancienne_destination_traversee,
    pa.numero_place AS ancien_numero_place,
    pa.type_place AS ancien_type_place

FROM billets b

LEFT JOIN places p
ON b.id_place = p.id_place

LEFT JOIN traversees t
ON b.traversee_id = t.id

LEFT JOIN traversees ta
ON b.ancienne_traversee_id = ta.id

LEFT JOIN places pa
ON b.ancienne_place_id = pa.id_place

$where

ORDER BY b.date_reservation DESC

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

foreach ($billets as $b) {
    $total_billets += $b['prix'];
    $total_frais += $b['frais_service'];
    $total_general += ($b['prix'] + $b['frais_service']);
}
?>

<!DOCTYPE html>
<html lang="fr">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Billets reportés</title>

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
    font-size:14px;
}

.total-box{
    border-radius:15px;
    padding:15px;
    color:white;
}

.bg-blue{
    background:#0d6efd;
}

.bg-orange{
    background:#fd7e14;
}

.bg-dark2{
    background:#212529;
}

.bg-green{
    background:#198754;
}

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
            🔁 Billets reportés
        </h3>

        <div class="d-flex gap-2 flex-wrap">

            <a href="#" class="btn btn-outline-primary">
                🎟️ Billets émis
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

                    <div class="col-md-5">

                        <input
                        type="text"
                        name="search"
                        class="form-control"
                        placeholder="🔍 Nom, téléphone ou code billet"
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

    <!-- TOTALS -->
    <div class="row mb-4">

        <div class="col-md-3 mb-2">

            <div class="total-box bg-orange shadow">
                <h6>🔁 Nb billets reportés</h6>
                <h4><?= count($billets) ?></h4>
            </div>

        </div>

        <div class="col-md-3 mb-2">

            <div class="total-box bg-blue shadow">
                <h6>🎫 Total billets</h6>
                <h4><?= number_format($total_billets, 0, ',', ' ') ?> FCFA</h4>
            </div>

        </div>

        <div class="col-md-3 mb-2">

            <div class="total-box bg-dark2 shadow">
                <h6>💳 Frais service</h6>
                <h4><?= number_format($total_frais, 0, ',', ' ') ?> FCFA</h4>
            </div>

        </div>

        <div class="col-md-3 mb-2">

            <div class="total-box bg-green shadow">
                <h6>💰 Total général</h6>
                <h4><?= number_format($total_general, 0, ',', ' ') ?> FCFA</h4>
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
                    <th>Place actuelle</th>
                    <th>Traversée ancienne</th>
                    <th>Traversée actuelle</th>
                    <th>Date réservation</th>
                    <th>Prix billet</th>
                    <th>Frais</th>
                    <th>Total payé</th>
                    <th>Code billet</th>
                    <th>Statut</th>
                    <th>Actions</th>
                </tr>

                </thead>

                <tbody>

                <?php if (count($billets) > 0): ?>

                    <?php foreach ($billets as $b): ?>

                        <?php $total_paye = $b['prix'] + $b['frais_service']; ?>

                        <tr>

                            <td>
                                <?= htmlspecialchars($b['prenom'] . ' ' . $b['nom']) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($b['telephone']) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($b['type_place'] ?? '') ?>
                                <?php if (!empty($b['numero_place'])): ?>
                                    <br>
                                    <span class="badge bg-info text-dark">
                                        N° <?= htmlspecialchars($b['numero_place']) ?>
                                    </span>
                                <?php endif; ?>
                            </td>

                            <td>
                                <?php if (!empty($b['ancienne_reference_voyage']) || !empty($b['ancien_depart_traversee'])): ?>
                                    <span class="text-muted">
                                        <?= htmlspecialchars($b['ancienne_reference_voyage'] ?? '') ?>
                                    </span>
                                    <br>
                                    <small class="text-muted">
                                        <?= htmlspecialchars($b['ancien_depart_traversee'] ?? '') ?>
                                        →
                                        <?= htmlspecialchars($b['ancienne_destination_traversee'] ?? '') ?>
                                    </small>
                                    <br>
                                    <small class="text-muted">
                                        <?= !empty($b['ancienne_date_depart']) ? date('d/m/Y H:i', strtotime($b['ancienne_date_depart'])) : '' ?>
                                        <?php if (!empty($b['ancien_numero_place'])): ?>
                                            — N° <?= htmlspecialchars($b['ancien_numero_place']) ?>
                                        <?php endif; ?>
                                    </small>
                                <?php else: ?>
                                    <span class="text-muted" title="Report effectué avant la mise en place de l'historique">
                                        — inconnue
                                    </span>
                                <?php endif; ?>
                            </td>

                            <td>
                                <?php if (!empty($b['reference_voyage'])): ?>
                                    <?= htmlspecialchars($b['reference_voyage']) ?>
                                    <br>
                                    <small class="text-muted">
                                        <?= htmlspecialchars($b['depart_traversee'] ?? '') ?>
                                        →
                                        <?= htmlspecialchars($b['destination_traversee'] ?? '') ?>
                                    </small>
                                    <br>
                                    <small class="text-muted">
                                        <?= !empty($b['date_depart']) ? date('d/m/Y H:i', strtotime($b['date_depart'])) : '' ?>
                                    </small>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>

                            <td>
                                <?= date('d/m/Y H:i', strtotime($b['date_reservation'])) ?>
                            </td>

                            <td>
                                <span class="fw-bold text-primary">
                                    <?= number_format($b['prix'], 0, ',', ' ') ?> FCFA
                                </span>
                            </td>

                            <td>
                                <span class="text-dark fw-bold">
                                    <?= number_format($b['frais_service'], 0, ',', ' ') ?> FCFA
                                </span>
                            </td>

                            <td>
                                <span class="fw-bold text-success fs-6">
                                    <?= number_format($total_paye, 0, ',', ' ') ?> FCFA
                                </span>
                            </td>

                            <td>
                                <span class="badge bg-secondary">
                                    <?= $b['code_qr'] ?>
                                </span>
                            </td>

                            <td>

                                <?php if ($b['statut'] == 'embarque'): ?>
                                    <span class="badge bg-primary">🚢 Embarqué</span>
                                <?php elseif ($b['statut'] == 'valide'): ?>
                                    <span class="badge bg-success">✅ Valide</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">
                                        <?= htmlspecialchars(ucfirst($b['statut'])) ?>
                                    </span>
                                <?php endif; ?>

                                <br>

                                <span class="badge bg-orange mt-1">
                                    🔁 Reporté
                                </span>

                            </td>

                            <td>

                                <div class="d-grid gap-1">

                                    <a
                                    href="../public/generate_pdf.php?id=<?= $b['id'] ?>"
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
                        <td colspan="12" class="text-center py-4">
                            ❌ Aucun billet reporté trouvé
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
