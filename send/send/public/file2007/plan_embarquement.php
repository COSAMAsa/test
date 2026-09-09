<?php
require_once '../config/database.php';

$id = $_GET['id'] ?? 0;

/* =========================
   RECUPERATION DES PLACES
========================= */

$stmt = $pdo->prepare("
    SELECT *
    FROM places
    WHERE traversee_id=?
    ORDER BY type_place ASC, numero_place ASC
");

$stmt->execute([$id]);

$places = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* =========================
   ORGANISATION DES SIEGES
========================= */

$plans = [];

foreach($places as $p){

    $type = $p['type_place'];

    if(!isset($plans[$type])){
        $plans[$type] = [];
    }

    $plans[$type][] = $p;
}

/* =========================
   VERIFICATION DISPONIBILITE
========================= */

// On vérifie s'il existe au moins UNE place avec un "restant" > 0
$auMoinsUnePlaceDispo = false;

foreach($places as $p){
    if($p['restant'] > 0){
        $auMoinsUnePlaceDispo = true;
        break;
    }
}

/* =========================
   ICONES CABINES
========================= */

function iconeCabine($type){

    $t = strtolower($type);

    // 👨 Homme
    if(strpos($t,'homme') !== false){
        return "👨";
    }

    // 👩 Femme
    if(strpos($t,'femme') !== false){
        return "👩";
    }

    // 👨‍👩 Mixte
    if(strpos($t,'mixte') !== false){
        return "👨‍👩";
    }

    // 🚢 Pullman
    if(strpos($t,'pullman') !== false){
        return "💺";
    }

    return "🛏️";
}

/* =========================
   COULEURS CABINES
========================= */

function couleurCabine($type){

    $t = strtolower($type);

    // 🔵 Homme
    if(strpos($t,'homme') !== false){
        return '#289458';
    }

    // 🩷 Femme
    if(strpos($t,'femme') !== false){
        return '#298f75';
    }

    // 🟣 Mixte
    if(strpos($t,'mixte') !== false){
        return '#21a181';
    }

    // 🟢 Pullman
    if(strpos($t,'pullman') !== false){
        return '#198754';
    }

    return '#6c757d';
}

?>

<!DOCTYPE html>
<html lang="fr">

<head>

<meta charset="UTF-8">

<meta
name="viewport"
content="width=device-width, initial-scale=1.0"
>

<title>Plan embarquement</title>

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

.retour-flottant{
    display:inline-flex;
    align-items:center;
    gap:6px;
    background:white;
    color:#0d6efd;
    text-decoration:none;
    font-weight:600;
    padding:10px 16px;
    border-radius:12px;
    box-shadow:0 3px 10px rgba(0,0,0,.08);
    margin-bottom:20px;
    transition:.2s;
}

.retour-flottant:hover{
    background:#0d6efd;
    color:white;
    transform:translateX(-3px);
}

.header{
    background:linear-gradient(135deg,#0d6efd,#0a58ca);
    color:white;
    padding:25px;
    border-radius:20px;
    text-align:center;
    margin-bottom:30px;
    box-shadow:0 8px 25px rgba(13,110,253,.25);
}

.header h1{
    margin:0;
    font-size:32px;
}

.header p{
    margin-top:8px;
    opacity:.9;
}

.legend{
    display:flex;
    justify-content:center;
    gap:20px;
    flex-wrap:wrap;
    margin-bottom:30px;
}

.badge{
    background:white;
    padding:12px 18px;
    border-radius:12px;
    display:flex;
    align-items:center;
    gap:10px;
    box-shadow:0 3px 10px rgba(0,0,0,.08);
    font-weight:600;
}

.couleur{
    width:22px;
    height:22px;
    border-radius:6px;
}

.zone{
    background:white;
    border-radius:20px;
    padding:25px;
    margin-bottom:30px;
    box-shadow:0 5px 20px rgba(0,0,0,.08);
}

.zone h2{
    margin-bottom:20px;
    color:#0d6efd;
    font-size:24px;
    display:flex;
    align-items:center;
    gap:10px;
    border-bottom:2px solid #f1f1f1;
    padding-bottom:15px;
}

.grille{
    display:grid;
    grid-template-columns:
    repeat(auto-fill,minmax(110px,1fr));
    gap:15px;
}

.siege{
    min-height:100px;
    border-radius:18px;
    color:white;
    cursor:pointer;
    display:flex;
    flex-direction:column;
    align-items:center;
    justify-content:center;
    transition:.25s;
    box-shadow:0 5px 15px rgba(0,0,0,.15);
    position:relative;
}

.siege:hover{
    transform:translateY(-5px);
    box-shadow:0 12px 25px rgba(0,0,0,.20);
}

.siege .icone{
    font-size:28px;
}

.siege .place{
    font-size:18px;
    font-weight:bold;
    margin-top:8px;
}

.siege .restant{
    font-size:11px;
    margin-top:5px;
    opacity:.9;
}

.occupe{
    background:#dc3545 !important;
    cursor:not-allowed;
}

.occupe::after{
    content:"✖";
    position:absolute;
    top:8px;
    right:10px;
    font-size:14px;
}

.disponible::after{
    content:"✔";
    position:absolute;
    top:8px;
    right:10px;
    font-size:14px;
}

.footer-info{
    text-align:center;
    color:#6c757d;
    margin-top:20px;
    font-size:14px;
}

/* =========================
   BLOC "AUCUNE DISPONIBILITE"
========================= */

.no-dispo{
    background:white;
    border-radius:20px;
    padding:40px 25px;
    text-align:center;
    box-shadow:0 5px 20px rgba(0,0,0,.08);
}

.no-dispo .emoji{
    font-size:50px;
    margin-bottom:15px;
}

.no-dispo h2{
    color:#dc3545;
    margin-bottom:10px;
}

.no-dispo p{
    color:#6c757d;
    margin-bottom:25px;
}

.btn-fermer{
    background:#0d6efd;
    color:white;
    border:none;
    padding:14px 28px;
    border-radius:12px;
    font-size:16px;
    font-weight:600;
    cursor:pointer;
    transition:.2s;
    display:inline-block;
    text-decoration:none;
}

.btn-fermer:hover{
    background:#0a58ca;
    transform:translateY(-2px);
}

@media(max-width:768px){

    body{
        padding:15px;
    }

    .header h1{
        font-size:24px;
    }

    .grille{
        grid-template-columns:
        repeat(auto-fill,minmax(90px,1fr));
    }

    .siege{
        min-height:90px;
    }
}

</style>

</head>

<body>

<a href="index.php" class="retour-flottant" onclick="return retourPlan(event)">← Retour</a>

<div class="header">
    <h1>🚢 Plan d'Embarquement</h1>
    <p>Sélectionnez votre cabine ou votre siège disponible</p>
</div>

<?php if(!$auMoinsUnePlaceDispo): ?>

<!-- =========================
     AUCUNE PLACE DISPONIBLE
========================= -->

<div class="no-dispo">

    <div class="emoji">🚫</div>

    <h2>Aucune place disponible</h2>

    <p>
        Toutes les places de cette traversée sont
        actuellement occupées. Veuillez réessayer
        plus tard ou choisir une autre traversée.
    </p>

</div>

<?php else: ?>

<div class="legend">

    <div class="badge">

        <div
        class="couleur"
        style="background:#198754"
        ></div>

        Disponible

    </div>

    <div class="badge">

        <div
        class="couleur"
        style="background:#dc3545"
        ></div>

        Occupé

    </div>

</div>

<?php foreach($plans as $type => $liste): ?>

<div class="zone">

<h2>

<?= iconeCabine($type) ?>

<?= $type ?>

</h2>

<div class="grille">

<?php foreach($liste as $s): ?>

<div

class="siege <?= $s['restant'] > 0 ? '' : 'occupe' ?>"

style="
background:
<?= $s['restant'] > 0
    ? couleurCabine($type)
    : '#dc3545'
?>;
"

<?php if($s['restant'] > 0): ?>

onclick="choisirPlace(
'<?= $s['id_place'] ?>',
'<?= $s['numero_place'] ?>',
'<?= $s['type_place'] ?>',
'<?= $s['restant'] ?>'
)"

<?php endif; ?>

>

<div style="font-size:18px;">

<?= iconeCabine($type) ?>

</div>

<div class="numero">

<?= $s['numero_place'] ?>

</div>

</div>

<?php endforeach; ?>

</div>

</div>

<?php endforeach; ?>

<?php endif; ?>

<script>

function choisirPlace(
    id,
    numero,
    type,
    restant
){

    if(window.opener){

        window.opener.postMessage({

            id_place:id,
            numero:numero,
            type:type,
            restant:restant

        }, "*");
    }

    window.close();
}

/* =========================
   BOUTON RETOUR
========================= */

function retourPlan(e){

    // Si cette fenêtre a été ouverte en popup depuis acheter.php,
    // on la ferme simplement pour retrouver acheter.php tel quel
    // (formulaire déjà rempli, modifiable), sans écraser index.php.
    if(window.opener){

        e.preventDefault();

        window.close();

        return false;
    }

    // Sinon (page ouverte directement), on laisse le lien
    // normal vers index.php faire son travail.
    return true;
}

</script>

</body>
</html>
