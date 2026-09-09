<?php
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => '',
    'secure' => true,
    'httponly' => true,
    'samesite' => 'None'
]);

session_start();

if(!isset($_SESSION['paiement'])){
    die("Session expirée");
}

$data = $_SESSION['paiement'];
?>

<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Paiement Orange Money</title>

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

<style>

*{
    margin:0;
    padding:0;
    box-sizing:border-box;
}

body{
    font-family:'Poppins',sans-serif;
    background:linear-gradient(135deg,#ff7900,#ff9f45);
    min-height:100vh;
    display:flex;
    justify-content:center;
    align-items:center;
    padding:20px;
}

.container{
    width:100%;
    max-width:550px;
}

.card{
    background:#fff;
    border-radius:20px;
    padding:40px;
    text-align:center;
    box-shadow:0 20px 40px rgba(0,0,0,0.15);
}

.logo{
    width:90px;
    margin-bottom:15px;
}

.title{
    color:#333;
    font-size:28px;
    font-weight:700;
    margin-bottom:10px;
}

.subtitle{
    color:#777;
    font-size:14px;
    margin-bottom:30px;
}

.amount-box{
    background:#fff7f0;
    border:2px solid #ff7900;
    border-radius:15px;
    padding:15px;
    margin-bottom:25px;
}

.amount-label{
    color:#777;
    font-size:14px;
}

.amount{
    color:#ff7900;
    font-size:32px;
    font-weight:700;
}

.qr-container{
    background:#fafafa;
    border:1px solid #eee;
    border-radius:15px;
    padding:20px;
    margin-bottom:25px;
}

.qr-container img{
    max-width:250px;
}

.instructions{
    text-align:left;
    background:#f8f9fa;
    border-radius:12px;
    padding:15px;
    margin-bottom:25px;
}

.instructions h4{
    margin-bottom:10px;
    color:#333;
}

.instructions ul{
    padding-left:20px;
    color:#666;
    font-size:14px;
}

.instructions li{
    margin-bottom:8px;
}

.pay-btn{
    display:inline-block;
    width:100%;
    background:#ff7900;
    color:#fff;
    text-decoration:none;
    padding:16px;
    border-radius:12px;
    font-size:18px;
    font-weight:600;
    transition:.3s;
}

.pay-btn:hover{
    background:#e66d00;
    transform:translateY(-2px);
}

.secure{
    margin-top:20px;
    color:#777;
    font-size:13px;
}

.badge{
    display:inline-block;
    background:#28a745;
    color:white;
    padding:5px 12px;
    border-radius:30px;
    font-size:12px;
    margin-bottom:15px;
}

.footer{
    margin-top:25px;
    font-size:12px;
    color:#999;
}

@media(max-width:600px){

    .card{
        padding:25px;
    }

    .amount{
        font-size:26px;
    }

    .title{
        font-size:22px;
    }

    .qr-container img{
        width:100%;
        max-width:220px;
    }
}

</style>

</head>
<body>

<div class="container">

    <div class="card">

        <a href="index.php" class="back-btn">
            ⬅️ Retour
        </a>


        <div class="badge">
            Paiement Sécurisé
        </div>

        <h1 class="title">
            Orange Money
        </h1>

        <p class="subtitle">
            Scannez le QR Code ou cliquez sur le bouton ci-dessous pour effectuer votre paiement.
        </p>

        <div class="amount-box">
            <div class="amount-label">
                Montant à payer
            </div>

            <div class="amount">
                <?= number_format($data['total'],0,' ',' ') ?> FCFA
            </div>
        </div>

        <div class="qr-container">

            <img
                src="data:image/png;base64,<?= $data['qrCode'] ?>"
                alt="QR Code Paiement">

        </div>

        <div class="instructions">
            <h4>Instructions</h4>

            <ul>
                <li>Ouvrez votre application Orange Money.</li>
                <li>Scannez le QR Code affiché.</li>
                <li>Confirmez le paiement sur votre téléphone.</li>
                <li>Attendez la validation automatique de la transaction.</li>
            </ul>
        </div>

        <a
            href="<?= htmlspecialchars($data['deepLink']) ?>"
            target="_blank"
            class="pay-btn">

            📱 Payer avec Orange Money

        </a>

        <div class="secure">
            🔒 Transaction sécurisée et cryptée
        </div>

        <div class="footer">
            © <?= date('Y') ?> - Plateforme de Paiement
        </div>

    </div>

</div>

</body>
</html>
