<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user'])) {
    header("Location: index.php");
    exit;
}

/* =========================================
   📅 FILTRE DATE (optionnel)
========================================= */
$where = "WHERE 1=1";
$params = [];

if(!empty($_GET['date_debut'])){
    $where .= " AND DATE(t.date_depart) >= ?";
    $params[] = $_GET['date_debut'];
}
if(!empty($_GET['date_fin'])){
    $where .= " AND DATE(t.date_depart) <= ?";
    $params[] = $_GET['date_fin'];
}
if(!empty($_GET['q'])){
    $where .= " AND (t.reference_voyage LIKE ? OR t.bateau LIKE ?)";
    $s = "%".$_GET['q']."%";
    $params[] = $s;
    $params[] = $s;
}

/* =========================================
   📋 RECUPERATION TRAVERSEES + COMPTAGE EMBARQUES
========================================= */
$sql = "
SELECT
    t.*,
    COUNT(b.id) AS nb_embarques
FROM traversees t
LEFT JOIN billets b
    ON b.traversee_id = t.id
    AND b.statut = 'embarque'
$where
GROUP BY t.id
ORDER BY t.date_depart DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$traversees = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="fr">
<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Manifestes par traversée</title>

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
            🧾 Manifestes des passagers embarqués
        </h3>

        <div class="d-flex gap-2">

            <a href="billets_embarques.php" class="btn btn-primary">
                🚢 Billets embarqués
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
                        name="q"
                        class="form-control"
                        placeholder="🔍 Référence voyage ou bateau"
                        value="<?= htmlspecialchars($_GET['q'] ?? '') ?>"
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

    <!-- TABLEAU -->
    <div class="card shadow">

        <div class="card-body table-responsive">

            <table class="table table-bordered table-hover text-center align-middle">

                <thead class="table-dark">

                <tr>

                    <th>Référence voyage</th>
                    <th>Bateau</th>
                    <th>Départ</th>
                    <th>Destination</th>
                    <th>Date départ</th>
                    <th>Passagers embarqués</th>
                    <th>Action</th>

                </tr>

                </thead>

                <tbody>

                <?php if(count($traversees) > 0): ?>

                    <?php foreach ($traversees as $t): ?>

                    <tr>

                        <td>
                            <span class="badge bg-secondary">
                                <?= htmlspecialchars($t['reference_voyage']) ?>
                            </span>
                        </td>

                        <td>
                            <?= htmlspecialchars($t['bateau']) ?>
                        </td>

                        <td>
                            <?= htmlspecialchars($t['depart']) ?>
                        </td>

                        <td>
                            <?= htmlspecialchars($t['destination']) ?>
                        </td>

                        <td>
                            <?= date('d/m/Y H:i', strtotime($t['date_depart'])) ?>
                        </td>

                        <td>

                            <span class="badge bg-success fs-6">
                                <?= (int)$t['nb_embarques'] ?> passagers
                            </span>

                        </td>

                        <td>

                            
                            href="generate_manifeste_embarques.php?traversee_id=<?= $t['id'] ?>"
                            class="btn btn-success btn-sm"
                            target="_blank"
                            >

                            📥 Manifeste PDF

                            </a>

                        </td>

                    </tr>

                    <?php endforeach; ?>

                <?php else: ?>

                    <tr>
                        <td colspan="7" class="text-center py-4">
                            ❌ Aucune traversée trouvée
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
