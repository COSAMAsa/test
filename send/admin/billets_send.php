<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user'])) {
    header("Location: index.php");
    exit;
}

$traversees = $pdo->query("
    SELECT *
    FROM traversees
    ORDER BY date_depart DESC
")->fetchAll();

$billets = [];
$total_passagers = 0;

if (!empty($_GET['traversee_id'])) {

$stmt = $pdo->prepare("
    SELECT
        b.*,
        p.numero_place,
        p.type_place,
        t.date_depart

    FROM billets b

    LEFT JOIN places p
        ON b.id_place = p.id_place

    LEFT JOIN traversees t
        ON b.traversee_id = t.id

    WHERE b.statut='valide'
    AND b.traversee_id=?

    ORDER BY p.numero_place ASC
");

    $stmt->execute([$_GET['traversee_id']]);

    $billets = $stmt->fetchAll();

    $total_passagers = count($billets);
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Billets par traversée</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

<style>

body{
    background:#f4f6f9;
}

.card{
    border:none;
    border-radius:18px;
}

.table th{
    white-space:nowrap;
}

.stat-card{
    background:white;
    padding:20px;
    border-radius:15px;
    box-shadow:0 4px 15px rgba(0,0,0,.08);
}

</style>

</head>

<body>

<div class="container-fluid mt-4">

    <div class="d-flex justify-content-between align-items-center mb-4">

        <h3 class="fw-bold">
            🎟️ Billets par traversée
        </h3>

        <a href="comptabilite.php" class="btn btn-secondary">
            ⬅ Retour
        </a>

    </div>

    <div class="card shadow mb-4">

        <div class="card-body">

            <form method="GET">

                <div class="row">

                    <div class="col-md-9">

                        <select
                            name="traversee_id"
                            class="form-select"
                            required
                        >

                            <option value="">
                                -- Sélectionner une traversée --
                            </option>

                            <?php foreach($traversees as $t): ?>

                                <option
                                    value="<?= $t['id'] ?>"
                                    <?= (($_GET['traversee_id'] ?? '') == $t['id']) ? 'selected' : '' ?>
                                >

                                    VOYAGE du
                                    <?= date('d/m/Y H:i', strtotime($t['date_depart'])) ?>

                                </option>

                            <?php endforeach; ?>

                        </select>

                    </div>

                    <div class="col-md-3">

                        <button class="btn btn-primary w-100">
                            Afficher
                        </button>

                    </div>

                </div>

            </form>

        </div>

    </div>

    <?php if(!empty($_GET['traversee_id'])): ?>

    <div class="row mb-4">

        <div class="col-md-4">

            <div class="stat-card">

                <h6>Total passagers</h6>

                <h3 class="text-primary">
                    <?= $total_passagers ?>
                </h3>

            </div>

        </div>

    </div>

    <div class="card shadow">

        <div class="card-body table-responsive">

            <table class="table table-bordered table-hover align-middle">

                <thead class="table-dark">

                    <tr>

                        <th>#</th>
                        <th>Nom</th>
                        <th>Téléphone</th>
                        <th>CNI</th>
                        <th>N° Place</th>
<th>Type place</th>
                        <th>Type passager</th>
                        <th>Nationalité</th>
                        <th>Code billet</th>
                        <th>Date réservation</th>

                    </tr>

                </thead>

                <tbody>

                <?php if(count($billets)>0): ?>

                    <?php $i=1; ?>

                    <?php foreach($billets as $b): ?>

                    <tr>

                        <td><?= $i++ ?></td>

                        <td>
                            <?= htmlspecialchars($b['nom']) ?>
                        </td>

                        <td>
                            <?= htmlspecialchars($b['telephone']) ?>
                        </td>

                        <td>
                            <?= htmlspecialchars($b['cni']) ?>
                        </td>
<td>
    <span class="fw-bold text-primary">
        <?= htmlspecialchars($b['numero_place']) ?>
    </span>
</td>
                        <td>

                            <span class="badge bg-primary">

                                <?= htmlspecialchars($b['type_place']) ?>

                            </span>

                        </td>

                        <td>

                            <?= ucfirst($b['type_passager']) ?>

                        </td>

                        <td>

                            <?php

                            if($b['type_client']=='senegalais'){
                                echo "Sénégalais";
                            }
                            elseif($b['type_client']=='resident'){
                                echo "Étranger résident";
                            }
                            else{
                                echo "Étranger non résident";
                            }

                            ?>

                        </td>

                        <td>

                            <span class="badge bg-secondary">

                                <?= $b['code_qr'] ?>

                            </span>

                        </td>

                        <td>

                            <?= date(
                                'd/m/Y H:i',
                                strtotime($b['date_reservation'])
                            ) ?>

                        </td>

                    </tr>

                    <?php endforeach; ?>

                <?php else: ?>

                    <tr>

                        <td colspan="9" class="text-center py-4">

                            Aucun billet trouvé pour cette traversée.

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
