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
    header("Location: reservations.php?error=" . urlencode("Méthode non autorisée"));
    exit;
}

$reference = trim($_POST['reference'] ?? '');

if ($reference === '') {
    header("Location: reservations.php?error=" . urlencode("Référence manquante"));
    exit;
}

try {

    $pdo->beginTransaction();

    /* =========================================
       🔒 ÉTAPE CLÉ : on verrouille la ligne ET on
       change son statut EN PREMIER, de façon
       atomique. Si deux requêtes arrivent en même
       temps (double-clic, double soumission...),
       une seule pourra faire passer statut
       'attente' -> 'traite'. La seconde ne
       modifiera aucune ligne (rowCount = 0) et
       sera rejetée avant toute insertion de billet.
    ========================================= */
    $stmtLock = $pdo->prepare("
        UPDATE reservations_attente
        SET statut = 'traite'
        WHERE reference = ?
        AND statut = 'attente'
    ");
    $stmtLock->execute([$reference]);

    if ($stmtLock->rowCount() === 0) {
        // Soit la réservation n'existe pas, soit elle a déjà
        // été traitée (par ce même clic en double, ou avant).
        $pdo->rollBack();
        header("Location: reservations.php?error=" . urlencode(
            "Réservation introuvable ou déjà traitée"
        ));
        exit;
    }

    /* =========================================
       📋 Récupération des données de la réservation
       (maintenant qu'on est certain d'être seul à
       la traiter)
    ========================================= */
    $stmt = $pdo->prepare("
        SELECT *
        FROM reservations_attente
        WHERE reference = ?
    ");
    $stmt->execute([$reference]);
    $resa = $stmt->fetch();

    if (!$resa) {
        $pdo->rollBack();
        header("Location: reservations.php?error=" . urlencode("Réservation introuvable"));
        exit;
    }

    /* =========================================
       ✅ Conversion en billet (même logique que
          la confirmation de paiement Orange Money)
    ========================================= */
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
