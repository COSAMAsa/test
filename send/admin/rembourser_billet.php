<?php
session_start();

require_once '../config/database.php';
require_once __DIR__ . '/includes/auth.php';

if (!isset($_SESSION['user'])) {

    header("Location: index.php");
    exit;
}

if (
    $_SESSION['user']['role'] != 'admin' &&
    $_SESSION['user']['role'] != 'finance'
) {
    die("⛔ Accès refusé");
}

/* =========================================
   📌 ID BILLET
========================================= */

$id = $_GET['id'] ?? $_POST['id'] ?? null;

if (!$id) {
    die("⛔ ID billet manquant");
}

/* =========================================
   🔎 RECUPERATION BILLET (affichage / vérif)
========================================= */

$stmt = $pdo->prepare("SELECT * FROM billets WHERE id = ?");
$stmt->execute([$id]);
$billet = $stmt->fetch();

if (!$billet) {
    die("⛔ Billet introuvable");
}

/* =========================================
   🧾 ETAPE 1 (GET) : FORMULAIRE DE CONFIRMATION
========================================= */

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    if ($billet['statut'] === 'remboursé') {
        die("⛔ Ce billet est déjà remboursé");
    }
    ?>
    <!DOCTYPE html>
    <html lang="fr">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Confirmer le remboursement</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <style>
            body{
                background:#f4f6f9;
                display:flex;
                align-items:center;
                justify-content:center;
                min-height:100vh;
            }
            .card{
                max-width:500px;
                width:100%;
                border:none;
                border-radius:18px;
            }
        </style>
    </head>
    <body>

    <div class="card shadow p-4">

        <h4 class="mb-3">💸 Confirmer le remboursement</h4>

        <div class="alert alert-secondary">
            <strong>Passager :</strong>
            <?= htmlspecialchars($billet['prenom'] . ' ' . $billet['nom']) ?>
            <br>
            <strong>Code billet :</strong> <?= htmlspecialchars($billet['code_qr']) ?>
            <br>
            <strong>Montant à rembourser :</strong>
            <?= number_format($billet['prix'], 0, ',', ' ') ?> FCFA
            <small class="text-muted">(frais de service non remboursés : <?= number_format($billet['frais_service'], 0, ',', ' ') ?> FCFA)</small>
        </div>

        <form method="POST">

            <input type="hidden" name="id" value="<?= htmlspecialchars($id) ?>">

            <div class="mb-3">
                <label class="form-label fw-bold">
                    Motif du remboursement <span class="text-danger">*</span>
                </label>
                <textarea
                    name="motif"
                    class="form-control"
                    rows="3"
                    required
                    placeholder="Ex: Annulation voyage client, erreur de saisie, traversée annulée..."
                ></textarea>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-danger flex-grow-1">
                    ✅ Confirmer le remboursement
                </button>
                <a href="javascript:history.back()" class="btn btn-secondary">
                    Annuler
                </a>
            </div>

        </form>

    </div>

    </body>
    </html>
    <?php
    exit;
}

/* =========================================
   🧾 ETAPE 2 (POST) : TRAITEMENT DU REMBOURSEMENT
========================================= */

$motif = trim($_POST['motif'] ?? '');

if ($motif === '') {
    die("⛔ Le motif du remboursement est obligatoire");
}

try {

    $pdo->beginTransaction();

    /* =========================================
       🔎 RECUPERATION BILLET (verrouillée)
    ========================================= */

    $stmt = $pdo->prepare("

        SELECT *
        FROM billets
        WHERE id = ?
        FOR UPDATE

    ");

    $stmt->execute([$id]);

    $billet = $stmt->fetch();

    if (!$billet) {

        throw new Exception(
            "⛔ Billet introuvable"
        );
    }

    if (
        $billet['statut'] === 'remboursé'
    ) {

        throw new Exception(
            "⛔ Ce billet est déjà remboursé"
        );
    }

    /* =========================================
       💰 MONTANT REMBOURSÉ
       (SANS FRAIS SERVICE)
    ========================================= */

    $montant_rembourse = $billet['prix'];

    /* =========================================
       💸 UPDATE BILLET
    ========================================= */

    $stmt = $pdo->prepare("

        UPDATE billets

        SET
            statut = 'remboursé',
            montant_rembourse = ?,
            date_remboursement = NOW()

        WHERE id = ?

    ");

    $stmt->execute([

        $montant_rembourse,
        $id

    ]);

    /* =========================================
       🪑 LIBERER PLACE
    ========================================= */

    if (!empty($billet['id_place'])) {

        $stmt = $pdo->prepare("

            UPDATE places

            SET restant = 1

            WHERE id_place = ?

        ");

        $stmt->execute([
            $billet['id_place']
        ]);
    }

    /* =========================================
       📊 HISTORIQUE REMBOURSEMENT (avec motif)
    ========================================= */

    $stmt = $pdo->prepare("

        INSERT INTO remboursements
        (
            billet_id,
            montant,
            frais,
            total,
            user_id,
            date_remboursement,
            commentaire
        )

        VALUES (?, ?, ?, ?, ?, NOW(), ?)

    ");

    $stmt->execute([

        $billet['id'],

        /* montant remboursé */
        $montant_rembourse,

        /* frais non remboursés */
        $billet['frais_service'],

        /* total réellement remboursé */
        $montant_rembourse,

        $_SESSION['user']['id'] ?? null,

        /* motif du remboursement */
        $motif

    ]);

    /* =========================================
       ✅ VALIDATION
    ========================================= */

    $pdo->commit();

    header(
        "Location: billets_emis1.php?msg=remboursement_ok"
    );

    exit;

} catch (Exception $e) {

    $pdo->rollBack();

    die(
        "❌ Erreur : "
        . $e->getMessage()
    );
}
?>
