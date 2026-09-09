<?php
function finaliserBillet(PDO $pdo, string $reference): array
{
    // 🔒 Transaction + verrou pour empêcher une double finalisation
    // concurrente de la même réservation (ex: webhook + retour navigateur
    // qui arrivent en même temps). SELECT ... FOR UPDATE bloque les autres
    // exécutions tant que celle-ci n'a pas terminé ou annulé.
    $pdo->beginTransaction();

    try {
        $stmt = $pdo->prepare("
            SELECT * FROM reservations_attente
            WHERE reference = ? AND statut IN ('attente', 'payee')
            FOR UPDATE
        ");
        $stmt->execute([$reference]);
        $resa = $stmt->fetch();

        if (!$resa) {
            $pdo->rollBack();
            return ['ok' => false, 'billet_id' => null, 'billet_token' => null];
        }

        // 🆕 Le billet reste valide 21 jours à compter de la date du voyage
        $stmtTraversee = $pdo->prepare("SELECT date_depart FROM traversees WHERE id = ?");
        $stmtTraversee->execute([$resa['traversee_id']]);
        $traversee = $stmtTraversee->fetch();

        $dateAchat = date('Y-m-d H:i:s');
        $dateExpiration = date('Y-m-d H:i:s', strtotime($traversee['date_depart'] . ' + 21 days'));

        /* =========================================
           🔒 Token opaque, aléatoire, non devinable :
           c'est lui qui sert de clé d'accès au billet
           dans billet.php et generate_pdf.php, à la
           place de l'id incrémental.
        ========================================= */
        $token = bin2hex(random_bytes(32)); // 64 caractères hexadécimaux

        $stmt = $pdo->prepare("
            INSERT INTO billets
            (nom, prenom, telephone, sexe, cni, id_place, traversee_id, code_qr, type_client, pays, type_passager, prix, frais_service, depart_client, mode_paiement, date_achat, date_expiration, token_acces)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
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
            $dateExpiration,
            $token
        ]);

        $billet_id = $pdo->lastInsertId();

        $pdo->prepare("UPDATE places SET restant = restant - 1 WHERE id_place=?")
            ->execute([$resa['id_place']]);

        $pdo->prepare("
            UPDATE reservations_attente
            SET statut = 'traite'
            WHERE reference = ?
        ")->execute([$reference]);

        $pdo->commit();

        return ['ok' => true, 'billet_id' => $billet_id, 'billet_token' => $token];

    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
}

function marquerEchecPaiement(PDO $pdo, string $reference): void
{
    $pdo->prepare("
        UPDATE reservations_attente
        SET statut = 'echec'
        WHERE reference = ? AND statut IN ('attente', 'payee')
    ")->execute([$reference]);
}
