<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user'])) {
    header("Location: index.php");
    exit;
}

/* =========================================
   📅 FILTRES
========================================= */
$debut = $_GET['debut'] ?? date('Y-m-01');
$fin   = $_GET['fin'] ?? date('Y-m-d');
$bateau = $_GET['bateau'] ?? '';

/* =========================================
   🔥 REQUETE — regroupée PAR VOYAGE
========================================= */
$sql = "

SELECT
    t.id            AS voyage,
    t.date_depart   AS date_depart,
    t.bateau        AS navire,
    COUNT(CASE WHEN b.type_client IN ('resident','non_resident') THEN 1 END) AS effectif,
    SUM(CASE WHEN b.type_client IN ('resident','non_resident') THEN 400 ELSE 0 END) AS montant

FROM traversees t

LEFT JOIN billets b
ON b.traversee_id = t.id
AND b.statut IN ('valide','embarque')

WHERE DATE(t.date_depart) BETWEEN ? AND ?

";

$params = [$debut, $fin];

if ($bateau != '') {
    $sql .= " AND t.bateau = ? ";
    $params[] = $bateau;
}

$sql .= "

GROUP BY t.id, t.date_depart, t.bateau
HAVING effectif > 0
ORDER BY t.date_depart ASC

";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$voyages = $stmt->fetchAll();

/* =========================================
   🔢 TOTALS
========================================= */
$totalEffectif = 0;
$totalMontant  = 0;

foreach ($voyages as $v) {
    $totalEffectif += $v['effectif'];
    $totalMontant  += $v['montant'];
}

$commissionTaux = 0; // % — ajustez si une commission s'applique
$commissionMontant = $totalMontant * ($commissionTaux / 100);
$netAPercevoir = $totalMontant - $commissionMontant;

/* =========================================
   🖨️ IMPRESSION / EXPORT (vue imprimable = PDF via navigateur)
========================================= */
$print = isset($_GET['print']);
?>

<!DOCTYPE html>
<html lang="fr">
<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Recouvrement Redevance Portuaire</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

<style>

body{
    background:#eef2f7;
}

.card{
    border:none;
    border-radius:18px;
    overflow:hidden;
}

.report-box{
    background:white;
    border-radius:12px;
    padding:30px;
}

.report-header{
    display:flex;
    justify-content:space-between;
    align-items:center;
    border-bottom:2px solid #212529;
    padding-bottom:15px;
    margin-bottom:20px;
}

.report-title{
    font-weight:bold;
    font-size:22px;
}

.table th, .table td{
    vertical-align:middle;
    text-align:center;
}

tfoot th{
    font-size:16px;
}

.signature-box{
    margin-top:60px;
    display:flex;
    justify-content:space-between;
}

@media print{
    .no-print{
        display:none !important;
    }
    body{
        background:white;
    }
    .report-box{
        padding:0;
    }
}

</style>

</head>

<body>

<div class="container-fluid mt-4 px-2 px-md-4">

    <!-- HEADER -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap no-print">

        <h3 class="fw-bold">
            🏦 Recouvrement Redevance Portuaire
        </h3>

        <div class="d-flex gap-2 flex-wrap">

            <button onclick="window.print()" class="btn btn-success">
                🖨️ Imprimer / PDF
            </button>

            <a href="billets_emis.php" class="btn btn-secondary">
                ⬅️ Retour
            </a>

        </div>

    </div>

    <!-- FILTRE -->
    <div class="card shadow-sm mb-4 no-print">

        <div class="card-body">

            <form method="GET" class="row g-2">

                <div class="col-12 col-md-4">
                    <label class="fw-bold mb-1">Début</label>
                    <input type="date" name="debut" value="<?= htmlspecialchars($debut) ?>" class="form-control">
                </div>

                <div class="col-12 col-md-4">
                    <label class="fw-bold mb-1">Fin</label>
                    <input type="date" name="fin" value="<?= htmlspecialchars($fin) ?>" class="form-control">
                </div>

                <div class="col-12 col-md-4">
                    <label class="fw-bold mb-1">Navire</label>
                    <select name="bateau" class="form-select">
                        <option value="">Tous les navires</option>
                        <option value="A.S.D" <?= $bateau == 'A.S.D' ? 'selected' : '' ?>>A.S.D</option>
                        <option value="AGUENE" <?= $bateau == 'AGUENE' ? 'selected' : '' ?>>AGUENE</option>
                        <option value="DIAMBOGNE" <?= $bateau == 'DIAMBOGNE' ? 'selected' : '' ?>>DIAMBOGNE</option>
                    </select>
                </div>

                <div class="col-12">
                    <button class="btn btn-dark w-100">🔍 Filtrer</button>
                </div>

            </form>

        </div>

    </div>

    <!-- RAPPORT -->
    <div class="card shadow">

        <div class="card-body report-box">

            <div class="report-header">

                <div>
                    <div class="report-title">RECOUVREMENT REDEVANCE PORTUAIRE</div>
                    <div>
                        Période — Du <?= date('d/m/Y', strtotime($debut)) ?>
                        au <?= date('d/m/Y', strtotime($fin)) ?>
                        <?= $bateau != '' ? ' — Navire : ' . htmlspecialchars($bateau) : '' ?>
                    </div>
                </div>

                <div class="text-end">
                    <small>Édité le <?= date('d/m/Y à H:i:s') ?></small>
                </div>

            </div>

            <table class="table table-bordered table-hover">

                <thead class="table-dark">
                    <tr>
                        <th>VOYAGE</th>
                        <th>DATE VOYAGE / NAVIRE</th>
                        <th>EFFECTIF</th>
                        <th>MONTANT</th>
                    </tr>
                </thead>

                <tbody>

                <?php if (count($voyages) > 0): ?>

                    <?php foreach ($voyages as $v): ?>

                        <tr>
                            <td class="fw-bold"><?= (int) $v['voyage'] ?></td>
                            <td>
                                <?= date('Y-m-d H:i:s', strtotime($v['date_depart'])) ?>
                                / <?= htmlspecialchars($v['navire']) ?>
                            </td>
                            <td><?= (int) $v['effectif'] ?></td>
                            <td><?= number_format($v['montant'], 0, ',', ' ') ?></td>
                        </tr>

                    <?php endforeach; ?>

                <?php else: ?>

                    <tr>
                        <td colspan="4" class="py-4">❌ Aucun voyage trouvé sur cette période</td>
                    </tr>

                <?php endif; ?>

                </tbody>

                <tfoot>

                    <tr class="table-secondary">
                        <th colspan="2">TOTAL :</th>
                        <th><?= $totalEffectif ?></th>
                        <th><?= number_format($totalMontant, 0, ',', ' ') ?></th>
                    </tr>

                    <tr>
                        <th colspan="3">COMMISSION (<?= $commissionTaux ?>%) :</th>
                        <th><?= number_format($commissionMontant, 0, ',', ' ') ?></th>
                    </tr>

                    <tr class="table-dark">
                        <th colspan="3">NET À PERCEVOIR :</th>
                        <th><?= number_format($netAPercevoir, 0, ',', ' ') ?></th>
                    </tr>

                </tfoot>

            </table>

            <div class="signature-box">
                <div><strong>VISA COSAMA :</strong></div>
                <div><strong>VISA DGTP :</strong></div>
            </div>

        </div>

    </div>

</div>

</body>
</html>
