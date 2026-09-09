<?php
session_start();

require_once '../config/database.php';
require_once __DIR__ . '/includes/auth.php';

if (
    !isset($_SESSION['user']) ||
    (
        $_SESSION['user']['role'] != 'admin' &&
        $_SESSION['user']['role'] != 'finance'
    )
){
    die("⛔ Accès refusé");
}

/* =========================================
   💰 CHIFFRE D'AFFAIRES BRUT
========================================= */

$ca = $pdo->query("
SELECT 
SUM(prix + frais_service) AS total
FROM billets
WHERE statut IN ('payé','valide')
")->fetch();

/* =========================================
   ⚓ TAXES PORTUAIRES
========================================= */

$total_taxe = $pdo->query("
SELECT 
SUM(
    CASE 
        WHEN type_client IN ('resident','non_resident')
        THEN 400
        ELSE 0
    END
) AS total_taxe
FROM billets
WHERE statut IN ('payé','valide')
")->fetch();

/* =========================================
   📊 CA RÉEL
========================================= */

$ca_reel =
($ca['total'] ?? 0)
-
($total_taxe['total_taxe'] ?? 0);

/* =========================================
   📅 CHIFFRE D'AFFAIRES JOURNALIER
========================================= */

$ca_journalier = $pdo->query("
SELECT 

    DATE(date_reservation) as jour,
    
    SUM(prix + frais_service) as total_jour,

    SUM(
        CASE 
            WHEN type_client IN ('resident','non_resident')
            THEN 400
            ELSE 0
        END
    ) as taxe_jour

FROM billets

WHERE statut IN ('payé','valide')

GROUP BY DATE(date_reservation)

ORDER BY jour DESC
")->fetchAll();

?>

<!DOCTYPE html>
<html lang="fr">

<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>État Financier</title>

<link
href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css"
rel="stylesheet"
>

<style>

body{
    background:#f4f6f9;
    font-family:Arial, sans-serif;
}

/* TITRE */
.page-title{
    font-size:28px;
    font-weight:bold;
    margin-bottom:30px;
    color:#081827;
}

/* CARD */
.card-box{
    background:white;
    border-radius:18px;
    padding:30px;
    box-shadow:0 5px 20px rgba(0,0,0,0.08);
    text-align:center;
    transition:.3s;
}

.card-box:hover{
    transform:translateY(-5px);
}

.card-box h5{
    color:#666;
    margin-bottom:15px;
}

.card-box h2{
    color:#081827;
    font-size:32px;
    font-weight:bold;
}

/* TABLE */
.table{
    background:white;
    border-radius:12px;
    overflow:hidden;
}

.table th{
    white-space:nowrap;
}

/* MOBILE */
@media(max-width:768px){

    .page-title{
        font-size:22px;
    }

    .card-box{
        padding:20px;
    }

    .card-box h2{
        font-size:24px;
    }

}

</style>

</head>

<body>

<div class="container py-5">

    <!-- HEADER -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap">

        <h1 class="page-title">
            💰 État Financier
        </h1>

        <a href="comptabilite.php" class="btn btn-dark">
            ← Retour
        </a>

    </div>

    <!-- KPI -->
    <div class="row g-4">

        <!-- CA BRUT -->
        <div class="col-md-4">

            <div class="card-box">

                <h5>
                    💰 Chiffre d'affaires brut
                </h5>

                <h2>

                    <?= number_format(
                        $ca['total'] ?? 0,
                        0,
                        ',',
                        ' '
                    ) ?>

                    FCFA

                </h2>

            </div>

        </div>

        <!-- TAXE PORTUAIRE -->
        <div class="col-md-4">

            <div class="card-box">

                <h5>
                    ⚓ Taxes portuaires
                </h5>

                <h2>

                    <?= number_format(
                        $total_taxe['total_taxe'] ?? 0,
                        0,
                        ',',
                        ' '
                    ) ?>

                    FCFA

                </h2>

            </div>

        </div>

        <!-- CA REEL -->
        <div class="col-md-4">

            <div class="card-box">

                <h5>
                    📊 Chiffre d'affaires réel
                </h5>

                <h2>

                    <?= number_format(
                        $ca_reel ?? 0,
                        0,
                        ',',
                        ' '
                    ) ?>

                    FCFA

                </h2>

            </div>

        </div>

    </div>

    <!-- 📅 TABLEAU JOURNALIER -->
    <div class="card-box mt-5">

        <h4 class="mb-4">
            📅 Chiffre d'affaires journalier
        </h4>

        <div class="table-responsive">

            <table class="table table-bordered table-hover align-middle text-center">

                <thead class="table-dark">

                    <tr>

                        <th>Date</th>

                        <th>CA Brut</th>

                        <th>Taxes Portuaires</th>

                        <th>CA Réel</th>

                    </tr>

                </thead>

                <tbody>

                <?php if(count($ca_journalier) > 0): ?>

                    <?php foreach($ca_journalier as $j): 

                        $ca_reel_jour =
                        $j['total_jour']
                        -
                        $j['taxe_jour'];

                    ?>

                    <tr>

                        <td class="fw-bold">

                            <?= date(
                                'd/m/Y',
                                strtotime($j['jour'])
                            ) ?>

                        </td>

                        <td class="text-success fw-bold">

                            <?= number_format(
                                $j['total_jour'],
                                0,
                                ',',
                                ' '
                            ) ?>

                            FCFA

                        </td>

                        <td class="text-warning fw-bold">

                            <?= number_format(
                                $j['taxe_jour'],
                                0,
                                ',',
                                ' '
                            ) ?>

                            FCFA

                        </td>

                        <td class="text-primary fw-bold">

                            <?= number_format(
                                $ca_reel_jour,
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

                        <td colspan="4">

                            ❌ Aucun chiffre d'affaires trouvé

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