<?php
echo "<!-- SESSION ID: " . session_id() . " | PAIEMENT EXISTE: " . (isset($_SESSION['paiement']) ? 'OUI' : 'NON') . " -->";
session_start();

/*
=========================================================
CALLBACK ANNULATION - Orange Money eWallet QR Code
=========================================================
Cette page est appelée automatiquement par les serveurs
Orange Sonatel si le client annule le paiement ou si la
transaction échoue/expire.
=========================================================
*/

// Log brut pour debug
$raw_input = file_get_contents('php://input');
error_log("=== CALLBACK ANNULATION RECU ===");
error_log("METHOD: " . $_SERVER['REQUEST_METHOD']);
error_log("GET: " . print_r($_GET, true));
error_log("POST: " . print_r($_POST, true));
error_log("RAW BODY: " . $raw_input);

$_SESSION['paiement_statut'] = 'annule';

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Paiement annulé</title>
    <style>
        body {
            font-family: 'DM Sans', sans-serif;
            background: #f3f8fd;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            margin: 0;
        }
        .box {
            background: white;
            padding: 40px 30px;
            border-radius: 24px;
            box-shadow: 0 10px 30px rgba(0,0,0,.1);
            text-align: center;
            max-width: 400px;
        }
        .icon {
            width: 70px;
            height: 70px;
            background: #ef4444;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            color: white;
            font-size: 32px;
        }
        h1 { color: #081827; font-size: 22px; margin-bottom: 10px; }
        p { color: #555; font-size: 14px; }
        a {
            display: inline-block;
            margin-top: 20px;
            padding: 14px 28px;
            background: #081827;
            color: white;
            text-decoration: none;
            border-radius: 14px;
            font-weight: 700;
        }
    </style>
</head>
<body>
    <div class="box">
        <div class="icon">✕</div>
        <h1>Paiement annulé</h1>
        <p>Votre paiement n'a pas été finalisé. Vous pouvez réessayer.</p>
        <a href="index.php">Retour à l'accueil</a>
    </div>
</body>
</html>
