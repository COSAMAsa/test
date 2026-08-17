<?php
ob_start(); // filet de sécurité : empêche toute sortie accidentelle de bloquer les futurs header()

session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => '',
    'secure' => true,
    'httponly' => true,
    'samesite' => 'None'
]);
session_start();

// debug sans casser les headers
error_log("SESSION ID: " . session_id() . " | PAIEMENT EXISTE: " . (isset($_SESSION['paiement']) ? 'OUI' : 'NON'));

require_once '../config/database.php';
require_once '../lib/phpqrcode/qrlib.php';

// 🆕 Liste des pays actifs pour le champ "Pays de nationalité"
$stmt = $pdo->query("SELECT nom FROM pays WHERE actif = 1 ORDER BY nom ASC");
$liste_pays = $stmt->fetchAll(PDO::FETCH_COLUMN);

include '../includes/header.php';
include '../includes/messages.php';

$id = $_GET['id'];

// 🔒 Voyage
$stmt = $pdo->prepare("
    SELECT date_depart, depart, destination
    FROM traversees
    WHERE id=?
");

$stmt->execute([$id]);

$traversee = $stmt->fetch();

if(!$traversee){
    die("❌ Voyage introuvable");
}

// Sénégal
date_default_timezone_set('Africa/Dakar');

$date_actuelle = date('Y-m-d H:i:s');
$date_depart = $traversee['date_depart'];

// 💳 frais
$frais_service = 500;

/* =========================
   TARIFS
========================= */

$tarifs = [

"senegalais" => [

"Cabine 2 places Homme" => [
"adulte" => 26500,
"enfant" => 13500
],

"Cabine 2 places Femme" => [
"adulte" => 26500,
"enfant" => 13500
],

"Cabine 4 places Homme" => [
"adulte" => 24500,
"enfant" => 12500
],

"Cabine 4 places Femme" => [
"adulte" => 24500,
"enfant" => 12500
],

"Cabine 4 places Mixte" => [
"adulte" => 24500,
"enfant" => 12500
],

"Cabine 8 places Homme" => [
"adulte" => 12500,
"enfant" => 6500
],

"Cabine 8 places Femme" => [
"adulte" => 12500,
"enfant" => 6500
],

"Cabine 8 places Mixte" => [
"adulte" => 12500,
"enfant" => 6500
],

"Pullman" => [
"adulte" => 5000,
"enfant" => 2500
]

],

"resident" => [

"Cabine 2 places Homme" => [
"adulte" => 26900,
"enfant" => 13900
],

"Cabine 2 places Femme" => [
"adulte" => 26900,
"enfant" => 13900
],

"Cabine 4 places Homme" => [
"adulte" => 24900,
"enfant" => 12900
],

"Cabine 4 places Femme" => [
"adulte" => 24900,
"enfant" => 12900
],

"Cabine 4 places Mixte" => [
"adulte" => 24900,
"enfant" => 12900
],

"Cabine 8 places Homme" => [
"adulte" => 12900,
"enfant" => 6900
],

"Cabine 8 places Femme" => [
"adulte" => 12900,
"enfant" => 6900
],

"Cabine 8 places Mixte" => [
"adulte" => 12900,
"enfant" => 6900
],

"Pullman" => [
"adulte" => 10900,
"enfant" => 5900
]

],

"non_resident" => [

"Cabine 2 places Homme" => [
"adulte" => 30900,
"enfant" => 15900
],

"Cabine 2 places Femme" => [
"adulte" => 30900,
"enfant" => 15900
],

"Cabine 4 places Homme" => [
"adulte" => 28900,
"enfant" => 14900
],

"Cabine 4 places Femme" => [
"adulte" => 28900,
"enfant" => 14900
],

"Cabine 4 places Mixte" => [
"adulte" => 28900,
"enfant" => 14900
],

"Cabine 8 places Homme" => [
"adulte" => 18900,
"enfant" => 9900
],

"Cabine 8 places Femme" => [
"adulte" => 18900,
"enfant" => 9900
],

"Cabine 8 places Mixte" => [
"adulte" => 18900,
"enfant" => 9900
],

"Pullman" => [
"adulte" => 15900,
"enfant" => 8400
]

]

];

/* =========================
   MAPPING
========================= */

function mapPlace($type){

$type = strtolower(trim($type));

if(strpos($type,'cabine 2 places homme') !== false)
return "Cabine 2 places Homme";

if(strpos($type,'cabine 2 places femme') !== false)
return "Cabine 2 places Femme";

if(strpos($type,'cabine 4 places homme') !== false)
return "Cabine 4 places Homme";

if(strpos($type,'cabine 4 places femme') !== false)
return "Cabine 4 places Femme";

if(strpos($type,'cabine 4 places mixte') !== false)
return "Cabine 4 places Mixte";

if(strpos($type,'cabine 8 places homme') !== false)
return "Cabine 8 places Homme";

if(strpos($type,'cabine 8 places femme') !== false)
return "Cabine 8 places Femme";

if(strpos($type,'cabine 8 places mixte') !== false)
return "Cabine 8 places Mixte";

if(strpos($type,'pullman') !== false)
return "Pullman";

return $type;
}

/* =========================
   PLACES
========================= */

$stmt = $pdo->prepare("
    SELECT *
    FROM places
    WHERE traversee_id=?
");

$stmt->execute([$id]);

$places = $stmt->fetchAll();

/* =========================
   TRAITEMENT
========================= */

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

$id_place = $_POST['id_place'];
$sexe = strtoupper($_POST['sexe']);
$type_client = strtolower($_POST['type_client']);
$type_passager = strtolower($_POST['type_passager']);
$depart_client = $_POST['depart_client'];
$mode_paiement = $_POST['mode_paiement'] ?? 'om'; // 🆕 'om' ou 'wave'

// 🆕 Pays uniquement si étranger résident/non résident
$pays = ($type_client === 'resident' || $type_client === 'non_resident')
        ? trim($_POST['pays'] ?? '')
        : null;

$stmt = $pdo->prepare("SELECT * FROM places WHERE id_place=?");
$stmt->execute([$id_place]);
$place = $stmt->fetch();

if($place && $place['restant'] > 0 && $date_depart > $date_actuelle){

$type_place = mapPlace($place['type_place']);

// 🔒 Anti-doublon : un même passager (identifié par son CNI) ne peut pas
// acheter plusieurs billets pour le même voyage (traversee_id).



if(!isset($tarifs[$type_client][$type_place][$type_passager])){
die("❌ Tarif introuvable");
}

$prix = $tarifs[$type_client][$type_place][$type_passager];
$frais_service = 500; // ⚠️ ajuste selon le vrai montant souhaité (5 semblait être un bug)
$commission = (int) round($prix * 0.01); // 1% du prix du billet
$total = $prix + $frais_service + $commission;

$reference_billet = "BILLET-" . time();

// On enregistre la réservation en attente AVANT de générer le QR
$prenom = trim($_POST['prenom'] ?? '');
$nom_saisi = trim($_POST['nom'] ?? '');

$stmt = $pdo->prepare("
    INSERT INTO reservations_attente
    (reference, nom, prenom, telephone, sexe, cni, id_place, traversee_id, code_qr, type_client, pays, type_passager, prix, frais_service, depart_client, mode_paiement, statut)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'attente')
");

$stmt->execute([
    $reference_billet,
    $nom_saisi,
    $prenom,
    $_POST['telephone'],
    $sexe,
    $_POST['cni'],
    $id_place,
    $id,
    $reference_billet,
    $type_client,
    $pays,
    $type_passager,
    $prix,
    $frais_service,
    $depart_client,
    $mode_paiement
]);

/* =========================
   AIGUILLAGE MODE DE PAIEMENT
========================= */

if ($mode_paiement === 'wave') {

    /* =========================
       PAIEMENT WAVE
    ========================= */

    $waveConfig = require '../config/wave.php';

    $donneesWave = json_encode([
        "amount" => (string) $total,
        "currency" => "XOF",
        "client_reference" => $reference_billet,
        "success_url" => "https://batobi.sn/paiement_attente.php?ref=" . $reference_billet,
        "error_url" => "https://batobi.sn/paiement_annule.php?orderId=" . $reference_billet,
    ]);

// 🆕 Signature de la requête (obligatoire car activée sur cette clé)
    $timestampWave = time();
    $signingSecret = trim($waveConfig['signing_secret']); // voir config/wave.php ci-dessous
    $signatureWave = hash_hmac('sha256', $timestampWave . $donneesWave, $signingSecret);
    $waveSignatureHeader = "t={$timestampWave},v1={$signatureWave}";

    $ch = curl_init("https://api.wave.com/v1/checkout/sessions");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $donneesWave);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer " . trim($waveConfig['api_key']),
        "Content-Type: application/json",
        "Wave-Signature: " . $waveSignatureHeader,   // 🆕 ajouté
    ]);

    $reponseWave = curl_exec($ch);
    $erreurCurlWave = curl_error($ch);
    $httpCodeWave = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($erreurCurlWave) {
        error_log("Erreur cURL Wave : " . $erreurCurlWave);
        $_SESSION['error'] = "❌ Erreur de connexion au service de paiement Wave. Veuillez réessayer.";
        header("Location: acheter.php?id=" . $id);
        exit;
    }


$sessionWave = json_decode($reponseWave, true);

// 🆕 On stocke le checkout_id EN BASE, pas seulement en session
    $stmt = $pdo->prepare("UPDATE reservations_attente SET wave_checkout_id = ? WHERE reference = ?");
    $stmt->execute([$sessionWave['id'], $reference_billet]);

    // On stocke la référence, comme pour OM, pour retrouver la réservation ensuite
    $_SESSION['paiement'] = [
        "mode" => "wave",
        "code" => $reference_billet,
        "prix" => $prix,
        "frais" => $frais_service,
        "total" => $total,
        "traversee_id" => $id,
        "wave_checkout_id" => $sessionWave['id'],
    ];

    // Redirection navigateur classique (jamais dans une webview)
    header("Location: " . $sessionWave['wave_launch_url']);
    exit;

} else {

    /* =========================
       PAIEMENT ORANGE MONEY (existant, inchangé)
    ========================= */

    $client_id = "0bdd923d-ab73-41fc-a56c-8f56b1ff2fdf";
    $client_secret = "2462e4f5-a412-4ec2-9f29-4d1af6f27398";

    $ch = curl_init("https://api.orange-sonatel.com/oauth/token");

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);

    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Basic " . base64_encode($client_id . ":" . $client_secret),
        "Content-Type: application/x-www-form-urlencoded"
    ]);

    curl_setopt($ch, CURLOPT_POSTFIELDS, "grant_type=client_credentials");

    $response_token = curl_exec($ch);
    curl_close($ch);

    $token_data = json_decode($response_token, true);

    if(!isset($token_data['access_token'])){
        echo "<pre>";
        print_r($token_data);
        exit;
    }

    $token = $token_data['access_token'];

    /* =========================
       APPEL API QR CODE
    ========================= */

    $url = "https://api.orange-sonatel.com/api/eWallet/v4/qrcode";

    // Code marchand fixe fourni par Orange Sonatel (toujours le même)
    $merchant_code = "397666";

    $data = [
        "amount" => [
            "unit" => "XOF",
            "value" => (int)$total
        ],
        "code" => $merchant_code,
        "name" => "Achat billet " . $reference_billet,
        "callbackSuccessUrl" => "https://batobi.sn/paiement_succes.php?orderId=" . $reference_billet,
        "callbackCancelUrl"  => "https://batobi.sn/paiement_annule.php?orderId=" . $reference_billet,
        "validity" => 1800
    ];

    $json = json_encode($data);

    $ch = curl_init($url);

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1_2);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer $token",
        "Content-Type: application/json; charset=utf-8",
        "Accept: application/json"
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $json);

    $verbose = fopen('php://temp', 'w+');
    curl_setopt($ch, CURLOPT_VERBOSE, true);
    curl_setopt($ch, CURLOPT_STDERR, $verbose);

    $response = curl_exec($ch);

    if ($response === false) {
        error_log("Erreur cURL QR Code : " . curl_error($ch));
        $_SESSION['error'] = "❌ Erreur de connexion au service de paiement. Veuillez réessayer.";
        header("Location: acheter.php?id=" . $id);
        exit;
    }

    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $result = json_decode($response, true);

    if ($http_code !== 200 || !isset($result['qrCode'])) {
        error_log("Réponse QR Code invalide (HTTP $http_code) : " . $response);
        $_SESSION['error'] = "❌ Impossible de générer le QR Code de paiement. Veuillez réessayer.";
        header("Location: acheter.php?id=" . $id);
        exit;
    }

    /* =========================
       STOCKER EN SESSION
    ========================= */

    $_SESSION['paiement'] = [
        "mode" => "om",
        "qrCode" => $result['qrCode'],
        "deepLink" => $result['deepLink'],
        "qrId" => $result['qrId'] ?? null,
        "code" => $reference_billet,
        "prix" => $prix,
        "frais" => $frais_service,
        "total" => $total,
        "traversee_id" => $id
    ];

    header("Location: paiement.php");
    exit;
}

}else if($date_depart <= $date_actuelle){

$_SESSION['error'] = "❌ Ce voyage est déjà passé.";

}else{

$_SESSION['error'] = "❌ Plus de places disponibles";
header("Location: acheter.php?id=".$id);
exit;
}
}
?>

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<link rel="preconnect" href="https://fonts.googleapis.com">

<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@500;600;700&family=DM+Sans:wght@300;400;500;700&display=swap" rel="stylesheet">

<style>

:root{

--navy:#081827;
--royal:#0d5b8c;
--ocean:#1282c4;
--azure:#25a8ff;
--gold:#d8b75f;
--white:#ffffff;
--foam:#eef7fd;
--shadow:0 15px 40px rgba(0,0,0,.22);

}

*{
margin:0;
padding:0;
box-sizing:border-box;
}

body{

background:
linear-gradient(rgba(5,15,30,.78), rgba(5,15,30,.82)),
url('../assets/ASD1.jfif') no-repeat center center fixed;

background-size:cover;

font-family:'DM Sans', sans-serif;

min-height:100vh;
}

.main-app{

width:100%;
max-width:430px;

margin:auto;

min-height:100vh;

background:#f3f8fd;
}

/* HERO */

.hero{

    background:linear-gradient(
    160deg,
    var(--navy),
    var(--royal)
    );

    padding:22px 20px 35px;

    border-radius:0 0 35px 35px;

    position:relative;

    z-index:10;

    overflow:hidden;

    box-shadow:var(--shadow);
}

.hero::before{

content:'';

position:absolute;

top:-50px;
right:-50px;

width:180px;
height:180px;

background:rgba(255,255,255,.04);

border-radius:50%;
}

.hero::after{

content:'';

position:absolute;

bottom:-80px;
left:-40px;

width:180px;
height:180px;

background:rgba(37,168,255,.12);

border-radius:50%;
}

.topbar{

display:flex;
justify-content:space-between;
align-items:center;

position:relative;
z-index:2;
}

.brand{

display:flex;
align-items:center;
gap:12px;
}

.brand img{

width:58px;
height:58px;

background:#fff;

border-radius:16px;

padding:5px;
}

.brand-title{

font-family:'Cormorant Garamond', serif;

font-size:30px;

font-weight:700;

color:#fff;
}

.brand-sub{

font-size:11px;

letter-spacing:2px;

text-transform:uppercase;

color:rgba(255,255,255,.65);
}

.back-btn{

width:45px;
height:45px;

border-radius:50%;

background:rgba(255,255,255,.10);

display:flex;
align-items:center;
justify-content:center;

color:#fff;

text-decoration:none;

font-size:20px;
}

.hero-content{

position:relative;
z-index:2;

margin-top:25px;
}

.hero-content p{

color:rgba(255,255,255,.7);

font-size:12px;

letter-spacing:2px;

text-transform:uppercase;

margin-bottom:6px;
}

.hero-content h1{

font-family:'Cormorant Garamond', serif;

color:#fff;

font-size:36px;

line-height:1.1;
}

.hero-content h1 span{

color:var(--gold);
}

.trip-info{

margin-top:18px;

background:rgba(255,255,255,.10);

padding:14px;

border-radius:18px;

color:#fff;

font-size:14px;
}

/* CARD */

.form-card{

background:#fff;

margin:20px 16px;

border-radius:28px;

padding:20px;

box-shadow:0 10px 30px rgba(0,0,0,.10);
}

.form-title{

font-family:'Cormorant Garamond', serif;

font-size:32px;

color:#081827;

margin-bottom:20px;

text-align:center;
}

/* INPUTS */

.form-control,
.form-select{

height:60px;

border-radius:18px;

border:1px solid #dbe7f2;

font-size:15px;

padding:0 18px;

margin-bottom:15px;

box-shadow:none !important;
}

.form-control:focus,
.form-select:focus{

border-color:var(--ocean);
}

/* PLACE */

.place-card{

background:#eef7fd;

padding:18px;

border-radius:22px;

text-align:center;

margin-bottom:15px;
}

.place-selected{

font-weight:700;

color:#0d5b8c;

margin-bottom:12px;
}

/* BTN */

.btn-modern{

width:100%;

height:60px;

border:none;

border-radius:18px;

font-size:17px;

font-weight:700;

transition:.3s;
}

.btn-place{

background:#081827;

color:#fff;
}

.btn-buy{

margin-top:18px;

background:linear-gradient(135deg,var(--ocean),var(--azure));

color:#fff;

box-shadow:0 10px 25px rgba(0,0,0,.18);
}

/* PRICE */

#prix .alert{

border:none;

border-radius:18px;

padding:18px;

font-size:15px;
}

/* MOBILE */

@media(max-width:480px){

.hero-content h1{
font-size:30px;
}

.form-title{
font-size:28px;
}

}


.progress-steps {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0;
    margin-bottom: 24px;
  }
  .step {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 4px;
  }
  .step .circle {
    width: 32px; height: 32px;
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: 12px;
    font-weight: 700;
  }
  .step.active .circle {
    background: linear-gradient(135deg, var(--ocean), var(--azure));
    color: white;
    box-shadow: 0 4px 12px rgba(30,91,173,0.35);
  }
  .step.done .circle {
    background: #22c55e;
    color: white;
  }
  .step.pending .circle {
    background: white;
    border: 2px solid var(--mist);
    color: var(--text-light);
  }
  .step .label {
    font-size: 9px;
    text-transform: uppercase;
    letter-spacing: 0.1em;
    color: var(--text-light);
    font-weight: 600;
  }
  .step.active .label { color: var(--ocean); }
  .step-connector {
    width: 40px; height: 2px;
    background: var(--mist);
    margin: 0 4px;
    margin-bottom: 14px;
  }
  .step-connector.done { background: var(--ocean); }

    .form-card h3 .card-icon {
    width: 30px; height: 30px;
    background: var(--foam);
    border-radius: 8px;
    display: flex; align-items: center; justify-content: center;
    font-size: 15px;
  }

 .form-appbar-top {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 16px;
    position: relative; z-index: 1;
  }
  .form-appbar-top h2 {
    font-family: 'Cormorant Garamond', serif;
    font-size: 20px;
    font-weight: 600;
    color: white;
  }

.modal-overlay {
    background: rgba(10,22,40,0.7);
    backdrop-filter: blur(4px);
    position: relative;
    padding: 40px 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: 400px;
  }
  .modal-box {
    background: white;
    border-radius: 20px;
    padding: 24px;
    box-shadow: var(--shadow-lg);
    width: 100%;
    max-width: 320px;
  }
  .modal-box .modal-icon {
    width: 48px; height: 48px;
    background: linear-gradient(135deg, var(--ocean), var(--azure));
    border-radius: 14px;
    display: flex; align-items: center; justify-content: center;
    font-size: 24px;
    margin: 0 auto 16px;
    box-shadow: 0 8px 24px rgba(30,91,173,0.3);
  }
  .modal-box h3 {
    font-family: 'Cormorant Garamond', serif;
    font-size: 20px;
    font-weight: 700;
    color: var(--navy);
    text-align: center;
    margin-bottom: 8px;
  }
  .modal-box .modal-route {
    text-align: center;
    font-size: 13px;
    font-weight: 600;
    color: var(--ocean);
    margin-bottom: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
  }
  .modal-detail-row {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 8px 0;
    border-bottom: 1px solid var(--border);
    font-size: 12px;
  }
  .modal-detail-row:last-child { border-bottom: none; }
  .modal-detail-row .mdr-icon { color: var(--ocean); font-size: 14px; width: 20px; text-align: center; }
  .modal-detail-row .mdr-label { color: var(--text-light); flex: 1; }
  .modal-detail-row .mdr-val { font-weight: 600; color: var(--navy); }
  .modal-fee-note {
    background: rgba(201,168,76,0.08);
    border: 1px solid rgba(201,168,76,0.2);
    border-radius: 10px;
    padding: 8px 12px;
    font-size: 11px;
    color: #92640a;
    margin: 12px 0;
    display: flex;
    gap: 8px;
    align-items: center;
  }
  .modal-btns { display: flex; gap: 10px; margin-top: 16px; }
  .btn-modal-cancel {
    flex: 1;
    padding: 12px;
    border: 1.5px solid var(--border);
    border-radius: 12px;
    background: white;
    font-family: 'DM Sans', sans-serif;
    font-size: 13px;
    font-weight: 600;
    color: var(--text-mid);
    cursor: pointer;
  }
  .btn-modal-ok {
    flex: 1;
    padding: 12px;
    border: none;
    border-radius: 12px;
    background: linear-gradient(135deg, var(--ocean), var(--azure));
    font-family: 'DM Sans', sans-serif;
    font-size: 13px;
    font-weight: 700;
    color: white;
    cursor: pointer;
    box-shadow: 0 4px 14px rgba(30,91,173,0.35);
  }

  .modal-overlay{

position:fixed;
inset:0;

background:rgba(0,0,0,.6);

display:flex;
align-items:center;
justify-content:center;

z-index:9999;

padding:20px;
}

.modal-box{

background:#fff;

width:100%;
max-width:380px;

border-radius:26px;

padding:25px;

animation:fadeIn .25s ease;
}

.modal-icon{

width:60px;
height:60px;

border-radius:50%;

background:#22c55e;

display:flex;
align-items:center;
justify-content:center;

font-size:28px;

color:#fff;

margin:auto auto 18px;
}

.modal-box h3{

text-align:center;

margin-bottom:18px;

font-size:22px;

color:#081827;
}

.modal-route{

text-align:center;

font-weight:700;

color:#0d5b8c;

margin-bottom:18px;
}

.modal-detail-row{

display:flex;
justify-content:space-between;

padding:12px 0;

border-bottom:1px solid #eef2f7;

font-size:14px;
}

.modal-fee-note{

margin-top:18px;

background:#eef7fd;

padding:14px;

border-radius:14px;

font-size:14px;

text-align:center;
}

.modal-btns{

display:flex;
gap:12px;

margin-top:20px;
}

.btn-modal-cancel,
.btn-modal-ok{

flex:1;

height:50px;

border:none;

border-radius:14px;

font-weight:700;
}

.btn-modal-cancel{

background:#edf2f7;
}

.btn-modal-ok{

background:linear-gradient(135deg,var(--ocean),var(--azure));

color:#fff;
}

@keyframes fadeIn{

from{
opacity:0;
transform:translateY(20px);
}

to{
opacity:1;
transform:translateY(0);
}

}
  

  .modal-detail-row {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 8px 0;
    border-bottom: 1px solid var(--border);
    font-size: 12px;
  }
  .modal-detail-row:last-child { border-bottom: none; }
  .modal-detail-row .mdr-icon { color: var(--ocean); font-size: 14px; width: 20px; text-align: center; }
  .modal-detail-row .mdr-label { color: var(--text-light); flex: 1; }
  .modal-detail-row .mdr-val { font-weight: 600; color: var(--navy); }
  .modal-fee-note {
    background: rgba(201,168,76,0.08);
    border: 1px solid rgba(201,168,76,0.2);
    border-radius: 10px;
    padding: 8px 12px;
    font-size: 11px;
    color: #92640a;
    margin: 12px 0;
    display: flex;
    gap: 8px;
    align-items: center;
  }
  .modal-btns { display: flex; gap: 10px; margin-top: 16px; }
  .btn-modal-cancel {
    flex: 1;
    padding: 12px;
    border: 1.5px solid var(--border);
    border-radius: 12px;
    background: white;
    font-family: 'DM Sans', sans-serif;
    font-size: 13px;
    font-weight: 600;
    color: var(--text-mid);
    cursor: pointer;
  }
  .btn-modal-ok {
    flex: 1;
    padding: 12px;
    border: none;
    border-radius: 12px;
    background: linear-gradient(135deg, var(--ocean), var(--azure));
    font-family: 'DM Sans', sans-serif;
    font-size: 13px;
    font-weight: 700;
    color: white;
    cursor: pointer;
    box-shadow: 0 4px 14px rgba(30,91,173,0.35);
  }
</style>

<div class="main-app">

<!-- HERO -->

<div class="hero">

<div class="topbar">

<div class="brand">



<div>


    <div class="form-appbar-top">
              <h2><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:inline-block;vertical-align:middle;"><path d="M2 9a3 3 0 0 1 0 6v2a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-2a3 3 0 0 1 0-6V7a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2z"/><line x1="9" y1="12" x2="15" y2="12"/></svg> Réserver un billet</h2>
            </div>



</div>

</div>

<a href="index.php" class="back-btn">
←
</a>

</div>

<div class="hero-content">

        
<div class="trip-info">

<?= $traversee['depart'] ?>
→ <?= $traversee['destination'] ?>

<br>

<span class="rp-date">
<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:inline-block;vertical-align:middle;">
<rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
<line x1="16" y1="2" x2="16" y2="6"/>
<line x1="8" y1="2" x2="8" y2="6"/>
<line x1="3" y1="10" x2="21" y2="10"/>
</svg>

<?= date('d/m/Y · H\hi', strtotime($traversee['date_depart'])) ?>

</span>

</div>

</div>

</div>

<!-- FORM -->

<div class="form-card">

  <!-- Progress steps -->
            <div class="progress-steps">
              <div class="step done">
                <div class="circle">✓</div>
                <div class="label">Voyage</div>
              </div>
              <div class="step-connector done"></div>
              <div class="step active">
                <div class="circle">2</div>
                <div class="label">Passager</div>
              </div>
              <div class="step-connector"></div>
              <div class="step pending">
                <div class="circle">3</div>
                <div class="label">Paiement</div>
              </div>
            </div>
      <h3><div class="card-icon"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:inline-block;vertical-align:middle;"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg></div> Informations passager</h3>
<form method="POST">


<select class="form-select" name="sexe" required>

<option value="">
Sexe
</option>

<option value="M">
Homme
</option>

<option value="F">
Femme
</option>

</select>

<select class="form-select" name="type_client" id="type_client" required>

<option value="">
Nationalité
</option>

<option value="senegalais">
Résident
</option>

<option value="resident">
Étranger Résident
</option>

<option value="non_resident">
Étranger Non Résident
</option>

</select>

<!-- 🆕 Pays de nationalité, affiché seulement si étranger -->
<select
class="form-select"
name="pays"
id="pays"
style="display:none;">

<option value="">Pays de nationalité</option>

<?php foreach ($liste_pays as $p): ?>
<option value="<?= htmlspecialchars($p) ?>"><?= htmlspecialchars($p) ?></option>
<?php endforeach; ?>

</select>

<input
class="form-control"
name="prenom"
placeholder="Prénom"
required>

<input
class="form-control"
name="nom"
placeholder="Nom"
required>

<input
class="form-control"
name="telephone"
placeholder="Téléphone"
required>

<input
class="form-control"
name="cni"
placeholder="Numéro CNI ou Passeport"
required>

<select
class="form-select"
name="type_passager"
required>

<option value="">
Type passager
</option>

<option value="adulte">
Adulte
</option>

<option value="enfant">
Enfant (4 à moins de 12 ans)
</option>

</select>

<select
class="form-select"
name="depart_client">

<option value="">
Lieu de départ (optionnel)
</option>

<option value="Carabane">
Carabane
</option>

</select>

<input
type="hidden"
name="id_place"
id="id_place">

<!-- 🆕 Mode de paiement choisi (om ou wave), rempli par le modal de choix -->
<input
type="hidden"
name="mode_paiement"
id="mode_paiement">

<!-- PLACE -->

<div class="place-card">

<div
id="placeChoisie"
class="place-selected">

Aucune place sélectionnée

</div>

<button
type="button"
class="btn-modern btn-place"
onclick="ouvrirPlan()">

🚢 Choisir une place

</button>

</div>

<div id="disponibilite"></div>

<div id="prix"></div>

<?php if ($date_depart > $date_actuelle): ?>

<button class="btn-modern btn-buy">

🎟️ Valider la réservation

</button>

<?php else: ?>

<div class="alert alert-danger text-center">

❌ Ce voyage est déjà passé

</div>

<?php endif; ?>





</form>

</div>

</div>

<script>

// 🆕 Affichage conditionnel du champ Pays selon la nationalité
const typeClientEl = document.getElementById('type_client');
const paysEl = document.getElementById('pays');

typeClientEl.addEventListener('change', function(){

    if(this.value === 'resident' || this.value === 'non_resident'){
        paysEl.style.display = 'block';
        paysEl.required = true;
    } else {
        paysEl.style.display = 'none';
        paysEl.required = false;
        paysEl.value = '';
    }

});

document.querySelector("form")
.addEventListener("submit", function(e){

let sexe =
document.querySelector('[name="sexe"]').value;

let prenom =
document.querySelector('[name="prenom"]').value.trim();

let nom =
document.querySelector('[name="nom"]').value.trim();

let tel =
document.querySelector('[name="telephone"]').value.trim();

let cni =
document.querySelector('[name="cni"]').value.trim();

let client =
document.querySelector('[name="type_client"]').value;

let pays =
document.querySelector('[name="pays"]').value;

let type =
document.querySelector('[name="type_passager"]').value;

let place =
document.querySelector('[name="id_place"]').value;

let departEl =
document.querySelector('[name="depart_client"]');

let depart =
departEl ? departEl.value : "";

let erreurs = [];

if(prenom.length < 2)
erreurs.push("Prénom invalide");

if(nom.length < 2)
erreurs.push("Nom invalide");

if(!/^[0-9]{1,15}$/.test(tel)){

erreurs.push("Téléphone invalide");

}

if(cni.length < 5)
erreurs.push("CNI invalide");

if(!client)
erreurs.push("Choisir la nationalité");

// 🆕 Pays obligatoire si étranger résident/non résident
if((client === "resident" || client === "non_resident") && !pays){
erreurs.push("Choisir le pays de nationalité");
}

if(!sexe)
erreurs.push("Choisir le sexe");

if(!type)
erreurs.push("Choisir type passager");

if(!place)
erreurs.push("Choisir une place");

if(erreurs.length > 0){

alert(
"❌ Erreurs:\n\n" +
erreurs.join("\n")
);

e.preventDefault();

return;
}

e.preventDefault();

// Remplir les infos dans le modal
document.getElementById("modalRoute").innerHTML =
"<?= $traversee['depart'] ?> → <?= $traversee['destination'] ?>";

document.getElementById("modalDepart").innerHTML =
depart ? depart : "<?= $traversee['depart'] ?>";

document.getElementById("modalType").innerHTML =
document.querySelector("#placeChoisie").innerText;

// 🆕 Ajout du pays dans le récap passager si présent
document.getElementById("modalPassager").innerHTML =
prenom + " " + nom + " · " + type + " · " + client + (pays ? " · " + pays : "");
// Afficher modal
document.getElementById("confirmModal").style.display = "flex";

});

/* PLAN */

function ouvrirPlan(){

window.open(

"plan_embarquement.php?id=<?= $id ?>",

"_blank",

"width=1400,height=900"

);

}

/* RECUP PLACE */

window.addEventListener("message", function(e){

if(e.data.id_place){

document.getElementById("id_place").value =
e.data.id_place;

document.getElementById("placeChoisie").innerHTML =

"✅ Place sélectionnée : <b>" +
e.data.numero +
"</b>";

}

});

function fermerModal(){

document.getElementById("confirmModal").style.display = "none";

}

// 🆕 Après confirmation du récap, on ouvre le choix du mode de paiement
// au lieu de soumettre directement le formulaire.
function confirmerReservation(){

document.getElementById("confirmModal").style.display = "none";
document.getElementById("paiementModal").style.display = "flex";

}

// 🆕 Fermer le modal de choix du mode de paiement
function fermerModalPaiement(){

document.getElementById("paiementModal").style.display = "none";

}

// 🆕 L'utilisateur choisit OM ou Wave -> on remplit le champ caché et on soumet
function choisirModePaiement(mode){

document.getElementById("mode_paiement").value = mode;
document.getElementById("paiementModal").style.display = "none";
document.querySelector("form").submit();

}
</script>
<!-- MODAL CONFIRMATION -->
<div class="modal-overlay" id="confirmModal" style="display:none;">

    <div class="modal-box">

        <div class="modal-icon"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:inline-block;vertical-align:middle;"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg></div>

        <h3>Vérifiez vos informations</h3>

        <div class="modal-route" id="modalRoute"></div>

        <div class="modal-detail-row">
           <span class="mdr-icon"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:inline-block;vertical-align:middle;"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg></span>
            <span class="mdr-label">Départ</span>
            <span class="mdr-val" id="modalDepart"></span>
        </div>

        <div class="modal-detail-row">
          <span class="mdr-icon"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:inline-block;vertical-align:middle;"><path d="M2 20v-8a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v8"/><path d="M2 12V8a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v4"/><path d="M2 20h20"/><path d="M6 10h.01"/></svg></span>
            <span class="mdr-label">Place</span>
            <span class="mdr-val" id="modalType"></span>
        </div>

        <div class="modal-detail-row">
          <span class="mdr-icon"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:inline-block;vertical-align:middle;"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg></span>
            <span class="mdr-label">Passager</span>
            <span class="mdr-val" id="modalPassager"></span>
        </div>

       <div class="modal-fee-note"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:inline-block;vertical-align:middle;"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg> Des frais de service de <strong>500 FCFA</strong> seront ajoutés au total.</div>

        <div class="modal-btns">

            <button
            type="button"
            class="btn-modal-cancel"
            onclick="fermerModal()">

                Annuler

            </button>

            <button
            type="button"
            class="btn-modal-ok"
            onclick="confirmerReservation()">

                Confirmer ✓

            </button>

        </div>

    </div>

</div>

<!-- 🆕 MODAL CHOIX DU MODE DE PAIEMENT -->
<div class="modal-overlay" id="paiementModal" style="display:none;">

    <div class="modal-box">

        <h3>Choisissez votre mode de paiement</h3>

        <div class="modal-btns" style="flex-direction:column; gap:14px;">

            <button
            type="button"
            class="btn-modal-ok"
            style="background:linear-gradient(135deg,#ff6600,#ff8c00);"
            onclick="choisirModePaiement('om')">

                📱 Orange Money

            </button>

            <button
            type="button"
            class="btn-modal-ok"
            style="background:#1BA0E2;"
            onclick="choisirModePaiement('wave')">

                🌊 Wave

            </button>

            <button
            type="button"
            class="btn-modal-cancel"
            onclick="fermerModalPaiement()">

                Annuler

            </button>

        </div>

    </div>

</div>
<?php include '../includes/footer.php'; ?>
