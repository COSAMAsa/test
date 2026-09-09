<?php

function finaliserBillet(PDO $pdo, string $reference): array
{
    $stmt = $pdo->prepare("
        SELECT * FROM reservations_attente
        WHERE reference = ? AND statut IN ('attente', 'payee')
    ");
    $stmt->execute([$reference]);
    $resa = $stmt->fetch();

    if (!$resa) {
        return ['ok' => false, 'billet_id' => null];
    }

    // 🆕 Le billet reste valide 21 jours à compter de la date du voyage
    $stmtTraversee = $pdo->prepare("SELECT date_depart FROM traversees WHERE id = ?");
    $stmtTraversee->execute([$resa['traversee_id']]);
    $traversee = $stmtTraversee->fetch();

    $dateAchat = date('Y-m-d H:i:s');
    $dateExpiration = date('Y-m-d H:i:s', strtotime($traversee['date_depart'] . ' + 21 days'));

    $stmt = $pdo->prepare("
        INSERT INTO billets
        (nom, prenom, telephone, sexe, cni, id_place, traversee_id, code_qr, type_client, pays, type_passager, prix, frais_service, depart_client, mode_paiement, date_achat, date_expiration)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
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
        $resa['mode_paiement'],
        $dateAchat,
        $dateExpiration
    ]);
    $billet_id = $pdo->lastInsertId();

    $pdo->prepare("UPDATE places SET restant = restant - 1 WHERE id_place=?")
        ->execute([$resa['id_place']]);

    $pdo->prepare("
        UPDATE reservations_attente
        SET statut = 'traite'
        WHERE reference = ?
    ")->execute([$reference]);

    return ['ok' => true, 'billet_id' => $billet_id];
}

function marquerEchecPaiement(PDO $pdo, string $reference): void
{
    $pdo->prepare("
        UPDATE reservations_attente
        SET statut = 'echec'
        WHERE reference = ? AND statut IN ('attente', 'payee')
    ")->execute([$reference]);
}
