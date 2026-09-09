<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user'])) {
    header("Location: index.php");
    exit;
}

/* =========================================
   📋 VOYAGE
========================================= */
$traversee = null;
$billets = [];
$traversee_id = (int) ($_GET['traversee_id'] ?? 0);

if ($traversee_id > 0) {

    $stmt = $pdo->prepare("SELECT * FROM traversees WHERE id = ?");
    $stmt->execute([$traversee_id]);
    $traversee = $stmt->fetch();

    if ($traversee) {

        // On détecte si le voyage est ZIGUINCHOR -> DAKAR
        // (même règle que pour le manifeste général)
        $depart_norm      = strtolower(trim($traversee['depart']));
        $destination_norm = strtolower(trim($traversee['destination']));

        $exclure_carabane = ($depart_norm === 'ziguinchor' && $destination_norm === 'dakar');

        $sql_base = "
        SELECT
            b.*,
            p.type_place,
            p.numero_place
        FROM billets b
        LEFT JOIN places p
            ON b.id_place = p.id_place
        WHERE b.traversee_id = ?
        AND b.statut = 'embarque'
        AND b.type_passager LIKE '%enfant%'
        ";

        $sql_avec_filtre = $sql_base . "
            AND (b.depart_client IS NULL OR TRIM(b.depart_client) != 'Carabane')
            ORDER BY p.type_place, p.numero_place
        ";

        $sql_sans_filtre = $sql_base . "
            ORDER BY p.type_place, p.numero_place
        ";

        try {

            $sql = $exclure_carabane ? $sql_avec_filtre : $sql_sans_filtre;

            $stmt = $pdo->prepare($sql);
            $stmt->execute([$traversee_id]);
            $billets = $stmt->fetchAll();

        } catch (PDOException $e) {

            error_log("manifeste_enfants.php - erreur SQL avec filtre carabane: " . $e->getMessage());

            $stmt = $pdo->prepare($sql_sans_filtre);
            $stmt->execute([$traversee_id]);
            $billets = $stmt->fetchAll();
        }
    }
}

/* =========================================
   💰 RECAPITULATIF
========================================= */
$garcon = 0;
$fille = 0;
$non_renseigne = 0;

foreach ($billets as $b) {

    $sexe = strtoupper(trim($b['sexe'] ?? ''));

    if ($sexe === 'M') {
        $garcon++;
    } elseif ($sexe === 'F') {
        $fille++;
    } else {
        $non_renseigne++;
    }
}

$total_enfants = count($billets);
?>

<!DOCTYPE html>
<html lang="fr">
<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Manifeste enfants</title>

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

.recap-box{
    border-radius:15px;
    padding:15px;
    background:#6f42c1;
    color:white;
    font-weight:bold;
}

.manifeste-header{
    text-align:center;
    margin-bottom:20px;
}

@media(max-width:768px){
    h3{
        font-size:22px;
    }
    .btn{
        margin-bottom:5px;
    }
    .table{
        font-size:13px;
    }
}

@media print{

    .no-print{
        display:none !important;
    }

    body{
        background:white;
    }

    .card{
        box-shadow:none !important;
        border-radius:0;
    }

    .table{
        font-size:11px;
    }

}

</style>

</head>

<body>

<div class="container-fluid mt-4 px-2 px-md-4">

    <!-- HEADER -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap no-print">

        <h3 class="fw-bold">
            👶 Manifeste enfants
        </h3>

        <div class="d-flex gap-2">

            <a href="manifeste_voyage.php?traversee_id=<?= $traversee_id ?>" class="btn btn-secondary">
                ⬅️ Retour
            </a>

            <?php if ($traversee): ?>
            <button onclick="window.print()" class="btn btn-success">
                🖨️ Imprimer
            </button>
            <?php endif; ?>

        </div>

    </div>

    <?php if (!$traversee): ?>

        <div class="alert alert-danger">
            ❌ Voyage introuvable.
        </div>

    <?php else: ?>

    <!-- ENTETE MANIFESTE -->
    <div class="card shadow mb-4">

        <div class="card-body">

            <div class="manifeste-header">

                <h4 class="fw-bold mb-1">
                    MANIFESTE ENFANTS EMBARQUÉS
                </h4>

                <p class="mb-1">
                    Ligne: <?= htmlspecialchars($traversee['depart']) ?> - <?= htmlspecialchars($traversee['destination']) ?>
                </p>

                <p class="mb-1">
                    Voyage du <?= date('d/m/Y', strtotime($traversee['date_depart'])) ?>
                    du navire <strong>&laquo; <?= htmlspecialchars($traversee['bateau']) ?> &raquo;</strong>
                </p>

                <p class="mb-0">
                    Numéro de voyage: <strong><?= htmlspecialchars($traversee['reference_voyage']) ?></strong>
                </p>

            </div>

            <!-- TABLEAU -->
            <div class="table-responsive">

                <table class="table table-bordered table-hover text-center align-middle">

                    <thead class="table-dark">

                    <tr>

                        <th>Billet</th>
                        <th>Nom</th>
                        <th>Téléphone</th>
                        <th>Sexe</th>
                        <th>CNI</th>
                        <th>Place</th>
                        <th>Client</th>

                    </tr>

                    </thead>

                    <tbody>

                    <?php if(count($billets) > 0): ?>

                        <?php foreach ($billets as $b): ?>

                        <tr>

                            <td>
                                <span class="badge bg-secondary">
                                    <?= htmlspecialchars($b['code_qr']) ?>
                                </span>
                            </td>

                            <td>
                                <?= trim(($b['prenom'] ?? '') . ' ' . ($b['nom'] ?? '')) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($b['telephone']) ?>
                            </td>

                            <td>

                                <?php
                                $sexe_b = strtoupper(trim($b['sexe'] ?? ''));

                                if ($sexe_b === 'M') {
                                    echo '<span class="badge bg-primary">Garçon</span>';
                                } elseif ($sexe_b === 'F') {
                                    echo '<span class="badge bg-danger">Fille</span>';
                                } else {
                                    echo '<span class="badge bg-secondary">—</span>';
                                }
                                ?>

                            </td>

                            <td>
                                <?= htmlspecialchars($b['cni']) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($b['type_place']) ?>
                                <?= !empty($b['numero_place']) ? '('.htmlspecialchars($b['numero_place']).')' : '' ?>
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

                        </tr>

                        <?php endforeach; ?>

                    <?php else: ?>

                        <tr>
                            <td colspan="7" class="text-center py-4">
                                ❌ Aucun enfant embarqué pour ce voyage
                            </td>
                        </tr>

                    <?php endif; ?>

                    </tbody>

                </table>

            </div>

            <!-- RECAPITULATIF -->
            <div class="recap-box text-center mt-3">

                Garçons: <?= $garcon ?> &nbsp;|&nbsp;
                Filles: <?= $fille ?> &nbsp;|&nbsp;
                <?php if ($non_renseigne > 0): ?>
                Non renseigné: <?= $non_renseigne ?> &nbsp;|&nbsp;
                <?php endif; ?>
                <strong>Total enfants: <?= $total_enfants ?></strong>

            </div>

        </div>

    </div>

    <?php endif; ?>

</div>

</body>
</html>
