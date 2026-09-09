<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user'])) {
    header("Location: index.php");
    exit;
}

if (
    $_SESSION['user']['role'] != 'admin' &&
    $_SESSION['user']['role'] != 'finance'
) {
    die("⛔ Accès refusé");
}

$stmt = $pdo->query("
    SELECT r.*, b.nom, b.telephone, b.code_qr
    FROM remboursements r
    LEFT JOIN billets b ON b.id = r.billet_id
    ORDER BY r.date_remboursement DESC
");

$rows = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Historique remboursements</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">

<div class="container mt-4">

    <h3 class="mb-4">💸 Historique des remboursements</h3>

    <a href="dashboard.php" class="btn btn-secondary mb-3">⬅️ Retour</a>

    <div class="card">
        <div class="card-body table-responsive">

            <table class="table table-bordered text-center">

                <thead class="table-dark">
                    <tr>
                        <th>Date</th>
                        <th>Nom</th>
                        <th>Téléphone</th>
                        <th>Code billet</th>
                        <th>Montant</th>
                        <th>Frais</th>
                        <th>Total</th>
                    </tr>
                </thead>

                <tbody>

                <?php foreach($rows as $r): ?>

                    <tr>
                        <td><?= date('d/m/Y H:i', strtotime($r['date_remboursement'])) ?></td>
                        <td><?= $r['nom'] ?></td>
                        <td><?= $r['telephone'] ?></td>
                        <td><?= $r['code_qr'] ?></td>
                        <td><?= number_format($r['montant'],0,',',' ') ?> FCFA</td>
                        <td><?= number_format($r['frais'],0,',',' ') ?> FCFA</td>
                        <td class="fw-bold text-danger">
                            <?= number_format($r['total'],0,',',' ') ?> FCFA
                        </td>
                    </tr>

                <?php endforeach; ?>

                </tbody>

            </table>

        </div>
    </div>

</div>

</body>
</html>
