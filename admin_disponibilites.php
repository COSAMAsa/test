<?php
require_once '../config/database.php';

/* =========================
   MODE "REPORT" (choix de place depuis report_billet.php)
========================= */

$modeReport = ($_GET['mode'] ?? '') === 'report';

/* =========================
   TRAVERSEE SELECTIONNEE
========================= */

// Liste des voyages avec leurs infos (référence, trajet, date) et le nombre
// de places embarquées (billets valides), sur le même modèle que la page
// de gestion des voyages.
$stmtTraversees = $pdo->query("
    SELECT
        t.id,
        t.reference_voyage,
        t.depart,
        t.destination,
        t.date_depart,
        (
            SELECT COUNT(*)
            FROM billets b
            WHERE b.traversee_id = t.id
            AND b.statut IN ('valide', 'embarque')
        ) AS nb_embarques
    FROM traversees t
    ORDER BY t.date_depart DESC
");
$traversees = $stmtTraversees->fetchAll(PDO::FETCH_ASSOC);

$id = $_GET['id'] ?? ($traversees[0]['id'] ?? 0);

/* =========================
   RECUPERATION DES PLACES
========================= */

$stmt = $pdo->prepare("
    SELECT *
    FROM places
    WHERE traversee_id = ?
    ORDER BY type_place ASC, numero_place ASC
");
$stmt->execute([$id]);
$places = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* =========================
   BILLETS ACTIFS PAR PLACE
   (pour pouvoir "libérer" une place occupée
   si le passager est absent)
========================= */

$stmtBillets = $pdo->prepare("
    SELECT id, id_place, nom, prenom, statut
    FROM billets
    WHERE traversee_id = ?
    AND statut IN ('valide', 'embarque')
");
$stmtBillets->execute([$id]);

$billetsParPlace = [];
foreach ($stmtBillets->fetchAll(PDO::FETCH_ASSOC) as $b) {
    $billetsParPlace[$b['id_place']] = $b;
}

/* =========================
   CALCUL DES STATISTIQUES
========================= */

$totalPlaces = count($places);
$totalOccupees = 0;
$totalDisponibles = 0;

$statsParType = [];

foreach ($places as $p) {

    $type = $p['type_place'];
    $occupe = $p['restant'] <= 0;

    if (!isset($statsParType[$type])) {
        $statsParType[$type] = [
            'total' => 0,
            'occupees' => 0,
            'disponibles' => 0,
        ];
    }

    $statsParType[$type]['total']++;

    if ($occupe) {
        $statsParType[$type]['occupees']++;
        $totalOccupees++;
    } else {
        $statsParType[$type]['disponibles']++;
        $totalDisponibles++;
    }
}

$tauxOccupationGlobal = $totalPlaces > 0
    ? round(($totalOccupees / $totalPlaces) * 100, 1)
    : 0;

/* =========================
   REGROUPEMENT POUR LE PLAN
========================= */

$plans = [];

foreach ($places as $p) {

    $type = $p['type_place'];

    if (!isset($plans[$type])) {
        $plans[$type] = [];
    }

    $plans[$type][] = $p;
}

/* =========================
   ICONES / COULEURS CABINES
========================= */

function iconeCabine($type)
{
    $t = strtolower($type);

    if (strpos($t, 'homme') !== false) return "👨";
    if (strpos($t, 'femme') !== false) return "👩";
    if (strpos($t, 'mixte') !== false) return "👨‍👩";
    if (strpos($t, 'pullman') !== false) return "💺";

    return "🛏️";
}

function couleurCabine($type)
{
    $t = strtolower($type);

    if (strpos($t, 'homme') !== false) return '#289458';
    if (strpos($t, 'femme') !== false) return '#298f75';
    if (strpos($t, 'mixte') !== false) return '#21a181';
    if (strpos($t, 'pullman') !== false) return '#198754';

    return '#6c757d';
}

?>
<!DOCTYPE html>
<html lang="fr">
<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Tableau de bord - Disponibilité des places</title>

<style>

*{
    margin:0;
    padding:0;
    box-sizing:border-box;
}

body{
    font-family:'Segoe UI',sans-serif;
    background:#eef2f7;
    padding:25px;
    color:#333;
}

.header{
    background:linear-gradient(135deg,#0d6efd,#0a58ca);
    color:white;
    padding:25px;
    border-radius:20px;
    margin-bottom:25px;
    box-shadow:0 8px 25px rgba(13,110,253,.25);
    display:flex;
    justify-content:space-between;
    align-items:center;
    flex-wrap:wrap;
    gap:15px;
}

.header h1{
    font-size:28px;
}

.header p{
    margin-top:6px;
    opacity:.9;
    font-size:14px;
}

.selector{
    background:white;
    border-radius:20px;
    padding:20px;
    margin-bottom:25px;
    box-shadow:0 5px 20px rgba(0,0,0,.08);
    display:flex;
    align-items:center;
    gap:15px;
    flex-wrap:wrap;
}

.selector label{
    font-weight:600;
    color:#0a58ca;
}

.selector select{
    padding:10px 15px;
    border-radius:10px;
    border:1px solid #dbe3ee;
    font-size:14px;
    font-family:inherit;
}

.cards{
    display:grid;
    grid-template-columns:repeat(auto-fit,minmax(200px,1fr));
    gap:20px;
    margin-bottom:30px;
}

.card{
    background:white;
    border-radius:18px;
    padding:22px;
    box-shadow:0 5px 20px rgba(0,0,0,.08);
    text-align:center;
}

.card .valeur{
    font-size:34px;
    font-weight:bold;
}

.card .label{
    margin-top:6px;
    color:#6c757d;
    font-size:13px;
    text-transform:uppercase;
    letter-spacing:.5px;
}

.card.total .valeur{ color:#0d6efd; }
.card.occupe .valeur{ color:#dc3545; }
.card.dispo .valeur{ color:#198754; }
.card.taux .valeur{ color:#6610f2; }

.zone{
    background:white;
    border-radius:20px;
    padding:25px;
    margin-bottom:25px;
    box-shadow:0 5px 20px rgba(0,0,0,.08);
}

.zone h2{
    margin-bottom:20px;
    color:#0d6efd;
    font-size:22px;
    display:flex;
    align-items:center;
    gap:10px;
    border-bottom:2px solid #f1f1f1;
    padding-bottom:15px;
}

.type-row{
    margin-bottom:22px;
}

.type-row:last-child{
    margin-bottom:0;
}

.type-row-header{
    display:flex;
    justify-content:space-between;
    align-items:center;
    margin-bottom:8px;
    font-weight:600;
}

.type-row-header .nom{
    display:flex;
    align-items:center;
    gap:8px;
}

.type-row-header .chiffres{
    font-size:13px;
    color:#6c757d;
    font-weight:normal;
}

.barre{
    width:100%;
    height:16px;
    border-radius:10px;
    background:#e9ecef;
    overflow:hidden;
    display:flex;
}

.barre-occupe{
    height:100%;
    background:#dc3545;
}

.barre-dispo{
    height:100%;
    background:#198754;
}

.empty-msg{
    text-align:center;
    color:#6c757d;
    padding:30px;
}

.retour-btn{
    background:rgba(255,255,255,.15);
    color:white;
    text-decoration:none;
    padding:12px 20px;
    border-radius:12px;
    font-weight:600;
    font-size:14px;
    white-space:nowrap;
    transition:.2s;
}

.retour-btn:hover{
    background:rgba(255,255,255,.28);
}

.plan-grille{
    display:grid;
    grid-template-columns:repeat(auto-fill,minmax(90px,1fr));
    gap:12px;
}

.plan-siege{
    min-height:80px;
    border-radius:16px;
    color:white;
    display:flex;
    flex-direction:column;
    align-items:center;
    justify-content:center;
    box-shadow:0 4px 12px rgba(0,0,0,.12);
    position:relative;
}

.plan-siege .icone{
    font-size:20px;
}

.plan-siege .numero{
    font-size:14px;
    font-weight:bold;
    margin-top:5px;
}

.plan-siege.occupe{
    background:#dc3545 !important;
}

.plan-siege.occupe::after{
    content:"✖";
    position:absolute;
    top:6px;
    right:8px;
    font-size:11px;
}

.plan-siege.disponible::after{
    content:"✔";
    position:absolute;
    top:6px;
    right:8px;
    font-size:11px;
}

.plan-siege.selectionnable{
    cursor:pointer;
    transition:.2s;
}

.plan-siege.selectionnable:hover{
    transform:translateY(-4px);
    box-shadow:0 10px 20px rgba(0,0,0,.2);
}

.plan-siege.liberable{
    cursor:pointer;
    transition:.2s;
}

.plan-siege.liberable:hover{
    transform:translateY(-4px);
    box-shadow:0 10px 20px rgba(0,0,0,.2);
    opacity:.85;
}

.plan-siege.liberable:hover::before{
    content:"🔓 Absent ?";
    position:absolute;
    bottom:6px;
    font-size:10px;
    font-weight:700;
    background:rgba(0,0,0,.35);
    padding:2px 6px;
    border-radius:6px;
}

.plan-legend{
    display:flex;
    gap:20px;
    margin-bottom:20px;
    flex-wrap:wrap;
}

.plan-legend .badge-legend{
    display:flex;
    align-items:center;
    gap:8px;
    font-size:13px;
    font-weight:600;
    color:#495057;
}

.plan-legend .couleur{
    width:16px;
    height:16px;
    border-radius:5px;
}

.plan-type-block{
    margin-bottom:28px;
}

.plan-type-block:last-child{
    margin-bottom:0;
}

.plan-type-title{
    font-weight:700;
    color:#0d6efd;
    margin-bottom:12px;
    display:flex;
    align-items:center;
    gap:8px;
}

.bandeau-report{
    background:#fff3cd;
    color:#664d03;
    border:1px solid #ffe69c;
    border-radius:14px;
    padding:14px 18px;
    margin-bottom:25px;
    font-weight:600;
}

@media(max-width:768px){
    body{ padding:15px; }
    .header h1{ font-size:22px; }
    .plan-grille{
        grid-template-columns:repeat(auto-fill,minmax(70px,1fr));
    }
}

</style>

</head>
<body>

<div class="header">
    <div>
        <h1>📊 Tableau de bord — Disponibilité des places</h1>
        <p>Vue d'ensemble de l'occupation par traversée</p>
    </div>
    <?php if(!$modeReport): ?>
    <a href="billets_emis.php" class="retour-btn">⬅️ Retour</a>
    <?php endif; ?>
</div>

<?php if($modeReport): ?>
<div class="bandeau-report">
    🔁 Sélectionnez une place disponible ci-dessous pour valider le report du billet.
</div>
<?php endif; ?>

<div class="selector">
    <label for="traversee">Sélectionner une référence de voyage :</label>
    <select id="traversee" onchange="window.location.href='?id='+this.value+'<?= $modeReport ? '&mode=report' : '' ?>'">

        <?php foreach ($traversees as $t): ?>

        <option
        value="<?= $t['id'] ?>"
        <?= $t['id'] == $id ? 'selected' : '' ?>
        >

            <?= htmlspecialchars($t['reference_voyage']) ?>
            —
            <?= htmlspecialchars($t['depart']) ?> → <?= htmlspecialchars($t['destination']) ?>
            (<?= date('d/m/Y H:i', strtotime($t['date_depart'])) ?>)
            —
            <?= (int)$t['nb_embarques'] > 0 ? (int)$t['nb_embarques'] . ' passagers' : 'aucun passager' ?>

        </option>

        <?php endforeach; ?>

    </select>
</div>

<?php if ($totalPlaces === 0): ?>

    <div class="zone">
        <p class="empty-msg">Aucune place trouvée pour cette traversée.</p>
    </div>

<?php else: ?>

    <div class="cards">

        <div class="card total">
            <div class="valeur"><?= $totalPlaces ?></div>
            <div class="label">Total places</div>
        </div>

        <div class="card dispo">
            <div class="valeur"><?= $totalDisponibles ?></div>
            <div class="label">Disponibles</div>
        </div>

        <div class="card occupe">
            <div class="valeur"><?= $totalOccupees ?></div>
            <div class="label">Occupées</div>
        </div>

        <div class="card taux">
            <div class="valeur"><?= $tauxOccupationGlobal ?>%</div>
            <div class="label">Taux d'occupation</div>
        </div>

    </div>

    <?php if(!$modeReport): ?>
    <div class="zone">

        <h2>📋 Détail par type de place</h2>

        <?php foreach ($statsParType as $type => $s): ?>

            <?php
                $pctOccupe = $s['total'] > 0
                    ? round(($s['occupees'] / $s['total']) * 100, 1)
                    : 0;
                $pctDispo = 100 - $pctOccupe;
            ?>

            <div class="type-row">

                <div class="type-row-header">
                    <div class="nom">
                        <span><?= iconeCabine($type) ?></span>
                        <span><?= htmlspecialchars($type) ?></span>
                    </div>
                    <div class="chiffres">
                        <?= $s['disponibles'] ?> dispo / <?= $s['occupees'] ?> occupées
                        sur <?= $s['total'] ?> (<?= $pctOccupe ?>% occupé)
                    </div>
                </div>

                <div class="barre">
                    <div class="barre-occupe" style="width:<?= $pctOccupe ?>%;"></div>
                    <div class="barre-dispo" style="width:<?= $pctDispo ?>%;"></div>
                </div>

            </div>

        <?php endforeach; ?>

    </div>
    <?php endif; ?>

    <div class="zone">

        <h2>🚢 Plan des places</h2>

        <div class="plan-legend">

            <div class="badge-legend">
                <div class="couleur" style="background:#198754;"></div>
                Disponible
            </div>

            <div class="badge-legend">
                <div class="couleur" style="background:#dc3545;"></div>
                Occupé
            </div>

            <?php if(!$modeReport): ?>
            <div class="badge-legend">
                🔓 Cliquez sur une place occupée pour la libérer (le billet reste inchangé)
            </div>
            <?php endif; ?>

        </div>

        <?php foreach ($plans as $type => $liste): ?>

            <div class="plan-type-block">

                <div class="plan-type-title">
                    <span><?= iconeCabine($type) ?></span>
                    <span><?= htmlspecialchars($type) ?></span>
                </div>

                <div class="plan-grille">

                    <?php foreach ($liste as $s): ?>

                        <?php
                            $dispo = $s['restant'] > 0;
                            $billet = $billetsParPlace[$s['id_place']] ?? null;
                            $nomPassager = $billet
                                ? trim($billet['prenom'] . ' ' . $billet['nom'])
                                : '';
                        ?>

                        <div
                        class="plan-siege
                            <?= $dispo ? 'disponible' : 'occupe' ?>
                            <?= ($modeReport && $dispo) ? 'selectionnable' : '' ?>
                            <?= (!$modeReport && !$dispo) ? 'liberable' : '' ?>
                        "
                        style="background:<?= $dispo ? couleurCabine($type) : '#dc3545' ?>;"

                        <?php if($modeReport && $dispo): ?>
                        onclick="choisirPlace(
                        '<?= $s['id_place'] ?>',
                        '<?= $s['numero_place'] ?>',
                        '<?= $s['type_place'] ?>',
                        '<?= $s['restant'] ?>'
                        )"
                        <?php endif; ?>

                        <?php if(!$modeReport && !$dispo): ?>
                        onclick="libererPlace(
                        '<?= $s['id_place'] ?>',
                        '<?= htmlspecialchars($nomPassager ?: 'ce passager', ENT_QUOTES) ?>',
                        '<?= htmlspecialchars($s['numero_place'], ENT_QUOTES) ?>'
                        )"
                        title="Libérer la place <?= htmlspecialchars($s['numero_place'], ENT_QUOTES) ?>"
                        <?php endif; ?>

                        >

                            <div class="icone"><?= iconeCabine($type) ?></div>
                            <div class="numero"><?= htmlspecialchars($s['numero_place']) ?></div>

                        </div>

                    <?php endforeach; ?>

                </div>

            </div>

        <?php endforeach; ?>

    </div>

<?php endif; ?>

<script>
<?php if($modeReport): ?>
function choisirPlace(id, numero, type, restant){

    if(window.opener){

        window.opener.postMessage({
            id_place: id,
            numero: numero,
            type: type,
            restant: restant
        }, "*");
    }

    window.close();
}
<?php else: ?>
function libererPlace(idPlace, nomPassager, numero){

    const confirmation = confirm(
        'Place ' + numero + ' (' + nomPassager + ')\n\n' +
        'Libérer cette place ? Le billet lié n\'est pas modifié et pourra être reporté séparément.'
    );

    if(!confirmation) return;

    fetch('liberer_place.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'id_place=' + encodeURIComponent(idPlace)
    })
    .then(r => r.json())
    .then(data => {
        if(data.success){
            location.reload();
        } else {
            alert('Erreur : ' + (data.message || 'Impossible de libérer la place.'));
        }
    })
    .catch(() => alert('Erreur réseau, réessayez.'));
}
<?php endif; ?>
</script>

</body>
</html>
