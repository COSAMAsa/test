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

// 🔒 CORRECTIF — TRIM sur type_place pour éviter les doublons invisibles
// dans le menu déroulant (ex: "Cabine 2 places Homme" vs "Cabine 2 places Homme ")
$types_place = $pdo->query("
    SELECT DISTINCT TRIM(type_place) AS type_place
    FROM places
    WHERE type_place IS NOT NULL
    AND TRIM(type_place) != ''
    ORDER BY type_place ASC
")->fetchAll(PDO::FETCH_COLUMN);

$billets = [];
$total_passagers = 0;

if (!empty($_GET['traversee_id'])) {

    $sql = "
        SELECT
            b.*,
            p.numero_place,
            COALESCE(NULLIF(TRIM(p.type_place), ''), 'Non défini') AS type_place,
            t.date_depart

        FROM billets b

        LEFT JOIN places p
            ON b.id_place = p.id_place

        LEFT JOIN traversees t
            ON b.traversee_id = t.id

        WHERE LOWER(TRIM(b.statut)) = 'embarque'
        AND b.traversee_id = ?
    ";

    $params = [$_GET['traversee_id']];

    // 🔒 CORRECTIF — comparaison normalisée (TRIM + LOWER des deux côtés)
    // pour ne plus rater les cabines à cause d'espaces ou de casse différente
    // entre la valeur choisie dans le formulaire et celle stockée en base.
    if (!empty($_GET['type_place']) && in_array($_GET['type_place'], $types_place, true)) {
        $sql .= " AND LOWER(TRIM(p.type_place)) = LOWER(TRIM(?))";
        $params[] = $_GET['type_place'];
    }

    $sql .= " ORDER BY p.numero_place ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    $billets = $stmt->fetchAll();

    $total_passagers = count($billets);
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Passagers embarqués</title>

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
            🚢 Passagers embarqués
        </h3>

    </div>

    <div class="card shadow mb-4">

        <div class="card-body">

            <form method="GET">

                <div class="row g-2">

                    <div class="col-md-6">

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

                        <select
                            name="type_place"
                            class="form-select"
                        >

                            <option value="">
                                🛏️ Tous les types de place
                            </option>

                            <?php foreach($types_place as $tp): ?>

                                <option
                                    value="<?= htmlspecialchars($tp) ?>"
                                    <?= (($_GET['type_place'] ?? '') === $tp) ? 'selected' : '' ?>
                                >

                                    <?= htmlspecialchars($tp) ?>

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

                <h6>🚢 Total embarqués</h6>

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
                            <?= htmlspecialchars(trim(($b['prenom'] ?? '') . ' ' . ($b['nom'] ?? ''))) ?>
                        </td>

                        <td>
                            <?= htmlspecialchars($b['telephone']) ?>
                        </td>

                        <td>
                            <?= htmlspecialchars($b['cni']) ?>
                        </td>

                        <td>
                            <span class="fw-bold text-primary">
                                <?= htmlspecialchars($b['numero_place'] ?? '—') ?>
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

                            Aucun passager embarqué pour cette traversée.

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
