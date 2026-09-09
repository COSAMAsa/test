<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user'])) {
    header("Location: index.php");
    exit;
}

$where = "WHERE r.statut IN ('attente','traite','payee')";
$params = [];

/* =========================================
   🔎 RECHERCHE
========================================= */
if(!empty($_GET['search'])){

    $where .= " AND (
        r.nom LIKE ?
        OR r.reference LIKE ?
        OR r.telephone LIKE ?
    )";

    $search = "%".$_GET['search']."%";

    $params[] = $search;
    $params[] = $search;
    $params[] = $search;
}

/* =========================================
   📌 FILTRE STATUT
========================================= */
if(!empty($_GET['statut']) && in_array($_GET['statut'], ['attente','traite','payee'])){

    $where = str_replace(
        "r.statut IN ('attente','traite','payee')",
        "r.statut = ?",
        $where
    );

    array_unshift($params, $_GET['statut']);
}

/* =========================================
   📋 RECUPERATION RESERVATIONS
========================================= */
$sql = "

SELECT
    r.*,
    t.depart,
    t.destination,
    t.date_depart,

    (
        SELECT COUNT(*)
        FROM billets b
        WHERE b.code_qr = r.code_qr
    ) AS billet_existe

FROM reservations_attente r

LEFT JOIN traversees t
ON r.traversee_id = t.id

$where

ORDER BY r.id DESC

";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);

$reservations = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="fr">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Réservations en attente / traitées</title>

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
    font-size:13px;
}

@media(max-width:768px){

    h3{
        font-size:22px;
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
            ⏳ Réservations en attente / traitées
        </h3>

        <div class="d-flex gap-2">

            <a href="billets_emis.php" class="btn btn-primary">
                🎟️ Billets émis
            </a>

            <a href="billets_emis.php" class="btn btn-secondary">
                ⬅️ Retour
            </a>

        </div>

    </div>

    <!-- MESSAGES -->
    <?php if(!empty($_GET['error'])): ?>

        <div class="alert alert-danger">
            ⛔ <?= htmlspecialchars($_GET['error']) ?>
        </div>

    <?php endif; ?>

    <?php if(!empty($_GET['success'])): ?>

        <div class="alert alert-success">
            ✅ <?= htmlspecialchars($_GET['success']) ?>
        </div>

    <?php endif; ?>

    <!-- FILTRE -->
    <div class="card shadow-sm mb-4">

        <div class="card-body">

            <form method="GET">

                <div class="row g-2">

                    <div class="col-md-5">

                        <input
                        type="text"
                        name="search"
                        class="form-control"
                        placeholder="🔍 Nom, référence ou téléphone"
                        value="<?= htmlspecialchars($_GET['search'] ?? '') ?>"
                        >

                    </div>

                    <div class="col-md-4">

                        <select name="statut" class="form-select">

                            <option value="">
                                Tous les statuts
                            </option>

                            <option value="attente" <?= (($_GET['statut'] ?? '') === 'attente') ? 'selected' : '' ?>>
                                En attente
                            </option>

                            <option value="traite" <?= (($_GET['statut'] ?? '') === 'traite') ? 'selected' : '' ?>>
                                Traitée
                            </option>

                            <option value="payee" <?= (($_GET['statut'] ?? '') === 'payee') ? 'selected' : '' ?>>
                                Payée
                            </option>

                        </select>

                    </div>

                    <div class="col-md-3">

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
                    <th>id</th>
                    <th>Référence</th>
                    <th>Nom</th>
                    <th>Téléphone</th>
                    <th>Sexe</th>
                    <th>CNI</th>
                    <th>Client</th>
                    <th>Mode paiement</th>
                    <th>Passager</th>
                    <th>Voyage</th>
                    <th>Date départ</th>
                    <th>Prix</th>
                    <th>Frais</th>
                    <th>Statut</th>
                    <th>Actions</th>

                </tr>

                </thead>

                <tbody>

                <?php if(count($reservations) > 0): ?>

                    <?php foreach ($reservations as $r): ?>

                    <tr>
                        <td>
                            <?= htmlspecialchars($r['id']) ?>
                        </td>
                        <td>
                            <span class="badge bg-secondary">
                                <?= htmlspecialchars($r['reference']) ?>
                            </span>
                        </td>

                       <td>
    <?= htmlspecialchars(($r['prenom'] . ' ' . $r['nom'])) ?>
</td>

                        <td>
                            <?= htmlspecialchars($r['telephone']) ?>
                        </td>

                        <td>

                            <?php
                            $sexe_r = strtoupper(trim($r['sexe'] ?? ''));

                            if ($sexe_r === 'M') {
                                echo '<span class="badge bg-primary">Homme</span>';
                            } elseif ($sexe_r === 'F') {
                                echo '<span class="badge bg-danger">Femme</span>';
                            } else {
                                echo '<span class="badge bg-secondary">—</span>';
                            }
                            ?>

                        </td>

                        <td>
                            <?= htmlspecialchars($r['cni']) ?>
                        </td>

                        <td>

                            <?php
                            if($r['type_client'] == 'senegalais'){
                                echo "Sénégalais";
                            }
                            elseif($r['type_client'] == 'resident'){
                                echo "Étranger Résident";
                            }
                            else{
                                echo "Étranger Non Résident";
                            }
                            ?>

                        </td>

                        <td>

                            <?php if(($r['mode_paiement'] ?? 'om') === 'wave'): ?>

                                <span class="badge" style="background:#1BA0E2;">
                                    🌊 Wave
                                </span>

                            <?php else: ?>

                                <span class="badge" style="background:#ff6600;">
                                    📱 OM
                                </span>

                            <?php endif; ?>

                        </td>

                        <td>
                            <?= ucfirst($r['type_passager']) ?>
                        </td>

                        <td>
                            <?= htmlspecialchars($r['depart'] ?? '-') ?>
                            →
                            <?= htmlspecialchars($r['destination'] ?? '-') ?>
                        </td>

                        <td>

                            <?= !empty($r['date_depart'])
                                ? date('d/m/Y H:i', strtotime($r['date_depart']))
                                : '-' ?>

                        </td>

                        <td>

                            <span class="fw-bold text-primary">
                                <?= number_format($r['prix'],0,',',' ') ?> FCFA
                            </span>

                        </td>

                        <td>

                            <span class="text-dark">
                                <?= number_format($r['frais_service'],0,',',' ') ?> FCFA
                            </span>

                        </td>

                        <td>

                            <?php if($r['statut'] === 'attente'): ?>

                                <span class="badge bg-warning text-dark">
                                    ⏳ En attente
                                </span>

                            <?php elseif($r['statut'] === 'traite'): ?>

                                <span class="badge bg-success">
                                    ✅ Traitée
                                </span>

                            <?php else: ?>

                                <span class="badge bg-primary">
                                    💰 Payée
                                </span>

                                <?php if((int)$r['billet_existe'] === 0): ?>

                                    <br>
                                    <span class="badge bg-danger mt-1">
                                        ⚠️ Billet non émis
                                    </span>

                                <?php endif; ?>

                            <?php endif; ?>

                        </td>

                        <td>

                            <?php if($r['statut'] === 'attente' || ($r['statut'] === 'payee' && (int)$r['billet_existe'] === 0)): ?>

                                <form
                                method="POST"
                                action="valider_reservation.php"
                                onsubmit="return confirm('Confirmer la validation de cette réservation en billet ?');"
                                style="display:inline;"
                                >

                                    <input type="hidden" name="reference" value="<?= htmlspecialchars($r['reference']) ?>">

                                    <button type="submit" class="btn btn-sm btn-success">
                                        ✅ Valider en billet
                                    </button>

                                </form>

                            <?php else: ?>

                                <span class="text-muted">—</span>

                            <?php endif; ?>

                        </td>

                    </tr>

                    <?php endforeach; ?>

                <?php else: ?>

                    <tr>

                        <td colspan="15" class="text-center py-4">

                            ❌ Aucune réservation trouvée

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
