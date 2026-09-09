<?php
session_start();

require_once '../config/database.php';
require_once '../lib/phpqrcode/qrlib.php';

include '../includes/header.php';

$id = $_GET['id'] ?? null;

if(!$id){
    die("❌ Billet introuvable");
}

/* =========================
   BILLET
========================= */

$stmt = $pdo->prepare("
SELECT 
b.*,
pl.type_place,
t.depart,
t.destination,
t.date_depart

FROM billets b

JOIN places pl
ON b.id_place = pl.id_place

JOIN traversees t
ON b.traversee_id = t.id

WHERE b.id=?
");

$stmt->execute([$id]);

$billet = $stmt->fetch();

if(!$billet){
    die("❌ Billet introuvable");
}

/* =========================
   QR CODE
========================= */

if(!file_exists('qrcodes')){
    mkdir('qrcodes');
}

$qrFile =
"qrcodes/billet_" .
$billet['id'] .
".png";

if(!file_exists($qrFile)){

QRcode::png(
$billet['code_qr'],
$qrFile
);

}

/* =========================
   PRIX
========================= */

$frais_service =
(!empty($billet['frais_service']))
? (int)$billet['frais_service']
: 500;

$prix_billet =
(int)$billet['prix'];

$total =
$prix_billet + $frais_service;

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
--success:#0fb36b;

--shadow:
0 15px 40px rgba(0,0,0,.22);

}

*{
margin:0;
padding:0;
box-sizing:border-box;
}

body{

background:
linear-gradient(rgba(5,15,30,.78), rgba(5,15,30,.82)),
url('../assets/ASD1.jfif')
no-repeat center center fixed;

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

background:
linear-gradient(
160deg,
var(--navy),
var(--royal)
);

padding:22px 20px 35px;

border-radius:0 0 35px 35px;

position:relative;

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

background:
rgba(255,255,255,.04);

border-radius:50%;
}

.hero::after{

content:'';

position:absolute;

bottom:-80px;
left:-40px;

width:180px;
height:180px;

background:
rgba(37,168,255,.12);

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

font-family:
'Cormorant Garamond',
serif;

font-size:30px;

font-weight:700;

color:#fff;
}

.brand-sub{

font-size:11px;

letter-spacing:2px;

text-transform:uppercase;

color:
rgba(255,255,255,.65);
}

.home-btn{

width:45px;
height:45px;

border-radius:50%;

background:
rgba(255,255,255,.10);

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

color:
rgba(255,255,255,.7);

font-size:12px;

letter-spacing:2px;

text-transform:uppercase;

margin-bottom:6px;
}

.hero-content h1{

font-family:
'Cormorant Garamond',
serif;

color:#fff;

font-size:36px;

line-height:1.1;
}

.hero-content h1 span{

color:var(--gold);
}

/* TICKET */

.ticket-card{

background:#fff;

margin:20px 16px;

border-radius:30px;

overflow:hidden;

box-shadow:
0 10px 30px rgba(0,0,0,.12);
}

.ticket-header{

background:
linear-gradient(
135deg,
#0fb36b,
#19d584
);

padding:24px;

text-align:center;

color:#fff;
}

.ticket-header h2{

font-size:32px;

font-family:
'Cormorant Garamond',
serif;

margin-bottom:5px;
}

.ticket-header p{

opacity:.9;

font-size:14px;
}

/* BODY */

.ticket-body{

padding:22px;
}

/* VOYAGE */

.trip-box{

background:#eef7fd;

padding:18px;

border-radius:22px;

margin-bottom:18px;

text-align:center;
}

.trip-route{

font-size:22px;

font-weight:700;

color:#081827;
}

.trip-date{

margin-top:10px;

font-size:15px;

color:#0d5b8c;
}

/* INFO */

.info-card{

background:#fff;

border:1px solid #edf2f7;

padding:18px;

border-radius:22px;

margin-bottom:18px;
}

.info-line{

display:flex;
justify-content:space-between;

padding:10px 0;

border-bottom:
1px solid #edf2f7;
}

.info-line:last-child{
border-bottom:none;
}

.info-label{

font-weight:600;

color:#64748b;
}

.info-value{

font-weight:700;

color:#081827;
}

/* PRICE */

.price-card{

background:
linear-gradient(
135deg,
#f0fdf4,
#dcfce7
);

padding:18px;

border-radius:24px;

margin-bottom:22px;
}

.price-line{

display:flex;
justify-content:space-between;

margin-bottom:12px;
}

.total-line{

border-top:
1px dashed rgba(0,0,0,.15);

padding-top:14px;

margin-top:12px;

font-size:24px;

font-weight:800;

color:#0f766e;
}

/* QR */

.qr-box{

text-align:center;

padding:24px;

background:#fff;

border-radius:24px;

border:
1px solid #edf2f7;
}

.qr-box img{

width:220px;

max-width:100%;

background:#fff;

padding:10px;

border-radius:20px;

box-shadow:
0 10px 25px rgba(0,0,0,.10);
}

.qr-code{

margin-top:18px;

font-weight:700;

color:#0d5b8c;

font-size:15px;
}

/* BUTTONS */

.btn-modern{

width:100%;

height:62px;

border:none;

border-radius:20px;

font-size:17px;

font-weight:700;

display:flex;
align-items:center;
justify-content:center;

text-decoration:none;

margin-top:15px;

transition:.3s;
}

.btn-pdf{

background:
linear-gradient(
135deg,
#ef4444,
#dc2626
);

color:#fff;

box-shadow:
0 10px 25px rgba(220,38,38,.30);
}

.btn-home{

background:
linear-gradient(
135deg,
var(--ocean),
var(--azure)
);

color:#fff;

box-shadow:
0 10px 25px rgba(0,0,0,.18);
}

/* MOBILE */

@media(max-width:480px){

.hero-content h1{
font-size:30px;
}

.ticket-header h2{
font-size:28px;
}

.trip-route{
font-size:20px;
}

.total-line{
font-size:22px;
}

}

/* Section title inside screen */
  .screen-section-title {
    padding: 20px 24px 12px;
    display: flex;
    justify-content: space-between;
    align-items: center;
  }
  .screen-section-title h3 {
    font-family: 'Cormorant Garamond', serif;
    font-size: 20px;
    font-weight: 600;
    color: var(--navy);
  }
  .screen-section-title span {
    font-size: 12px;
    color: var(--ocean);
    font-weight: 500;
    letter-spacing: 0.04em;
  }
/* ─── SCREEN 5: TICKET ─── */
  .ticket-screen { background: #f0f5fb; min-height: 780px; }
  .ticket-appbar {
    background: linear-gradient(160deg, var(--navy), var(--royal));
    padding: 16px 20px 20px;
    display: flex;
    align-items: center;
    gap: 12px;
  }
  .ticket-appbar h2 {
    font-family: 'Cormorant Garamond', serif;
    font-size: 20px;
    font-weight: 600;
    color: white;
  }
  .ticket-appbar .success-dot {
    width: 10px; height: 10px;
    border-radius: 50%;
    background: #22c55e;
    box-shadow: 0 0 0 3px rgba(34,197,94,0.2);
  }
  .ticket-body { padding: 20px 16px; }

  .ticket-card {
    background: white;
    border-radius: 20px;
    overflow: hidden;
    box-shadow: var(--shadow-md);
    border: 1px solid var(--border);
    margin-bottom: 16px;
  }
  .ticket-header {
    background: linear-gradient(135deg, var(--navy), var(--royal));
    padding: 20px;
    text-align: center;
    position: relative;
    overflow: hidden;
  }
  .ticket-header::before {
    content: '';
    position: absolute;
    top: -40px; left: -40px;
    width: 120px; height: 120px;
    border-radius: 50%;
    background: rgba(255,255,255,0.04);
  }
  .ticket-header .confirmed-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: rgba(34,197,94,0.15);
    border: 1px solid rgba(34,197,94,0.3);
    border-radius: 20px;
    padding: 4px 12px;
    margin-bottom: 10px;
    font-size: 11px;
    font-weight: 600;
    color: #86efac;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    position: relative; z-index: 1;
  }
  .ticket-header .confirmed-badge::before {
    content: '';
    width: 6px; height: 6px;
    border-radius: 50%;
    background: #22c55e;
  }
  .ticket-header .ticket-route {
    font-family: 'Cormorant Garamond', serif;
    font-size: 26px;
    font-weight: 700;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 12px;
    position: relative; z-index: 1;
    margin-bottom: 4px;
  }
  .ticket-header .ticket-route .arr { color: var(--gold); font-size: 20px; }
  .ticket-header .ticket-date {
    font-size: 12px;
    color: rgba(200,222,250,0.7);
    position: relative; z-index: 1;
    font-weight: 500;
  }

  /* Tear line */
  .tear-line {
    height: 1px;
    background: repeating-linear-gradient(90deg, var(--border) 0, var(--border) 8px, transparent 8px, transparent 14px);
    margin: 0 -1px;
    position: relative;
  }
  .tear-line::before,
  .tear-line::after {
    content: '';
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    width: 20px; height: 20px;
    border-radius: 50%;
    background: #f0f5fb;
    border: 1px solid var(--border);
  }
  .tear-line::before { left: -11px; }
  .tear-line::after { right: -11px; }

  .ticket-details { padding: 20px; }
  .detail-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 14px;
    margin-bottom: 16px;
  }
  .detail-item .di-label {
    font-size: 9px;
    text-transform: uppercase;
    letter-spacing: 0.12em;
    color: var(--text-light);
    font-weight: 600;
    margin-bottom: 3px;
  }
  .detail-item .di-val {
    font-size: 13px;
    font-weight: 700;
    color: var(--navy);
  }

  .ticket-price-box {
    background: linear-gradient(135deg, #e8f3ff, #daeeff);
    border: 1px solid var(--mist);
    border-radius: 14px;
    padding: 14px 16px;
    margin-bottom: 16px;
  }
  .tpb-row {
    display: flex;
    justify-content: space-between;
    font-size: 12px;
    padding: 3px 0;
    color: var(--text-mid);
  }
  .tpb-row .tv { font-weight: 600; color: var(--navy); }
  .tpb-divider { height: 1px; background: var(--mist); margin: 8px 0; }
  .tpb-total {
    display: flex;
    justify-content: space-between;
    align-items: center;
  }
  .tpb-total .tl { font-size: 11px; font-weight: 700; color: var(--navy); text-transform: uppercase; letter-spacing: 0.06em; }
  .tpb-total .tv {
    font-family: 'Cormorant Garamond', serif;
    font-size: 24px;
    font-weight: 700;
    color: var(--ocean);
  }

  /* QR code area */
  .qr-section {
    text-align: center;
    padding: 16px 20px 20px;
  }
  .qr-wrapper {
    display: inline-block;
    padding: 14px;
    background: white;
    border: 1px solid var(--border);
    border-radius: 16px;
    box-shadow: var(--shadow-sm);
    margin-bottom: 10px;
  }
  .qr-placeholder {
    width: 120px; height: 120px;
    background: var(--navy);
    border-radius: 8px;
    position: relative;
    overflow: hidden;
    display: flex; align-items: center; justify-content: center;
  }
  .qr-placeholder::before {
    content: '';
    position: absolute;
    inset: 0;
    background: repeating-linear-gradient(
      0deg,
      transparent 0px,
      transparent 6px,
      rgba(255,255,255,0.06) 6px,
      rgba(255,255,255,0.06) 7px
    ),
    repeating-linear-gradient(
      90deg,
      transparent 0px,
      transparent 6px,
      rgba(255,255,255,0.06) 6px,
      rgba(255,255,255,0.06) 7px
    );
  }
  /* QR corner squares */
  .qr-corner {
    position: absolute;
    width: 28px; height: 28px;
    border: 3px solid white;
    border-radius: 3px;
  }
  .qr-corner.tl { top: 8px; left: 8px; }
  .qr-corner.tr { top: 8px; right: 8px; }
  .qr-corner.bl { bottom: 8px; left: 8px; }
  .qr-corner::after {
    content: '';
    position: absolute;
    top: 4px; left: 4px;
    width: 12px; height: 12px;
    background: white;
    border-radius: 1px;
  }
  .qr-code-text {
    font-size: 11px;
    font-weight: 700;
    color: var(--navy);
    letter-spacing: 0.08em;
    font-family: 'DM Sans', monospace;
  }

  .ticket-actions { padding: 0 16px 20px; display: flex; gap: 10px; }
  .btn-pdf {
    flex: 1;
    padding: 14px;
    background: linear-gradient(135deg, var(--navy), var(--royal));
    border: none;
    border-radius: 14px;
    font-family: 'DM Sans', sans-serif;
    font-size: 13px;
    font-weight: 600;
    color: white;
    cursor: pointer;
    display: flex; align-items: center; justify-content: center; gap: 8px;
    box-shadow: 0 4px 14px rgba(10,22,40,0.3);
  }
  .btn-home {
    flex: 1;
    padding: 14px;
    background: linear-gradient(135deg, var(--ocean), var(--azure));
    border: none;
    border-radius: 14px;
    font-family: 'DM Sans', sans-serif;
    font-size: 13px;
    font-weight: 600;
    color: white;
    cursor: pointer;
    display: flex; align-items: center; justify-content: center; gap: 8px;
    box-shadow: 0 4px 14px rgba(30,91,173,0.35);
  }

  /* Screen section wrapper */
  .screen-section {
    position: relative;
    z-index: 2;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0;
    width: 100%;
  }

  /* Divider with label */
  .flow-arrow {
    color: rgba(200,222,250,0.3);
    font-size: 24px;
    text-align: center;
    padding: 8px 0;
    z-index: 1;
  }
</style>

<div class="main-app">

<!-- HERO -->

<div class="hero">

<div class="topbar">

<div class="brand">



<div>



</div>

</div>

<div style="
display:flex;
align-items:center;
gap:12px;
color:white;
">

<a href="index.php"
style="
width:45px;
height:45px;
border-radius:50%;
background:rgba(255,255,255,.10);
display:flex;
align-items:center;
justify-content:center;
color:#fff;
text-decoration:none;
font-size:22px;
border:1px solid rgba(255,255,255,0.2);
">

←

</a>

<h2 style="
margin:0;
font-size:22px;
font-family:'Cormorant Garamond', serif;
font-weight:600;
color:white;
">

Billet confirmé

</h2>

</div>

</div>



</div>

<!-- TICKET -->

<div class="ticket-card">

<!-- HEADER -->

</div><!-- ÉCRAN 5: Billet confirmé --> 

<div class="screen-section">

<div class="phone-frame">

<div class="ticket-screen">





<div class="ticket-body">

<div class="ticket-card">

<div class="ticket-header">

<div class="confirmed-badge">
✓ Billet confirmé
</div>

<div class="ticket-route">

<span>
<?= strtoupper($billet['depart']) ?>
</span>

<span class="arr">
→
</span>

<span>
<?= strtoupper($billet['destination']) ?>
</span>

</div>

<div class="ticket-date">

<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:inline-block;vertical-align:middle;">
<rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
<line x1="16" y1="2" x2="16" y2="6"/>
<line x1="8" y1="2" x2="8" y2="6"/>
<line x1="3" y1="10" x2="21" y2="10"/>
</svg>

<?= date('d M Y', strtotime($billet['date_depart'])) ?>

· Départ

<?= date('H\hi', strtotime($billet['date_depart'])) ?>

</div>

</div>

<div class="ticket-details">

<div class="detail-grid">

<div class="detail-item">

<div class="di-label">
Passager
</div>

<div class="di-val">
<?= $billet['nom'] ?>
</div>

</div>

<div class="detail-item">

<div class="di-label">
Type
</div>

<div class="di-val">
<?= ucfirst($billet['type_passager']) ?>
</div>

</div>

<div class="detail-item">

<div class="di-label">
Catégorie
</div>

<div class="di-val">
<?= $billet['type_place'] ?>
</div>

</div>

<div class="detail-item">

<div class="di-label">
Nationalité
</div>

<div class="di-val">

<?= ucfirst(
str_replace('_',' ',
$billet['type_client'])
) ?>

</div>

</div>

</div>

<div class="ticket-price-box">

<div class="tpb-row">

<span>

<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:inline-block;vertical-align:middle;">
<path d="M2 9a3 3 0 0 1 0 6v2a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-2a3 3 0 0 1 0-6V7a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2z"/>
<line x1="9" y1="12" x2="15" y2="12"/>
</svg>

Prix billet

</span>

<span class="tv">

<?= number_format(
$prix_billet,
0,
',',
' '
) ?>

FCFA

</span>

</div>

<div class="tpb-row">

<span>

<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:inline-block;vertical-align:middle;">
<rect x="1" y="4" width="22" height="16" rx="2" ry="2"/>
<line x1="1" y1="10" x2="23" y2="10"/>
</svg>

Frais service

</span>

<span class="tv">

<?= number_format(
$frais_service,
0,
',',
' '
) ?>

FCFA

</span>

</div>

<div class="tpb-divider"></div>

<div class="tpb-total">

<span class="tl">

<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:inline-block;vertical-align:middle;">
<path d="M12 2L2 7l10 5 10-5-10-5z"/>
<path d="M2 17l10 5 10-5"/>
<path d="M2 12l10 5 10-5"/>
</svg>

Total payé

</span>

<span class="tv">

<?= number_format(
$total,
0,
',',
' '
) ?>

FCFA

</span>

</div>

</div>

</div>

<div class="tear-line"></div>

<div class="qr-section">

<div class="qr-wrapper">

<img
src="<?= $qrFile ?>"
style="
width:170px;
height:170px;
object-fit:contain;
background:#fff;
padding:10px;
border-radius:16px;
">

</div>

<div class="qr-code-text">

Code :
<?= $billet['code_qr'] ?>

</div>

</div>

</div>

</div>

</div>

</div>

<!-- ACTIONS -->

<div class="ticket-actions">

<a
href="generate_pdf.php?id=<?= $billet['id'] ?>"
target="_blank"
class="btn-pdf">

<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:inline-block;vertical-align:middle;">
<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
<polyline points="14 2 14 8 20 8"/>
<line x1="16" y1="13" x2="8" y2="13"/>
<line x1="16" y1="17" x2="8" y2="17"/>
<polyline points="10 9 9 9 8 9"/>
</svg>

Télécharger PDF

</a>

<a
href="index.php"
class="btn-home">

<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:inline-block;vertical-align:middle;">
<path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
<polyline points="9 22 9 12 15 12 15 22"/>
</svg>

Accueil

</a>

</div>

</div>

</div>

</div>

<?php include '../includes/footer.php'; ?>