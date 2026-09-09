<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Non autorisé.']);
    exit;
}

$idPlace = $_POST['id_place'] ?? null;

if (!$idPlace) {
    echo json_encode(['success' => false, 'message' => 'Place manquante.']);
    exit;
}

try {
    // Verrouille la ligne le temps de la mise à jour (évite les doubles clics concurrents)
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("SELECT id_place, restant FROM places WHERE id_place = ? FOR UPDATE");
    $stmt->execute([$idPlace]);
    $place = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$place) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Place introuvable.']);
        exit;
    }

    if ($place['restant'] > 0) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Cette place est déjà disponible.']);
        exit;
    }

    // On libère uniquement la place. Le billet lié n'est PAS modifié
    // (il garde son statut valide/embarque et pourra être reporté séparément).
    $pdo->prepare("UPDATE places SET restant = restant + 1 WHERE id_place = ?")
        ->execute([$idPlace]);

    $pdo->commit();

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    $pdo->rollBack();
    error_log('liberer_place.php : ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erreur serveur.']);
}
