<?php
require_once '../config/database.php';
require_once __DIR__ . '/includes/auth.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* =========================================
   🔐 SECURITE
========================================= */
if(
    !isset($_SESSION['user'])
){
    header("Location: index.php");
    exit;
}

if(
    $_SESSION['user']['role'] != 'admin'
    &&
    $_SESSION['user']['role'] != 'finance'
){
    die("⛔ Accès refusé");
}

/* =========================================
   📅 FILTRES
========================================= */
$debut = $_GET['debut'] ?? date('Y-m-01');

$fin = $_GET['fin'] ?? date('Y-m-d');

$bateau = $_GET['bateau'] ?? '';

/* =========================================
   🔥 REQUETE
========================================= */

$sql = "

SELECT

    DATE(b.date_reservation) as date,

    t.bateau as navire,

    COUNT(*) as total_billets,

    SUM(
        b.prix + COALESCE(b.frais_service,0)
    ) as total_montant,

    SUM(
        COALESCE(b.frais_service,0)
    ) as total_frais,

    SUM(
        CASE
            WHEN b.type_client IN ('resident','non_resident')
            THEN 400
            ELSE 0
        END
    ) as taxe_portuaire,

    SUM(
        CASE
            WHEN pl.type_place LIKE '%Pullman%'
            THEN 1
            ELSE 0
        END
    ) as pullman,

    SUM(
        CASE
            WHEN pl.type_place LIKE '%Cabine 2%'
            THEN 1
            ELSE 0
        END
    ) as cabine2,

    SUM(
        CASE
            WHEN pl.type_place LIKE '%Cabine 4%'
            THEN 1
            ELSE 0
        END
    ) as cabine4,

    SUM(
        CASE
            WHEN pl.type_place LIKE '%Cabine 8%'
            THEN 1
            ELSE 0
        END
    ) as cabine8

FROM billets b

JOIN places pl
ON b.id_place = pl.id_place

JOIN traversees t
ON b.traversee_id = t.id

WHERE (
    b.statut = 'payé'
    OR b.statut = 'valide'
    OR b.statut = 'embarque'
)

AND DATE(b.date_reservation)
BETWEEN ? AND ?

";

$params = [$debut, $fin];

/* =========================================
   🚢 FILTRE BATEAU
========================================= */

if($bateau != ''){

    $sql .= " AND t.bateau = ? ";

    $params[] = $bateau;
}

/* =========================================
   📊 GROUP BY
========================================= */

$sql .= "

GROUP BY
DATE(b.date_reservation),
t.bateau

ORDER BY date DESC

";

$stmt = $pdo->prepare($sql);

$stmt->execute($params);

$ventes = $stmt->fetchAll();

/* =========================================
   🔢 TOTALS
========================================= */
$totalGeneral = 0;

$totalFrais = 0;

$totalBillets = 0;

$totalPullman = 0;

$totalCabine2 = 0;

$totalCabine4 = 0;

$totalCabine8 = 0;

$totalTaxe = 0;

?>

<!DOCTYPE html>
<html lang="fr">

<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>État des ventes</title>

<link
href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css"
rel="stylesheet"
>

<style>

body{
    background:#eef2f7;
}

h3{
    font-weight:bold;
}

.card{
    border:none;
    border-radius:18px;
    overflow:hidden;
}

table{
    font-size:16px;
}

.table th{
    white-space:nowrap;
}

tfoot{
    font-size:17px;
}

.kpi{
    padding:20px;
    border-radius:15px;
    color:white;
    text-align:center;
}

.bg-blue{
    background:#0d6efd;
}

.bg-green{
    background:#198754;
}

.bg-orange{
    background:#fd7e14;
}

.bg-dark2{
    background:#212529;
}

.bg-purple{
    background:#6f42c1;
}

@media(max-width:768px){

    table{
        font-size:14px;
    }

    h3{
        font-size:22px;
    }

}

</style>

</head>

<body>

<div class="container-fluid mt-4 px-2 px-md-4">

    <!-- HEADER -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap">

        <h3>
            📊 État des ventes
        </h3>

        <a href="dashboard.php" class="btn btn-secondary">
            ⬅️ Retour
        </a>

    </div>

    <!-- FILTRE -->
    <div class="card shadow-sm mb-4">

        <div class="card-body">

            <form method="GET" class="row g-2">

                <!-- DATE DEBUT -->
                <div class="col-12 col-md-4">

                    <label class="fw-bold mb-1">
                        Début
                    </label>

                    <input
                    type="date"
                    name="debut"
                    value="<?= $debut ?>"
                    class="form-control"
                    >

                </div>

                <!-- DATE FIN -->
                <div class="col-12 col-md-4">

                    <label class="fw-bold mb-1">
                        Fin
                    </label>

                    <input
                    type="date"
                    name="fin"
                    value="<?= $fin ?>"
                    class="form-control"
                    >

                </div>

                <!-- NAVIRE -->
                <div class="col-12 col-md-4">

                    <label class="fw-bold mb-1">
                        Navire
                    </label>

                    <select
                    name="bateau"
                    class="form-select"
                    >

                        <option value="">
                            Tous les navires
                        </option>

                        <option
                        value="A.S.D"
                        <?= $bateau == 'A.S.D' ? 'selected' : '' ?>
                        >
                            A.S.D
                        </option>

                        <option
                        value="AGUENE"
                        <?= $bateau == 'AGUENE' ? 'selected' : '' ?>
                        >
                            AGUENE
                        </option>

                        <option
                        value="DIAMBOGNE"
                        <?= $bateau == 'DIAMBOGNE' ? 'selected' : '' ?>
                        >
                            DIAMBOGNE
                        </option>

                    </select>

                </div>

                <!-- BTN -->
                <div class="col-12">

                    <button class="btn btn-primary w-100">
                        🔍 Filtrer
                    </button>

                </div>

            </form>

        </div>

    </div>

    <!-- TABLE -->
    <div class="card shadow">

        <div class="card-body table-responsive">

            <table class="table table-bordered table-hover text-center align-middle">

                <thead class="table-dark">

                <tr>

                    <th>Date</th>

                    <th>Navire</th>

                    <th>Total billets</th>

                    <th>Pullman</th>

                    <th>Cabine 2</th>

                    <th>Cabine 4</th>

                    <th>Cabine 8</th>

                    <th>Frais service</th>

                    <th>Taxe portuaire</th>

                    <th>Total ventes</th>

                </tr>

                </thead>

                <tbody>

                <?php if(count($ventes) > 0): ?>

                    <?php foreach ($ventes as $v):

                    $totalGeneral += $v['total_montant'];

                    $totalFrais += $v['total_frais'];

                    $totalBillets += $v['total_billets'];

                    $totalPullman += $v['pullman'];

                    $totalCabine2 += $v['cabine2'];

                    $totalCabine4 += $v['cabine4'];

                    $totalCabine8 += $v['cabine8'];

                    $totalTaxe += $v['taxe_portuaire'];

                    ?>

                    <tr>

                        <td class="fw-bold">

                            <?= date(
                                'd/m/Y',
                                strtotime($v['date'])
                            ) ?>

                        </td>

                        <td class="fw-bold text-dark">

                            🚢 <?= $v['navire'] ?>

                        </td>

                        <td class="fw-bold text-primary">

                            <?= $v['total_billets'] ?>

                        </td>

                        <td>
                            <?= $v['pullman'] ?>
                        </td>

                        <td>
                            <?= $v['cabine2'] ?>
                        </td>

                        <td>
                            <?= $v['cabine4'] ?>
                        </td>

                        <td>
                            <?= $v['cabine8'] ?>
                        </td>

                        <td class="fw-bold text-dark">

                            <?= number_format(
                                $v['total_frais'],
                                0,
                                ',',
                                ' '
                            ) ?>

                            FCFA

                        </td>

                        <td class="fw-bold text-warning">

                            <?= number_format(
                                $v['taxe_portuaire'],
                                0,
                                ',',
                                ' '
                            ) ?>

                            FCFA

                        </td>

                        <td class="fw-bold text-success">

                            <?= number_format(
                                $v['total_montant'],
                                0,
                                ',',
                                ' '
                            ) ?>

                            FCFA

                        </td>

                    </tr>

                    <?php endforeach; ?>

                <?php else: ?>

                    <tr>

                        <td colspan="10">

                            ❌ Aucune vente trouvée

                        </td>

                    </tr>

                <?php endif; ?>

                </tbody>

                <!-- TOTAL -->
                <tfoot>

                <tr class="table-dark">

                    <th>TOTAL</th>

                    <th></th>

                    <th><?= $totalBillets ?></th>

                    <th><?= $totalPullman ?></th>

                    <th><?= $totalCabine2 ?></th>

                    <th><?= $totalCabine4 ?></th>

                    <th><?= $totalCabine8 ?></th>

                    <th>

                        <?= number_format(
                            $totalFrais,
                            0,
                            ',',
                            ' '
                        ) ?>

                        FCFA

                    </th>

                    <th>

                        <?= number_format(
                            $totalTaxe,
                            0,
                            ',',
                            ' '
                        ) ?>

                        FCFA

                    </th>

                    <th>

                        <?= number_format(
                            $totalGeneral,
                            0,
                            ',',
                            ' '
                        ) ?>

                        FCFA

                    </th>

                </tr>

                </tfoot>

            </table>

        </div>

    </div>

    <!-- KPI -->
    <div class="row mt-4 g-3">

        <!-- TOTAL -->
        <div class="col-md-3">

            <div class="kpi bg-green shadow">

                <h5>
                    💰 Total ventes
                </h5>

                <h3>

                    <?= number_format(
                        $totalGeneral,
                        0,
                        ',',
                        ' '
                    ) ?>

                    FCFA

                </h3>

            </div>

        </div>

        <!-- FRAIS -->
        <div class="col-md-3">

            <div class="kpi bg-dark2 shadow">

                <h5>
                    💳 Frais service
                </h5>

                <h3>

                    <?= number_format(
                        $totalFrais,
                        0,
                        ',',
                        ' '
                    ) ?>

                    FCFA

                </h3>

            </div>

        </div>

        <!-- TAXE -->
        <div class="col-md-3">

            <div class="kpi bg-purple shadow">

                <h5>
                    🏦 Taxe portuaire
                </h5>

                <h3>

                    <?= number_format(
                        $totalTaxe,
                        0,
                        ',',
                        ' '
                    ) ?>

                    FCFA

                </h3>

            </div>

        </div>

        <!-- BILLETS -->
        <div class="col-md-3">

            <div class="kpi bg-blue shadow">

                <h5>
                    🎟️ Billets vendus
                </h5>

                <h3>

                    <?= $totalBillets ?>

                </h3>

            </div>

        </div>

    </div>

</div>

</body>

</html>
