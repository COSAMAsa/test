<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Non autorisé.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$ids = $input['ids'] ?? [];

if (!is_array($ids) || count($ids) === 0) {
    echo json_encode(['success' => false, 'message' => 'Aucune place sélectionnée.']);
    exit;
}

// On ne garde que des identifiants numériques valides
$ids = array_values(array_filter(array_map('intval', $ids)));

if (count($ids) === 0) {
    echo json_encode(['success' => false, 'message' => 'Sélection invalide.']);
    exit;
}

try {
    $pdo->beginTransaction();

    $placeholders = implode(',', array_fill(0, count($ids), '?'));

    // Verrouille toutes les lignes concernées avant modification
    $stmt = $pdo->prepare("SELECT id_place, restant FROM places WHERE id_place IN ($placeholders) FOR UPDATE");
    $stmt->execute($ids);
    $places = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($places) === 0) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Aucune de ces places n\'existe.']);
        exit;
    }

    // On ne touche qu'aux places actuellement disponibles (restant > 0),
    // pour ne jamais écraser une place déjà occupée par un vrai billet
    $idsDisponibles = array_column(
        array_filter($places, fn($p) => $p['restant'] > 0),
        'id_place'
    );

    if (count($idsDisponibles) === 0) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Ces places sont déjà occupées.']);
        exit;
    }

    $placeholdersDispo = implode(',', array_fill(0, count($idsDisponibles), '?'));
    $stmt = $pdo->prepare("UPDATE places SET restant = 0 WHERE id_place IN ($placeholdersDispo)");
    $stmt->execute($idsDisponibles);

    // Traçabilité minimale dans les logs serveur (qui, quoi, quand)
    $agent = trim(($_SESSION['user']['prenom'] ?? '') . ' ' . ($_SESSION['user']['nom'] ?? ''));
    if ($agent === '') {
        $agent = $_SESSION['user']['nom'] ?? $_SESSION['user']['username'] ?? $_SESSION['user']['login'] ?? 'Inconnu';
    }
    error_log("marquer_occupe.php : places " . implode(',', $idsDisponibles) . " marquées occupées par {$agent}");

    $pdo->commit();

    echo json_encode(['success' => true, 'nb' => count($idsDisponibles)]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('marquer_occupe.php : ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erreur serveur.']);
}
