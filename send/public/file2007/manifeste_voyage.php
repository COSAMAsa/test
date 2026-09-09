<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user'])) {
    header("Location: index.php");
    exit;
}

/* =========================================
   📋 LISTE DES VOYAGES (pour le select)
   -> on affiche TOUS les voyages, même ceux
      sans billet embarqué (nb_embarques = 0)
========================================= */
$sql_voyages = "
SELECT
    t.id,
    t.reference_voyage,
    t.bateau,
    t.depart,
    t.destination,
    t.date_depart,
    COUNT(b.id) AS nb_embarques
FROM traversees t
LEFT JOIN billets b
    ON b.traversee_id = t.id
    AND b.statut = 'embarque'
GROUP BY t.id
ORDER BY t.date_depart DESC
";

$voyages = $pdo->query($sql_voyages)->fetchAll();

/* =========================================
   📋 SI UN VOYAGE EST SELECTIONNE
========================================= */
$billets = [];
$traversee = null;
$traversee_id = 0;

if (!empty($_GET['traversee_id'])) {

    $traversee_id = (int) $_GET['traversee_id'];

    // Infos du voyage
    $stmt = $pdo->prepare("SELECT * FROM traversees WHERE id = ?");
    $stmt->execute([$traversee_id]);
    $traversee = $stmt->fetch();

    if ($traversee) {

        $sql = "
        SELECT
            b.*,
            p.type_place,
            p.numero_place
        FROM billets b
        LEFT JOIN places p
            ON b.id_place = p.id_place
        WHERE b.traversee_id = ?
        AND b.statut = 'embarque'
        ORDER BY p.type_place, p.numero_place
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$traversee_id]);
        $billets = $stmt->fetchAll();

    }
}

/* =========================================
   💰 RECAPITULATIF
========================================= */
$homme = 0;
$femme = 0;
$non_renseigne = 0;
$ch = 0;
$c2 = 0;
$c4 = 0;
$c8 = 0;
$carabane = 0;

foreach ($billets as $b) {

    $sexe = strtoupper(trim($b['sexe'] ?? ''));

    if ($sexe === 'M') {
        $homme++;
    } elseif ($sexe === 'F') {
        $femme++;
    } else {
        // billets créés avant l'ajout du champ sexe
        $non_renseigne++;
    }

    $type = strtolower($b['type_place'] ?? '');

    if (strpos($type, 'chaise') !== false) {
        $ch++;
    } elseif (strpos($type, 'cabine 2') !== false) {
        $c2++;
    } elseif (strpos($type, 'cabine 4') !== false) {
        $c4++;
    } elseif (strpos($type, 'cabine 8') !== false) {
        $c8++;
    } elseif (strpos($type, 'pullman') !== false || strpos($type, 'carabane') !== false) {
        $carabane++;
    }
}

$total_passagers = count($billets);
?>

<!DOCTYPE html>
<html lang="fr">
<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Manifeste par voyage</title>

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
    background:#212529;
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

/* =========================================
   🖨️ STYLE IMPRESSION
========================================= */
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
            📋 Manifeste par voyage
        </h3>

        <div class="d-flex gap-2">

            <a href="billets_embarques.php" class="btn btn-secondary">
                ⬅️ Retour
            </a>

        </div>

    </div>

    <!-- SELECTION DU VOYAGE -->
    <div class="card shadow-sm mb-4 no-print">

        <div class="card-body">

            <form method="GET" class="row g-2 align-items-end">

                <div class="col-md-9">

                    <label class="form-label fw-bold">
                        Sélectionner une référence de voyage
                    </label>

                    <select name="traversee_id" class="form-select" required>

                        <option value="">-- Choisir un voyage --</option>

                        <?php foreach ($voyages as $v): ?>

                        <option
                        value="<?= $v['id'] ?>"
                        <?= (isset($_GET['traversee_id']) && $_GET['traversee_id'] == $v['id']) ? 'selected' : '' ?>
                        >

                            <?= htmlspecialchars($v['reference_voyage']) ?>
                            —
                            <?= htmlspecialchars($v['depart']) ?> → <?= htmlspecialchars($v['destination']) ?>
                            (<?= date('d/m/Y H:i', strtotime($v['date_depart'])) ?>)
                            —
                            <?= (int)$v['nb_embarques'] > 0 ? (int)$v['nb_embarques'] . ' passagers' : 'aucun passager' ?>

                        </option>

                        <?php endforeach; ?>

                    </select>

                </div>

                <div class="col-md-3">

                    <button class="btn btn-dark w-100">
                        🔎 Afficher le manifeste
                    </button>

                </div>

            </form>

        </div>

    </div>

    <?php if ($traversee): ?>

    <!-- BOUTON IMPRIMER -->
    <div class="d-flex justify-content-end gap-2 mb-3 no-print flex-wrap">
 
        <a
        href="manifeste_enfants.php?traversee_id=<?= $traversee_id ?>"
        target="_blank"
        class="btn"
        style="background:#6f42c1; color:#fff;"
        >
            👶 Manifeste enfants
        </a>
 
        <a
        href="manifeste_print.php?traversee_id=<?= $traversee_id ?>"
        target="_blank"
        class="btn btn-success"
        >
            🖨️ Imprimer le manifeste
        </a>
 
    </div>

    <!-- ENTETE MANIFESTE -->
    <div class="card shadow mb-4">

        <div class="card-body">

            <div class="manifeste-header">

                <h4 class="fw-bold mb-1">
                    MANIFESTE PASSAGERS EMBARQUÉS
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
                        <th>Type</th>
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
                                    echo '<span class="badge bg-primary">Homme</span>';
                                } elseif ($sexe_b === 'F') {
                                    echo '<span class="badge bg-danger">Femme</span>';
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
                                <?= ucfirst($b['type_passager']) ?>
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
                            <td colspan="8" class="text-center py-4">
                                ❌ Aucun passager embarqué pour ce voyage
                            </td>
                        </tr>

                    <?php endif; ?>

                    </tbody>

                </table>

            </div>

            <!-- RECAPITULATIF -->
            <div class="recap-box text-center mt-3">

                Homme: <?= $homme ?> &nbsp;|&nbsp;
                Femme: <?= $femme ?> &nbsp;|&nbsp;
                <?php if ($non_renseigne > 0): ?>
                Non renseigné: <?= $non_renseigne ?> &nbsp;|&nbsp;
                <?php endif; ?>
                Chaise: <?= $ch ?> &nbsp;|&nbsp;
                Cabine 2p: <?= $c2 ?> &nbsp;|&nbsp;
                Cabine 4p: <?= $c4 ?> &nbsp;|&nbsp;
                Cabine 8p: <?= $c8 ?> &nbsp;|&nbsp;
                Carabane: <?= $carabane ?> &nbsp;|&nbsp;
                <strong>Total passagers: <?= $total_passagers ?></strong>

            </div>

        </div>

    </div>

    <?php endif; ?>

</div>

</body>
</html>
