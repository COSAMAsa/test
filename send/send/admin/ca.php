<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user'])) {
    header("Location: index.php");
    exit;
}

// Sécurité rôle
$role = $_SESSION['user']['role'] ?? '';
if (!in_array($role, ['admin', 'exploitation', 'finance'])) {
    header("Location: dashboard.php");
    exit;
}

/* =========================================
   📅 FILTRES
========================================= */
$traversee_id = $_GET['traversee_id'] ?? '';
$date_debut   = $_GET['date_debut'] ?? '';
$date_fin     = $_GET['date_fin'] ?? '';

/* =========================================
   📋 LISTE DES TRAVERSEES (pour le select + filtre date)
========================================= */
$sqlTrav = "SELECT id, date_depart FROM traversees";
$condTrav = [];
$paramsTrav = [];

if (!empty($date_debut)) {
    $condTrav[] = "DATE(date_depart) >= ?";
    $paramsTrav[] = $date_debut;
}

if (!empty($date_fin)) {
    $condTrav[] = "DATE(date_depart) <= ?";
    $paramsTrav[] = $date_fin;
}

if (!empty($condTrav)) {
    $sqlTrav .= " WHERE " . implode(" AND ", $condTrav);
}

$sqlTrav .= " ORDER BY date_depart DESC";

$stmtTrav = $pdo->prepare($sqlTrav);
$stmtTrav->execute($paramsTrav);
$traversees = $stmtTrav->fetchAll();

/* =========================================
   💰 FONCTION CALCUL CA POUR UNE TRAVERSEE
========================================= */
function calculerCA($pdo, $traversee_id, $statuts) {

    $placeholders = implode(',', array_fill(0, count($statuts), '?'));

    $sql = "
        SELECT
            COALESCE(SUM(b.prix), 0) AS total_billets,
            COALESCE(SUM(b.frais_service), 0) AS total_frais,
            COUNT(*) AS nb_billets
        FROM billets b
        WHERE b.traversee_id = ?
        AND b.statut IN ($placeholders)
    ";

    $params = array_merge([$traversee_id], $statuts);

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    $res = $stmt->fetch();

    $res['total_general'] = $res['total_billets'] + $res['total_frais'];

    return $res;
}

/* =========================================
   📊 CAS 1 : UNE TRAVERSEE SELECTIONNEE
========================================= */
$ca_global   = null;
$ca_embarque = null;
$billets_detail = [];
$t_selected = null;

if (!empty($traversee_id)) {

    $ca_global   = calculerCA($pdo, $traversee_id, ['valide', 'embarque']);
    $ca_embarque = calculerCA($pdo, $traversee_id, ['embarque']);

    foreach ($traversees as $t) {
        if ($t['id'] == $traversee_id) {
            $t_selected = $t;
            break;
        }
    }

    $sqlDetail = "
        SELECT
            b.*,
            p.type_place

        FROM billets b

        LEFT JOIN places p
        ON b.id_place = p.id_place

        WHERE b.traversee_id = ?
        AND b.statut IN ('valide', 'embarque')

        ORDER BY b.id DESC
    ";

    $stmtDetail = $pdo->prepare($sqlDetail);
    $stmtDetail->execute([$traversee_id]);
    $billets_detail = $stmtDetail->fetchAll();
}

/* =========================================
   📊 CAS 2 : AUCUNE TRAVERSEE -> TABLEAU RECAP DE TOUTES
========================================= */
$recap = [];
$ca_recap_total_global   = 0;
$ca_recap_total_embarque = 0;

if (empty($traversee_id)) {

    foreach ($traversees as $t) {

        $g = calculerCA($pdo, $t['id'], ['valide', 'embarque']);
        $e = calculerCA($pdo, $t['id'], ['embarque']);

        $ca_recap_total_global   += $g['total_general'];
        $ca_recap_total_embarque += $e['total_general'];

        $recap[] = [
            'traversee'    => $t,
            'ca_global'    => $g,
            'ca_embarque'  => $e,
        ];
    }
}
?>

<!DOCTYPE html>
<html lang="fr">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Chiffre d'affaires</title>

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

.total-box{
    border-radius:15px;
    padding:20px;
    color:white;
}

.bg-blue{
    background:#0d6efd;
}

.bg-purple{
    background:#6f42c1;
}

.bg-dark2{
    background:#212529;
}

.bg-green{
    background:#198754;
}

.bg-teal{
    background:#20c997;
}

/* En-tête d'impression : masqué à l'écran, visible seulement sur papier */
.print-header{
    display:none;
}

@media(max-width:768px){

    h3{
        font-size:22px;
    }

    .table{
        font-size:13px;
    }

}

/* =========================================
   🖨️ MISE EN PAGE IMPRESSION
========================================= */
@media print{

    body{
        background:white;
        -webkit-print-color-adjust:exact;
        print-color-adjust:exact;
    }

    /* On masque tout ce qui n'est pas utile sur papier */
    .no-print,
    .card.shadow-sm,
    form{
        display:none !important;
    }

    .print-header{
        display:block;
        margin-bottom:20px;
    }

    .print-header h1{
        font-size:22px;
        color:#0d6efd;
        margin-bottom:4px;
    }

    .print-header p{
        font-size:13px;
        color:#333;
        margin-bottom:2px;
    }

    .card{
        box-shadow:none !important;
        border:1px solid #ddd !important;
    }

    .total-box{
        -webkit-print-color-adjust:exact;
        print-color-adjust:exact;
        border:1px solid rgba(0,0,0,.1);
    }

    .table{
        font-size:12px;
    }

    .badge{
        border:1px solid rgba(0,0,0,.2);
    }

    @page{
        margin:15mm;
    }
}

</style>

</head>

<body>

<div class="container-fluid mt-4 px-2 px-md-4">

    <!-- HEADER -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap no-print">

        <h3 class="fw-bold">
            💹 Chiffre d'affaires
        </h3>

        <div class="d-flex gap-2 flex-wrap">

            <a
            href="export_ca.php?traversee_id=<?= urlencode($traversee_id) ?>&date_debut=<?= urlencode($date_debut) ?>&date_fin=<?= urlencode($date_fin) ?>"
            class="btn btn-success"
            >
                📊 Exporter CSV
            </a>

            <button type="button" class="btn btn-outline-primary" onclick="window.print()">
                🖨️ Imprimer
            </button>

            <a href="dashboard.php" class="btn btn-secondary">
                ⬅️ Retour
            </a>

        </div>

    </div>

    <!-- EN-TETE IMPRESSION -->
    <div class="print-header">

        <h1>💹 Chiffre d'affaires</h1>

        <?php if (!empty($traversee_id) && $t_selected): ?>

            <p>
                Traversée du <?= date('d/m/Y à H:i', strtotime($t_selected['date_depart'])) ?>
            </p>

        <?php else: ?>

            <p>
                Récapitulatif de toutes les traversées
                <?php if (!empty($date_debut) || !empty($date_fin)): ?>
                    (période :
                    <?= !empty($date_debut) ? date('d/m/Y', strtotime($date_debut)) : '...' ?>
                    →
                    <?= !empty($date_fin) ? date('d/m/Y', strtotime($date_fin)) : '...' ?>
                    )
                <?php endif; ?>
            </p>

        <?php endif; ?>

        <p>
            Document généré le <?= date('d/m/Y à H:i') ?>
        </p>

    </div>

    <!-- FILTRE -->
    <div class="card shadow-sm mb-4 no-print">

        <div class="card-body">

            <form method="GET">

                <div class="row g-2">

                    <div class="col-md-5">

                        <select name="traversee_id" class="form-select">

                            <option value="">-- Toutes les traversées --</option>

                            <?php foreach ($traversees as $t): ?>

                                <option
                                value="<?= $t['id'] ?>"
                                <?= ($traversee_id == $t['id']) ? 'selected' : '' ?>
                                >
                                    Traversée du <?= date('d/m/Y H:i', strtotime($t['date_depart'])) ?>
                                </option>

                            <?php endforeach; ?>

                        </select>

                    </div>

                    <div class="col-md-3">

                        <input
                        type="date"
                        name="date_debut"
                        class="form-control"
                        value="<?= htmlspecialchars($date_debut) ?>"
                        >

                    </div>

                    <div class="col-md-3">

                        <input
                        type="date"
                        name="date_fin"
                        class="form-control"
                        value="<?= htmlspecialchars($date_fin) ?>"
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

    <?php if (!empty($traversee_id)): ?>

        <!-- =========================================
             VUE DETAIL POUR UNE TRAVERSEE
        ========================================= -->

        <h5 class="mb-3 no-print">
            🚢 Traversée du
            <?= $t_selected ? date('d/m/Y H:i', strtotime($t_selected['date_depart'])) : '' ?>
        </h5>

        <!-- CA VALIDE + EMBARQUE -->
        <div class="mb-2">
            <span class="badge bg-primary">Billets valides + embarqués</span>
        </div>

        <div class="row mb-4">

            <div class="col-md-3 mb-2">
                <div class="total-box bg-blue shadow">
                    <h6>🎫 Nb billets</h6>
                    <h4><?= $ca_global['nb_billets'] ?></h4>
                </div>
            </div>

            <div class="col-md-3 mb-2">
                <div class="total-box bg-purple shadow">
                    <h6>🎫 Total billets</h6>
                    <h4><?= number_format($ca_global['total_billets'],0,',',' ') ?> FCFA</h4>
                </div>
            </div>

            <div class="col-md-3 mb-2">
                <div class="total-box bg-dark2 shadow">
                    <h6>💳 Frais service</h6>
                    <h4><?= number_format($ca_global['total_frais'],0,',',' ') ?> FCFA</h4>
                </div>
            </div>

            <div class="col-md-3 mb-2">
                <div class="total-box bg-green shadow">
                    <h6>💰 CA total</h6>
                    <h4><?= number_format($ca_global['total_general'],0,',',' ') ?> FCFA</h4>
                </div>
            </div>

        </div>

        <!-- CA EMBARQUE UNIQUEMENT -->
        <div class="mb-2">
            <span class="badge bg-teal">Billets embarqués uniquement</span>
        </div>

        <div class="row mb-4">

            <div class="col-md-3 mb-2">
                <div class="total-box bg-blue shadow">
                    <h6>🎫 Nb billets</h6>
                    <h4><?= $ca_embarque['nb_billets'] ?></h4>
                </div>
            </div>

            <div class="col-md-3 mb-2">
                <div class="total-box bg-purple shadow">
                    <h6>🎫 Total billets</h6>
                    <h4><?= number_format($ca_embarque['total_billets'],0,',',' ') ?> FCFA</h4>
                </div>
            </div>

            <div class="col-md-3 mb-2">
                <div class="total-box bg-dark2 shadow">
                    <h6>💳 Frais service</h6>
                    <h4><?= number_format($ca_embarque['total_frais'],0,',',' ') ?> FCFA</h4>
                </div>
            </div>

            <div class="col-md-3 mb-2">
                <div class="total-box bg-teal shadow">
                    <h6>💰 CA embarqué</h6>
                    <h4><?= number_format($ca_embarque['total_general'],0,',',' ') ?> FCFA</h4>
                </div>
            </div>

        </div>

        <!-- DETAIL DES BILLETS -->
        <div class="card shadow">

            <div class="card-body table-responsive">

                <table class="table table-bordered table-hover text-center align-middle">

                    <thead class="table-dark">

                    <tr>
                        <th>Nom</th>
                        <th>Téléphone</th>
                        <th>Place</th>
                        <th>Prix</th>
                        <th>Frais</th>
                        <th>Total payé</th>
                        <th>Code billet</th>
                        <th>Statut</th>
                    </tr>

                    </thead>

                    <tbody>

                    <?php if (count($billets_detail) > 0): ?>

                        <?php foreach ($billets_detail as $b): ?>

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
                                </td>

                                <td>
                                    <?= number_format($b['prix'],0,',',' ') ?> FCFA
                                </td>

                                <td>
                                    <?= number_format($b['frais_service'],0,',',' ') ?> FCFA
                                </td>

                                <td class="fw-bold text-success">
                                    <?= number_format($total_paye,0,',',' ') ?> FCFA
                                </td>

                                <td>
                                    <span class="badge bg-secondary">
                                        <?= $b['code_qr'] ?>
                                    </span>
                                </td>

                                <td>
                                    <?php if ($b['statut'] == 'embarque'): ?>
                                        <span class="badge bg-primary">🚢 Embarqué</span>
                                    <?php else: ?>
                                        <span class="badge bg-success">✅ Valide</span>
                                    <?php endif; ?>
                                </td>

                            </tr>

                        <?php endforeach; ?>

                    <?php else: ?>

                        <tr>
                            <td colspan="8" class="text-center py-4">
                                ❌ Aucun billet trouvé
                            </td>
                        </tr>

                    <?php endif; ?>

                    </tbody>

                </table>

            </div>

        </div>

    <?php else: ?>

        <!-- =========================================
             VUE RECAP TOUTES TRAVERSEES
        ========================================= -->

        <!-- TOTAUX GLOBAUX -->
        <div class="row mb-4">

            <div class="col-md-6 mb-2">
                <div class="total-box bg-green shadow">
                    <h6>💰 CA total (valide + embarqué) — toutes traversées</h6>
                    <h4><?= number_format($ca_recap_total_global,0,',',' ') ?> FCFA</h4>
                </div>
            </div>

            <div class="col-md-6 mb-2">
                <div class="total-box bg-teal shadow">
                    <h6>💰 CA embarqué — toutes traversées</h6>
                    <h4><?= number_format($ca_recap_total_embarque,0,',',' ') ?> FCFA</h4>
                </div>
            </div>

        </div>

        <div class="card shadow">

            <div class="card-body table-responsive">

                <table class="table table-bordered table-hover text-center align-middle">

                    <thead class="table-dark">

                    <tr>
                        <th>Traversée</th>
                        <th>Nb billets (valide+embarqué)</th>
                        <th>CA valide + embarqué</th>
                        <th>Nb billets embarqués</th>
                        <th>CA embarqué</th>
                        <th class="no-print">Détail</th>
                    </tr>

                    </thead>

                    <tbody>

                    <?php if (count($recap) > 0): ?>

                        <?php foreach ($recap as $r): ?>

                            <tr>

                                <td>
                                    <?= date('d/m/Y H:i', strtotime($r['traversee']['date_depart'])) ?>
                                </td>

                                <td>
                                    <?= $r['ca_global']['nb_billets'] ?>
                                </td>

                                <td class="fw-bold text-primary">
                                    <?= number_format($r['ca_global']['total_general'],0,',',' ') ?> FCFA
                                </td>

                                <td>
                                    <?= $r['ca_embarque']['nb_billets'] ?>
                                </td>

                                <td class="fw-bold text-success">
                                    <?= number_format($r['ca_embarque']['total_general'],0,',',' ') ?> FCFA
                                </td>

                                <td class="no-print">
                                    <a
                                    href="ca.php?traversee_id=<?= $r['traversee']['id'] ?>"
                                    class="btn btn-sm btn-outline-dark"
                                    >
                                        🔍 Voir
                                    </a>
                                </td>

                            </tr>

                        <?php endforeach; ?>

                    <?php else: ?>

                        <tr>
                            <td colspan="6" class="text-center py-4">
                                ❌ Aucune traversée trouvée
                            </td>
                        </tr>

                    <?php endif; ?>

                    </tbody>

                </table>

            </div>

        </div>

    <?php endif; ?>

</div>

</body>

</html>
