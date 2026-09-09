<?php

session_start();
require_once '../config/database.php';

require_once __DIR__ . '/includes/auth.php';

// 🔐 sécurité
if (!isset($_SESSION['user'])) {
    header("Location: index.php");
    exit;
}

$id = $_GET['id'];

// 🔥 récupérer voyage
$stmt = $pdo->prepare("SELECT * FROM traversees WHERE id=?");
$stmt->execute([$id]);
$traversee = $stmt->fetch();

// 🔥 récupérer places
$stmt = $pdo->prepare("
    SELECT *
    FROM places
    WHERE traversee_id=?
    ORDER BY type_place, numero_place
");
$stmt->execute([$id]);

$places = $stmt->fetchAll();

$placesData = [];

foreach($places as $p){

    $placesData[$p['type_place']][] = $p;

}

$message = "";
$plans = [

"Pullman" => [

        "A2.6",  "A2.7",  "A2.8",  "A2.9",
        "B2.10", "B2.11", "B2.12", "B2.13", "B2.14", "B2.15",
        "B2.2",  "B2.3",  "B2.4",  "B2.5",  "B2.6",  "B2.7",  "B2.8",  "B2.9",
        "C2.10", "C2.11", "C2.12", "C2.13", "C2.14", "C2.15",
        "C2.2",  "C2.3",  "C2.4",  "C2.5",  "C2.6",  "C2.7",
        "C2.8",  "C2.9",
        "D2.10", "D2.11", "D2.12", "D2.13", "D2.14", "D2.15",
        "D2.2",  "D2.3",  "D2.4",  "D2.5",  "D2.6",  "D2.7",  "D2.8",  "D2.9",
        "E2.10", "E2.11", "E2.12", "E2.13", "E2.14", "E2.15",
        "E2.2",  "E2.3",  "E2.4",  "E2.5",  "E2.6",  "E2.7",  "E2.8",  "E2.9",
        "F2.10", "F2.11", "F2.12", "F2.13", "F2.14", "F2.15",
        "F2.2",  "F2.3",  "F2.4",  "F2.5",  "F2.6",  "F2.7",  "F2.8",  "F2.9",
        "G2.1",  "G2.10", "G2.11", "G2.12", "G2.13", "G2.14",
        "G2.15", "G2.16", "G2.2",  "G2.3",  "G2.4",  "G2.5",  "G2.6",  "G2.7",  "G2.8",  "G2.9",
        "H2.1",  "H2.10", "H2.11", "H2.12", "H2.13", "H2.14",
        "H2.15", "H2.16",
        "H2.2",  "H2.3",  "H2.4",  "H2.5",  "H2.6",  "H2.7",  "H2.8",  "H2.9",
        "I2.1",  "I2.13", "I2.14", "I2.15", "I2.16",
        "I2.2",  "I2.3",  "I2.4",
        "J2.1",  "J2.13", "J2.14", "J2.15", "J2.16",
        "J2.2",  "J2.3",  "J2.4",
                // Rangée A
        "A3.14", "A3.15",
        "A3.7",  "A3.8",
 
        // Rangée B
        "B3.1",
        "B3.14", "B3.15", "B3.16", "B3.17", "B3.18",
        "B3.19",
        "B3.2",  "B3.20", "B3.21",
        "B3.3",  "B3.4",  "B3.5",  "B3.6",  "B3.7",  "B3.8",
 
        // Rangée C
        "C3.1",
        "C3.14", "C3.15", "C3.16", "C3.17", "C3.18", "C3.19",
        "C3.2",  "C3.20", "C3.21",
        "C3.3",  "C3.4",  "C3.5",  "C3.6",  "C3.7",  "C3.8",
 
        // Rangée D
        "D3.1",  "D3.10", "D3.11", "D3.12",
        "D3.13", "D3.14", "D3.15", "D3.16", "D3.17", "D3.18", "D3.19",
        "D3.2",  "D3.20", "D3.21",
        "D3.3",  "D3.4",  "D3.5",  "D3.6",  "D3.7",  "D3.8",  "D3.9",
 
        // Rangée E
        "E3.1",  "E3.10", "E3.11", "E3.12", "E3.13", "E3.14", "E3.15",
        "E3.19",
        "E3.2",  "E3.20", "E3.21",
        "E3.3",  "E3.7",  "E3.8",  "E3.9",
 
        // Rangée F
        "F3.1",  "F3.10", "F3.11", "F3.12", "F3.13", "F3.14", "F3.15", "F3.16",
        "F3.17", "F3.18", "F3.19",
        "F3.2",  "F3.20", "F3.21",
        "F3.3",  "F3.4",  "F3.5",  "F3.6",  "F3.7",  "F3.8",  "F3.9",
 
        // Rangée G
        "G3.1",  "G3.10", "G3.11", "G3.12", "G3.13", "G3.14", "G3.15",
        "G3.16", "G3.17", "G3.18", "G3.19",
        "G3.2",  "G3.20", "G3.21",
        "G3.3",  "G3.4",  "G3.5",  "G3.6",  "G3.7",  "G3.8",  "G3.9",
 
        // Rangée H
        "H3.1",  "H3.16", "H3.17", "H3.18", "H3.19",
        "H3.2"

],

"Cabine 2 places Homme" => [

    "301.14A",
    "301.14B",

    "301.18A",
    "301.18B",

    "301.24A",
    "301.24B",

    "401.08A",
    "401.08B",

    "401.14A",
    "401.14B",
    "401.14C",
    "401.14D"

],

"Cabine 2 places Femme" => [

    "301.15A",
    "301.15B",

    "401.01A",
    "401.01B"

],

    "Cabine 4 places Homme" => [
 
        // Pont 3 — Cabines 301
        "301.17A", "301.17B", "301.17C", "301.17D",
        "301.26A", "301.26B", "301.26C", "301.26D",
 
        // Pont 4 — Cabines 401/402
        "401.24A", "401.24B", "401.24C", "401.24D",
        "401.30A", "401.30B", "401.30C", "401.30D",
        "401.32A", "401.32B", "401.32C", "401.32D",
        "402.03 VIP",
        "402.04A", "402.04B", "402.04C", "402.04D",
        "402.08A", "402.08B", "402.08C", "402.08D",
        "402.12A", "402.12B", "402.12C", "402.12D",
 
    ],

"Cabine 4 places Femme" => [

    "401.01A",
    "401.01B",

    "401.11A",
    "401.11B",
    "401.11C",
    "401.11D",

    "401.15B",
    "401.15C",
    "401.15D",

    "401.21A",
    "401.21B",
    "401.21C",
    "401.21D",

    "301.15A",
    "301.15B",

    "301.17D"

],

"Cabine 4 places Mixte" => [

    "401.04A",
    "401.04B",
    "401.04C",
    "401.04D",

    "VIP1",

    "401.22A",
    "401.22B",
    "401.22C",
    "401.22D",

    "401.32A",
    "401.32B",
    "401.32C",
    "401.32D",

    "402.04A",
    "402.04B",
    "402.04C",
    "402.04D",

    "402.08A",
    "402.08B",
    "402.08C",
    "402.08D",

    "402.12A",
    "402.12B",
    "402.12C",
    "402.12D"

],

  "Cabine 8 places Homme" => [
 
        // Pont 3 — Cabines 302
        "302.01A", "302.01B", "302.01C", "302.01D", "302.01E", "302.01F", "302.01G", "302.01H",
        "302.02A", "302.02B", "302.02C", "302.02D", "302.02E", "302.02F", "302.02G", "302.02H",
        "302.04A", "302.04B", "302.04C", "302.04D", "302.04E", "302.04F", "302.04G", "302.04H",
        "302.05A", "302.05B", "302.05C", "302.05D", "302.05E", "302.05F", "302.05G", "302.05H",
        "302.07A", "302.07B", "302.07C", "302.07D", "302.07E", "302.07F", "302.07G", "302.07H",
        "302.10A", "302.10B", "302.10C", "302.10D", "302.10E", "302.10F", "302.10G", "302.10H",
        "302.11A", "302.11B", "302.11C", "302.11D", "302.11E", "302.11F", "302.11G", "302.11H",
        "302.12A", "302.12B", "302.12C", "302.12D", "302.12E", "302.12F", "302.12G", "302.12H",
        "302.13A", "302.13B", "302.13C", "302.13D", "302.13E", "302.13F", "302.13G", "302.13H",
        "302.16A", "302.16B", "302.16C", "302.16D", "302.16E", "302.16F", "302.16G", "302.16H",
        "302.17A", "302.17B", "302.17C", "302.17D", "302.17E", "302.17F", "302.17G", "302.17H",
        "302.18A", "302.18B", "302.18C", "302.18D", "302.18E", "302.18F", "302.18G", "302.18H",
        "302.21A", "302.21B", "302.21C", "302.21D", "302.21E", "302.21F", "302.21G", "302.21H",
        "302.22A", "302.22B", "302.22C", "302.22D", "302.22E", "302.22F", "302.22G", "302.22H",
 
    ],

    "Cabine 8 places Femme" => [
 
        // Pont 3 — Cabines 302
        "302.03A", "302.03B", "302.03C", "302.03D", "302.03E", "302.03F", "302.03G", "302.03H",
        "302.08A", "302.08B", "302.08C", "302.08D", "302.08E", "302.08F", "302.08G", "302.08H",
        "302.09A", "302.09B", "302.09C", "302.09D", "302.09E", "302.09F", "302.09G", "302.09H",
        "302.14A", "302.14B", "302.14C", "302.14D", "302.14E", "302.14F", "302.14G", "302.14H",
        "302.15A", "302.15B", "302.15C", "302.15D", "302.15E", "302.15F", "302.15G", "302.15H",
        "302.19A", "302.19B", "302.19C", "302.19D", "302.19E", "302.19F", "302.19G", "302.19H",
        "302.20A", "302.20B", "302.20C", "302.20D", "302.20E", "302.20F", "302.20G", "302.20H",
 
    ],
"Cabine 8 places Mixte" => [

    "302.01A","302.01B",
    "302.01C","302.01D",

    "302.03A","302.03B",
    "302.03D","302.03E",

    "302.04A","302.04B",
    "302.04C",

    "302.05D",

    "302.07A","302.07B",
    "302.07C","302.07D",

    "302.10A","302.10B",
    "302.10D",

    "302.11B",

    "302.14D",

    "302.17D",

    "302.18B",

    "302.19D",

    "302.20D"

]

];

/* =========================
   PLACES AUTOMATIQUES
   (toujours insérées en base, pas besoin de les cocher)
========================= */

$sieges_obligatoires = [
    "Cabine 8 places Homme" => [
        "302.12A", "302.12B", "302.12C", "302.12D",
        "302.12E", "302.12F", "302.12G", "302.12H"
    ],
    "Cabine 8 places Femme" => [
        "302.15A", "302.15B", "302.15C", "302.15D",
        "302.15E", "302.15F", "302.15G", "302.15H"
    ],
    "Pullman" => []
];

// 🔁 Génération Pullman : A2.6 à A2.15
for($i = 6; $i <= 9; $i++){
    $sieges_obligatoires["Pullman"][] = "A2.$i";
}

// 🔁 Génération Pullman : B2.2 à B2.15
for($i = 2; $i <= 15; $i++){
    $sieges_obligatoires["Pullman"][] = "B2.$i";
}

// 🔁 Génération Pullman : C2.2 à C2.15
for($i = 2; $i <= 15; $i++){
    $sieges_obligatoires["Pullman"][] = "C2.$i";
}

// 🔁 Génération Pullman : D2.2 et D2.3
for($i = 2; $i <= 3; $i++){
    $sieges_obligatoires["Pullman"][] = "D2.$i";
}


// 🔒 Places automatiques à masquer du formulaire (affichage uniquement)
$places_auto_affichage = [
    "Cabine 8 places Homme" => [
        "302.12A","302.12B","302.12C","302.12D","302.12E","302.12F","302.12G","302.12H"
    ],
    "Cabine 8 places Femme" => [
        "302.15A","302.15B","302.15C","302.15D","302.15E","302.15F","302.15G","302.15H"
    ]
];
$placesExistantes = [];

foreach($places as $p){

    $placesExistantes[] = $p['numero_place'];

}

/* ========================
   TRAITEMENT
======================== */
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    try {

        // Mise à jour de la traversée
        $stmt = $pdo->prepare("
            UPDATE traversees
            SET depart=?,
                destination=?,
                date_depart=?,
                bateau=?
            WHERE id=?
        ");

        $stmt->execute([
            $_POST['depart'],
            $_POST['destination'],
            $_POST['date_depart'],
            $_POST['bateau'],
            $id
        ]);

        /* ==========================
           SUPPRESSION DES PLACES
        ========================== */

        /* ==========================
   GESTION DES PLACES COCHÉES
========================== */

$siegesChoisis = $_POST['sieges'] ?? [];

/*
 * 1. Supprimer les places décochées
 */

foreach($places as $place){

    $conserver = false;

    if(isset($siegesChoisis[$place['type_place']])){

        if(
            in_array(
                $place['numero_place'],
                $siegesChoisis[$place['type_place']]
            )
        ){
            $conserver = true;
        }
    }

    if(!$conserver && $place['restant'] > 0){

        $stmt = $pdo->prepare("
            DELETE FROM places
            WHERE id_place=?
        ");

        $stmt->execute([
            $place['id_place']
        ]);
    }
}

/*
 * 2. Ajouter les nouvelles places cochées
 */

foreach($siegesChoisis as $type => $liste){

    foreach($liste as $numeroPlace){

        $check = $pdo->prepare("
            SELECT COUNT(*)
            FROM places
            WHERE traversee_id=?
            AND numero_place=?
        ");

        $check->execute([
            $id,
            $numeroPlace
        ]);

        if($check->fetchColumn() == 0){

            $stmt = $pdo->prepare("
                INSERT INTO places
                (
                    traversee_id,
                    type_place,
                    numero_place,
                    total,
                    restant
                )
                VALUES (?, ?, ?, 1, 1)
            ");

            $stmt->execute([
                $id,
                $type,
                $numeroPlace
            ]);
        }
    }
}

        /* ==========================
           AJOUT D'UNE PLACE
        ========================== */

        if(
            !empty($_POST['nouvelle_place'])
            &&
            !empty($_POST['nouveau_type'])
        ){

            $numeroPlace = strtoupper(
                trim($_POST['nouvelle_place'])
            );

            $check = $pdo->prepare("
                SELECT COUNT(*)
                FROM places
                WHERE traversee_id=?
                AND numero_place=?
            ");

            $check->execute([
                $id,
                $numeroPlace
            ]);

            if($check->fetchColumn() == 0){

                $stmt = $pdo->prepare("
                    INSERT INTO places
                    (
                        traversee_id,
                        type_place,
                        numero_place,
                        total,
                        restant
                    )
                    VALUES (?, ?, ?, 1, 1)
                ");

                $stmt->execute([
                    $id,
                    $_POST['nouveau_type'],
                    $numeroPlace
                ]);
            }
        }

        $message = "✅ Voyage modifié avec succès";

    } catch (Exception $e) {

        $message = "❌ Erreur : ".$e->getMessage();

    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Modifier voyage</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">

<div class="container mt-5">

<h3 class="mb-4">✏️ Modifier voyage</h3>

<?php if($message): ?>
<div class="alert alert-info"><?= $message ?></div>
<?php endif; ?>

<form method="POST">

<select class="form-select mb-3" name="depart" required>
<option><?= $traversee['depart'] ?></option>
<option>DAKAR</option>
<option>ZIGUINCHOR</option>
<option>CARABANE</option>
</select>

<select class="form-select mb-3" name="destination" required>
<option><?= $traversee['destination'] ?></option>
<option>DAKAR</option>
<option>ZIGUINCHOR</option>
<option>CARABANE</option>
</select>

<input class="form-control mb-3" type="datetime-local" name="date_depart"
value="<?= date('Y-m-d\TH:i', strtotime($traversee['date_depart'])) ?>" required>

<select class="form-select mb-3" name="bateau" required>
<option><?= $traversee['bateau'] ?></option>
<option>A.S.D DIAMBOGNE</option>
<option>AGUENE</option>
</select>

<button class="btn btn-success mt-3">💾 Enregistrer</button>
<a href="liste_traversees.php" class="btn btn-secondary mt-3">⬅️ Retour</a>

<h5 class="mb-3">🚢 Plan d'embarquement</h5>

<?php foreach($plans as $type => $liste): ?>

<div class="card mb-4">

    <div class="card-header fw-bold">
        <?= $type ?>
    </div>

    <div class="card-body">

        <div class="row">

            <?php foreach($liste as $numero): ?>

                <div class="col-md-2 mb-2">

                    <div class="form-check border rounded p-2">

                        <input
                            class="form-check-input"
                            type="checkbox"
                            name="sieges[<?= $type ?>][]"
                            value="<?= $numero ?>"
                            <?= in_array($numero, $placesExistantes) ? 'checked' : '' ?>
                        >

                        <label class="form-check-label">
                            <?= $numero ?>
                        </label>

                    </div>

                </div>

            <?php endforeach; ?>

        </div>

    </div>

</div>

<?php endforeach; ?>
<button class="btn btn-success mt-3">💾 Enregistrer</button>
<a href="liste_traversees.php" class="btn btn-secondary mt-3">⬅️ Retour</a>

</form>

</div>
</body>
</html>
