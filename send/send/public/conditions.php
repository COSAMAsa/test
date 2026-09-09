<?php
session_start();

$id = $_GET['id'] ?? null;

if(!$id){
    die("❌ Voyage introuvable");
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Conditions de vente - COSAMA</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

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

    position:relative;
}

/* HEADER */

.hero{

    background:linear-gradient(160deg,var(--navy), var(--royal));

    padding:22px 20px 35px;

    border-radius:0 0 35px 35px;

    position:sticky;
    top:0;

    z-index:1000;

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

.brand-box{

    display:flex;
    align-items:center;
    gap:12px;
}

.brand-box img{

    width:58px;
    height:58px;

    background:#fff;

    border-radius:16px;

    object-fit:contain;

    padding:5px;
}

.brand-title{

    font-family:'Cormorant Garamond', serif;

    font-size:30px;

    font-weight:700;

    color:#fff;
}

.brand-sub{

    color:rgba(255,255,255,.65);

    font-size:11px;

    letter-spacing:2px;

    text-transform:uppercase;
}

.back-btn{

    width:44px;
    height:44px;

    border-radius:50%;

    background:rgba(255,255,255,.10);

    color:#fff;

    display:flex;
    align-items:center;
    justify-content:center;

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

    margin-bottom:5px;
}

.hero-content h1{

    font-family:'Cormorant Garamond', serif;

    color:#fff;

    font-size:38px;

    line-height:1.1;
}

.hero-content h1 span{

    color:var(--gold);
}

/* CARD */

.conditions-card{

    background:#fff;

    margin:20px 16px;

    border-radius:30px;

    overflow:hidden;

    box-shadow:0 10px 30px rgba(0,0,0,.10);
}

.card-header-modern{

    background:linear-gradient(135deg,var(--navy),var(--royal));

    padding:20px;

    color:#fff;
}

.card-header-modern h3{

    font-family:'Cormorant Garamond', serif;

    font-size:30px;

    margin:0;
}

.card-header-modern p{

    margin-top:5px;

    font-size:13px;

    opacity:.75;
}

.conditions-body{

    padding:20px;
}

/* CONDITIONS */

.conditions{

    background:#f6fbff;

    border-radius:20px;

    padding:20px;

    font-size:15px;

    line-height:1.8;

    color:#081827;

    border:1px solid rgba(0,0,0,.05);
}

.conditions strong{

    color:#0d5b8c;

    font-size:16px;
}

.conditions ul{

    padding-left:20px;
}

.conditions li{

    margin-bottom:8px;
}

/* CHECKBOX */

.accept-box{

    margin-top:20px;

    background:#eef7fd;

    padding:18px;

    border-radius:18px;

    display:flex;
    align-items:center;
    gap:15px;
}

.form-check-input{

    width:28px;
    height:28px;

    cursor:pointer;
}

.accept-text{

    font-size:14px;

    font-weight:600;

    color:#081827;
}

/* BUTTONS */

.btn-modern{

    width:100%;

    height:60px;

    border:none;

    border-radius:18px;

    font-size:17px;

    font-weight:700;

    margin-top:18px;

    transition:.3s;
}

.btn-buy{

    background:linear-gradient(135deg,var(--ocean),var(--azure));

    color:#fff;

    box-shadow:0 10px 25px rgba(0,0,0,.18);
}

.btn-buy:disabled{

    opacity:.5;
}

.btn-cancel{

    background:#e5e7eb;

    color:#111827;

    text-decoration:none;

    display:flex;
    align-items:center;
    justify-content:center;
}

/* SCROLLBAR */

.conditions::-webkit-scrollbar{
    width:8px;
}

.conditions::-webkit-scrollbar-thumb{

    background:var(--ocean);

    border-radius:10px;
}

/* MOBILE */

@media(max-width:480px){

    .hero-content h1{
        font-size:32px;
    }

    .conditions{
        font-size:14px;
    }

    .card-header-modern h3{
        font-size:26px;
    }
}

  .conditions-intro {
    background: linear-gradient(135deg, var(--navy), var(--royal));
    border-radius: 16px;
    padding: 16px 18px;
    margin-bottom: 16px;
    color: white;
    font-size: 12px;
    line-height: 1.6;
    display: flex;
    gap: 10px;
    align-items: flex-start;
  }
  .conditions-intro .icon { font-size: 20px; flex-shrink: 0; margin-top: 1px; }
  .conditions-intro strong { display: block; margin-bottom: 4px; font-family: 'Cormorant Garamond', serif; font-size: 15px; }
  .accept-row p { font-size: 12px; color: var(--text-mid); font-weight: 500; }

    .btn-continue {
    width: 100%;
    background: linear-gradient(135deg, var(--ocean), var(--azure));
    border: none;
    border-radius: 14px;
    padding: 16px;
    font-family: 'DM Sans', sans-serif;
    font-size: 14px;
    font-weight: 600;
    color: white;
    cursor: pointer;
    letter-spacing: 0.04em;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    box-shadow: 0 6px 20px rgba(30,91,173,0.35);
    margin-bottom: 10px;
  }
  .btn-cancel {
    width: 100%;
    background: white;
    border: 1px solid var(--border);
    border-radius: 14px;
    padding: 14px;
    font-family: 'DM Sans', sans-serif;
    font-size: 14px;
    font-weight: 500;
    color: var(--text-mid);
    cursor: pointer;
  }

  .article-card {
    background: white;
    border-radius: 14px;
    padding: 14px 16px;
    margin-bottom: 0px;
    border: 1px solid var(--border);
    box-shadow: var(--shadow-sm);
  }
  .article-card .art-title {
    font-weight: 600;
    font-size: 13px;
    color: var(--navy);
    margin-bottom: 8px;
    display: flex;
    align-items: center;
    gap: 8px;
  }
  .article-card .art-title .num {
    width: 22px; height: 22px;
    background: linear-gradient(135deg, var(--ocean), var(--azure));
    border-radius: 6px;
    display: flex; align-items: center; justify-content: center;
    font-size: 10px;
    font-weight: 700;
    color: white;
    flex-shrink: 0;
  }
  .article-card p {
    font-size: 11.5px;
    color: var(--text-mid);
    line-height: 1.65;
  }
  .article-card ul {
    list-style: none;
    margin-top: 6px;
  }
  .article-card ul li {
    font-size: 11px;
    color: var(--text-mid);
    padding: 3px 0;
    padding-left: 14px;
    position: relative;
  }
  .article-card ul li::before {
    content: '›';
    position: absolute;
    left: 0;
    color: var(--ocean);
    font-weight: 700;
  }
</style>

</head>

<body>

<div class="main-app">

    <!-- HERO -->

    <div class="hero">

        <div class="topbar">

            <div class="brand-box">

                
        <div class="hero-content">

         

             <h2 style="color:white;">
<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:inline-block;vertical-align:middle;">
<path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/>
<rect x="8" y="2" width="8" height="4" rx="1" ry="1"/>
</svg>

Conditions de vente
</h2>

        </div>
                

            </div>

            <a href="index.php" class="back-btn">
                ←
            </a>

        </div>



    </div>

    <!-- CARD -->

    <div class="conditions-card">

        <div class="conditions-intro">

<div class="icon">

<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:inline-block;vertical-align:middle;">
<path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
<line x1="12" y1="9" x2="12" y2="13"/>
<line x1="12" y1="17" x2="12.01" y2="17"/>
</svg>

</div>

<div>

<strong>
À lire attentivement
</strong>

Veuillez prendre connaissance des conditions avant de procéder à votre réservation.

</div>

</div>
        <div class="conditions-body">

         

<!-- CONDITIONS -->

<div class="article-card">

<div class="art-title">

<div class="num">
1
</div>

Caractère non remboursable des billets

</div>

<p>
Tout billet acheté en ligne est strictement non remboursable. En validant votre achat, vous acceptez cette condition sans réserve.
</p>

</div>

<div class="article-card">
  <div class="art-title">
    <div class="num">2</div>
    Vérification préalable des informations
  </div>

  <p>
    Avant de procéder au paiement, vous devez vérifier attentivement toutes les informations saisies, notamment vos données personnelles et celles de votre pièce d’identité.
    Vous avez la possibilité de corriger ces informations avant la validation finale.
    Une fois l’achat validé, aucune modification ne sera possible.
  </p>
</div>

<div class="article-card">

  <div class="art-title">
    <div class="num">3</div>
    Pièces d’identité requises
  </div>

  <ul>
    <li>Résidents : Passeport, carte nationale d’identité, carte scolaire en cours de validité, extrait de naissance pour les enfants</li>
    <li>Hôtes résidents : Carte consulaire, passeport</li>
    <li>Hôtes non-résidents : Passeport en cours de validité</li>
  </ul>

  <p>
    Tout passager ne présentant pas la pièce d’identité correspondant au billet acheté se verra refuser l’accès à la gare maritime.
  </p>

</div>

<div class="article-card">

  <div class="art-title">
    <div class="num">4</div>
    Exactitude des données saisies
  </div>

  <p>
    Le numéro de la pièce d’identité doit être saisi avec exactitude. 
    Toute erreur ou incohérence entraînera l’invalidité du billet, sans possibilité de remboursement.
  </p>

</div>

<div class="article-card">

  <div class="art-title">
    <div class="num">5</div>
    Documents à présenter lors de l’embarquement
  </div>

  <ul>
    <li>Le billet électronique délivré après l’achat en ligne</li>
    <li>La pièce d’identité utilisée lors de l’achat</li>
  </ul>

  <p>
    Lors du check-in, une carte d’embarquement mentionnant le numéro de siège sera remise au passager.
  </p>

</div>

<div class="article-card">

  <div class="art-title">
    <div class="num">6</div>
    Acceptation des conditions
  </div>

  <p>
    En validant votre achat en ligne, vous reconnaissez avoir lu, compris et accepté l’ensemble des présentes conditions.
  </p>

</div>

<div class="article-card">

  <div class="art-title">
    <div class="num">7</div>
    Validité du billet et conditions de report
  </div>

  <p>
    Le billet est valable pour une durée de vingt-et-un (21) jours à compter de la date initiale du voyage.
    Durant cette période, le passager peut demander un report unique, sous réserve de disponibilité, sans pénalité ni perte sur le montant du billet.
  </p>

  <p><strong>Conditions :</strong></p>

  <ul>
    <li>Le report est autorisé une seule fois</li>
    <li>Il doit être effectué en présentiel uniquement dans les agences GMD, GMZ ou Carabane</li>
  </ul>

  <p>
    Au-delà de ce délai ou après utilisation du report, le billet devient caduc.
  </p>

</div>

<div class="article-card">

  <div class="art-title">
    <div class="num">8</div>
    Conditions de remboursement exceptionnelles
  </div>

  <ul>
    <li>Demande formelle adressée à la Direction Générale</li>
    <li>Incapacité avérée de voyager couvrant :
      <ul>
        <li>Le jour du voyage initial</li>
        <li>Toute la période de validité (21 jours)</li>
      </ul>
    </li>
  </ul>

  <p>
    Cette incapacité doit être justifiée par des documents probants.
  </p>

  <p><strong>Modalités :</strong></p>

  <ul>
    <li>Le remboursement, s’il est validé, est effectué exclusivement au guichet unique dédié</li>
    <li>Aucun remboursement ne sera effectué en ligne ou par un autre canal</li>
  </ul>

</div>

<div class="article-card">

  <div class="art-title">
    <div class="num">9</div>
    Structure du prix du billet en ligne
  </div>

  <ul>
    <li>Le prix de base du billet</li>
    <li>Des frais de service fixes de 500 FCFA</li>
  </ul>

  <p>
    Ces frais sont non remboursables, même en cas de remboursement exceptionnel.
  </p>

</div>

<div class="article-card">

  <div class="art-title">
    <div class="num">10</div>
    Clause antifraude et contrôle
  </div>

  <ul>
    <li>Annulation immédiate du billet</li>
    <li>Refus d’embarquement</li>
    <li>Poursuites conformément à la réglementation en vigueur</li>
  </ul>

  <p>
    La structure se réserve le droit de bloquer toute transaction suspecte et de procéder à des vérifications complémentaires.
  </p>

</div>

<div class="article-card">

  <div class="art-title">
    <div class="num">11</div>
    Traçabilité et preuve des transactions
  </div>

  <ul>
    <li>Horodatage des transactions</li>
    <li>Identifiants de paiement</li>
    <li>Données de réservation</li>
    <li>Journaux techniques (logs)</li>
  </ul>

  <p>
    Ces éléments constituent preuve des transactions en cas de litige.
  </p>

</div>

<div class="article-card">

  <div class="art-title">
    <div class="num">12</div>
    Responsabilité
  </div>

  <ul>
    <li>Exactitude des informations fournies</li>
    <li>Conservation du billet électronique</li>
    <li>Présentation des documents requis</li>
  </ul>

  <p>La structure ne saurait être tenue responsable :</p>

  <ul>
    <li>Des erreurs de saisie imputables à l’utilisateur</li>
    <li>Des indisponibilités temporaires techniques ou réseau</li>
    <li>Des défaillances du partenaire de paiement</li>
  </ul>

</div>

<div class="article-card">

  <div class="art-title">
    <div class="num">13</div>
    Disponibilité du service
  </div>

  <p>
    La plateforme est accessible en continu, sauf maintenance ou cas de force majeure.
    La structure met en œuvre les moyens nécessaires sans obligation de résultat.
  </p>

</div>
            </div>

            <!-- ACCEPT -->

            <form method="POST">

                <div class="accept-box">

                    <input
                    type="checkbox"
                    id="accept"
                    class="form-check-input"
                    disabled>

                    <div class="accept-text">

                        J’ai lu et accepté les conditions générales de vente COSAMA.

                    </div>

                </div>

            <button
type="submit"
class="btn-continue"
id="btnContinue"
disabled>

Continuer vers l'achat →

</button>

</form>

<button
onclick="window.location.href='index.php'"
class="btn-cancel">

← Annuler

</button>
        </div>

    </div>

</div>

<script>

// éléments
const box = document.getElementById("conditionsBox");
const checkbox = document.getElementById("accept");
const btn = document.getElementById("btnContinue");

// activation checkbox en bas
// activation checkbox en bas de page
window.addEventListener("scroll", function(){

    const scrollTop =
        window.scrollY;

    const windowHeight =
        window.innerHeight;

    const documentHeight =
        document.documentElement.scrollHeight;

    // utilisateur arrivé en bas
    if(scrollTop + windowHeight >= documentHeight - 20){

        checkbox.disabled = false;
    }
});
// bouton actif si accepté
checkbox.addEventListener("change", function(){

    btn.disabled = !this.checked;
});

// redirection
document.querySelector("form").addEventListener("submit", function(e){

    e.preventDefault();

    window.location.href = "acheter.php?id=<?= $id ?>";
});

</script>

</body>
</html>