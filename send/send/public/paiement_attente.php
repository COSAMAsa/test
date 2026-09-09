<?php
/**
 * paiement_attente.php
 *
 * C'est ici que Wave redirige le client après un paiement réussi
 * (success_url). A ce stade, le paiement a probablement réussi côté
 * Wave, MAIS le webhook (seule source fiable de confirmation) peut
 * ne pas encore être arrivé sur webhook_wave.php.
 *
 * Cette page attend donc, via un polling AJAX, que la réservation
 * passe au statut "payee" en base avant d'afficher le billet.
 */

session_start();
require_once '../config/database.php';
include '../includes/header.php';

$reference = $_GET['ref'] ?? ($_SESSION['paiement']['code'] ?? null);

if (!$reference) {
    die("❌ Référence de paiement introuvable.");
}
?>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@600;700&family=DM+Sans:wght@400;500;700&display=swap" rel="stylesheet">

<style>
:root{
    --navy:#081827;
    --royal:#0d5b8c;
    --ocean:#1282c4;
    --azure:#25a8ff;
    --gold:#d8b75f;
}
*{ margin:0; padding:0; box-sizing:border-box; }
body{
    background: linear-gradient(rgba(5,15,30,.85), rgba(5,15,30,.9)),
        url('../assets/ASD1.jfif') no-repeat center center fixed;
    background-size: cover;
    font-family:'DM Sans', sans-serif;
    min-height:100vh;
}
.main-app{
    width:100%;
    max-width:430px;
    margin:auto;
    min-height:100vh;
    display:flex;
    align-items:center;
    justify-content:center;
    padding:20px;
}
.wait-card{
    background:#fff;
    border-radius:28px;
    padding:36px 28px;
    text-align:center;
    box-shadow:0 15px 40px rgba(0,0,0,.25);
    width:100%;
}
.spinner{
    width:64px;
    height:64px;
    margin:0 auto 24px;
    border-radius:50%;
    border:6px solid #eef7fd;
    border-top-color: var(--ocean);
    animation: tourner 1s linear infinite;
}
@keyframes tourner{ to{ transform:rotate(360deg); } }
.wait-card h2{
    font-family:'Cormorant Garamond', serif;
    font-size:26px;
    color: var(--navy);
    margin-bottom:10px;
}
.wait-card p{
    font-size:14px;
    color:#5b6b7a;
    line-height:1.5;
}
.ref{
    margin-top:18px;
    background:#eef7fd;
    border-radius:12px;
    padding:10px 14px;
    font-size:13px;
    color: var(--royal);
    font-weight:600;
}
.icon-ok{
    width:64px; height:64px;
    margin:0 auto 20px;
    border-radius:50%;
    background:#22c55e;
    color:#fff;
    display:flex;
    align-items:center;
    justify-content:center;
    font-size:32px;
}
.icon-fail{
    width:64px; height:64px;
    margin:0 auto 20px;
    border-radius:50%;
    background:#ef4444;
    color:#fff;
    display:flex;
    align-items:center;
    justify-content:center;
    font-size:32px;
}
.btn-continue{
    display:inline-block;
    margin-top:22px;
    width:100%;
    height:54px;
    line-height:54px;
    border-radius:16px;
    background:linear-gradient(135deg,var(--ocean),var(--azure));
    color:#fff;
    font-weight:700;
    text-decoration:none;
}
.btn-retry{
    display:inline-block;
    margin-top:22px;
    width:100%;
    height:54px;
    line-height:54px;
    border-radius:16px;
    background:#edf2f7;
    color:var(--navy);
    font-weight:700;
    text-decoration:none;
}
</style>

<div class="main-app">
    <div class="wait-card" id="carteAttente">
        <div class="spinner"></div>
        <h2>Confirmation en cours...</h2>
        <p>Nous vérifions votre paiement Wave auprès de notre système.<br>
        Cela prend généralement quelques secondes, merci de patienter.</p>
        <div class="ref">Réf. réservation : <?= htmlspecialchars($reference) ?></div>
    </div>
</div>

<script>
const reference = <?= json_encode($reference) ?>;
let tentatives = 0;
const maxTentatives = 40; // 40 x 3s = 2 minutes max d'attente

function verifierStatut() {
    tentatives++;

    fetch('verifier_statut_wave.php?ref=' + encodeURIComponent(reference))
        .then(r => r.json())
        .then(data => {
            if (data.statut === 'payee') {
                afficherSucces();
            } else if (data.statut === 'echec') {
                afficherEchec();
            } else if (tentatives >= maxTentatives) {
                afficherDelaiDepasse();
            } else {
                setTimeout(verifierStatut, 3000);
            }
        })
        .catch(() => {
            if (tentatives >= maxTentatives) {
                afficherDelaiDepasse();
            } else {
                setTimeout(verifierStatut, 3000);
            }
        });
}

function afficherSucces() {
    document.getElementById('carteAttente').innerHTML = `
        <div class="icon-ok">✓</div>
        <h2>Paiement confirmé !</h2>
        <p>Votre billet a été généré avec succès.</p>
        <div class="ref">Réf. réservation : ${reference}</div>
        <a class="btn-continue" href="valider_paiement.php?ref=${encodeURIComponent(reference)}">Voir mon billet</a>
    `;
}

function afficherEchec() {
    document.getElementById('carteAttente').innerHTML = `
        <div class="icon-fail">✕</div>
        <h2>Paiement non abouti</h2>
        <p>Le paiement Wave n'a pas pu être confirmé. Aucun montant n'a été débité durablement si la transaction a échoué côté Wave.</p>
        <div class="ref">Réf. réservation : ${reference}</div>
        <a class="btn-retry" href="index.php">Retour à l'accueil</a>
    `;
}

function afficherDelaiDepasse() {
    document.getElementById('carteAttente').innerHTML = `
        <div class="icon-fail" style="background:#f59e0b;">…</div>
        <h2>Confirmation plus longue que prévue</h2>
        <p>Votre paiement est peut-être passé mais la confirmation tarde à arriver. Ne réessayez pas de payer une seconde fois : contactez le support avec votre référence si le billet n'apparaît pas d'ici quelques minutes.</p>
        <div class="ref">Réf. réservation : ${reference}</div>
        <a class="btn-retry" href="index.php">Retour à l'accueil</a>
    `;
}

verifierStatut();
</script>

<?php include '../includes/footer.php'; ?>
