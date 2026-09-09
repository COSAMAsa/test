<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user'])) {
    header("Location: ../admin/index.php");
    exit;
}

$id = $_GET['id'] ?? null;

if (!$id) {
    die("ID billet manquant");
}

/* =========================================
   📋 RECUPERATION DU BILLET
========================================= */
$stmt = $pdo->prepare("
    SELECT
        b.*,
        p.type_place,
        p.numero_place,
        t.depart,
        t.destination,
        t.date_depart
    FROM billets b
    LEFT JOIN places p ON b.id_place = p.id_place
    LEFT JOIN traversees t ON b.traversee_id = t.id
    WHERE b.id = ?
");
$stmt->execute([$id]);
$billet = $stmt->fetch();

if (!$billet) {
    die("❌ Billet introuvable");
}

$total_paye = $billet['prix'] + $billet['frais_service'];

/* =========================================
   ⚠️ Adapte ces 2 lignes aux vrais noms de
   colonnes de ta table "traversees" si besoin
========================================= */
$origine     = $billet['depart']      ?? '-';
$destination = $billet['destination'] ?? '-';

$date_depart = !empty($billet['date_depart'])
    ? date('d/m/Y H:i', strtotime($billet['date_depart']))
    : '-';

$type_client_label = match($billet['type_client']) {
    'senegalais' => 'Sénégalais',
    'resident'   => 'Étranger Résident',
    default      => 'Étranger Non Résident',
};

$qr_url = "https://api.qrserver.com/v1/create-qr-code/?size=180x180&data=" . urlencode($billet['code_qr']);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Carte d'embarquement - <?= htmlspecialchars($billet['code_qr']) ?></title>
<style>

    * {
        box-sizing: border-box;
    }

    body {
        font-family: Arial, Helvetica, sans-serif;
        background: #eef1f5;
        margin: 0;
        padding: 12px;
    }

    .carte {
        max-width: 380px;
        margin: 0 auto;
        background: #ffffff;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 4px 18px rgba(0,0,0,0.15);
    }

    .entete {
        background: #ffffff;
        color: #111;
        text-align: center;
        padding: 10px 12px 6px;
        border-bottom: 2px solid #eee;
    }

    .entete h1 {
        margin: 0;
        font-size: 18px;
        font-weight: 900;
        letter-spacing: 1px;
        text-transform: uppercase;
        color: #000;
    }

    .entete p {
        margin: 6px 0 0;
        font-size: 14px;
        font-weight: 900;
        letter-spacing: 0.3px;
        color: #000;
        background: #f0f0f0;
        border-radius: 6px;
        padding: 4px 8px;
        display: inline-block;
    }

    .qr-zone {
        text-align: center;
        padding: 10px 12px 2px;
    }

    .qr-zone img {
        width: 100px;
        height: 100px;
    }

    .section-titre {
        background: #e3f2fd;
        color: #0d47a1;
        font-weight: bold;
        font-size: 11px;
        text-align: center;
        padding: 3px;
        text-transform: uppercase;
        letter-spacing: 0.4px;
    }

    .section-contenu {
        text-align: center;
        padding: 5px 12px;
        font-size: 13px;
        color: #222;
        line-height: 1.3;
    }

    .section-contenu.small {
        font-size: 11px;
        color: #555;
        padding-top: 0;
        padding-bottom: 6px;
    }

    .total {
        background: #e8f5e9;
        color: #1b5e20;
        font-weight: bold;
        font-size: 15px;
        text-align: center;
        padding: 8px;
    }

    .footer-note {
        font-size: 10px;
        color: #777;
        text-align: center;
        padding: 8px 12px;
        border-top: 1px solid #eee;
        line-height: 1.3;
    }

    .btn-print {
        display: block;
        width: 180px;
        margin: 14px auto 0;
        padding: 8px;
        background: #0d47a1;
        color: #fff;
        border: none;
        border-radius: 8px;
        font-size: 14px;
        cursor: pointer;
        text-align: center;
    }

    .btn-print:hover {
        background: #093070;
    }

    @media print {
        body {
            background: #fff;
            padding: 0;
        }
        .carte {
            box-shadow: none;
            max-width: 100%;
        }
        .no-print {
            display: none !important;
        }
    }

</style>
</head>
<body>

    <div class="carte">

        <div class="entete">
            <h1>🛂 Carte d'embarquement</h1>
            <p>Code billet : <?= htmlspecialchars($billet['code_qr']) ?></p>
        </div>

        <div class="qr-zone">
            <img src="<?= $qr_url ?>" alt="QR Code">
        </div>

        <div class="section-titre">Trajet</div>
        <div class="section-contenu">
            <?= htmlspecialchars($origine) ?> → <?= htmlspecialchars($destination) ?>
        </div>
        <div class="section-contenu small">
            Date départ : <?= $date_depart ?>
        </div>

        <div class="section-titre">Passager</div>
        <div class="section-contenu">
            <?= htmlspecialchars(trim($billet['prenom'] . ' ' . $billet['nom'])) ?>
        </div>
        <div class="section-contenu small">
            CNI : <?= htmlspecialchars($billet['cni']) ?>
        </div>

        <div class="section-titre">Détails billet</div>
        <div class="section-contenu">
            Place : <?= htmlspecialchars($billet['type_place']) ?>
            <?= !empty($billet['numero_place']) ? ' | Numéro : ' . htmlspecialchars($billet['numero_place']) : '' ?>
        </div>
        <div class="section-contenu small">
            Type : <?= ucfirst($billet['type_passager']) ?> / <?= $type_client_label ?>
        </div>

        <div class="section-titre">Paiement</div>
        <div class="section-contenu small">
            Prix billet : <?= number_format($billet['prix'], 0, ',', ' ') ?> FCFA
        </div>
        <div class="section-contenu small">
            Frais service : <?= number_format($billet['frais_service'], 0, ',', ' ') ?> FCFA
        </div>
        <div class="total">
            TOTAL PAYÉ : <?= number_format($total_paye, 0, ',', ' ') ?> FCFA
        </div>

        <div class="footer-note">
            Bon voyage et merci pour votre confiance.<br>
            Merci de vous munir de votre pièce d'identification pendant le voyage.
        </div>

    </div>

    <button class="btn-print no-print" onclick="window.print()">
        🖨️ Imprimer la carte
    </button>

</body>
</html>
