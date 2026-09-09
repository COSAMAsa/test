<?php
require_once __DIR__ . '/includes/auth.php';
require_once '../config/database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if(
    $_SESSION['user']['role'] != 'admin'
    &&
    $_SESSION['user']['role'] != 'exploitation'
    &&
    $_SESSION['user']['role'] != 'agent'
){
    die("⛔ Accès refusé");
}

date_default_timezone_set('Africa/Dakar');

$result          = null;
$enfant_message  = null;

/* =========================================
   👶 AJOUT D'UN ENFANT ACCOMPAGNANT (0-3 ANS)
========================================= */
if(isset($_POST['form_action']) && $_POST['form_action'] === 'add_enfant'){

    $billet_id  = (int) ($_POST['billet_id'] ?? 0);
    $nom_enfant = trim($_POST['nom_enfant'] ?? '');
    $age_valeur = (int) ($_POST['age_valeur'] ?? -1);
    $age_unite  = ($_POST['age_unite'] ?? '') === 'ans' ? 'ans' : 'mois';

    if($billet_id > 0 && $nom_enfant !== '' && $age_valeur >= 0){

        // Garde-fou : enfant de 0 à 3 ans uniquement
        $age_ok = ($age_unite === 'mois' && $age_valeur <= 36)
               || ($age_unite === 'ans'  && $age_valeur <= 3);

        if($age_ok){

            $pdo->prepare("
                INSERT INTO enfants_accompagnants
                    (billet_id, nom_enfant, age_valeur, age_unite)
                VALUES (?, ?, ?, ?)
            ")->execute([$billet_id, $nom_enfant, $age_valeur, $age_unite]);

            $enfant_message = [
                "type" => "success",
                "text" => "👶 Enfant « " . htmlspecialchars($nom_enfant) . " » ajouté avec succès."
            ];

        } else {

            $enfant_message = [
                "type" => "error",
                "text" => "⛔ L'âge doit être compris entre 0 et 3 ans."
            ];
        }

    } else {

        $enfant_message = [
            "type" => "error",
            "text" => "⚠️ Merci de renseigner le nom et l'âge de l'enfant."
        ];
    }

    // On recharge le billet concerné pour ré-afficher son statut
    if($billet_id > 0){

        $stmt = $pdo->prepare("
            SELECT 
                b.*,
                t.date_depart,
                t.depart,
                t.destination,
                p.type_place

            FROM billets b

            JOIN traversees t
            ON b.traversee_id = t.id

            LEFT JOIN places p
            ON b.id_place = p.id_place

            WHERE b.id = ?
        ");

        $stmt->execute([$billet_id]);

        $billet = $stmt->fetch();

        if($billet){

            $result = [
                "type" => ($billet['statut'] == 'embarque') ? 'success' : 'used',
                "data" => $billet
            ];
        }
    }

}

/* =========================================
   🔎 RECHERCHE BILLET
========================================= */
elseif(isset($_POST['code'])){

    $code = trim($_POST['code']);

    // 🔍 Si code court (3 à 5 chiffres)
    if(preg_match('/^\d{3,5}$/', $code)){

        $stmt = $pdo->prepare("
            SELECT 
                b.*,
                t.date_depart,
                t.depart,
                t.destination,
                p.type_place

            FROM billets b

            JOIN traversees t
            ON b.traversee_id = t.id

            LEFT JOIN places p
            ON b.id_place = p.id_place

            WHERE b.code_qr LIKE ?
        ");

        $stmt->execute(["%".$code."%"]);

        $billets = $stmt->fetchAll();

        // ⚠️ Plusieurs résultats
        if(count($billets) == 1){

            $billet = $billets[0];

        }
        elseif(count($billets) > 1){

            $result = [
                "type" => "multiple",
                "data" => $billets
            ];

            $billet = null;

        }
        else{

            $billet = false;
        }

    } else {

        // 🔎 Code complet
        $stmt = $pdo->prepare("
            SELECT 
                b.*,
                t.date_depart,
                t.depart,
                t.destination,
                p.type_place

            FROM billets b

            JOIN traversees t
            ON b.traversee_id = t.id

            LEFT JOIN places p
            ON b.id_place = p.id_place

            WHERE b.code_qr = ?
        ");

        $stmt->execute([$code]);

        $billet = $stmt->fetch();
    }

    /* =========================================
       ❌ BILLET INTROUVABLE
    ========================================= */

    if(!$billet && !$result){

        $result = [
            "type" => "error"
        ];

    }

    /* =========================================
       ✅ TRAITEMENT BILLET
    ========================================= */

    elseif($billet){

        $today = date('Y-m-d');

        $jour_voyage = date(
            'Y-m-d',
            strtotime($billet['date_depart'])
        );

        /* =========================================
           💸 BILLET REMBOURSÉ
        ========================================= */

        if($billet['statut'] == "remboursé"){

            $result = [
                "type" => "rembourse",
                "data" => $billet
            ];
        }

        /* =========================================
           ⚠️ DÉJÀ EMBARQUÉ
        ========================================= */

        elseif($billet['statut'] == "embarque"){

            $result = [
                "type" => "used",
                "data" => $billet
            ];
        }

        /* =========================================
           📅 MAUVAIS JOUR
        ========================================= */

        elseif($jour_voyage != $today){

            $result = [
                "type" => "wrong_day",
                "data" => $billet
            ];
        }

        /* =========================================
           ⏰ EMBARQUEMENT FERMÉ APRÈS 20H00
           (le jour même du voyage uniquement)
        ========================================= */

        elseif(date('H:i:s') > '20:00:00'){

            $result = [
                "type" => "too_late",
                "data" => $billet
            ];
        }

        /* =========================================
           ✅ EMBARQUEMENT
        ========================================= */

        else{

            $pdo->prepare("
                UPDATE billets
                SET statut='embarque'
                WHERE id=?
            ")->execute([$billet['id']]);

            // 🆕 ENREGISTREMENT DE L'AGENT QUI A EFFECTUÉ L'EMBARQUEMENT
            $agent_nom = $_SESSION['user']['nom']
                      ?? $_SESSION['user']['username']
                      ?? 'Agent';

            $pdo->prepare("
                INSERT INTO embarquements
                    (billet_id, agent)
                VALUES (?, ?)
            ")->execute([$billet['id'], $agent_nom]);

            $result = [
                "type" => "success",
                "data" => $billet
            ];
        }
    }
}

/* =========================================
   👶 ENFANTS DÉJÀ DÉCLARÉS POUR CE BILLET
========================================= */
$billet_id_for_display = null;
$enfants_list          = [];

if(
    $result
    &&
    in_array($result['type'], ['success', 'used'])
    &&
    isset($result['data']['id'])
){

    $billet_id_for_display = $result['data']['id'];

    $stmt = $pdo->prepare("
        SELECT *
        FROM enfants_accompagnants
        WHERE billet_id = ?
        ORDER BY created_at ASC
    ");

    $stmt->execute([$billet_id_for_display]);

    $enfants_list = $stmt->fetchAll();
}

/* =========================================
   🧩 FONCTION UTILITAIRE : NOM COMPLET
========================================= */
function nom_complet($data){
    $prenom = trim($data['prenom'] ?? '');
    $nom    = trim($data['nom'] ?? '');
    return htmlspecialchars(trim($prenom . ' ' . $nom));
}
?>

<!DOCTYPE html>
<html lang="fr">

<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Contrôle embarquement</title>

<link
href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css"
rel="stylesheet"
>

<script src="https://unpkg.com/html5-qrcode"></script>

<style>

body{
    background:#eef2f7;
}

/* Scanner */
#reader{
    max-width:350px;
    margin:auto;
}

/* Résultat */
.result-box{
    border-radius:20px;
    padding:20px;
    font-size:18px;
    box-shadow:0 4px 15px rgba(0,0,0,0.08);
}

.success{
    background:#d4edda;
}

.error{
    background:#f8d7da;
}

.warning{
    background:#fff3cd;
}

/* Bloc enfant accompagnant */
.enfant-box{
    background:#fff;
    border-radius:14px;
    padding:16px;
    margin-top:16px;
    text-align:left;
    font-size:15px;
}

.enfant-box h5{
    font-size:16px;
    margin-bottom:10px;
}

/* MOBILE */
@media(max-width:768px){

    .result-box{
        font-size:16px;
    }

}

</style>

</head>

<body>

<div class="container mt-4">

    <h2 class="text-center mb-4">
        🎫 Contrôle embarquement
    </h2>

    <div class="d-flex justify-content-between mb-3">

        <a href="dashboard.php" class="btn btn-secondary">
            ⬅️ Retour
        </a>

        <a href="dashboard.php" class="btn btn-outline-dark">
            🏠 Accueil
        </a>

    </div>

    <!-- CARD -->
    <div class="card shadow">

        <div class="card-body text-center">

            <!-- SCANNER -->
            <div id="reader"></div>

            <hr>

            <!-- SAISIE -->
            <form method="POST">

                <input
                type="text"
                id="codeInput"
                name="code"
                class="form-control mb-3 text-center"
                placeholder="Entrer code billet"
                required
                >

                <button class="btn btn-primary w-100">
                    Valider manuellement
                </button>

            </form>

        </div>

    </div>

    <!-- MESSAGE AJOUT ENFANT -->
    <?php if($enfant_message): ?>

        <div class="alert mt-4 <?= $enfant_message['type'] == 'success' ? 'alert-success' : 'alert-danger' ?>">
            <?= $enfant_message['text'] ?>
        </div>

    <?php endif; ?>

    <!-- RESULTAT -->
    <?php if($result): ?>

    <div class="mt-4 result-box text-center

    <?php

    if($result['type']=="success"){
        echo "success";
    }
    elseif(
        $result['type']=="error"
        ||
        $result['type']=="rembourse"
    ){
        echo "error";
    }
    else{
        echo "warning";
    }

    ?>

    ">

    <!-- SUCCESS -->
    <?php if($result['type']=="success"): ?>

        <h3>✅ BILLET VALIDÉ</h3>

        <hr>

        <strong>
            <?= nom_complet($result['data']) ?>
        </strong>

        <br>

        🚢
        <?= $result['data']['depart'] ?>
        →
        <?= $result['data']['destination'] ?>

        <br>

        📅
        <?= date(
            'd/m/Y H:i',
            strtotime($result['data']['date_depart'])
        ) ?>

        <br>

        💺 Place :
        <?= $result['data']['type_place'] ?>

        <br>

        👤
        <?= $result['data']['type_passager'] ?>
        -
        <?= $result['data']['type_client'] ?>

    <?php endif; ?>

    <!-- DÉJÀ EMBARQUÉ -->
    <?php if($result['type']=="used"): ?>

        <h3>⚠️ DÉJÀ EMBARQUÉ</h3>

        <hr>

        <strong>
            <?= nom_complet($result['data']) ?>
        </strong>

        <br>

        🚢
        <?= $result['data']['depart'] ?>
        →
        <?= $result['data']['destination'] ?>

        <br>

        📅
        <?= date(
            'd/m/Y H:i',
            strtotime($result['data']['date_depart'])
        ) ?>

    <?php endif; ?>

    <!-- REMBOURSÉ -->
    <?php if($result['type']=="rembourse"): ?>

        <h3>💸 BILLET REMBOURSÉ</h3>

        <hr>

        <strong>
            <?= nom_complet($result['data']) ?>
        </strong>

        <br>

        🚢
        <?= $result['data']['depart'] ?>
        →
        <?= $result['data']['destination'] ?>

        <br>

        📅
        <?= date(
            'd/m/Y H:i',
            strtotime($result['data']['date_depart'])
        ) ?>

        <br><br>

        ⛔ Ce billet ne peut plus embarquer.

    <?php endif; ?>

    <!-- MAUVAIS JOUR -->
    <?php if($result['type']=="wrong_day"): ?>

        <h3>⛔ MAUVAIS JOUR</h3>

        <hr>

        Ce billet n'est pas pour aujourd'hui

        <br><br>

        <strong>

            🚢
            <?= $result['data']['depart'] ?>
            →
            <?= $result['data']['destination'] ?>

        </strong>

        <br>

        📅
        <?= date(
            'd/m/Y H:i',
            strtotime($result['data']['date_depart'])
        ) ?>

    <?php endif; ?>

    <!-- EMBARQUEMENT FERMÉ (après 20h00) -->
    <?php if($result['type']=="too_late"): ?>

        <h3>⏰ EMBARQUEMENT FERMÉ</h3>

        <hr>

        L'embarquement pour aujourd'hui est clos (après 20h00)

        <br><br>

        <strong>
            <?= nom_complet($result['data']) ?>
        </strong>

        <br>

        🚢
        <?= $result['data']['depart'] ?>
        →
        <?= $result['data']['destination'] ?>

        <br>

        📅
        <?= date(
            'd/m/Y H:i',
            strtotime($result['data']['date_depart'])
        ) ?>

    <?php endif; ?>

    <!-- INTROUVABLE -->
    <?php if($result['type']=="error"): ?>

        <h3>❌ Billet introuvable</h3>

    <?php endif; ?>

    <!-- MULTIPLE -->
    <?php if($result['type']=="multiple"): ?>

        <h3>⚠️ Plusieurs billets trouvés</h3>

        <hr>

        <?php foreach($result['data'] as $b): ?>

            <div class="border rounded p-2 mb-2 bg-white">

                <strong>
                    <?= nom_complet($b) ?>
                </strong>

                <br>

                🎫 <?= $b['code_qr'] ?>

                <br>

                🚢
                <?= $b['depart'] ?>
                →
                <?= $b['destination'] ?>

            </div>

        <?php endforeach; ?>

    <?php endif; ?>

    </div>

    <!-- =========================================
         👶 ENFANT ACCOMPAGNANT (0-3 ANS)
    ========================================== -->
    <?php if($billet_id_for_display): ?>

        <div class="enfant-box shadow-sm">

            <h5>👶 Enfant(s) accompagnant(s) (0 à 3 ans)</h5>

            <?php if($enfants_list): ?>

                <ul class="list-group mb-3">

                    <?php foreach($enfants_list as $e): ?>

                        <li class="list-group-item d-flex justify-content-between align-items-center">

                            <?= htmlspecialchars($e['nom_enfant']) ?>

                            <span class="badge bg-info text-dark">
                                <?= (int) $e['age_valeur'] ?>
                                <?= $e['age_unite'] == 'mois' ? 'mois' : 'an(s)' ?>
                            </span>

                        </li>

                    <?php endforeach; ?>

                </ul>

            <?php else: ?>

                <p class="text-muted mb-3">Aucun enfant déclaré pour ce billet.</p>

            <?php endif; ?>

            <form method="POST" class="row g-2 align-items-end">

                <input type="hidden" name="form_action" value="add_enfant">
                <input type="hidden" name="billet_id" value="<?= (int) $billet_id_for_display ?>">

                <div class="col-12 col-md-5">
                    <label class="form-label small mb-1">Nom de l'enfant</label>
                    <input
                    type="text"
                    name="nom_enfant"
                    class="form-control"
                    placeholder="Nom de l'enfant"
                    required
                    >
                </div>

                <div class="col-6 col-md-3">
                    <label class="form-label small mb-1">Âge</label>
                    <input
                    type="number"
                    name="age_valeur"
                    class="form-control"
                    min="0"
                    max="36"
                    placeholder="Ex: 8"
                    required
                    >
                </div>

                <div class="col-6 col-md-2">
                    <label class="form-label small mb-1">Unité</label>
                    <select name="age_unite" class="form-select">
                        <option value="mois">Mois</option>
                        <option value="ans">Ans</option>
                    </select>
                </div>

                <div class="col-12 col-md-2">
                    <button class="btn btn-success w-100">
                        ➕ Ajouter
                    </button>
                </div>

            </form>

        </div>

    <?php endif; ?>

    <?php endif; ?>

</div>

<script>

// ✅ Scan QR automatique
function onScanSuccess(decodedText){

    document.getElementById("codeInput").value = decodedText;

    document.forms[0].submit();
}

let scanner = new Html5QrcodeScanner(
    "reader",
    {
        fps: 10,
        qrbox: 250
    }
);

scanner.render(onScanSuccess);

</script>

</body>

</html>
