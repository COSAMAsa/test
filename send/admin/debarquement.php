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

// 🆕 Pré-remplissage automatique si on arrive avec un code_qr en GET (depuis billets_emis.php)
if(isset($_GET['code']) && $_SERVER['REQUEST_METHOD'] !== 'POST'){
    $_POST['code'] = trim($_GET['code']);
}

$result                = null;
$debarquement_message  = null;
$billet_id_for_display = null;
$debarquements_list    = [];

/* =========================================
   🚤 ENREGISTREMENT D'UN DÉBARQUEMENT
========================================= */
if(isset($_POST['form_action']) && $_POST['form_action'] === 'add_debarquement'){

    $billet_id   = (int) ($_POST['billet_id'] ?? 0);
    $motif_choix = trim($_POST['motif'] ?? '');
    $motif_autre = trim($_POST['motif_autre'] ?? '');

    $motif_final = ($motif_choix === 'Autre' && $motif_autre !== '')
        ? $motif_autre
        : $motif_choix;

    if($billet_id > 0 && $motif_final !== ''){

        $agent_nom = $_SESSION['user']['nom']
                  ?? $_SESSION['user']['username']
                  ?? 'Agent';

        $pdo->prepare("
            INSERT INTO debarquements
                (billet_id, motif, agent)
            VALUES (?, ?, ?)
        ")->execute([$billet_id, $motif_final, $agent_nom]);

        // 🆕 Mise à jour du statut du billet
        $pdo->prepare("
            UPDATE billets
            SET statut = 'debarque'
            WHERE id = ?
        ")->execute([$billet_id]);

        $debarquement_message = [
            "type" => "success",
            "text" => "🚤 Débarquement enregistré avec succès."
        ];

    } else {

        $debarquement_message = [
            "type" => "error",
            "text" => "⚠️ Merci de préciser le motif du débarquement."
        ];
    }

    // On recharge le billet concerné pour ré-afficher son état
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
                "type" => "found",
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

    if(!$billet && !$result){

        $result = [
            "type" => "error"
        ];

    }

    elseif($billet){

        $result = [
            "type" => "found",
            "data" => $billet
        ];
    }
}

/* =========================================
   🚤 HISTORIQUE DES DÉBARQUEMENTS DU BILLET
========================================= */
if(
    $result
    &&
    $result['type'] === 'found'
    &&
    isset($result['data']['id'])
){

    $billet_id_for_display = $result['data']['id'];

    $stmt = $pdo->prepare("
        SELECT *
        FROM debarquements
        WHERE billet_id = ?
        ORDER BY created_at DESC
    ");

    $stmt->execute([$billet_id_for_display]);

    $debarquements_list = $stmt->fetchAll();
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

<title>Contrôle débarquement</title>

<link
href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css"
rel="stylesheet"
>

<script src="https://unpkg.com/html5-qrcode"></script>

<style>

body{
    background:#eef2f7;
}

#reader{
    max-width:350px;
    margin:auto;
}

.result-box{
    border-radius:20px;
    padding:20px;
    font-size:18px;
    box-shadow:0 4px 15px rgba(0,0,0,.08);
}

.found{
    background:#d4edda;
}

.error{
    background:#f8d7da;
}

.warning{
    background:#fff3cd;
}

.debarquement-box{
    background:#fff;
    border-radius:14px;
    padding:16px;
    margin-top:16px;
    text-align:left;
    font-size:15px;
}

.debarquement-box h5{
    font-size:16px;
    margin-bottom:10px;
}

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
        🚤 Contrôle débarquement
    </h2>

    <div class="d-flex justify-content-between mb-3">

        <a href="dashboard.php" class="btn btn-secondary">
            ⬅️ Retour
        </a>

        <a href="embarquement.php" class="btn btn-outline-dark">
            🎫 Embarquement
        </a>

    </div>

    <!-- CARD RECHERCHE -->
    <div class="card shadow">

        <div class="card-body text-center">

            <div id="reader"></div>

            <hr>

            <form method="POST">

                <input
                type="text"
                id="codeInput"
                name="code"
                class="form-control mb-3 text-center"
                placeholder="Entrer code billet"
                value="<?= htmlspecialchars($_POST['code'] ?? '') ?>"
                required
                >

                <button class="btn btn-primary w-100">
                    Rechercher le billet
                </button>

            </form>

        </div>

    </div>

    <!-- RESULTAT RECHERCHE -->
    <?php if($result): ?>

    <div class="mt-4 result-box text-center

    <?php

    if($result['type']=="found"){
        echo "found";
    }
    elseif($result['type']=="error"){
        echo "error";
    }
    else{
        echo "warning";
    }

    ?>

    ">

    <!-- BILLET TROUVÉ -->
    <?php if($result['type']=="found"): ?>

        <h3>🎫 BILLET TROUVÉ</h3>

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

        <br>

        📌 Statut billet :
        <strong><?= htmlspecialchars($result['data']['statut']) ?></strong>

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
         🚤 FORMULAIRE DÉBARQUEMENT
    ========================================== -->
    <?php if($billet_id_for_display): ?>

        <div class="debarquement-box shadow-sm">

            <h5>🚤 Enregistrer un débarquement</h5>

            <?php if($debarquement_message): ?>

                <div class="alert <?= $debarquement_message['type'] == 'success' ? 'alert-success' : 'alert-danger' ?>">
                    <?= $debarquement_message['text'] ?>
                </div>

            <?php endif; ?>

            <?php if($debarquements_list): ?>

                <h6 class="mt-3">Historique des débarquements pour ce billet</h6>

                <ul class="list-group mb-3">

                    <?php foreach($debarquements_list as $d): ?>

                        <li class="list-group-item">

                            <div class="d-flex justify-content-between align-items-center">

                                <span><?= htmlspecialchars($d['motif']) ?></span>

                                <small class="text-muted">
                                    <?= date('d/m/Y H:i', strtotime($d['created_at'])) ?>
                                </small>

                            </div>

                            <?php if(!empty($d['agent'])): ?>

                                <small class="text-muted">
                                    Par : <?= htmlspecialchars($d['agent']) ?>
                                </small>

                            <?php endif; ?>

                        </li>

                    <?php endforeach; ?>

                </ul>

            <?php else: ?>

                <p class="text-muted mb-3">Aucun débarquement enregistré pour ce billet.</p>

            <?php endif; ?>

            <?php if($result['data']['statut'] !== 'debarque'): ?>

            <form method="POST" class="row g-2 align-items-end">

                <input type="hidden" name="form_action" value="add_debarquement">
                <input type="hidden" name="billet_id" value="<?= (int) $billet_id_for_display ?>">

                <div class="col-12 col-md-6">
                    <label class="form-label small mb-1">Motif du débarquement</label>
                    <select
                    name="motif"
                    id="motifSelect"
                    class="form-select"
                    required
                    onchange="toggleAutreMotif()"
                    >
                        <option value="">-- Choisir un motif --</option>
                        <option value="Fin de voyage">Fin de voyage</option>
                        <option value="Escale technique">Escale technique</option>
                        <option value="Urgence médicale">Urgence médicale</option>
                        <option value="Refus d'embarquement">Refus d'embarquement</option>
                        <option value="Annulation passager">Annulation passager</option>
                        <option value="Autre">Autre (préciser)</option>
                    </select>
                </div>

                <div class="col-12 col-md-4" id="motifAutreWrap" style="display:none;">
                    <label class="form-label small mb-1">Préciser le motif</label>
                    <input
                    type="text"
                    name="motif_autre"
                    id="motifAutreInput"
                    class="form-control"
                    placeholder="Motif détaillé"
                    >
                </div>

                <div class="col-12 col-md-2">
                    <button class="btn btn-warning w-100">
                        🚤 Enregistrer
                    </button>
                </div>

            </form>

            <?php else: ?>

                <div class="alert alert-secondary mb-0">
                    ✅ Ce billet a déjà été débarqué. Aucune action supplémentaire n'est possible.
                </div>

            <?php endif; ?>

        </div>

    <?php endif; ?>

    <?php endif; ?>

</div>

<script>

function toggleAutreMotif(){

    const select = document.getElementById('motifSelect');
    const wrap   = document.getElementById('motifAutreWrap');

    wrap.style.display = (select.value === 'Autre') ? 'block' : 'none';
}

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
