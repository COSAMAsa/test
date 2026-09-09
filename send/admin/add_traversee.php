<?php
require_once '../config/database.php';
require_once __DIR__ . '/includes/auth.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 👑 autorisation
if(
    $_SESSION['user']['role'] != 'admin'
    &&
    $_SESSION['user']['role'] != 'exploitation'
){
    die("⛔ Accès refusé");
}

// 🔐 sécurité
if (!isset($_SESSION['user'])) {
    header("Location: index.php");
    exit;
}

$message = "";

/* =========================
   PLANS D'EMBARQUEMENT PAR BATEAU
   ⚠️ Chaque bateau a son propre plan de sièges.
   Complétez "AGUENE" et "DIAMBOGNE" avec le même format
   que "A.S.D" dès que vous aurez les listes de sièges.
========================= */

$plans_par_bateau = [

    "A.S.D" => [

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
            "301.14A", "301.14B",
            "301.18A", "301.18B",
            "301.24A", "301.24B",
            "401.08A", "401.08B",
            "401.14A", "401.14B", "401.14C", "401.14D"
        ],

        "Cabine 2 places Femme" => [
            "301.15A", "301.15B",
            "401.01A", "401.01B"
        ],

        "Cabine 2 places Mixte" => [
            "301.24A", "301.24B"
        ],

        "Cabine 4 places Homme" => [
            // Pont 3 — Cabines 301
            "301.17A", "301.17B", "301.17C", "301.17D",
            "301.26A", "301.26B", "301.26C", "301.26D",

            // Pont 4 — Cabines 401/402
            "401.14A", "401.14B", "401.14C", "401.14D",
            "401.16A", "401.16B", "401.16C", "401.16D",
            "401.24A", "401.24B", "401.24C", "401.24D",
            "401.30A", "401.30B", "401.30C", "401.30D",
            "401.32A", "401.32B", "401.32C", "401.32D",
            "402.03 VIP",
            "402.04A", "402.04B", "402.04C", "402.04D",
            "402.08A", "402.08B", "402.08C", "402.08D",
            "402.12A", "402.12B", "402.12C", "402.12D",
        ],

        "Cabine 4 places Femme" => [
            "401.01A", "401.01B",
            "401.11A", "401.11B", "401.11C", "401.11D",
            "401.15A", "401.15B", "401.15C", "401.15D",
            "401.21A", "401.21B", "401.21C", "401.21D",
            "301.15A", "301.15B",
            "301.17D"
        ],

        "Cabine 4 places Mixte" => [
            "401.04A", "401.04B", "401.04C", "401.04D",
            "VIP1",
            "401.22A", "401.22B", "401.22C", "401.22D",
            "401.32A", "401.32B", "401.32C", "401.32D",
            "402.04A", "402.04B", "402.04C", "402.04D",
            "402.08A", "402.08B", "402.08C", "402.08D",
            "402.12A", "402.12B", "402.12C", "402.12D"
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
            "302.01A", "302.01B", "302.01C", "302.01D", "302.01E", "302.01F", "302.01G", "302.01H",
            "302.03A", "302.03B", "302.03C", "302.03D", "302.03E", "302.03F", "302.03G", "302.03H",
            "302.05A", "302.05B", "302.05C", "302.05D", "302.05E", "302.05F", "302.05G", "302.05H",
            "302.08A", "302.08B", "302.08C", "302.08D", "302.08E", "302.08F", "302.08G", "302.08H",
            "302.09A", "302.09B", "302.09C", "302.09D", "302.09E", "302.09F", "302.09G", "302.09H",
            "302.14A", "302.14B", "302.14C", "302.14D", "302.14E", "302.14F", "302.14G", "302.14H",
            "302.15A", "302.15B", "302.15C", "302.15D", "302.15E", "302.15F", "302.15G", "302.15H",
            "302.19A", "302.19B", "302.19C", "302.19D", "302.19E", "302.19F", "302.19G", "302.19H",
            "302.20A", "302.20B", "302.20C", "302.20D", "302.20E", "302.20F", "302.20G", "302.20H",
        ],

        "Cabine 8 places Mixte" => [
            "302.01A", "302.01B",
            "302.01C", "302.01D",

            "302.03A", "302.03B",
            "302.03C", "302.03D", "302.03E",
            "302.03F", "302.03G", "302.03H",

            "302.04A", "302.04B",
            "302.04C",

            "302.07A", "302.07B", "302.07C",
            "302.07D",
            "302.07E", "302.07F", "302.057", "302.057",



            "302.10A", "302.10B",
            "302.10D",

            "302.12B",

            "302.14D",

            "302.17D",

            "302.18B",

            "302.19D",

            "302.20D"
        ]
    ],

    "AGUENE" => [

        "Pullman" => [

            // --- AVANT (109 pax) ---
            "A2.1",  "A2.2",  "A2.3",  "A2.4",  "A2.5",  "A2.6",  "A2.7",  "A2.8",
            "A2.9",  "A2.10", "A2.11", "A2.12", "A2.13", "A2.14", "A2.15",

            "B2.1",  "B2.2",  "B2.3",  "B2.4",  "B2.5",  "B2.6",  "B2.7",  "B2.8",
            "B2.9",  "B2.10", "B2.11", "B2.12", "B2.13", "B2.14", "B2.15",

            "C2.1",  "C2.2",  "C2.3",  "C2.4",  "C2.5",  "C2.6",  "C2.7",  "C2.8",
            "C2.9",  "C2.10", "C2.11", "C2.12", "C2.13", "C2.14", "C2.15",

            "D2.1",  "D2.2",  "D2.3",  "D2.4",  "D2.5",  "D2.6",  "D2.7",  "D2.8",
            "D2.9",  "D2.10", "D2.11", "D2.12", "D2.13", "D2.14", "D2.15",

            "E2.1",  "E2.2",  "E2.3",  "E2.4",  "E2.5",  "E2.6",  "E2.7",  "E2.8",
            "E2.9",  "E2.10", "E2.11", "E2.12", "E2.13", "E2.14", "E2.15",

            "F2.1",  "F2.2",  "F2.3",  "F2.4",  "F2.5",  "F2.6",  "F2.7",  "F2.8",
            "F2.9",  "F2.10", "F2.11", "F2.12", "F2.13", "F2.14", "F2.15",

            "G2.1",  "G2.2",  "G2.3",  "G2.4",  "G2.5",  "G2.6",  "G2.7",  "G2.8",
            "G2.9",  "G2.10", "G2.11", "G2.12", "G2.13", "G2.14",

            "H2.6",  "H2.7",  "H2.8",  "H2.9",  "H2.10",

            // --- ARRIERE (97 pax) ---
            "A3.1",  "A3.2",  "A3.3",  "A3.4",  "A3.5",  "A3.6",  "A3.7",  "A3.8",
            "A3.9",  "A3.10", "A3.11",

            "B3.1",  "B3.2",  "B3.3",  "B3.4",  "B3.5",  "B3.6",  "B3.7",  "B3.8",
            "B3.9",  "B3.10", "B3.11", "B3.12", "B3.13", "B3.14", "B3.15",

            "C3.1",  "C3.2",  "C3.3",  "C3.4",  "C3.5",  "C3.6",  "C3.7",  "C3.8",
            "C3.9",  "C3.10", "C3.11", "C3.12", "C3.13", "C3.14", "C3.15",

            "D3.1",  "D3.2",  "D3.3",  "D3.4",  "D3.5",  "D3.6",  "D3.7",  "D3.8",
            "D3.9",  "D3.10", "D3.11", "D3.12", "D3.13", "D3.14", "D3.15",

            "E3.1",  "E3.2",  "E3.3",  "E3.4",  "E3.5",  "E3.6",  "E3.7",  "E3.8",
            "E3.9",  "E3.10", "E3.11", "E3.12", "E3.13", "E3.14", "E3.15",

            "F3.1",  "F3.2",  "F3.3",  "F3.4",  "F3.5",  "F3.6",  "F3.7",  "F3.8",
            "F3.9",  "F3.10", "F3.11", "F3.12", "F3.13", "F3.14", "F3.15",

            "G3.6",  "G3.7",  "G3.8",  "G3.9",  "G3.10", "G3.11",

            // --- ROOM (également des places Pullman) ---
            "ROOM A.1", "ROOM A.2", "ROOM A.3", "ROOM A.4",
            "ROOM B.1", "ROOM B.2", "ROOM B.3", "ROOM B.4",
            "ROOM C.1", "ROOM C.2", "ROOM C.3", "ROOM C.4",

        ],

    ],

    // 🚧 TODO : compléter avec la vraie liste de sièges de DIAMBOGNE
    "DIAMBOGNE" => [
        // "Pullman" => [ ... ],
        // "Cabine 2 places Homme" => [ ... ],
        // ...
    ],

];

/* =========================
   PLACES AUTOMATIQUES PAR BATEAU
   (toujours insérées en base, pas besoin de les cocher)
   🚧 TODO : adapter/compléter pour AGUENE et DIAMBOGNE
   une fois que vous connaîtrez leurs plans.
========================= */

$sieges_obligatoires_par_bateau = [

    "A.S.D" => [
      'Cabine 8 places Homme' => [
    '302.11A','302.11B','302.11C','302.11D',
    '302.11E','302.11F','302.11G','302.11H',
    '302.12A','302.12B','302.12C','302.12D',
    '302.12E','302.12F','302.12G','302.12H'
],

'Cabine 8 places Femme' => [
    '302.03A','302.03B','302.03C','302.03D',
    '302.03E','302.03F','302.03G','302.03H',
    '302.05A','302.05B','302.05C','302.05D',
    '302.05E','302.05F','302.05G','302.05H'
],

'Cabine 8 places Mixte' => [
    '302.01A','302.01B','302.01C','302.01D',
    '302.01E','302.01F','302.01G','302.01H',
    '302.07A','302.07B','302.07C','302.07D',
    '302.07E','302.07F','302.07G','302.07H'
],

'Cabine 4 places Homme' => [
    '401.14A','401.14B','401.14C','401.14D',
    '401.16A','401.16B','401.16C','401.16D'
],

'Cabine 4 places Femme' => [
    '401.11A','401.11B','401.11C','401.11D',
    '401.15A','401.15B','401.15C','401.15D'
],

'Cabine 2 places Homme' => [
    '401.08A','401.08B'
],

'Cabine 2 places Femme' => [
    '401.01A','401.01B'
],

'Cabine 2 places Mixte' => [
    '301.24A','301.24B'
],

'Cabine 4 places Mixte' => [
    '402.04A','402.04B','402.04C','402.04D',
    '402.08A','402.08B','402.08C','402.08D'
],
        "Pullman" => []
    ],

    "AGUENE" => [
        // 🚧 TODO
    ],

    "DIAMBOGNE" => [
        // 🚧 TODO
    ],

];

// 🔁 Génération Pullman pour A.S.D : A2.6 à A2.9


// 🔁 Génération Pullman pour A.S.D : B2.2 à B2.15
for($i = 2; $i <= 15; $i++){
    $sieges_obligatoires_par_bateau["A.S.D"]["Pullman"][] = "B2.$i";
}

// 🔁 Génération Pullman pour A.S.D : C2.2 à C2.15
for($i = 2; $i <= 15; $i++){
    $sieges_obligatoires_par_bateau["A.S.D"]["Pullman"][] = "C2.$i";
}

// 🔁 Génération Pullman pour A.S.D : D2.2 à D2.15
for($i = 2; $i <= 15; $i++){
    $sieges_obligatoires_par_bateau["A.S.D"]["Pullman"][] = "D2.$i";
}

// 🔁 Génération Pullman pour A.S.D : E2.2 à E2.15
for($i = 2; $i <= 15; $i++){
    $sieges_obligatoires_par_bateau["A.S.D"]["Pullman"][] = "E2.$i";
}

// 🔁 Génération Pullman pour A.S.D : F2.2 à F2.15
for($i = 2; $i <= 15; $i++){
    $sieges_obligatoires_par_bateau["A.S.D"]["Pullman"][] = "F2.$i";
}

// 🔁 Génération Pullman pour A.S.D : G2.1 à G2.16
for($i = 1; $i <= 16; $i++){
    $sieges_obligatoires_par_bateau["A.S.D"]["Pullman"][] = "G2.$i";
}

// 🔁 Génération Pullman pour A.S.D : H2.1 à H2.16
for($i = 1; $i <= 16; $i++){
    $sieges_obligatoires_par_bateau["A.S.D"]["Pullman"][] = "H2.$i";
}

// 🔁 Génération Pullman pour A.S.D : I2.1 à I2.4 et I2.13 à I2.16
for($i = 1; $i <= 4; $i++){
    $sieges_obligatoires_par_bateau["A.S.D"]["Pullman"][] = "I2.$i";
}
for($i = 13; $i <= 16; $i++){
    $sieges_obligatoires_par_bateau["A.S.D"]["Pullman"][] = "I2.$i";
}

// 🔁 Génération Pullman pour A.S.D : J2.1 à J2.4 et J2.13 à J2.16
for($i = 1; $i <= 4; $i++){
    $sieges_obligatoires_par_bateau["A.S.D"]["Pullman"][] = "J2.$i";
}
for($i = 13; $i <= 16; $i++){
    $sieges_obligatoires_par_bateau["A.S.D"]["Pullman"][] = "J2.$i";
}

// 🔒 Les places automatiques sont masquées du formulaire et ajoutées
// automatiquement à la création du voyage.
// IMPORTANT : on utilise exactement la même source que les places obligatoires
// afin d'éviter qu'une place soit obligatoire mais visible/cochable, ou inversement.
$places_auto_affichage_par_bateau = $sieges_obligatoires_par_bateau;


$bateaux_disponibles = ["A.S.D", "AGUENE", "DIAMBOGNE"];

/* =========================
   TRAITEMENT
========================= */

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    try {

        if($_POST['depart'] == $_POST['destination']){
            throw new Exception(
                "⛔ Le départ et la destination doivent être différents"
            );
        }

        $bateau = $_POST['bateau'];

        if(!in_array($bateau, $bateaux_disponibles)){
            throw new Exception("⛔ Bateau invalide");
        }

        // 🔥 EXTRAIRE DATE
        $date_jour = date(
            'Y-m-d',
            strtotime($_POST['date_depart'])
        );

        // 🚫 VERIFICATION DOUBLON
        $check = $pdo->prepare("
            SELECT COUNT(*)
            FROM traversees
            WHERE bateau = ?
            AND DATE(date_depart) = ?
        ");

        $check->execute([$bateau, $date_jour]);

        if($check->fetchColumn() > 0){

            throw new Exception(
                "⛔ Un voyage existe déjà pour ce bateau à cette date"
            );
        }

        // Générer le numéro du voyage
        $numeroVoyage = $pdo->query("
            SELECT COUNT(*) + 1
            FROM traversees
        ")->fetchColumn();

        $numeroVoyage = str_pad($numeroVoyage, 2, '0', STR_PAD_LEFT);

        // Abréviations
        $codes = [
            'DAKAR' => 'DKR',
            'ZIGUINCHOR' => 'ZIG',
            'CARABANE' => 'CAR'
        ];

        $departCode = $codes[$_POST['depart']];
        $destinationCode = $codes[$_POST['destination']];

        $dateVoyage = date(
            'Y-m-d',
            strtotime($_POST['date_depart'])
        );

        $referenceVoyage =
            "V{$numeroVoyage} {$departCode}-{$destinationCode} {$dateVoyage}";

        // ✅ INSERT TRAVERSEE
        $stmt = $pdo->prepare("
            INSERT INTO traversees
(
    reference_voyage,
    depart,
    destination,
    date_depart,
    bateau
)
VALUES (?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $referenceVoyage,
            $_POST['depart'],
            $_POST['destination'],
            $_POST['date_depart'],
            $bateau
        ]);

        $traversee_id = $pdo->lastInsertId();

        /* =========================
           FUSION SIEGES COCHES + AUTOMATIQUES
           (spécifiques au bateau sélectionné)
        ========================= */

        if(!isset($_POST['sieges'])){
            $_POST['sieges'] = [];
        }

        $sieges_obligatoires = $sieges_obligatoires_par_bateau[$bateau] ?? [];

        foreach($sieges_obligatoires as $type_place => $liste_obligatoire){

            if(!isset($_POST['sieges'][$type_place])){
                $_POST['sieges'][$type_place] = [];
            }

            foreach($liste_obligatoire as $numero){

                if(!in_array($numero, $_POST['sieges'][$type_place])){
                    $_POST['sieges'][$type_place][] = $numero;
                }
            }
        }

        /* =========================
           INSERTION DES SIEGES
        ========================= */

/* =========================
   INSERTION DES PLACES DU VOYAGE
========================= */

$places_a_creer = [];

/*
 * 1) AJOUTER EN PREMIER LES PLACES OBLIGATOIRES
 * Elles doivent TOUJOURS être créées et rester disponibles.
 * On ne les limite jamais avec array_slice().
 */
$sieges_obligatoires = $sieges_obligatoires_par_bateau[$bateau] ?? [];

foreach ($sieges_obligatoires as $type_place => $liste_obligatoire) {
    foreach ($liste_obligatoire as $numero_place) {
        $places_a_creer[] = [
            'type'   => $type_place,
            'numero' => $numero_place
        ];
    }
}

/*
 * 2) AJOUTER les places cochées dans le formulaire
 */
if (isset($_POST['sieges']) && is_array($_POST['sieges'])) {
    foreach ($_POST['sieges'] as $type_place => $liste) {
        if (!is_array($liste)) {
            continue;
        }

        foreach ($liste as $numero_place) {
            $places_a_creer[] = [
                'type'   => $type_place,
                'numero' => $numero_place
            ];
        }
    }
}

/*
 * 3) Supprimer les doublons en conservant les places obligatoires
 */
$uniques = [];

foreach ($places_a_creer as $place) {
    $cle = $place['type'] . '|' . $place['numero'];
    $uniques[$cle] = $place;
}

$places_a_creer = array_values($uniques);

/*
 * 4) NE PAS utiliser array_slice(0, 196) :
 * cela pouvait supprimer des places obligatoires si elles se trouvaient
 * après les places cochées.
 * On vérifie simplement que le plan final contient exactement 196 places.
 */
if (count($places_a_creer) !== 196) {
    throw new Exception(
        "⛔ Le plan du bateau {$bateau} contient "
        . count($places_a_creer)
        . " places sélectionnées/automatiques. Il faut exactement 196 places."
    );
}


/*
 * ==========================================
 * INSERTION DES 196 PLACES EN BASE
 * ==========================================
 */

$stmt = $pdo->prepare("
    INSERT INTO places
    (
        traversee_id,
        type_place,
        numero_place,
        total,
        restant
    )
    VALUES (?, ?, ?, ?, ?)
");

foreach ($places_a_creer as $place) {

    $stmt->execute([
        $traversee_id,
        $place['type'],
        $place['numero'],
        1,
        1
    ]);
}

        $message = "✅ Voyage ajouté avec succès";

    } catch (Exception $e) {

        $message = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="fr">

<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Ajouter voyage</title>

<link
href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css"
rel="stylesheet"
>

<style>

body{
    background:#f4f6f9;
}

.seat-box{
    position:relative;
}

.seat-box input{
    display:none;
}

.seat-box span{

    display:flex;

    align-items:center;

    justify-content:center;

    padding:15px;

    border-radius:12px;

    border:2px solid #0d6efd;

    cursor:pointer;

    transition:0.2s;

    font-weight:bold;

    text-align:center;

    min-height:70px;
}

.seat-box input:checked + span{

    background:#0d6efd;

    color:white;

    transform:scale(1.05);
}

.zone{

    display:grid;

    grid-template-columns:
    repeat(auto-fill,minmax(100px,1fr));

    gap:10px;
}

.card-header{
    font-size:18px;
}

.plan-bateau{
    display:none;
}

.plan-bateau.actif{
    display:block;
}

</style>

</head>

<body>

<div class="container mt-4 mb-5">

<div class="d-flex justify-content-between align-items-center mb-4">

<h3>➕ Ajouter un voyage</h3>

<a href="dashboard.php" class="btn btn-secondary">
⬅️ Retour
</a>

</div>

<?php if($message): ?>

<div class="alert alert-info text-center">

<?= $message ?>

</div>

<?php endif; ?>

<div class="card shadow">

<div class="card-body">

<form method="POST">

<!-- 🚩 DEPART -->

<select class="form-select mb-3" name="depart" required>

<option value="">Choisir départ</option>

<option value="DAKAR">DAKAR</option>

<option value="ZIGUINCHOR">ZIGUINCHOR</option>

<option value="CARABANE">CARABANE</option>

</select>

<!-- 🎯 DESTINATION -->

<select class="form-select mb-3" name="destination" required>

<option value="">Choisir destination</option>

<option value="DAKAR">DAKAR</option>

<option value="ZIGUINCHOR">ZIGUINCHOR</option>

<option value="CARABANE">CARABANE</option>

</select>

<!-- 📅 DATE -->

<input
class="form-control mb-3"
type="datetime-local"
name="date_depart"
required
>

<!-- 🚢 BATEAU -->

<select class="form-select mb-4" name="bateau" id="bateau" required>

<option value="">Choisir bateau</option>

<?php foreach($bateaux_disponibles as $b): ?>
<option value="<?= $b ?>"><?= $b ?></option>
<?php endforeach; ?>

</select>

<div class="alert alert-secondary">
ℹ️ Certaines places sont automatiquement réservées selon le bateau choisi, inutile de les cocher. Le plan de sièges ci-dessous change en fonction du bateau sélectionné.
</div>

<button class="btn btn-success w-100 mt-3">

✅ Ajouter le voyage

</button>

<h4 class="mb-4">
🚢 Sélection des sièges
</h4>

<?php foreach($bateaux_disponibles as $bateau_nom): ?>

<div class="plan-bateau" data-bateau="<?= $bateau_nom ?>">

<h5 class="mb-3">Plan — <?= $bateau_nom ?></h5>

<?php
$plans = $plans_par_bateau[$bateau_nom] ?? [];
$places_auto_affichage = $places_auto_affichage_par_bateau[$bateau_nom] ?? [];
?>

<?php if(empty($plans)): ?>

<div class="alert alert-warning">
🚧 Le plan de sièges pour <strong><?= $bateau_nom ?></strong> n'est pas encore configuré.
Aucune place ne sera enregistrée pour ce bateau tant que la liste des sièges
n'aura pas été ajoutée dans le code (<code>$plans_par_bateau["<?= $bateau_nom ?>"]</code>).
</div>

<?php else: ?>

<?php foreach($plans as $type => $liste): ?>

<div class="card mb-4">

<div class="card-header fw-bold">

<?php

$t = strtolower($type);

if(strpos($t,'homme') !== false){

    echo "👨 ";
}
elseif(strpos($t,'femme') !== false){

    echo "👩 ";
}
elseif(strpos($t,'mixte') !== false){

    echo "👨‍👩 ";
}
else{

    echo "🚢 ";
}
?>

<?= $type ?>

</div>

<div class="card-body">

<div class="zone">

<?php foreach($liste as $s): ?>

<?php
// 🔒 On masque les places automatiques du formulaire
if(
    isset($places_auto_affichage[$type])
    &&
    in_array($s, $places_auto_affichage[$type])
){
    continue;
}
?>

<label class="seat-box">

<input
type="checkbox"
name="sieges[<?= $type ?>][]"
value="<?= $s ?>"
>

<span>

<?php

if(strpos($t,'homme') !== false){

    echo "👨 ";
}
elseif(strpos($t,'femme') !== false){

    echo "👩 ";
}
elseif(strpos($t,'mixte') !== false){

    echo "👨‍👩 ";
}
?>

<?= $s ?>

</span>

</label>

<?php endforeach; ?>

</div>

</div>

</div>

<?php endforeach; ?>

<?php endif; ?>

</div>

<?php endforeach; ?>

<button class="btn btn-success w-100 mt-3">

✅ Ajouter le voyage

</button>

</form>

</div>

</div>

</div>

<script>

const depart = document.querySelector('[name="depart"]');
const destination = document.querySelector('[name="destination"]');
const bateauSelect = document.getElementById('bateau');
const plansBateau = document.querySelectorAll('.plan-bateau');

function verifierChoix(){

    if(
        depart.value &&
        destination.value &&
        depart.value === destination.value
    ){

        alert(
            "⛔ Départ et destination doivent être différents"
        );

        destination.value = "";
    }
}

function afficherPlanBateau(){

    plansBateau.forEach(function(div){
        if(div.dataset.bateau === bateauSelect.value){
            div.classList.add('actif');
        } else {
            div.classList.remove('actif');
            // décoche les sièges des bateaux non sélectionnés
            div.querySelectorAll('input[type="checkbox"]').forEach(function(cb){
                cb.checked = false;
            });
        }
    });
}

depart.addEventListener("change", verifierChoix);
destination.addEventListener("change", verifierChoix);
bateauSelect.addEventListener("change", afficherPlanBateau);

// initialisation au chargement (utile si le formulaire est re-rempli après une erreur)
afficherPlanBateau();

</script>

</body>
</html>
