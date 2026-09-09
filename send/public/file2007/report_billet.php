<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user'])) {
    header("Location: index.php");
    exit;
}

$id = $_GET['id'] ?? null;

if(!$id){
    die("Billet introuvable");
}

// 🔎 billet actuel
$stmt = $pdo->prepare("
SELECT b.*, t.depart, t.destination
FROM billets b
JOIN traversees t ON b.traversee_id = t.id
WHERE b.id = ?
");
$stmt->execute([$id]);
$billet = $stmt->fetch();

if(!$billet){
    die("Billet introuvable");
}

// 🚫 Bloquer si embarqué
if($billet['statut'] === 'embarque'){
    die("❌ Impossible de reporter un billet déjà embarqué.");
}

// 🚫 vérifier si déjà reporté
if($billet['report_effectue'] == 1){
    die("❌ Ce billet a déjà été reporté");
}

// 🔎 nouvelles traversées possibles : même trajet OU trajet retour
// (ex: un billet Dakar → Ziguinchor peut être reporté vers un voyage
// Ziguinchor → Dakar, pas seulement vers un autre Dakar → Ziguinchor)
$stmt = $pdo->prepare("
SELECT * FROM traversees 
WHERE (
    (depart = ? AND destination = ?)
    OR
    (depart = ? AND destination = ?)
)
AND date_depart > NOW()
ORDER BY date_depart ASC
");
$stmt->execute([
    $billet['depart'], $billet['destination'],   // même sens
    $billet['destination'], $billet['depart'],   // sens retour
]);
$traversees = $stmt->fetchAll();

// 🔁 traitement final : ici on reçoit traversee_id + la place choisie via le plan
if($_SERVER['REQUEST_METHOD'] == 'POST'){

    $new_traversee = $_POST['traversee_id'] ?? null;
    $place_id      = $_POST['place_id'] ?? null;
    $numero_place  = $_POST['numero_place'] ?? null;

    // 🔒 vérifier que la traversée choisie fait bien partie des options proposées
    $valide = false;
    foreach($traversees as $t){
        if($t['id'] == $new_traversee){
            $valide = true;
            break;
        }
    }

    if(!$valide){
        die("❌ Traversée sélectionnée invalide.");
    }

    if(!$place_id){
        die("❌ Aucune place sélectionnée.");
    }

    // 🔥 mise à jour du billet : nouvelle traversée + nouvelle place + report marqué
    $pdo->beginTransaction();

    try {
        // 🔒 on reverrouille le billet DANS la transaction pour empêcher
        // un double envoi (double-clic, deux onglets, etc.) de reporter
        // deux fois le même billet en même temps
        $stmt = $pdo->prepare("SELECT * FROM billets WHERE id = ? FOR UPDATE");
        $stmt->execute([$id]);
        $billet_lock = $stmt->fetch();

        if(!$billet_lock){
            $pdo->rollBack();
            die("❌ Billet introuvable.");
        }
        if($billet_lock['statut'] === 'embarque'){
            $pdo->rollBack();
            die("❌ Impossible de reporter un billet déjà embarqué.");
        }
        if($billet_lock['report_effectue'] == 1){
            $pdo->rollBack();
            die("❌ Ce billet a déjà été reporté.");
        }

        // 🆕 On garde une trace de l'ancienne place ET de l'ancienne traversée
        // AVANT de les écraser dans billets, pour pouvoir afficher
        // "traversée ancienne" à côté de "traversée actuelle" dans
        // billets_reportes.php
        $ancienne_place_id     = $billet_lock['id_place'];
        $ancienne_traversee_id = $billet_lock['traversee_id'];

        // 🆕 garde-fou : si l'ancienne place/traversée est vide, on ne
        // pourra de toute façon jamais retrouver la ligne à libérer
        if(empty($ancienne_place_id) || empty($ancienne_traversee_id)){
            $pdo->rollBack();
            error_log("Report billet {$id}: ancienne_place_id ou ancienne_traversee_id manquant (place={$ancienne_place_id}, traversee={$ancienne_traversee_id})");
            die("❌ Impossible de déterminer l'ancienne place de ce billet (données manquantes). Contactez le support.");
        }

        // 🔒 vérifier ET verrouiller la nouvelle place pour qu'elle ne
        // puisse pas être prise par une autre requête pendant qu'on la traite
        $stmt = $pdo->prepare("SELECT * FROM places WHERE id_place = ? AND traversee_id = ? FOR UPDATE");
        $stmt->execute([$place_id, $new_traversee]);
        $place = $stmt->fetch();

        if(!$place || $place['restant'] <= 0){
            $pdo->rollBack();
            die("❌ Cette place n'est plus disponible.");
        }

        $stmt = $pdo->prepare("
        UPDATE billets 
        SET traversee_id = ?, id_place = ?, ancienne_traversee_id = ?, ancienne_place_id = ?, report_effectue = 1 
        WHERE id = ?
        ");
        $stmt->execute([$new_traversee, $place_id, $ancienne_traversee_id, $ancienne_place_id, $id]);

        // 🆕 libérer l'ancienne place : on filtre aussi sur son ancienne
        // traversée pour ne jamais toucher une autre ligne par erreur
        $stmt = $pdo->prepare("UPDATE places SET restant = restant + 1 WHERE id_place = ? AND traversee_id = ?");
        $stmt->execute([$ancienne_place_id, $ancienne_traversee_id]);

        // 🆕 si aucune ligne n'a été touchée, la place à libérer n'existe
        // pas (ou plus) dans `places` avec ce couple id_place/traversee_id
        // -> on annule tout plutôt que de reporter le billet en silence
        if($stmt->rowCount() === 0){
            $pdo->rollBack();
            error_log("Report billet {$id}: échec libération ancienne place (id_place={$ancienne_place_id}, traversee_id={$ancienne_traversee_id}) - 0 ligne affectée dans `places`");
            die("❌ Erreur interne : l'ancienne place n'a pas pu être libérée (référence introuvable). Aucun changement n'a été appliqué, veuillez réessayer ou contacter le support.");
        }

        // décrémenter la place prise sur la nouvelle traversée
        $stmt = $pdo->prepare("UPDATE places SET restant = restant - 1 WHERE id_place = ? AND traversee_id = ?");
        $stmt->execute([$place_id, $new_traversee]);

        if($stmt->rowCount() === 0){
            $pdo->rollBack();
            error_log("Report billet {$id}: échec décrément nouvelle place (id_place={$place_id}, traversee_id={$new_traversee}) - 0 ligne affectée dans `places`");
            die("❌ Erreur interne : la nouvelle place n'a pas pu être réservée. Aucun changement n'a été appliqué, veuillez réessayer.");
        }

// Récupération du nom de l'agent connecté
$agent = trim(
    ($_SESSION['user']['prenom'] ?? '') . ' ' .
    ($_SESSION['user']['nom'] ?? '')
);

if ($agent === '') {
    $agent = $_SESSION['user']['nom']
          ?? $_SESSION['user']['username']
          ?? $_SESSION['user']['login']
          ?? 'Inconnu';
}

// Enregistrement dans l'historique des reports
$stmt = $pdo->prepare("
    INSERT INTO reports (billet_id, agent, created_at)
    VALUES (?, ?, NOW())
");
$stmt->execute([$id, $agent]);

$pdo->commit();


        $pdo->commit();
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Erreur report billet {$id}: " . $e->getMessage());
        die("❌ Une erreur est survenue lors du report, veuillez réessayer.");
    }

    header("Location: billets_emis.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Reporter billet</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">

<div class="container mt-5">
<a href="billets_emis.php" class="btn btn-secondary">⬅️ Retour</a>
<h3>🔁 Reporter le billet</h3>

<div class="card mt-3">
<div class="card-body">

<p><strong>Passager :</strong> <?= htmlspecialchars($billet['nom']) ?></p>
<p><strong>Trajet initial :</strong> <?= htmlspecialchars($billet['depart']) ?> → <?= htmlspecialchars($billet['destination']) ?></p>

<form method="POST" id="formReport">

<label>Choisir nouvelle date / trajet :</label>

<select name="traversee_id" id="traversee_id" class="form-select mb-3" required>
<option value="">-- Sélectionner --</option>
<?php foreach($traversees as $t): ?>
<option value="<?= $t['id'] ?>">
<?= htmlspecialchars($t['depart']) ?> → <?= htmlspecialchars($t['destination']) ?> —
<?= date('d/m/Y H:i', strtotime($t['date_depart'])) ?>
<?= ($t['depart'] === $billet['destination'] && $t['destination'] === $billet['depart']) ? ' (trajet retour)' : '' ?>
</option>
<?php endforeach; ?>
</select>

<!-- champs cachés remplis via le plan d'embarquement -->
<input type="hidden" name="place_id" id="place_id">
<input type="hidden" name="numero_place" id="numero_place">

<div id="placeChoisie" class="alert alert-info d-none mb-3"></div>

<button type="button" id="btnPlan" class="btn btn-primary w-100 mb-2" disabled>
🗺️ Choisir ma place sur le plan d'embarquement
</button>

<button type="submit" id="btnConfirmer" class="btn btn-success w-100" disabled>
✅ Confirmer le report
</button>

</form>

</div>
</div>

</div>

<script>
const selectTraversee   = document.getElementById('traversee_id');
const btnPlan           = document.getElementById('btnPlan');
const btnConfirmer      = document.getElementById('btnConfirmer');
const inputPlaceId      = document.getElementById('place_id');
const inputNumeroPlace  = document.getElementById('numero_place');
const divPlaceChoisie   = document.getElementById('placeChoisie');

// on active le bouton "plan" seulement quand une traversée est choisie
selectTraversee.addEventListener('change', function(){
    const dispo = !!this.value;
    btnPlan.disabled = !dispo;

    // si on change de traversée, on invalide la place précédemment choisie
    inputPlaceId.value = '';
    inputNumeroPlace.value = '';
    divPlaceChoisie.classList.add('d-none');
    btnConfirmer.disabled = true;
});

// ouverture du plan d'embarquement en popup pour la traversée choisie
btnPlan.addEventListener('click', function(){
    const traverseeId = selectTraversee.value;

    if(!traverseeId){
        return;
    }

    window.open(
        'admin_disponibilites.php?id=' + encodeURIComponent(traverseeId) + '&mode=report',
        'planEmbarquement',
        'width=1000,height=750,scrollbars=yes'
    );
});

// réception de la place choisie depuis le popup
window.addEventListener('message', function(event){

    if(!event.data || !event.data.id_place){
        return;
    }

    inputPlaceId.value     = event.data.id_place;
    inputNumeroPlace.value = event.data.numero;

    divPlaceChoisie.classList.remove('d-none');
    divPlaceChoisie.textContent =
        '✔️ Place sélectionnée : n°' + event.data.numero +
        ' (' + event.data.type + ')';

    btnConfirmer.disabled = false;
});
</script>

</body>
</html>
