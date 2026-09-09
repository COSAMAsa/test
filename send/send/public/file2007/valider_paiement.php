<?php
require_once '../config/database.php';
require_once '../lib/finaliser_billet.php';

// OM utilise 'orderId', Wave utilise 'ref' -> on supporte les deux
$reference = $_GET['orderId'] ?? $_POST['orderId'] ?? $_GET['ref'] ?? $_POST['ref'] ?? null;
$modeWave = isset($_GET['ref']) || isset($_POST['ref']);

if (!$reference) {
    die("Référence de paiement manquante");
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

    $stmt = $pdo->prepare("SELECT * FROM reservations_attente WHERE reference = ?");
    $stmt->execute([$reference]);
    $resa = $stmt->fetch();

    if (!$resa) {
        die("Réservation introuvable");
    }

    // Billet déjà généré (par le webhook ou un précédent appel)
    if ($resa['statut'] === 'traite') {
        $stmtBillet = $pdo->prepare("SELECT id FROM billets WHERE code_qr = ?");
        $stmtBillet->execute([$reference]);
        $billet = $stmtBillet->fetch();
        if ($billet) {
            header("Location: billet.php?id=" . $billet['id']);
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
            header("Location: billet.php?id=" . $resultat['billet_id']);
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
            header("Location: billet.php?id=" . $resultat['billet_id']);
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
       RETOUR ORANGE MONEY (comportement d'origine, inchangé)
    ========================= */

    $resultat = finaliserBillet($pdo, $reference);

    if (!$resultat['ok']) {
        die("Réservation introuvable ou déjà traitée");
    }

    header("Location: billet.php?id=" . $resultat['billet_id']);
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
