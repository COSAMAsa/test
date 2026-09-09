<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

/* =========================================
   🔒 On n'accepte que du POST pour cette action
========================================= */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: reservations.php?error=" . urlencode("Méthode non autorisée"));
    exit;
}

$reference = trim($_POST['reference'] ?? '');

if ($reference === '') {
    header("Location: reservations.php?error=" . urlencode("Référence manquante"));
    exit;
}

/* =========================================
   📋 Récupération de la réservation en attente
========================================= */
$stmt = $pdo->prepare("
    SELECT *
    FROM reservations_attente
    WHERE reference = ?
    AND statut = 'attente'
");
$stmt->execute([$reference]);
$resa = $stmt->fetch();

if (!$resa) {
    header("Location: reservations.php?error=" . urlencode("Réservation introuvable ou déjà traitée"));
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
        (nom, telephone, sexe, cni, id_place, traversee_id, code_qr, type_client, pays, type_passager, prix, frais_service, depart_client)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $stmt->execute([
        $resa['nom'],
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
        $resa['depart_client']
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

    header("Location: reservations.php?error=" . urlencode(
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
