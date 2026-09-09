<?php
session_start();
require_once '../config/database.php';
require_once '../lib/finaliser_billet.php';

if (!isset($_SESSION['user'])) {
    header("Location: index.php");
    exit;
}

/* =========================================
   🔒 On n'accepte que du POST pour cette action
========================================= */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: reservations_attente.php?error=" . urlencode("Méthode non autorisée"));
    exit;
}

$reference = trim($_POST['reference'] ?? '');

if ($reference === '') {
    header("Location: reservations_attente.php?error=" . urlencode("Référence manquante"));
    exit;
}

/* =========================================
   🔒 Sécurité anti-doublon : si un billet
   existe déjà pour cette réservation (via son
   code_qr), on ne recrée rien, on marque juste
   la réservation comme traitée et on redirige
   vers ce billet.
========================================= */
$stmtResa = $pdo->prepare("
    SELECT code_qr
    FROM reservations_attente
    WHERE reference = ?
");
$stmtResa->execute([$reference]);
$resaRef = $stmtResa->fetch();

if ($resaRef) {

    $stmtCheck = $pdo->prepare("
        SELECT id
        FROM billets
        WHERE code_qr = ?
        LIMIT 1
    ");
    $stmtCheck->execute([$resaRef['code_qr']]);
    $billetExistant = $stmtCheck->fetch();

    if ($billetExistant) {

        $pdo->prepare("
            UPDATE reservations_attente
            SET statut = 'traite'
            WHERE reference = ?
        ")->execute([$reference]);

        header("Location: billets_emis.php?id=" . $billetExistant['id']);
        exit;
    }
}

/* =========================================
   ✅ Conversion en billet — passe désormais
   par finaliserBillet(), la SEULE fonction
   qui doit créer un billet et décrémenter
   restant. Ne jamais dupliquer cette logique
   ailleurs : c'est ce qui a causé le bug de
   double décrémentation des places.
========================================= */
try {

    $resultat = finaliserBillet($pdo, $reference);

    if (!$resultat['ok']) {
        header("Location: reservations_attente.php?error=" . urlencode("Réservation introuvable ou déjà traitée"));
        exit;
    }

    $billet_id = $resultat['billet_id'];

} catch (Exception $e) {

    header("Location: reservations_attente.php?error=" . urlencode(
        "Erreur lors de la validation de la réservation : " . $e->getMessage()
    ));
    exit;
}

/* =========================================
   ➡️ Redirection vers le billet créé
========================================= */
header("Location: billets_emis.php?id=" . $billet_id);
exit;
