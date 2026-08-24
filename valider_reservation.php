<?php
session_start();
require_once '../config/database.php';

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
   📋 Récupération de la réservation
   (en attente, OU payée mais jamais convertie
   en billet — cas des paiements OM/Wave dont
   le webhook n'a pas déclenché la création)
========================================= */
$stmt = $pdo->prepare("
    SELECT *
    FROM reservations_attente
    WHERE reference = ?
    AND statut IN ('attente', 'payee')
");
$stmt->execute([$reference]);
$resa = $stmt->fetch();

if (!$resa) {
    header("Location: reservations_attente.php?error=" . urlencode("Réservation introuvable ou déjà traitée"));
    exit;
}

/* =========================================
   🔒 Sécurité anti-doublon : si un billet
   existe déjà pour ce code_qr, on ne recrée
   rien, on marque juste la réservation comme
   traitée et on redirige vers ce billet.
========================================= */
$stmtCheck = $pdo->prepare("
    SELECT id
    FROM billets
    WHERE code_qr = ?
    LIMIT 1
");
$stmtCheck->execute([$resa['code_qr']]);
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

/* =========================================
   ✅ Conversion en billet (même logique que
      la confirmation de paiement Orange Money)
========================================= */
try {

    $pdo->beginTransaction();

    $stmt = $pdo->prepare("
        INSERT INTO billets
        (nom, prenom, telephone, sexe, cni, id_place, traversee_id, code_qr, type_client, pays, type_passager, prix, frais_service, depart_client, mode_paiement)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $stmt->execute([
        $resa['nom'],
        $resa['prenom'],
        $resa['telephone'],
        $resa['sexe'],
        $resa['cni'],
        $resa['id_place'],
        $resa['traversee_id'],
        $resa['code_qr'],
        $resa['type_client'],
        $resa['pays'],
        $resa['type_passager'],
        $resa['prix'],
        $resa['frais_service'],
        $resa['depart_client'],
        $resa['mode_paiement']
    ]);

    $billet_id = $pdo->lastInsertId();

    $pdo->prepare("
        UPDATE places
        SET restant = restant - 1
        WHERE id_place = ?
    ")->execute([$resa['id_place']]);

    $pdo->prepare("
        UPDATE reservations_attente
        SET statut = 'traite'
        WHERE reference = ?
    ")->execute([$reference]);

    $pdo->commit();

} catch (Exception $e) {

    $pdo->rollBack();

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
?>
