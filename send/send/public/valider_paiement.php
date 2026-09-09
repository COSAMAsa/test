<?php
require_once '../config/database.php';
require_once '../lib/finaliser_billet.php';

// OM utilise 'orderId', Wave utilise 'ref' -> on supporte les deux
$reference = $_GET['orderId'] ?? $_POST['orderId'] ?? $_GET['ref'] ?? $_POST['ref'] ?? null;
$modeWave = isset($_GET['ref']) || isset($_POST['ref']);

if (!$reference) {
    die("Référence de paiement manquante");
}

/* =========================================
   🔒 Garde-fou commun aux deux flux (OM et Wave) :
   - la réservation doit exister
   - si elle est encore en 'attente', elle ne doit pas
     avoir dépassé son délai d'expiration
   Ceci bloque un lien copié/rejoué trop tard, quel que
   soit le prestataire de paiement.
========================================= */
$stmtResaCommun = $pdo->prepare("SELECT * FROM reservations_attente WHERE reference = ?");
$stmtResaCommun->execute([$reference]);
$resaCommun = $stmtResaCommun->fetch();

if (!$resaCommun) {
    die("Réservation introuvable");
}

if ($resaCommun['statut'] === 'attente'
    && !empty($resaCommun['expire_a'])
    && strtotime($resaCommun['expire_a']) < time()) {
    marquerEchecPaiement($pdo, $reference);
    die("⏱️ Le délai de paiement a expiré. Merci de refaire votre réservation.");
}

if ($modeWave) {

    /* =========================
       RETOUR WAVE
       - statut 'traite'  : billet déjà généré -> on redirige
       - statut 'echec'   : paiement refusé/annulé
       - statut 'payée'   : webhook a déjà confirmé le paiement, mais le
                            billet n'est pas encore créé -> on finalise
                            directement, sans rappeler l'API Wave
       - statut 'attente' : ni le webhook ni cette page n'ont encore
                            confirmé -> on vérifie directement auprès
                            de l'API Wave
    ========================= */

    session_start();
    $waveConfig = require '../config/wave.php';
    $checkoutId = $_SESSION['paiement']['wave_checkout_id'] ?? null;

    $resa = $resaCommun;

    // Billet déjà généré (par le webhook ou un précédent appel)
    if ($resa['statut'] === 'traite') {
        $stmtBillet = $pdo->prepare("SELECT id, token_acces FROM billets WHERE code_qr = ?");
        $stmtBillet->execute([$reference]);
        $billet = $stmtBillet->fetch();
        if ($billet) {
            header("Location: billet.php?token=" . urlencode($billet['token_acces']));
            exit;
        }
        die("Réservation introuvable ou déjà traitée");
    }

    if ($resa['statut'] === 'echec') {
        die("❌ Le paiement Wave n'a pas abouti. Aucun billet n'a été généré.");
    }

    // Le webhook a déjà confirmé le paiement -> on finalise directement
    if ($resa['statut'] === 'payee') {
        $resultat = finaliserBillet($pdo, $reference);
        if ($resultat['ok']) {
            header("Location: billet.php?token=" . urlencode($resultat['billet_token']));
            exit;
        }
        die("Réservation introuvable ou déjà traitée");
    }

    // Ici, statut === 'attente' : ni webhook ni page n'ont confirmé
    if (!$checkoutId) {
        // Session perdue (autre appareil, cookies vidés...) -> on ne peut
        // pas vérifier directement, on attend le webhook
        afficherAttenteWebhook($reference);
        exit;
    }

    $apiKey = trim($waveConfig['api_key']);
    $signingSecret = trim($waveConfig['signing_secret']);
    $timestamp = time();
    $signature = hash_hmac('sha256', $timestamp . '', $signingSecret);

    $ch = curl_init("https://api.wave.com/v1/checkout/sessions/{$checkoutId}");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer {$apiKey}",
        "Wave-Signature: t={$timestamp},v1={$signature}",
    ]);
    $reponse = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        error_log("valider_paiement.php (Wave) : erreur API Wave (HTTP $httpCode) : $reponse");
        afficherAttenteWebhook($reference);
        exit;
    }

    $session = json_decode($reponse, true);
    $paymentStatus = $session['payment_status'] ?? null;

    if ($paymentStatus === 'succeeded') {
        $resultat = finaliserBillet($pdo, $reference);
        if ($resultat['ok']) {
            header("Location: billet.php?token=" . urlencode($resultat['billet_token']));
            exit;
        }
        die("Réservation introuvable ou déjà traitée");
    }

    if ($paymentStatus === 'cancelled') {
        marquerEchecPaiement($pdo, $reference);
        die("❌ Le paiement Wave a été annulé ou refusé.");
    }

    // 'processing' : le paiement n'est pas encore confirmé côté Wave
    afficherAttenteWebhook($reference);
    exit;

} else {

    /* =========================
       RETOUR ORANGE MONEY
       🔒 Avant : aucune vérification avant finaliserBillet().
       Maintenant : on s'appuie sur $resaCommun (existence + expiration
       déjà vérifiées plus haut) avant de finaliser, et on redirige
       par token opaque au lieu d'un id incrémental.
    ========================= */

    if ($resaCommun['statut'] === 'traite') {
        $stmtBillet = $pdo->prepare("SELECT id, token_acces FROM billets WHERE code_qr = ?");
        $stmtBillet->execute([$reference]);
        $billet = $stmtBillet->fetch();
        if ($billet) {
            header("Location: billet.php?token=" . urlencode($billet['token_acces']));
            exit;
        }
        die("Réservation introuvable ou déjà traitée");
    }

    if ($resaCommun['statut'] === 'echec') {
        die("❌ Le paiement n'a pas abouti. Aucun billet n'a été généré.");
    }

    $resultat = finaliserBillet($pdo, $reference);

    if (!$resultat['ok']) {
        die("Réservation introuvable ou déjà traitée");
    }

    header("Location: billet.php?token=" . urlencode($resultat['billet_token']));
    exit;
}

/**
 * Page d'attente minimale affichée quand le paiement Wave est encore
 * 'processing' ou non vérifiable directement. Réessaie toutes les 3s.
 */
function afficherAttenteWebhook(string $reference): void
{
    header('Content-Type: text/html; charset=utf-8');
    echo '<!DOCTYPE html><html lang="fr"><head><meta charset="utf-8">
    <meta http-equiv="refresh" content="3">
    <title>Confirmation en cours...</title></head>
    <body style="font-family:sans-serif;text-align:center;padding:60px 20px;">
    <h2>Confirmation du paiement en cours...</h2>
    <p>Merci de patienter, la page se rafraîchit automatiquement.</p>
    <p style="color:#888;">Référence : ' . htmlspecialchars($reference) . '</p>
    </body></html>';
}
