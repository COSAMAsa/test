<?php
session_start();

require_once '../config/database.php';

// 🔐 SECURITE
if(
    !isset($_SESSION['user']) ||
    $_SESSION['user']['role'] != 'admin'
){
    die("⛔ Accès refusé");
}

/* =========================================
   💰 CHIFFRE D'AFFAIRES (valide + embarqué)
========================================= */
$ca = $pdo->query("
SELECT 
SUM(prix + COALESCE(frais_service,0))
FROM billets
WHERE statut IN ('valide','embarque')
")->fetchColumn();

if(!$ca){
    $ca = 0;
}

/* =========================================
   🎟️ BILLETS VENDUS (valide + embarqué)
========================================= */
$billets = $pdo->query("
SELECT COUNT(*)
FROM billets
WHERE statut IN ('valide','embarque')
")->fetchColumn();

/* =========================================
   🚢 BILLETS EMBARQUES
========================================= */
$embarques = $pdo->query("
SELECT COUNT(*)
FROM billets
WHERE statut='embarque'
")->fetchColumn();

/* =========================================
   💳 FRAIS SERVICE (valide + embarqué)
========================================= */
$frais = $pdo->query("
SELECT SUM(COALESCE(frais_service,0))
FROM billets
WHERE statut IN ('valide','embarque')
")->fetchColumn();

if(!$frais){
    $frais = 0;
}

/* =========================================
   🚢 TAUX DE VENTE (billets vendus / places disponibles)
   -> Basé sur les traversées à venir (date_depart >= NOW())
   -> taux = places vendues / capacité totale
========================================= */
$stmt = $pdo->query("
    SELECT
        SUM(p.total) as capacite_totale,
        SUM(p.total - p.restant) as places_vendues
    FROM traversees t
    JOIN places p ON t.id = p.traversee_id
    WHERE t.date_depart >= NOW()
");

$row_taux = $stmt->fetch();

if($row_taux && $row_taux['capacite_totale'] > 0){
    $taux = round(($row_taux['places_vendues'] / $row_taux['capacite_totale']) * 100, 2);
} else {
    $taux = 0;
}

/* =========================================
   📈 CA 7 DERNIERS JOURS (valide + embarqué)
========================================= */
$stmt = $pdo->query("

SELECT
    DATE(date_reservation) as jour,
    SUM(prix + COALESCE(frais_service,0)) as total

FROM billets

WHERE statut IN ('valide','embarque')

AND date_reservation >= DATE_SUB(NOW(), INTERVAL 7 DAY)

GROUP BY jour

ORDER BY jour ASC

");

$labels = [];
$data = [];

while($row = $stmt->fetch()){

    $labels[] = date(
        'd/m',
        strtotime($row['jour'])
    );

    $data[] = (int)$row['total'];
}

/* =========================================
   👥 KPI TYPE CLIENT (valide + embarqué)
========================================= */
$stmt = $pdo->query("
SELECT
    LOWER(TRIM(type_client)) as type_client,
    COUNT(*) as total
FROM billets
WHERE statut IN ('valide','embarque')
GROUP BY LOWER(TRIM(type_client))
");

$clients_map = [
    'senegalais' => 0,
    'resident' => 0,
    'non_resident' => 0
];

while($row = $stmt->fetch()){
    $type = strtolower(trim($row['type_client']));

    if(isset($clients_map[$type])){
        $clients_map[$type] = (int)$row['total'];
    }
}

/* Labels fixes */
$clients_labels = [
    'Sénégalais',
    'Étranger Résident',
    'Étranger Non Résident'
];

$clients_data = [
    $clients_map['senegalais'],
    $clients_map['resident'],
    $clients_map['non_resident']
];

/* =========================================
   📊 TOP PLACES (valide + embarqué)
========================================= */
$stmt = $pdo->query("

SELECT
    p.type_place,
    COUNT(*) as total

FROM billets b

JOIN places p
ON b.id_place = p.id_place

WHERE b.statut IN ('valide','embarque')

GROUP BY p.type_place

ORDER BY total DESC

LIMIT 5

");

$places_labels = [];
$places_data = [];

while($row = $stmt->fetch()){

    $places_labels[] = $row['type_place'];

    $places_data[] = (int)$row['total'];
}
?>

<!DOCTYPE html>
<html lang="fr">

<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Dashboard Direction</title>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<link
href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css"
rel="stylesheet"
>

<style>

body{
    background:#f4f6f9;
}

/* TITRE */
h2{
    font-weight:bold;
}

/* CARD */
.card{
    border:none;
    border-radius:20px;
    overflow:hidden;
}

/* KPI */
.kpi-card{
    color:white;
    padding:25px;
    text-align:center;
    transition:0.3s;
}

.kpi-card:hover{
    transform:translateY(-5px);
}

/* COLORS */
.bg-blue{
    background:#0d6efd;
}

.bg-green{
    background:#198754;
}

.bg-dark2{
    background:#212529;
}

.bg-orange{
    background:#fd7e14;
}

.bg-purple{
    background:#6f42c1;
}

/* CANVAS */
canvas{
    max-height:350px;
}

/* MOBILE */
@media(max-width:768px){

    .kpi-card{
        margin-bottom:10px;
    }

    h2{
        font-size:24px;
    }

}

</style>

</head>

<body>

<div class="container-fluid mt-4 px-2 px-md-4">

    <!-- HEADER -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap">

        <h2>
            📊 Dashboard Direction
        </h2>

        <a href="dashboard.php" class="btn btn-secondary">
            ⬅️ Retour
        </a>

    </div>

    <!-- KPI -->
    <div class="row g-3 mb-4">

        <!-- CA -->
        <div class="col-md-3">

            <div class="card shadow">

                <div class="kpi-card bg-green">

                    <h5>💰 Chiffre d'affaires</h5>

                    <h3>
                        <?= number_format($ca,0,',',' ') ?>
                        FCFA
                    </h3>

                </div>

            </div>

        </div>

        <!-- BILLETS -->
        <div class="col-md-3">

            <div class="card shadow">

                <div class="kpi-card bg-blue">

                    <h5>🎟️ Billets vendus</h5>

                    <h3>
                        <?= $billets ?>
                    </h3>

                </div>

            </div>

        </div>

        <!-- EMBARQUES -->
        <div class="col-md-3">

            <div class="card shadow">

                <div class="kpi-card bg-orange">

                    <h5>🚢 Embarqués</h5>

                    <h3>
                        <?= $embarques ?>
                    </h3>

                </div>

            </div>

        </div>

        <!-- TAUX -->
        <div class="col-md-3">

            <div class="card shadow">

                <div class="kpi-card bg-purple">

                    <h5>📈 Taux de Vente</h5>

                    <h3>
                        <?= $taux ?> %
                    </h3>

                </div>

            </div>

        </div>

    </div>

    <!-- FRAIS -->
    <div class="row mb-4">

        <div class="col-md-12">

            <div class="card shadow">

                <div class="kpi-card bg-dark2">

                    <h5>
                        💳 Total frais de service encaissés
                    </h5>

                    <h2>
                        <?= number_format($frais,0,',',' ') ?>
                        FCFA
                    </h2>

                </div>

            </div>

        </div>

    </div>

    <!-- GRAPH CA -->
    <div class="card shadow p-4 mb-4">

        <h5 class="mb-4">
            📈 Chiffre d'affaires (7 derniers jours)
        </h5>

        <canvas id="chartCA"></canvas>

    </div>

    <!-- ROW -->
    <div class="row">

        <!-- CLIENTS -->
        <div class="col-md-6 mb-4">

            <div class="card shadow p-4 h-100">

                <h5 class="mb-4">
                    👥 Répartition des clients
                </h5>

                <canvas id="chartClients"></canvas>

            </div>

        </div>

        <!-- TOP PLACES -->
        <div class="col-md-6 mb-4">

            <div class="card shadow p-4 h-100">

                <h5 class="mb-4">
                    🛏️ Places les plus vendues
                </h5>

                <canvas id="chartPlaces"></canvas>

            </div>

        </div>

    </div>

</div>

<script>

/* =========================================
   📈 CA
========================================= */
new Chart(document.getElementById('chartCA'), {

    type: 'line',

    data: {

        labels: <?= json_encode($labels) ?>,

        datasets: [{

            label: 'CA (FCFA)',

            data: <?= json_encode($data) ?>,

            tension: 0.4,

            fill: true,

            borderWidth: 3

        }]

    },

    options: {

        responsive:true,

        plugins:{
            legend:{
                display:true
            }
        }

    }

});

/* =========================================
   👥 CLIENTS
========================================= */
new Chart(document.getElementById('chartClients'), {

    type: 'doughnut',

    data: {

        labels: <?= json_encode($clients_labels) ?>,

        datasets: [{

            data: <?= json_encode($clients_data) ?>,

            backgroundColor: [
                '#198754', // Sénégalais (vert)
                '#0d6efd', // Étranger résident (bleu)
                '#dc3545'  // Étranger non résident (rouge)
            ],

            borderWidth: 1

        }]

    },

    options: {

        responsive: true,

        plugins: {
            legend: {
                position: 'top'
            }
        }

    }

});

/* =========================================
   🛏️ TOP PLACES
========================================= */
new Chart(document.getElementById('chartPlaces'), {

    type: 'bar',

    data: {

        labels: <?= json_encode($places_labels) ?>,

        datasets: [{

            label: 'Billets vendus',

            data: <?= json_encode($places_data) ?>,

            borderWidth: 1

        }]

    },

    options: {

        responsive:true,

        plugins:{
            legend:{
                display:false
            }
        },

        scales:{
            y:{
                beginAtZero:true
            }
        }

    }

});

</script>

</body>

</html>
