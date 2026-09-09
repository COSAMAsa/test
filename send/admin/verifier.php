<?php
require_once '../config/database.php';

header('Content-Type: application/json');

try {

    $code = $_POST['code'] ?? '';

    if (empty($code)) {
        echo json_encode([
            "status"=>"error",
            "message"=>"Code vide"
        ]);
        exit;
    }

    /* ========================
       RECHERCHE BILLET
    ======================== */
    $stmt = $pdo->prepare("
        SELECT b.id, b.nom, b.type_passager, b.type_client, b.statut,
               pl.type_place
        FROM billets b
        LEFT JOIN places pl ON b.id_place = pl.id_place
        WHERE b.code_qr = ?
    ");
    $stmt->execute([$code]);

    $billet = $stmt->fetch(PDO::FETCH_ASSOC);

    /* ========================
       INTROUVABLE
    ======================== */
    if (!$billet) {
        echo json_encode([
            "status"=>"error",
            "message"=>"Billet introuvable"
        ]);
        exit;
    }

    /* ========================
       DEJA EMBARQUE
    ======================== */
    if ($billet['statut'] === 'embarque') {
        echo json_encode([
            "status"=>"used",
            "nom"=>$billet['nom'],
            "place"=>$billet['type_place'],
            "type"=>$billet['type_passager'],
            "client"=>$billet['type_client']
        ]);
        exit;
    }

    /* ========================
       EMBARQUEMENT
    ======================== */
    $update = $pdo->prepare("
        UPDATE billets 
        SET statut = 'embarque'
        WHERE id = ?
    ");
    $update->execute([$billet['id']]);

    echo json_encode([
        "status"=>"success",
        "nom"=>$billet['nom'],
        "place"=>$billet['type_place'],
        "type"=>$billet['type_passager'],
        "client"=>$billet['type_client']
    ]);

} catch (Exception $e) {

    echo json_encode([
        "status"=>"error",
        "message"=>$e->getMessage()
    ]);
}