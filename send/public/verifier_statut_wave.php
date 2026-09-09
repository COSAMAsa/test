<?php
/**
 * verifier_statut_wave.php
 *
 * Endpoint JSON interrogé en AJAX par paiement_attente.php.
 *
 * Stratégie à deux niveaux :
 * 1) On regarde d'abord le statut en base (mis à jour par webhook_wave.php)
 *    -> c'est la source la plus fiable en production.
 * 2) Si toujours "attente" après quelques secondes, on interroge
 *    DIRECTEMENT l'API Wave (GET /v1/checkout/sessions/:id) en secours,
 *    utile si le webhook n'est pas encore configuré/arrivé (cas fréquent
 *    en test avec ngrok). Si l'API confirme le paiement, on met à jour
 *    la base nous-mêmes (même logique que dans le webhook).
 */

session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

$reference = $_GET['ref'] ?? null;

if (!$reference) {
    http_response_code(400);
    echo json_encode(['error' => 'reference manquante']);
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM reservations_attente WHERE reference = ?");
$stmt->execute([$reference]);
$resa = $stmt->fetch();

if (!$resa) {
    http_response_code(404);
    echo json_encode(['statut' => 'introuvable']);
    exit;
}

// 1) Déjà confirmé par le webhook -> on répond directement
if ($resa['statut'] === 'payee' || $resa['statut'] === 'echec') {
    echo json_encode(['statut' => $resa['statut'], 'source' => 'webhook']);
    exit;
}

// 2) Toujours "attente" -> on vérifie directement auprès de Wave
$waveConfig = require '../config/wave.php';
$checkoutId = $resa['wave_checkout_id'] ?? null;

if (!$checkoutId) {
    // Pas d'ID de session Wave en session (ex: si l'utilisateur a rechargé
    // la page depuis un autre appareil) -> on ne peut pas vérifier directement,
    // on retombe sur l'attente du webhook
    echo json_encode(['statut' => 'attente', 'source' => 'aucune_verification_directe']);
    exit;
}

$apiKey = trim($waveConfig['api_key']);
$signingSecret = trim($waveConfig['signing_secret']);

// Signature requise même pour un GET (corps vide -> payload = timestamp seul)
$timestamp = time();
$signature = hash_hmac('sha256', $timestamp . '', $signingSecret);
$waveSignatureHeader = "t={$timestamp},v1={$signature}";

$ch = curl_init("https://api.wave.com/v1/checkout/sessions/{$checkoutId}");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer {$apiKey}",
    "Wave-Signature: {$waveSignatureHeader}",
]);

$reponse = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200) {
    error_log("Vérification directe Wave échouée (HTTP $httpCode) : " . $reponse);
    echo json_encode(['statut' => 'attente', 'source' => 'erreur_api_wave']);
    exit;
}

$session = json_decode($reponse, true);
$paymentStatus = $session['payment_status'] ?? null; // 'processing' | 'cancelled' | 'succeeded'

if ($paymentStatus === 'succeeded') {

    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare("UPDATE reservations_attente SET statut = 'payee' WHERE reference = ? AND statut != 'payee'");
        $stmt->execute([$reference]);

        $stmt = $pdo->prepare("UPDATE places SET restant = restant - 1 WHERE id_place = ? AND restant > 0");
        $stmt->execute([$resa['id_place']]);

        // TODO : génération du billet définitif, comme dans webhook_wave.php

        $pdo->commit();
    } catch (\Exception $e) {
        $pdo->rollBack();
        error_log("Erreur mise à jour statut (vérif directe) : " . $e->getMessage());
    }

    echo json_encode(['statut' => 'payee', 'source' => 'verification_directe']);
    exit;

} elseif ($paymentStatus === 'cancelled') {

    $stmt = $pdo->prepare("UPDATE reservations_attente SET statut = 'echec' WHERE reference = ? AND statut = 'attente'");
    $stmt->execute([$reference]);

    echo json_encode(['statut' => 'echec', 'source' => 'verification_directe']);
    exit;
}

// Toujours en cours de traitement côté Wave ('processing')
echo json_encode(['statut' => 'attente', 'source' => 'verification_directe']);
