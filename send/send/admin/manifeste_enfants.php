<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user'])) {
    header("Location: index.php");
    exit;
}

if (empty($_GET['traversee_id'])) {
    die("❌ Voyage non spécifié.");
}

$traversee_id = (int) $_GET['traversee_id'];

/* =========================================
   📋 INFOS VOYAGE
========================================= */
$stmt = $pdo->prepare("SELECT * FROM traversees WHERE id = ?");
$stmt->execute([$traversee_id]);
$traversee = $stmt->fetch();

if (!$traversee) {
    die("❌ Voyage introuvable.");
}

/* =========================================
   👶 ENFANTS ACCOMPAGNANTS (billets embarqués)
========================================= */
$sql = "
SELECT
    e.id            AS enfant_id,
    e.nom_enfant,
    e.age_valeur,
    e.age_unite,
    e.created_at,
    b.id            AS billet_id,
    b.code_qr,
    b.prenom        AS prenom_accompagnateur,
    b.nom           AS nom_accompagnateur,
    b.telephone,
    b.sexe,
    p.type_place,
    p.numero_place
FROM enfants_accompagnants e
JOIN billets b
    ON e.billet_id = b.id
LEFT JOIN places p
    ON b.id_place = p.id_place
WHERE b.traversee_id = ?
AND b.statut = 'embarque'
ORDER BY b.nom ASC, e.created_at ASC
";

$stmt = $pdo->prepare($sql);
$stmt->execute([$traversee_id]);
$enfants = $stmt->fetchAll();

/* =========================================
   📊 RECAPITULATIF
========================================= */
$total_enfants   = count($enfants);
$total_moins_1an = 0; // 0 à 12 mois
$total_1_2ans    = 0; // 13 à 24 mois (soit >12 et <=24 mois, ou 1-2 ans)
$total_2_3ans    = 0; // >24 mois et <=36 mois, ou 3 ans

foreach ($enfants as $e) {

    // Conversion en mois pour classer facilement
    $mois = ($e['age_unite'] === 'ans')
        ? ((int) $e['age_valeur']) * 12
        : (int) $e['age_valeur'];

    if ($mois <= 12) {
        $total_moins_1an++;
    } elseif ($mois <= 24) {
        $total_1_2ans++;
    } else {
        $total_2_3ans++;
    }
}

// Date/heure d'édition
$edite_le = date('d-m-Y H:i:s');

// Logo COSAMA : on le charge depuis un fichier séparé pour ne pas alourdir ce script
// -> voir instructions ci-dessous pour le mettre en place
$logo_path = __DIR__ . '/assets/logo_cosama_base64.txt';
$logo_cosama_base64 = file_exists($logo_path) ? trim(file_get_contents($logo_path)) : '';
?>

<!DOCTYPE html>
<html lang="fr">
<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Manifeste Enfants - <?= htmlspecialchars($traversee['reference_voyage']) ?></title>

<style>

@page {
    size: A4 landscape;
    margin: 10mm 12mm;
}

*{
    box-sizing:border-box;
}

body{
    font-family: Arial, Helvetica, sans-serif;
    color:#000;
    margin:0;
    padding:0;
    font-size:12px;
}

.page-wrap{
    max-width: 1200px;
    margin: 0 auto;
}

.header{
    display:flex;
    align-items:center;
    justify-content:space-between;
    border-bottom:2px solid #212529;
    padding-bottom:8px;
    margin-bottom:10px;
}

.header-left{
    display:flex;
    align-items:center;
    gap:10px;
}

.logo-badge{
    height:44px;
    width:auto;
    flex-shrink:0;
    display:block;
}

.header-left h1{
    font-size:16px;
    margin:0;
    letter-spacing:.5px;
}

.header-right{
    text-align:right;
    font-size:12px;
}

.header-right .ligne-label{
    color:#555;
}

.header-right .ligne-value{
    font-weight:bold;
}

.subheader{
    text-align:center;
    margin-bottom:10px;
    font-size:12.5px;
}

.subheader .voyage-line{
    margin:2px 0;
}

.subheader .ref-voyage{
    margin-top:2px;
    font-size:12.5px;
}

table{
    width:100%;
    border-collapse:collapse;
    font-size:10.5px;
    margin-bottom:14px;
}

th, td{
    border:1px solid #333;
    padding:4px 6px;
    text-align:left;
}

td.center, th.center{
    text-align:center;
}

th{
    background:#6f42c1;
    color:#fff;
    text-align:left;
    font-size:10.5px;
}

tbody tr:nth-child(even){
    background:#f7f8fa;
}

.badge{
    padding:2px 8px;
    border-radius:10px;
    font-size:9.5px;
    color:#fff;
    display:inline-block;
    background:#6f42c1;
}

.recap-box{
    border-top:2px solid #212529;
    padding-top:8px;
    display:flex;
    flex-wrap:wrap;
    justify-content:center;
    gap:0;
    font-weight:bold;
    font-size:11.5px;
    background:#f4f6f9;
}

.recap-box span{
    padding:4px 12px;
    border-right:1px solid #cfd4da;
}

.recap-box span:last-child{
    border-right:none;
}

.footer{
    margin-top:14px;
    text-align:center;
    font-size:9.5px;
    color:#444;
    border-top:1px solid #ccc;
    padding-top:6px;
}

.footer .footer-line{
    margin:1px 0;
}

.toolbar{
    text-align:center;
    margin-bottom:15px;
}

.toolbar button{
    padding:10px 22px;
    font-size:14px;
    border:none;
    border-radius:8px;
    background:#6f42c1;
    color:#fff;
    cursor:pointer;
}

@media print{
    .toolbar{
        display:none;
    }
    body{
        margin:0;
    }
}

</style>

</head>

<body>

<div class="toolbar">
    <button onclick="window.print()">🖨️ Lancer l'impression</button>
</div>

<div class="page-wrap">

    <div class="header">

        <div class="header-left">
            <?php if ($logo_cosama_base64): ?>
            <img class="logo-badge" src="data:image/png;base64,<?= $logo_cosama_base64 ?>" alt="COSAMA">
            <?php endif; ?>
            <h1>👶 MANIFESTE ENFANTS ACCOMPAGNANTS (0-3 ANS)</h1>
        </div>

        <div class="header-right">
            <div class="ligne-label">Ligne</div>
            <div class="ligne-value">
                <?= htmlspecialchars($traversee['depart']) ?> - <?= htmlspecialchars($traversee['destination']) ?>
            </div>
        </div>

    </div>

    <div class="subheader">

        <p class="voyage-line">
            Manifeste des enfants accompagnants embarqués le
            <strong><?= date('d/m/Y', strtotime($traversee['date_depart'])) ?></strong>
            du navire &laquo; <?= htmlspecialchars($traversee['bateau']) ?> &raquo;
        </p>

        <p class="ref-voyage">
            Numéro de voyage: <strong><?= htmlspecialchars($traversee['reference_voyage']) ?></strong>
        </p>

    </div>

    <table>

        <thead>

        <tr>
            <th>Billet</th>
            <th>Accompagnateur</th>
            <th>Téléphone</th>
            <th>Nom de l'enfant</th>
            <th class="center">Âge</th>
            <th>Place accompagnateur</th>
        </tr>

        </thead>

        <tbody>

        <?php if (count($enfants) > 0): ?>

            <?php foreach ($enfants as $e): ?>

            <?php
                $nom_complet_accompagnateur = trim(
                    ($e['prenom_accompagnateur'] ?? '') . ' ' . ($e['nom_accompagnateur'] ?? '')
                );
            ?>

            <tr>

                <td><?= htmlspecialchars($e['code_qr']) ?></td>

                <td><?= htmlspecialchars($nom_complet_accompagnateur ?: '—') ?></td>

                <td><?= htmlspecialchars($e['telephone'] ?: '—') ?></td>

                <td><?= htmlspecialchars($e['nom_enfant']) ?></td>

                <td class="center">
                    <span class="badge">
                        <?= (int) $e['age_valeur'] ?>
                        <?= $e['age_unite'] == 'mois' ? 'mois' : 'an(s)' ?>
                    </span>
                </td>

                <td>
                    <?= htmlspecialchars($e['type_place'] ?? '') ?>
                    <?= !empty($e['numero_place']) ? '('.htmlspecialchars($e['numero_place']).')' : '' ?>
                </td>

            </tr>

            <?php endforeach; ?>

        <?php else: ?>

            <tr>
                <td colspan="6">❌ Aucun enfant accompagnant déclaré pour ce voyage</td>
            </tr>

        <?php endif; ?>

        </tbody>

    </table>

    <div class="recap-box">
        <span>0 à 12 mois: <?= $total_moins_1an ?></span>
        <span>13 à 24 mois: <?= $total_1_2ans ?></span>
        <span>25 à 36 mois: <?= $total_2_3ans ?></span>
        <span>Total enfants: <?= $total_enfants ?></span>
    </div>

    <div class="footer">
        <p class="footer-line">COSAMA - Consortium Sénégalais d'Activités MAritimes</p>
        <p class="footer-line">2, rue Joris - BP.:4136 Dakar</p>
        <p class="footer-line">Tel: 33 821 29 00 ou 33 991 72 00 / Fax: 33 821 29 01 ou 33 991 72 01 - E-mail: cosama@cosamasn.com</p>
        <p class="footer-line">Edité le <?= $edite_le ?></p>
    </div>

</div>

<script>
    window.onload = function() {
        setTimeout(function() {
            window.print();
        }, 400);
    };
</script>

</body>
</html>
