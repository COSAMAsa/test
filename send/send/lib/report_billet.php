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

    // 🔒 vérifier que la place appartient bien à la traversée choisie et qu'elle est dispo
    $stmt = $pdo->prepare("SELECT * FROM places WHERE id_place = ? AND traversee_id = ?");
    $stmt->execute([$place_id, $new_traversee]);
    $place = $stmt->fetch();

    if(!$place || $place['restant'] <= 0){
        die("❌ Cette place n'est plus disponible.");
    }

    // 🔥 mise à jour du billet : nouvelle traversée + nouvelle place + report marqué
    $pdo->beginTransaction();

    // 🆕 On garde une trace de l'ancienne place ET de l'ancienne traversée
    // AVANT de les écraser dans billets, pour pouvoir afficher
    // "traversée ancienne" à côté de "traversée actuelle" dans
    // billets_reportes.php
    $ancienne_place_id = $billet['id_place'];
    $ancienne_traversee_id = $billet['traversee_id'];

    // 🔧 DEBUG TEMPORAIRE - à retirer une fois le test validé
    error_log("REPORT DEBUG : billet={$id} ancienne_traversee={$ancienne_traversee_id} ancienne_place={$ancienne_place_id} nouvelle_place={$place_id} nouvelle_traversee={$new_traversee}");
    // 🔧 FIN DEBUG TEMPORAIRE

    $stmt = $pdo->prepare("
    UPDATE billets 
    SET traversee_id = ?, id_place = ?, ancienne_traversee_id = ?, ancienne_place_id = ?, report_effectue = 1 
    WHERE id = ?
    ");
    $stmt->execute([$new_traversee, $place_id, $ancienne_traversee_id, $ancienne_place_id, $id]);

    // 🆕 libérer l'ancienne place (elle redevient disponible sur son ancienne traversée)
    $stmt = $pdo->prepare("UPDATE places SET restant = restant + 1 WHERE id_place = ?");
    $stmt->execute([$ancienne_place_id]);

    // 🔧 DEBUG TEMPORAIRE
    error_log("REPORT DEBUG : libération ancienne place {$ancienne_place_id} -> lignes affectées = " . $stmt->rowCount());
    // 🔧 FIN DEBUG TEMPORAIRE

    // décrémenter la place prise sur la nouvelle traversée
    $stmt = $pdo->prepare("UPDATE places SET restant = restant - 1 WHERE id_place = ?");
    $stmt->execute([$place_id]);

    $pdo->commit();

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
