<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/../config/database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user'])) {
    header("Location: index.php");
    exit;
}

$user = $_SESSION['user'];

if($user['role'] != 'admin' && $user['role'] != 'finance'){
    die("⛔ Accès refusé");
}

$stmt = $pdo->prepare("
    SELECT *
    FROM reservations_attente
    WHERE mode_paiement = 'om'
      AND statut = 'traite'
    ORDER BY id DESC
");
$stmt->execute();
$transactions = $stmt->fetchAll();

$total_general = 0;
foreach ($transactions as $t) {
    $total_general += $t['prix'] + $t['frais_service'];
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Encaissements Orange Money</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body{ background:#f4f6f9; }
.card-total{
    background:linear-gradient(135deg,#ff6600,#ff8c00);
    color:#fff;
    border-radius:16px;
    padding:25px;
}
</style>
</head>
<body>

<div class="container py-5">

<a href="comptabilite.php" class="btn btn-secondary mb-4">⬅️ Retour</a>

<h2 class="mb-4">📱 Encaissements Orange Money</h2>

<div class="card-total mb-4">
    <h5>Total encaissé</h5>
    <h2><?= number_format($total_general, 0, ',', ' ') ?> FCFA</h2>
    <small><?= count($transactions) ?> transaction(s)</small>
</div>

<div class="table-responsive">
<table class="table table-bordered bg-white">
<thead class="table-light">
<tr>
    <th>Référence</th>
    <th>Passager</th>
    <th>Téléphone</th>
    <th>Type client</th>
    <th>Prix</th>
    <th>Frais</th>
    <th>Total</th>
</tr>
</thead>
<tbody>
<?php if(empty($transactions)): ?>
<tr><td colspan="7" class="text-center">Aucune transaction trouvée</td></tr>
<?php else: ?>
<?php foreach($transactions as $t): ?>
<tr>
    <td><?= htmlspecialchars($t['reference']) ?></td>
    <td><?= htmlspecialchars($t['prenom'] . ' ' . $t['nom']) ?></td>
    <td><?= htmlspecialchars($t['telephone']) ?></td>
    <td><?= htmlspecialchars($t['type_client']) ?></td>
    <td><?= number_format($t['prix'], 0, ',', ' ') ?></td>
    <td><?= number_format($t['frais_service'], 0, ',', ' ') ?></td>
    <td><strong><?= number_format($t['prix'] + $t['frais_service'], 0, ',', ' ') ?></strong></td>
</tr>
<?php endforeach; ?>
<?php endif; ?>
</tbody>
</table>
</div>

</div>
</body>
</html>
