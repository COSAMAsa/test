<?php

session_start();

require_once '../config/database.php';

if (!isset($_SESSION['user'])) {

    header("Location: index.php");

    exit;

}

// Sécurité rôle (aligné sur le bloc du dashboard)

$role = $_SESSION['user']['role'] ?? '';

if (!in_array($role, ['admin', 'exploitation', 'finance'])) {

    header("Location: dashboard.php");

    exit;

}

date_default_timezone_set('Africa/Dakar');

$tab = $_GET['tab'] ?? 'embarquements';

$search      = $_GET['search'] ?? '';

$date_debut  = $_GET['date_debut'] ?? '';

$date_fin    = $_GET['date_fin'] ?? '';

/* =========================================

   🧩 FONCTION UTILITAIRE : FILTRE COMMUN

========================================= */

function buildSearchFilter($search, $date_debut, $date_fin, $dateColumn) {

    $where = [];

    $params = [];

    if (!empty($search)) {

        $where[] = "(b.nom LIKE ? OR b.prenom LIKE ? OR b.code_qr LIKE ? OR b.telephone LIKE ?)";

        $s = "%" . $search . "%";

        $params[] = $s;

        $params[] = $s;

        $params[] = $s;

        $params[] = $s;

    }

    if (!empty($date_debut)) {

        $where[] = "DATE($dateColumn) >= ?";

        $params[] = $date_debut;

    }

    if (!empty($date_fin)) {

        $where[] = "DATE($dateColumn) <= ?";

        $params[] = $date_fin;

    }

    return [$where, $params];

}

/* =========================================

   🚢 ONGLET 1 : HISTORIQUE DES EMBARQUEMENTS

========================================= */

$billets_embarques = [];

if ($tab === 'embarquements') {

    [$extraWhere, $params] = buildSearchFilter($search, $date_debut, $date_fin, 't.date_depart');

    $where = "WHERE b.statut = 'embarque'";

    if ($extraWhere) {

        $where .= " AND " . implode(" AND ", $extraWhere);

    }

    $sql = "

        SELECT

            b.*,

            p.type_place,

            t.date_depart,

            t.reference_voyage,

            t.depart,

            t.destination,

            (SELECT e.agent FROM embarquements e WHERE e.billet_id = b.id ORDER BY e.created_at DESC LIMIT 1) AS agent_embarquement,

            (SELECT e.created_at FROM embarquements e WHERE e.billet_id = b.id ORDER BY e.created_at DESC LIMIT 1) AS date_embarquement

        FROM billets b

        LEFT JOIN places p ON b.id_place = p.id_place

        LEFT JOIN traversees t ON b.traversee_id = t.id

        $where

        ORDER BY t.date_depart DESC

    ";

    $stmt = $pdo->prepare($sql);

    $stmt->execute($params);

    $billets_embarques = $stmt->fetchAll();

}

/* =========================================

   ⚓ ONGLET 2 : HISTORIQUE DES DÉBARQUEMENTS

   (billets dont le statut est passé à 'debarque'

   via une action manuelle d'agent, cf debarquement.php)

========================================= */

$billets_debarques = [];

if ($tab === 'debarquements') {

    [$extraWhere, $params] = buildSearchFilter($search, $date_debut, $date_fin, 't.date_depart');

    $where = "WHERE b.statut = 'debarque'";

    if ($extraWhere) {

        $where .= " AND " . implode(" AND ", $extraWhere);

    }

    $sql = "

        SELECT

            b.*,

            p.type_place,

            t.date_depart,

            t.reference_voyage,

            t.depart,

            t.destination,

            (SELECT d.agent FROM debarquements d WHERE d.billet_id = b.id ORDER BY d.created_at DESC LIMIT 1) AS agent_debarquement,

            (SELECT d.motif FROM debarquements d WHERE d.billet_id = b.id ORDER BY d.created_at DESC LIMIT 1) AS motif_debarquement,

            (SELECT d.created_at FROM debarquements d WHERE d.billet_id = b.id ORDER BY d.created_at DESC LIMIT 1) AS date_debarquement

        FROM billets b

        LEFT JOIN places p ON b.id_place = p.id_place

        LEFT JOIN traversees t ON b.traversee_id = t.id

        $where

        ORDER BY t.date_depart DESC

    ";

    $stmt = $pdo->prepare($sql);

    $stmt->execute($params);

    $billets_debarques = $stmt->fetchAll();

}

/* =========================================

   🔁 ONGLET 3 : HISTORIQUE DES BILLETS REPORTÉS

========================================= */

$billets_reportes = [];

if ($tab === 'reportes') {

    [$extraWhere, $params] = buildSearchFilter($search, $date_debut, $date_fin, 'b.date_reservation');

    $where = "WHERE b.report_effectue = 1";

    if ($extraWhere) {

        $where .= " AND " . implode(" AND ", $extraWhere);

    }



    $sql = "

        SELECT

            b.*,

            p.type_place,

            p.numero_place,

            t.date_depart,

            t.reference_voyage,

            t.depart AS depart_traversee,

            t.destination AS destination_traversee,

            ta.date_depart AS ancienne_date_depart,

            ta.reference_voyage AS ancienne_reference_voyage,

            ta.depart AS ancien_depart_traversee,

            ta.destination AS ancienne_destination_traversee,

            pa.numero_place AS ancien_numero_place,

            pa.type_place AS ancien_type_place,

            (SELECT r.agent FROM reports r WHERE r.billet_id = b.id ORDER BY r.created_at DESC LIMIT 1) AS agent_report,

            (SELECT r.created_at FROM reports r WHERE r.billet_id = b.id ORDER BY r.created_at DESC LIMIT 1) AS date_report

        FROM billets b

        LEFT JOIN places p ON b.id_place = p.id_place

        LEFT JOIN traversees t ON b.traversee_id = t.id

        LEFT JOIN traversees ta ON b.ancienne_traversee_id = ta.id

        LEFT JOIN places pa ON b.ancienne_place_id = pa.id_place

        $where

        ORDER BY b.date_reservation DESC

    ";

    $stmt = $pdo->prepare($sql);

    $stmt->execute($params);

    $billets_reportes = $stmt->fetchAll();

}

/* =========================================

   🧩 FONCTION UTILITAIRE : NOM COMPLET

========================================= */

function nom_complet($data) {

    $prenom = trim($data['prenom'] ?? '');

    $nom    = trim($data['nom'] ?? '');

    return htmlspecialchars(trim($prenom . ' ' . $nom));

}

/* =========================================

   📊 TOTAL (selon onglet actif)

========================================= */

$currentList = [];

if ($tab === 'embarquements') $currentList = $billets_embarques;

if ($tab === 'debarquements') $currentList = $billets_debarques;

if ($tab === 'reportes')      $currentList = $billets_reportes;

// URL de base pour conserver les filtres en changeant d'onglet

$queryWithoutTab = $_GET;

unset($queryWithoutTab['tab']);

$baseQuery = http_build_query($queryWithoutTab);

?>

<!DOCTYPE html>

<html lang="fr">

<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Historique</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

<style>

body {

    background: #f4f6f9;

}

.card {

    border: none;

    border-radius: 18px;

    overflow: hidden;

}

.table th {

    vertical-align: middle;

    white-space: nowrap;

}

.table td {

    vertical-align: middle;

}

.badge {

    font-size: 14px;

}

.total-box {

    border-radius: 15px;

    padding: 15px;

    color: white;

}

.bg-blue { background: #0d6efd; }

.bg-orange { background: #fd7e14; }

.bg-dark2 { background: #212529; }

.bg-green { background: #198754; }

.bg-purple { background: #6f42c1; }

.nav-tabs .nav-link {

    font-weight: 600;

    border-radius: 12px 12px 0 0;

}

.nav-tabs .nav-link.active {

    background: #212529;

    color: white;

}

@media (max-width: 768px) {

    h3 { font-size: 22px; }

    .btn { margin-bottom: 5px; }

    .table { font-size: 14px; }

}

</style>

</head>

<body>

<div class="container-fluid mt-4 px-2 px-md-4">

    <!-- HEADER -->

    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap">

        <h3 class="fw-bold">

            🕘 Historique

        </h3>

        <div class="d-flex gap-2 flex-wrap">

            <a href="dashboard.php" class="btn btn-secondary">

                ⬅️ Retour

            </a>

        </div>

    </div>

    <!-- ONGLETS -->

    <ul class="nav nav-tabs mb-4">

        <li class="nav-item">

            <a class="nav-link <?= $tab === 'embarquements' ? 'active' : '' ?>"

               href="?tab=embarquements<?= $baseQuery ? '&' . $baseQuery : '' ?>">

                🎫 Embarquements

            </a>

        </li>

        <li class="nav-item">

            <a class="nav-link <?= $tab === 'debarquements' ? 'active' : '' ?>"

               href="?tab=debarquements<?= $baseQuery ? '&' . $baseQuery : '' ?>">

                ⚓ Débarquements

            </a>

        </li>

        <li class="nav-item">

            <a class="nav-link <?= $tab === 'reportes' ? 'active' : '' ?>"

               href="?tab=reportes<?= $baseQuery ? '&' . $baseQuery : '' ?>">

                🔁 Billets reportés

            </a>

        </li>

    </ul>

    <!-- FILTRE -->

    <div class="card shadow-sm mb-4">

        <div class="card-body">

            <form method="GET">

                <input type="hidden" name="tab" value="<?= htmlspecialchars($tab) ?>">

                <div class="row g-2">

                    <div class="col-md-5">

                        <input

                        type="text"

                        name="search"

                        class="form-control"

                        placeholder="🔍 Nom, téléphone ou code billet"

                        value="<?= htmlspecialchars($search) ?>"

                        >

                    </div>

                    <div class="col-md-3">

                        <input

                        type="date"

                        name="date_debut"

                        class="form-control"

                        value="<?= htmlspecialchars($date_debut) ?>"

                        >

                    </div>

                    <div class="col-md-3">

                        <input

                        type="date"

                        name="date_fin"

                        class="form-control"

                        value="<?= htmlspecialchars($date_fin) ?>"

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

    <!-- TOTAL -->

    <div class="row mb-4">

        <div class="col-md-3 mb-2">

            <div class="total-box bg-purple shadow">

                <h6>📊 Nb billets</h6>

                <h4><?= count($currentList) ?></h4>

            </div>

        </div>

    </div>

    <!-- ================= ONGLET EMBARQUEMENTS ================= -->

    <?php if ($tab === 'embarquements'): ?>

        <div class="card shadow">

            <div class="card-body table-responsive">

                <table class="table table-bordered table-hover text-center align-middle">

                    <thead class="table-dark">

                        <tr>

                            <th>Nom</th>

                            <th>Téléphone</th>

                            <th>CNI</th>

                            <th>Place</th>

                            <th>Voyage</th>

                            <th>Date départ</th>

                            <th>Code billet</th>

                            <th>Agent (embarquement)</th>

                            <th>Statut</th>

                        </tr>

                    </thead>

                    <tbody>

                        <?php if (count($billets_embarques) > 0): ?>

                            <?php foreach ($billets_embarques as $b): ?>

                                <tr>

                                    <td><?= nom_complet($b) ?></td>

                                    <td><?= htmlspecialchars($b['telephone']) ?></td>

                                    <td><?= htmlspecialchars($b['cni'] ?? '') ?></td>

                                    <td><?= htmlspecialchars($b['type_place'] ?? '') ?></td>

                                    <td>

                                        <?= htmlspecialchars($b['reference_voyage'] ?? '') ?>

                                        <br>

                                        <small class="text-muted">

                                            <?= htmlspecialchars($b['depart'] ?? '') ?> → <?= htmlspecialchars($b['destination'] ?? '') ?>

                                        </small>

                                    </td>

                                    <td><?= !empty($b['date_depart']) ? date('d/m/Y H:i', strtotime($b['date_depart'])) : '' ?></td>

                                    <td><span class="badge bg-secondary"><?= htmlspecialchars($b['code_qr']) ?></span></td>

                                    <td>

                                        <?php if (!empty($b['agent_embarquement'])): ?>

                                            <span class="fw-bold"><?= htmlspecialchars($b['agent_embarquement']) ?></span>

                                            <br>

                                            <small class="text-muted">

                                                <?= !empty($b['date_embarquement']) ? date('d/m/Y H:i', strtotime($b['date_embarquement'])) : '' ?>

                                            </small>

                                        <?php else: ?>

                                            <span class="text-muted">—</span>

                                        <?php endif; ?>

                                    </td>

                                    <td><span class="badge bg-success">🚢 Embarqué</span></td>

                                </tr>

                            <?php endforeach; ?>

                        <?php else: ?>

                            <tr><td colspan="9" class="text-center py-4">❌ Aucun embarquement trouvé</td></tr>

                        <?php endif; ?>

                    </tbody>

                </table>

                <p class="text-muted small mt-2 mb-0">

                    i️ L'agent affiché est celui qui a scanné/validé le billet à l'embarquement. Les embarquements effectués avant la mise en place de ce suivi n'ont pas d'agent associé.

                </p>

            </div>

        </div>

    <?php endif; ?>

    <!-- ================= ONGLET DÉBARQUEMENTS ================= -->

    <?php if ($tab === 'debarquements'): ?>

        <div class="card shadow">

            <div class="card-body table-responsive">

                <table class="table table-bordered table-hover text-center align-middle">

                    <thead class="table-dark">

                        <tr>

                            <th>Nom</th>

                            <th>Téléphone</th>

                            <th>CNI</th>

                            <th>Place</th>

                            <th>Voyage</th>

                            <th>Date départ (voyage)</th>

                            <th>Code billet</th>

                            <th>Agent (débarquement)</th>

                            <th>Statut</th>

                        </tr>

                    </thead>

                    <tbody>

                        <?php if (count($billets_debarques) > 0): ?>

                            <?php foreach ($billets_debarques as $b): ?>

                                <tr>

                                    <td><?= nom_complet($b) ?></td>

                                    <td><?= htmlspecialchars($b['telephone']) ?></td>

                                    <td><?= htmlspecialchars($b['cni'] ?? '') ?></td>

                                    <td><?= htmlspecialchars($b['type_place'] ?? '') ?></td>

                                    <td>

                                        <?= htmlspecialchars($b['reference_voyage'] ?? '') ?>

                                        <br>

                                        <small class="text-muted">

                                            <?= htmlspecialchars($b['depart'] ?? '') ?> → <?= htmlspecialchars($b['destination'] ?? '') ?>

                                        </small>

                                    </td>

                                    <td><?= !empty($b['date_depart']) ? date('d/m/Y H:i', strtotime($b['date_depart'])) : '' ?></td>

                                    <td><span class="badge bg-secondary"><?= htmlspecialchars($b['code_qr']) ?></span></td>

                                    <td>

                                        <?php if (!empty($b['agent_debarquement'])): ?>

                                            <span class="fw-bold"><?= htmlspecialchars($b['agent_debarquement']) ?></span>

                                            <br>

                                            <small class="text-muted">

                                                <?= htmlspecialchars($b['motif_debarquement'] ?? '') ?>

                                            </small>

                                            <br>

                                            <small class="text-muted">

                                                <?= !empty($b['date_debarquement']) ? date('d/m/Y H:i', strtotime($b['date_debarquement'])) : '' ?>

                                            </small>

                                        <?php else: ?>

                                            <span class="text-muted">—</span>

                                        <?php endif; ?>

                                    </td>

                                    <td><span class="badge bg-primary">⚓ Débarqué</span></td>

                                </tr>

                            <?php endforeach; ?>

                        <?php else: ?>

                            <tr><td colspan="9" class="text-center py-4">❌ Aucun débarquement trouvé</td></tr>

                        <?php endif; ?>

                    </tbody>

                </table>

            </div>

        </div>

    <?php endif; ?>

    <?php if ($tab === 'reportes'): ?>

        <div class="card shadow">

            <div class="card-body table-responsive">

                <table class="table table-bordered table-hover text-center align-middle">

                    <thead class="table-dark">

                        <tr>

                            <th>Nom</th>

                            <th>Téléphone</th>

                            <th>Place actuelle</th>

                            <th>Traversée ancienne</th>

                            <th>Traversée actuelle</th>

                            <th>Date réservation</th>

                            <th>Code billet</th>

                            <th>Agent (report)</th>

                            <th>Statut</th>

                        </tr>

                    </thead>

                    <tbody>

                        <?php if (count($billets_reportes) > 0): ?>

                            <?php foreach ($billets_reportes as $b): ?>

                                <tr>

                                    <td><?= nom_complet($b) ?></td>

                                    <td><?= htmlspecialchars($b['telephone']) ?></td>

                                    <td>

                                        <?= htmlspecialchars($b['type_place'] ?? '') ?>

                                        <?php if (!empty($b['numero_place'])): ?>

                                            <br><span class="badge bg-info text-dark">N° <?= htmlspecialchars($b['numero_place']) ?></span>

                                        <?php endif; ?>

                                    </td>

                                    <td>

                                        <?php if (!empty($b['ancienne_reference_voyage']) || !empty($b['ancien_depart_traversee'])): ?>

                                            <span class="text-muted"><?= htmlspecialchars($b['ancienne_reference_voyage'] ?? '') ?></span>

                                            <br>

                                            <small class="text-muted">

                                                <?= htmlspecialchars($b['ancien_depart_traversee'] ?? '') ?> → <?= htmlspecialchars($b['ancienne_destination_traversee'] ?? '') ?>

                                            </small>

                                            <br>

                                            <small class="text-muted">

                                                <?= !empty($b['ancienne_date_depart']) ? date('d/m/Y H:i', strtotime($b['ancienne_date_depart'])) : '' ?>

                                                <?php if (!empty($b['ancien_numero_place'])): ?> — N° <?= htmlspecialchars($b['ancien_numero_place']) ?><?php endif; ?>

                                            </small>

                                        <?php else: ?>

                                            <span class="text-muted">— inconnue</span>

                                        <?php endif; ?>

                                    </td>

            <td>

                                        <?php if (!empty($b['reference_voyage'])): ?>

                                            <?= htmlspecialchars($b['reference_voyage']) ?>

                                            <br>

                                            <small class="text-muted">

                                                <?= htmlspecialchars($b['depart_traversee'] ?? '') ?> → <?= htmlspecialchars($b['destination_traversee'] ?? '') ?>

                                            </small>

                                            <br>

                                            <small class="text-muted">

                                                <?= !empty($b['date_depart']) ? date('d/m/Y H:i', strtotime($b['date_depart'])) : '' ?>

                                            </small>

                                        <?php else: ?>

                                            <span class="text-muted">—</span>

                                        <?php endif; ?>

                                    </td>

                                    <td><?= date('d/m/Y H:i', strtotime($b['date_reservation'])) ?></td>

                                    <td><span class="badge bg-secondary"><?= htmlspecialchars($b['code_qr']) ?></span></td>

                                    <td>

                                        <?php if (!empty($b['agent_report'])): ?>

                                            <span class="fw-bold"><?= htmlspecialchars($b['agent_report']) ?></span>

                                            <br>

                                            <small class="text-muted">

                                                <?= !empty($b['date_report']) ? date('d/m/Y H:i', strtotime($b['date_report'])) : '' ?>

                                            </small>

                                        <?php else: ?>

                                            <span class="text-muted">—</span>

                                        <?php endif; ?>

                                    </td>

                                    <td>

                                        <?php if ($b['statut'] == 'embarque'): ?>

                                            <span class="badge bg-primary">🚢 Embarqué</span>

                                        <?php elseif ($b['statut'] == 'debarque'): ?>

                                            <span class="badge bg-info text-dark">⚓ Débarqué</span>

                                        <?php elseif ($b['statut'] == 'valide'): ?>

                                            <span class="badge bg-success">✅ Valide</span>

                                        <?php else: ?>

                                            <span class="badge bg-secondary"><?= htmlspecialchars(ucfirst($b['statut'])) ?></span>

                                        <?php endif; ?>

                                        <br>

                                        <span class="badge bg-orange mt-1">🔁 Reporté</span>

                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        <?php else: ?>

                            <tr><td colspan="9" class="text-center py-4">❌ Aucun billet reporté trouvé</td></tr>

                        <?php endif; ?>

                    </tbody>

                </table>

            </div>

        </div>

    <?php endif; ?>

</div>

</body>

</html>
