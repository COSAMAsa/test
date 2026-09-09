<?php
session_start();
require_once '../config/database.php';

// 🔐 sécurité
if (!isset($_SESSION['user'])) {
    header("Location: index.php");
    exit;
}

/* ========================
   REQUETE PRINCIPALE
======================== */
$stmt = $pdo->query("
SELECT 
    t.*,
    SUM(p.total) as total_places,
    SUM(p.restant) as places_restantes
FROM traversees t
LEFT JOIN places p ON t.id = p.traversee_id
GROUP BY t.id
ORDER BY t.date_depart DESC
");

$traversees = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Liste des voyages</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
.badge-lg {
    font-size: 14px;
    padding: 6px 10px;
}
</style>

</head>

<body class="bg-light">

<div class="container mt-5">

<div class="d-flex justify-content-between mb-4">
    <h3>📋 Liste des voyages</h3>
    <a href="dashboard.php" class="btn btn-secondary">⬅️ Retour</a>
</div>

<div class="card shadow">
<div class="card-body">

<table class="table table-bordered table-hover text-center align-middle">

<thead class="table-dark">
<tr>
<th>Référence</th>
    <th>Départ</th>
    <th>Destination</th>
    <th>Date</th>
    <th>Bateau</th>
    <th>Disponibilité</th>
    
    <th>Actions</th>
</tr>
</thead>

<tbody>

<?php if(count($traversees) > 0): ?>

<?php foreach ($traversees as $t): ?>

<?php
// 🔥 récupérer détail des places
$stmt2 = $pdo->prepare("
    SELECT
        type_place,
        numero_place,
        restant
    FROM places
    WHERE traversee_id = ?
    ORDER BY type_place, numero_place
");
$stmt2->execute([$t['id']]);

$details = $stmt2->fetchAll();
$plan = [];

foreach($details as $d){

    if($d['restant'] > 0){

        $plan[$d['type_place']][] = $d['numero_place'];

    }

}
?>

<tr>

<td>
    <span class="badge bg-primary">
        <?= htmlspecialchars($t['reference_voyage']) ?>
    </span>
</td>
<td><?= $t['depart'] ?></td>
<td><?= $t['destination'] ?></td>
<td><?= date('d/m/Y H:i', strtotime($t['date_depart'])) ?></td>
<td><?= $t['bateau'] ?></td>

<!-- 🔥 DISPONIBILITÉ GLOBALE -->
<td>
<?php
$restant = $t['places_restantes'] ?? 0;
$total = $t['total_places'] ?? 0;

if ($restant == 0) {
    echo "<span class='badge bg-danger badge-lg'>Complet</span>";
} elseif ($restant < ($total / 2)) {
    echo "<span class='badge bg-warning text-dark badge-lg'>$restant / $total</span>";
} else {
    echo "<span class='badge bg-success badge-lg'>$restant / $total</span>";
}
?>
</td>

<!-- 🔥 DETAIL PAR TYPE -->


<!-- ACTIONS -->
<td>

<a href="edit_traversee.php?id=<?= $t['id'] ?>" 
   class="btn btn-warning btn-sm mb-1">
   ✏️ Modifier
</a>

<a href="delete_traversee.php?id=<?= $t['id'] ?>" 
   class="btn btn-danger btn-sm"
   onclick="return confirm('Supprimer ce voyage ?')">
   🗑️ Supprimer
</a>

</td>

</tr>

<?php endforeach; ?>

<?php else: ?>

<tr>
<td colspan="8">Aucun voyage disponible</td>
</tr>

<?php endif; ?>

</tbody>

</table>

</div>
</div>

</div>

</body>
</html>
