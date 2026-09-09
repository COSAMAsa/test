<?php
/**
 * webhook_wave.php
 *
 * Reçoit les notifications POST de Wave (checkout.session.completed,
 * checkout.session.payment_failed) et confirme réellement le paiement.
 *
 * C'est CE fichier qui doit marquer une réservation comme "payée",
 * pas la page success_url (qui n'est qu'un affichage pour le client
 * et peut être manipulée / jamais atteinte en cas de coupure réseau).
 *
 * A enregistrer dans le portail Wave (business.wave.com -> Développeurs
 * -> Webhooks) avec l'URL :
 * https://votre-domaine/Billetterie/public/webhook_wave.php
 * en cochant checkout.session.completed et checkout.session.payment_failed.
 */

require_once '../config/database.php';
require_once '../lib/finaliser_billet.php';
$waveConfig = require '../config/wave.php';

// 1. Récupérer le corps BRUT et l'en-tête de signature
$rawBody = file_get_contents('php://input');
$waveSignature = $_SERVER['HTTP_WAVE_SIGNATURE'] ?? null;

/**
 * Vérifie la signature HMAC-SHA256 envoyée par Wave.
 * Le corps utilisé pour le calcul doit être EXACTEMENT celui reçu,
 * sans reparsing JSON (sinon la signature ne correspond plus).
 */
function verifierSignatureWave(?string $signatureHeader, string $body, string $secret): bool
{
    if (!$signatureHeader) {
        return false;
    }

    $parts = explode(',', $signatureHeader);
    $timestamp = null;
    $signatures = [];

    foreach ($parts as $part) {
        if (strpos($part, 't=') === 0) {
            $timestamp = substr($part, 2);
        } elseif (strpos($part, 'v1=') === 0) {
            $signatures[] = substr($part, 3);
        }
    }

    if (!$timestamp || empty($signatures)) {
        return false;
    }

    // Rejet si le timestamp est trop vieux (> 5 min) ou trop dans le futur (> 30 s)
    $maintenant = time();
    if (($maintenant - (int)$timestamp) > 300 || ((int)$timestamp - $maintenant) > 30) {
        error_log("Webhook Wave rejeté : timestamp hors fenêtre (t=$timestamp)");
        return false;
    }

    $calcule = hash_hmac('sha256', $timestamp . $body, $secret);

    foreach ($signatures as $sig) {
        if (hash_equals($calcule, $sig)) {
            return true;
        }
    }

    return false;
}

// 2. Vérifier la signature avant de faire quoi que ce soit
if (!verifierSignatureWave($waveSignature, $rawBody, $waveConfig['webhook_secret'])) {
    error_log("Webhook Wave : signature invalide. Header reçu: " . ($waveSignature ?? 'aucun'));
    http_response_code(401);
    echo 'Signature invalide';
    exit;
}

// 3. Parser l'événement
$event = json_decode($rawBody, true);

if (!$event || !isset($event['type'], $event['data'])) {
    http_response_code(400);
    echo 'JSON invalide';
    exit;
}

$eventId = $event['id'] ?? null;
$type = $event['type'];
$data = $event['data'];
$reference = $data['client_reference'] ?? null;

// Rien à faire si l'événement ne concerne pas une réservation connue
if (!$reference) {
    http_response_code(200);
    echo 'OK (pas de client_reference)';
    exit;
}

// 4. Idempotence : Wave peut renvoyer le même événement plusieurs fois.
//    On journalise chaque event_id traité pour ignorer les doublons.
//    Nécessite une table : webhook_events (event_id VARCHAR PRIMARY KEY, when_received DATETIME)
try {
    $stmtCheck = $pdo->prepare("SELECT 1 FROM webhook_events WHERE event_id = ?");
    $stmtCheck->execute([$eventId]);

    if ($eventId && $stmtCheck->fetch()) {
        // Déjà traité, on répond OK sans rien refaire
        http_response_code(200);
        echo 'OK (déjà traité)';
        exit;
    }
} catch (\PDOException $e) {
    // Si la table webhook_events n'existe pas encore, on log et on continue
    // sans bloquer le traitement (à créer avant la mise en prod).
    error_log("Table webhook_events indisponible : " . $e->getMessage());
}

// 5. Traiter selon le type d'événement
if ($type === 'checkout.session.completed') {

    $stmt = $pdo->prepare("SELECT * FROM reservations_attente WHERE reference = ?");
    $stmt->execute([$reference]);
    $resa = $stmt->fetch();

    if ($resa && $resa['statut'] !== 'traite') {

        try {
            $resultat = finaliserBillet($pdo, $reference);

            if (!$resultat['ok']) {
                error_log("finaliserBillet a échoué (webhook) pour référence : " . $reference);
            }
        } catch (\Exception $e) {
            error_log("Erreur traitement webhook Wave (completed) : " . $e->getMessage());
            http_response_code(500);
            echo 'Erreur interne';
            exit;
        }
    }

} elseif ($type === 'checkout.session.payment_failed') {

    $stmt = $pdo->prepare("SELECT * FROM reservations_attente WHERE reference = ?");
    $stmt->execute([$reference]);
    $resa = $stmt->fetch();

    if ($resa && $resa['statut'] === 'attente') {
        $stmt = $pdo->prepare("UPDATE reservations_attente SET statut = 'echec' WHERE reference = ?");
        $stmt->execute([$reference]);
    }

    $raison = $data['last_payment_error']['message'] ?? 'inconnue';
    error_log("Paiement Wave échoué pour {$reference} : {$raison}");
}

// 6. Journaliser l'event_id pour l'idempotence future
if ($eventId) {
    try {
        $stmt = $pdo->prepare("INSERT INTO webhook_events (event_id, when_received) VALUES (?, NOW())");
        $stmt->execute([$eventId]);
    } catch (\PDOException $e) {
        error_log("Impossible d'enregistrer webhook_events : " . $e->getMessage());
    }
}

// 7. Toujours répondre 2xx rapidement, sinon Wave réessaiera pendant 3 jours
http_response_code(200);
echo 'OK';
