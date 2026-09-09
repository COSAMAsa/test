<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user'])) {
    header("Location: index.php");
    exit;
}

/*
|--------------------------------------------------------------------------
| DÉTAILS VOYAGE
|--------------------------------------------------------------------------
| - Aucun remboursement n'est affiché.
| - Les statistiques passagers sont calculées sur les billets embarqués.
| - La logique sexe/type_passager/type_client est alignée sur le manifeste.
| - Une traversée doit être sélectionnée.
| - Les cabines Homme / Femme / Mixte sont regroupées par capacité.
|--------------------------------------------------------------------------
*/

$traversee_id = isset($_GET['traversee_id'])
    ? (int)$_GET['traversee_id']
    : 0;


/* =========================================================
   LISTE DES TRAVERSÉES
========================================================= */

$sql_traversees = "
    SELECT id, date_depart
    FROM traversees
    ORDER BY date_depart DESC
";

$stmt = $pdo->prepare($sql_traversees);
$stmt->execute();

$traversees = $stmt->fetchAll(PDO::FETCH_ASSOC);


/* =========================================================
   VARIABLES
========================================================= */

$voyage = null;

$details_places = [];
$embarques_places = [];

$hommes = 0;
$femmes = 0;
$autres = 0;

$bebes = 0;
$enfants = 0;
$adultes = 0;

$residents = 0;
$etrangers = 0;
$etranger_resident = 0;

$service = 0;
$vip = 0;
$accp = 0;

$total_billets = 0;
$total_prix = 0;
$total_frais = 0;
$total_general = 0;


/* =========================================================
   INFORMATIONS DU VOYAGE
========================================================= */

if ($traversee_id > 0) {

    $sql_voyage = "
        SELECT id, date_depart, depart, destination, reference_voyage
        FROM traversees
        WHERE id = ?
        LIMIT 1
    ";

    $stmt = $pdo->prepare($sql_voyage);
    $stmt->execute([$traversee_id]);

    $voyage = $stmt->fetch(PDO::FETCH_ASSOC);


    if ($voyage) {


        /* =================================================
           DÉTAILS DES PLACES
        ================================================= */

        $sql_places = "
            SELECT

                CASE

                    WHEN LOWER(COALESCE(p.type_place, ''))
                         LIKE '%cabine 2 places%'
                        THEN 'Cabine 2 places'

                    WHEN LOWER(COALESCE(p.type_place, ''))
                         LIKE '%cabine 4 places%'
                        THEN 'Cabine 4 places'

                    WHEN LOWER(COALESCE(p.type_place, ''))
                         LIKE '%cabine 8 places%'
                        THEN 'Cabine 8 places'

                    WHEN LOWER(COALESCE(p.type_place, ''))
                         LIKE '%chaise%'
                        THEN 'Chaise'

                    ELSE COALESCE(
                        NULLIF(p.type_place, ''),
                        'Non défini'
                    )

                END AS type_place,


                CASE

                    WHEN LOWER(COALESCE(p.type_place, ''))
                         LIKE '%homme%'
                        THEN 'Homme'

                    WHEN LOWER(COALESCE(p.type_place, ''))
                         LIKE '%femme%'
                        THEN 'Femme'

                    WHEN LOWER(COALESCE(p.type_place, ''))
                         LIKE '%mixte%'
                        THEN 'Mixte'

                    WHEN UPPER(TRIM(COALESCE(b.sexe, '')))
                         = 'M'
                        THEN 'Homme'

                    WHEN UPPER(TRIM(COALESCE(b.sexe, '')))
                         = 'F'
                        THEN 'Femme'

                    ELSE 'Autre'

                END AS categorie,


                COUNT(*) AS nombre,


                COALESCE(
                    SUM(b.prix),
                    0
                ) AS montant


            FROM billets b


            LEFT JOIN places p
                ON b.id_place = p.id_place


            WHERE b.traversee_id = ?

              AND b.statut IN (
                  'valide',
                  'embarque'
              )


            GROUP BY

                CASE

                    WHEN LOWER(COALESCE(p.type_place, ''))
                         LIKE '%cabine 2 places%'
                        THEN 'Cabine 2 places'

                    WHEN LOWER(COALESCE(p.type_place, ''))
                         LIKE '%cabine 4 places%'
                        THEN 'Cabine 4 places'

                    WHEN LOWER(COALESCE(p.type_place, ''))
                         LIKE '%cabine 8 places%'
                        THEN 'Cabine 8 places'

                    WHEN LOWER(COALESCE(p.type_place, ''))
                         LIKE '%chaise%'
                        THEN 'Chaise'

                    ELSE COALESCE(
                        NULLIF(p.type_place, ''),
                        'Non défini'
                    )

                END,


                CASE

                    WHEN LOWER(COALESCE(p.type_place, ''))
                         LIKE '%homme%'
                        THEN 'Homme'

                    WHEN LOWER(COALESCE(p.type_place, ''))
                         LIKE '%femme%'
                        THEN 'Femme'

                    WHEN LOWER(COALESCE(p.type_place, ''))
                         LIKE '%mixte%'
                        THEN 'Mixte'

                    WHEN UPPER(TRIM(COALESCE(b.sexe, '')))
                         = 'M'
                        THEN 'Homme'

                    WHEN UPPER(TRIM(COALESCE(b.sexe, '')))
                         = 'F'
                        THEN 'Femme'

                    ELSE 'Autre'

                END
        ";


        $stmt = $pdo->prepare($sql_places);
        $stmt->execute([$traversee_id]);

        $places_data = $stmt->fetchAll(PDO::FETCH_ASSOC);


        /* =================================================
           REGROUPEMENT DES CABINES
        ================================================= */

        foreach ($places_data as $row) {

            $type = $row['type_place'];
            $categorie = $row['categorie'];

            if (!isset($details_places[$type])) {

                $details_places[$type] = [

                    'type_place' => $type,

                    'homme' => 0,
                    'femme' => 0,
                    'mixte' => 0,
                    'autre' => 0,

                    'nombre' => 0,

                    'montant' => 0,

                    'redevance' => 0
                ];
            }


            $nombre = (int)$row['nombre'];

            $montant = (float)$row['montant'];


            if ($categorie === 'Homme') {

                $details_places[$type]['homme']
                    += $nombre;

            } elseif ($categorie === 'Femme') {

                $details_places[$type]['femme']
                    += $nombre;

            } elseif ($categorie === 'Mixte') {

                $details_places[$type]['mixte']
                    += $nombre;

            } else {

                $details_places[$type]['autre']
                    += $nombre;
            }


            $details_places[$type]['nombre']
                += $nombre;


            $details_places[$type]['montant']
                += $montant;
        }


        /* =================================================
           ORDRE D'AFFICHAGE
        ================================================= */

        $ordre_places = [

            'Chaise' => 1,

            'Cabine 2 places' => 2,

            'Cabine 4 places' => 3,

            'Cabine 8 places' => 4

        ];


        uksort(
            $details_places,
            function ($a, $b) use ($ordre_places) {

                $ordreA = $ordre_places[$a] ?? 99;

                $ordreB = $ordre_places[$b] ?? 99;


                if ($ordreA == $ordreB) {

                    return strcmp($a, $b);
                }


                return $ordreA <=> $ordreB;
            }
        );


        /* =================================================
           TARIF UNITAIRE
        ================================================= */

        foreach ($details_places as &$detail) {

            $type_recherche = $detail['type_place'];


            if (
                stripos(
                    $type_recherche,
                    'Cabine'
                ) !== false
            ) {

                $sql_tarif = "
                    SELECT

                        b.prix,

                        COUNT(*) AS nb

                    FROM billets b

                    LEFT JOIN places p
                        ON b.id_place = p.id_place

                    WHERE b.traversee_id = ?

                      AND b.statut IN (
                          'valide',
                          'embarque'
                      )

                      AND LOWER(p.type_place)
                          LIKE ?

                    GROUP BY b.prix

                    ORDER BY
                        nb DESC,
                        b.prix DESC

                    LIMIT 1
                ";


                $stmt_tarif =
                    $pdo->prepare($sql_tarif);


                $stmt_tarif->execute([

                    $traversee_id,

                    '%' .
                    strtolower(
                        $type_recherche
                    ) .
                    '%'

                ]);


                $tarif =
                    $stmt_tarif->fetch(
                        PDO::FETCH_ASSOC
                    );


                $detail['redevance'] =
                    $tarif
                    ? (float)$tarif['prix']
                    : 0;

            } else {

                $detail['redevance'] =
                    $detail['nombre'] > 0

                    ? $detail['montant']
                        / $detail['nombre']

                    : 0;
            }
        }


        unset($detail);


        $details_places =
            array_values(
                $details_places
            );


        /* =================================================
           SITUATION DES EMBARQUEMENTS
        ================================================= */

        $sql_embarquement = "
            SELECT

                CASE

                    WHEN LOWER(COALESCE(p.type_place, ''))
                         LIKE '%cabine 2 places%'
                        THEN 'Cabine 2 places'

                    WHEN LOWER(COALESCE(p.type_place, ''))
                         LIKE '%cabine 4 places%'
                        THEN 'Cabine 4 places'

                    WHEN LOWER(COALESCE(p.type_place, ''))
                         LIKE '%cabine 8 places%'
                        THEN 'Cabine 8 places'

                    WHEN LOWER(COALESCE(p.type_place, ''))
                         LIKE '%chaise%'
                        THEN 'Chaise'

                    ELSE COALESCE(
                        NULLIF(p.type_place, ''),
                        'Non défini'
                    )

                END AS type_place,


                CASE

                    WHEN LOWER(COALESCE(p.type_place, ''))
                         LIKE '%homme%'
                        THEN 'Homme'

                    WHEN LOWER(COALESCE(p.type_place, ''))
                         LIKE '%femme%'
                        THEN 'Femme'

                    WHEN LOWER(COALESCE(p.type_place, ''))
                         LIKE '%mixte%'
                        THEN 'Mixte'

                    WHEN UPPER(TRIM(COALESCE(b.sexe, '')))
                         = 'M'
                        THEN 'Homme'

                    WHEN UPPER(TRIM(COALESCE(b.sexe, '')))
                         = 'F'
                        THEN 'Femme'

                    ELSE 'Autre'

                END AS categorie,


                SUM(
                    CASE
                        WHEN b.statut = 'embarque'
                        THEN 1
                        ELSE 0
                    END
                ) AS embarques,


                SUM(
                    CASE
                        WHEN b.statut = 'valide'
                        THEN 1
                        ELSE 0
                    END
                ) AS non_embarques


            FROM billets b


            LEFT JOIN places p
                ON b.id_place = p.id_place


            WHERE b.traversee_id = ?

              AND b.statut IN (
                  'valide',
                  'embarque'
              )


            GROUP BY

                CASE

                    WHEN LOWER(COALESCE(p.type_place, ''))
                         LIKE '%cabine 2 places%'
                        THEN 'Cabine 2 places'

                    WHEN LOWER(COALESCE(p.type_place, ''))
                         LIKE '%cabine 4 places%'
                        THEN 'Cabine 4 places'

                    WHEN LOWER(COALESCE(p.type_place, ''))
                         LIKE '%cabine 8 places%'
                        THEN 'Cabine 8 places'

                    WHEN LOWER(COALESCE(p.type_place, ''))
                         LIKE '%chaise%'
                        THEN 'Chaise'

                    ELSE COALESCE(
                        NULLIF(p.type_place, ''),
                        'Non défini'
                    )

                END,


                CASE

                    WHEN LOWER(COALESCE(p.type_place, ''))
                         LIKE '%homme%'
                        THEN 'Homme'

                    WHEN LOWER(COALESCE(p.type_place, ''))
                         LIKE '%femme%'
                        THEN 'Femme'

                    WHEN LOWER(COALESCE(p.type_place, ''))
                         LIKE '%mixte%'
                        THEN 'Mixte'

                    WHEN UPPER(TRIM(COALESCE(b.sexe, '')))
                         = 'M'
                        THEN 'Homme'

                    WHEN UPPER(TRIM(COALESCE(b.sexe, '')))
                         = 'F'
                        THEN 'Femme'

                    ELSE 'Autre'

                END
        ";


        $stmt =
            $pdo->prepare(
                $sql_embarquement
            );


        $stmt->execute([
            $traversee_id
        ]);


        $embarquement_data =
            $stmt->fetchAll(
                PDO::FETCH_ASSOC
            );


        /* =================================================
           REGROUPEMENT EMBARQUEMENTS
        ================================================= */

        $embarques_places = [];


        foreach (
            $embarquement_data
            as $row
        ) {

            $type =
                $row['type_place'];

            $categorie =
                $row['categorie'];


            if (
                !isset(
                    $embarques_places[$type]
                )
            ) {

                $embarques_places[$type] = [

                    'type_place' => $type,

                    'homme_embarques' => 0,
                    'femme_embarques' => 0,
                    'mixte_embarques' => 0,

                    'homme_non_embarques' => 0,
                    'femme_non_embarques' => 0,
                    'mixte_non_embarques' => 0,

                    'embarques' => 0,
                    'non_embarques' => 0
                ];
            }


            $nb_embarques =
                (int)$row['embarques'];


            $nb_non =
                (int)$row['non_embarques'];


            if (
                $categorie === 'Homme'
            ) {

                $embarques_places[$type]
                    ['homme_embarques']
                    += $nb_embarques;


                $embarques_places[$type]
                    ['homme_non_embarques']
                    += $nb_non;


            } elseif (
                $categorie === 'Femme'
            ) {

                $embarques_places[$type]
                    ['femme_embarques']
                    += $nb_embarques;


                $embarques_places[$type]
                    ['femme_non_embarques']
                    += $nb_non;


            } elseif (
                $categorie === 'Mixte'
            ) {

                $embarques_places[$type]
                    ['mixte_embarques']
                    += $nb_embarques;


                $embarques_places[$type]
                    ['mixte_non_embarques']
                    += $nb_non;
            }


            $embarques_places[$type]
                ['embarques']
                += $nb_embarques;


            $embarques_places[$type]
                ['non_embarques']
                += $nb_non;
        }


        /* =================================================
           ORDRE EMBARQUEMENTS
        ================================================= */

        uksort(
            $embarques_places,
            function ($a, $b) use ($ordre_places) {

                $ordreA =
                    $ordre_places[$a] ?? 99;

                $ordreB =
                    $ordre_places[$b] ?? 99;


                if ($ordreA == $ordreB) {

                    return strcmp(
                        $a,
                        $b
                    );
                }


                return $ordreA <=> $ordreB;
            }
        );


        $embarques_places =
            array_values(
                $embarques_places
            );


        /* =================================================
           RÉPARTITION DES PASSAGERS EMBARQUÉS
           =================================================
           Même logique que manifeste_voyage.php :
           - uniquement les billets avec statut = 'embarque'
           - sexe depuis billets.sexe
           - âge/catégorie depuis type_passager
           - nationalité/résidence depuis type_client
           - service / VIP / ACCP recherchés dans type_passager ET type_client
        ================================================= */

        $sql_passagers = "
            SELECT
                LOWER(TRIM(COALESCE(b.sexe, ''))) AS sexe,
                LOWER(TRIM(COALESCE(b.type_passager, ''))) AS type_passager,
                LOWER(TRIM(COALESCE(b.type_client, ''))) AS type_client,
                COUNT(*) AS nombre
            FROM billets b
            WHERE b.traversee_id = ?
              AND b.statut = 'embarque'
            GROUP BY b.sexe, b.type_passager, b.type_client
        ";

        $stmt = $pdo->prepare($sql_passagers);
        $stmt->execute([$traversee_id]);

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {

            $sexe = strtoupper(trim((string)$row['sexe']));
            $passager = strtolower(trim((string)$row['type_passager']));
            $client = strtolower(trim((string)$row['type_client']));
            $nombre = (int)$row['nombre'];

            /* -------------------------------------------------
               NORMALISATION DES ACCENTS
            ------------------------------------------------- */
            $passager = str_replace(
                ['é','è','ê','ë','à','â','ä','î','ï','ô','ö','ù','û','ü','ç'],
                ['e','e','e','e','a','a','a','i','i','o','o','u','u','u','c'],
                $passager
            );

            $client = str_replace(
                ['é','è','ê','ë','à','â','ä','î','ï','ô','ö','ù','û','ü','ç'],
                ['e','e','e','e','a','a','a','i','i','o','o','u','u','u','c'],
                $client
            );

            /* -------------------------------------------------
               SEXE : source de vérité = billets.sexe
               (même logique que manifeste_voyage.php)
            ------------------------------------------------- */
            if ($sexe === 'M') {
                $hommes += $nombre;
            } elseif ($sexe === 'F') {
                $femmes += $nombre;
            } else {
                $autres += $nombre;
            }

            /* -------------------------------------------------
               BÉBÉS
            ------------------------------------------------- */
            if (
                strpos($passager, 'bebe') !== false ||
                strpos($passager, 'nourrisson') !== false ||
                strpos($passager, 'infant') !== false ||
                strpos($client, 'bebe') !== false ||
                strpos($client, 'nourrisson') !== false ||
                strpos($client, 'infant') !== false
            ) {
                $bebes += $nombre;
            }

            /* -------------------------------------------------
               ENFANTS
            ------------------------------------------------- */
            if (
                strpos($passager, 'enfant') !== false ||
                strpos($passager, 'child') !== false ||
                strpos($passager, 'mineur') !== false ||
                strpos($client, 'enfant') !== false ||
                strpos($client, 'child') !== false ||
                strpos($client, 'mineur') !== false
            ) {
                $enfants += $nombre;
            }

            /* -------------------------------------------------
               ADULTES
            ------------------------------------------------- */
            if (
                strpos($passager, 'adulte') !== false ||
                strpos($passager, 'adult') !== false ||
                strpos($client, 'adulte') !== false ||
                strpos($client, 'adult') !== false
            ) {
                $adultes += $nombre;
            }

            /* -------------------------------------------------
               ÉTRANGER-RÉSIDENT
               À tester avant résident et étranger.
            ------------------------------------------------- */
            $est_etranger_resident =
                strpos($passager, 'etranger-resident') !== false ||
                strpos($passager, 'etranger resident') !== false ||
                strpos($passager, 'etrangerresident') !== false ||
                strpos($client, 'etranger-resident') !== false ||
                strpos($client, 'etranger resident') !== false ||
                strpos($client, 'etrangerresident') !== false;

            if ($est_etranger_resident) {
                $etranger_resident += $nombre;
            } else {

                /* -------------------------------------------------
                   RÉSIDENTS
                ------------------------------------------------- */
                if (
                    strpos($client, 'resident') !== false ||
                    strpos($passager, 'resident') !== false
                ) {
                    $residents += $nombre;
                }

                /* -------------------------------------------------
                   ÉTRANGERS
                ------------------------------------------------- */
                if (
                    strpos($client, 'etranger') !== false ||
                    strpos($passager, 'etranger') !== false ||
                    strpos($client, 'foreign') !== false ||
                    strpos($passager, 'foreign') !== false
                ) {
                    $etrangers += $nombre;
                }
            }

            /* -------------------------------------------------
               SERVICE
            ------------------------------------------------- */
            if (
                strpos($passager, 'service') !== false ||
                strpos($client, 'service') !== false ||
                strpos($passager, 'personnel') !== false ||
                strpos($client, 'personnel') !== false
            ) {
                $service += $nombre;
            }

            /* -------------------------------------------------
               VIP
            ------------------------------------------------- */
            if (
                strpos($passager, 'vip') !== false ||
                strpos($client, 'vip') !== false
            ) {
                $vip += $nombre;
            }

            /* -------------------------------------------------
               ACCP
            ------------------------------------------------- */
            if (
                strpos($passager, 'accp') !== false ||
                strpos($client, 'accp') !== false
            ) {
                $accp += $nombre;
            }
        }

        /* =================================================
           TOTAUX FINANCIERS
        ================================================= */

        $sql_totaux = "
            SELECT

                COUNT(*) AS total_billets,

                COALESCE(
                    SUM(prix),
                    0
                ) AS total_prix,

                COALESCE(
                    SUM(frais_service),
                    0
                ) AS total_frais

            FROM billets

            WHERE traversee_id = ?

              AND statut IN (
                  'valide',
                  'embarque'
              )
        ";


        $stmt =
            $pdo->prepare(
                $sql_totaux
            );


        $stmt->execute([
            $traversee_id
        ]);


        $totaux =
            $stmt->fetch(
                PDO::FETCH_ASSOC
            );


        $total_billets =
            (int)$totaux['total_billets'];


        $total_prix =
            (float)$totaux['total_prix'];


        $total_frais =
            (float)$totaux['total_frais'];


        $total_general =
            $total_prix +
            $total_frais;
    }
}


/* =========================================================
   FORMATAGE
========================================================= */

function fcfa($montant)
{
    return number_format(
        (float)$montant,
        0,
        ',',
        ' '
    ) . ' FCFA';
}


function afficher_type_place($type)
{
    $type =
        trim(
            (string)$type
        );


    if ($type === '') {

        return 'Non défini';
    }


    return htmlspecialchars(
        $type,
        ENT_QUOTES,
        'UTF-8'
    );
}

?>

<!DOCTYPE html>

<html lang="fr">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Détails Voyage</title>


    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >


    <style>

        body {
            background: #f4f6f9;
        }


        .card {
            border: none;
            border-radius: 14px;
            overflow: hidden;
        }


        .section-title {
            background: #d1d1d1;
            font-weight: 700;
            text-align: center;
            padding: 14px;
        }


        .table th {
            vertical-align: middle;
            white-space: nowrap;
        }


        .table td {
            vertical-align: middle;
        }


        .total-box {
            border-radius: 12px;
            padding: 16px;
            color: white;
        }


        .voyage-header {
            background: #d1d1d1;
            padding: 18px;
            font-weight: 700;
            text-align: center;
        }


        .empty-message {
            padding: 35px;
            text-align: center;
        }


        .cabine-row {
            background: #f8f9fa;
        }


        .total-row {
            background: #fff3cd;
            font-weight: 700;
        }


        @media(max-width:768px) {

            .table {
                font-size: 13px;
            }

        }

    </style>

</head>


<body>


<div class="container-fluid mt-4 px-2 px-md-4">


    <!-- =====================================================
         HEADER
    ====================================================== -->

    <div
        class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2"
    >

        <h3 class="fw-bold">

            🚢 Détails Voyage

        </h3>


        <div class="d-flex gap-2 flex-wrap">


            <a
                href="billets_emis.php"
                class="btn btn-secondary"
            >

                ⬅️ Billets émis

            </a>


            <a
                href="dashboard.php"
                class="btn btn-outline-dark"
            >

                🏠 Tableau de bord

            </a>


        </div>

    </div>


    <!-- =====================================================
         SÉLECTION DU VOYAGE
    ====================================================== -->

    <div class="card shadow-sm mb-4">


        <div class="card-body">


            <form method="GET">


                <div class="row g-2 align-items-end">


                    <div class="col-md-10">


                        <label class="form-label fw-bold">

                            Sélectionner le voyage

                        </label>


                        <select
                            name="traversee_id"
                            class="form-select"
                            required
                        >


                            <option value="">

                                -- Sélectionner un voyage --

                            </option>


                            <?php foreach ($traversees as $t): ?>


                                <option
                                    value="<?= (int)$t['id'] ?>"
                                    <?= $traversee_id == $t['id']
                                        ? 'selected'
                                        : ''
                                    ?>
                                >

                                    Voyage du

                                    <?= !empty($t['date_depart'])

                                        ? date(
                                            'd/m/Y H:i',
                                            strtotime(
                                                $t['date_depart']
                                            )
                                        )

                                        : 'Date non renseignée'
                                    ?>

                                </option>


                            <?php endforeach; ?>


                        </select>


                    </div>


                    <div class="col-md-2">


                        <button
                            class="btn btn-primary w-100"
                        >

                            🔎 Afficher

                        </button>


                    </div>


                </div>


            </form>


        </div>


    </div>


    <?php if (
        $traversee_id > 0 &&
        !$voyage
    ): ?>


        <div class="alert alert-danger">

            ❌ Voyage introuvable.

        </div>


    <?php elseif ($voyage): ?>


        <!-- =================================================
             DÉTAILS VOYAGE
        ================================================== -->

        <div class="card shadow-sm mb-4">


            <div class="voyage-header">

                Détails Voyage

            </div>


            <div class="table-responsive">


                <table class="table table-bordered mb-0">


                    <tbody>


                        <tr>


                            <th style="width:20%">

                                Numéro :

                            </th>


                            <td style="width:30%">

                                <strong>

                                    V<?= (int)$voyage['id'] ?>

                                </strong>

                            </td>


                            <th style="width:20%">

                                Ligne :

                            </th>


                            <td style="width:30%">

                                <?= !empty($voyage['depart']) && !empty($voyage['destination'])
                                    ? htmlspecialchars($voyage['depart']) . ' → ' . htmlspecialchars($voyage['destination'])
                                    : 'Non renseignée'
                                ?>

                            </td>


                        </tr>


                        <tr>


                            <th>

                                Départ :

                            </th>


                            <td>

                                <strong>

                                    <?= !empty(
                                        $voyage['date_depart']
                                    )

                                        ? date(
                                            'd/m/Y H:i',
                                            strtotime(
                                                $voyage['date_depart']
                                            )
                                        )

                                        : 'Non renseigné'
                                    ?>

                                </strong>

                            </td>


                            <th>

                                Embarquement :

                            </th>


                            <td>

                                Selon les données
                                d'embarquement enregistrées

                            </td>


                        </tr>


                    </tbody>


                </table>


            </div>


        </div>


        <!-- =================================================
             DÉTAILS PASSAGERS
        ================================================== -->

        <div class="card shadow-sm mb-4">


            <div class="section-title">

                Détails Passagers

            </div>


            <div class="table-responsive">


                <table
                    class="table table-bordered table-hover text-center mb-0"
                >


                    <thead class="table-dark">


                        <tr>


                            <th rowspan="2">

                                Type de place

                            </th>


                            <th colspan="3">

                                Répartition

                            </th>


                            <th rowspan="2">

                                Total

                            </th>


                            <th rowspan="2">

                                Montant

                            </th>


                        </tr>


                        <tr>


                            <th>

                                Homme

                            </th>


                            <th>

                                Femme

                            </th>


                            <th>

                                Mixte

                            </th>


                        </tr>


                    </thead>


                    <tbody>


                    <?php if (
                        count($details_places) > 0
                    ): ?>


                        <?php

                        $total_hommes_places = 0;

                        $total_femmes_places = 0;

                        $total_mixte_places = 0;

                        ?>


                        <?php foreach (
                            $details_places
                            as $detail
                        ): ?>


                            <?php

                            $total_hommes_places +=
                                (int)$detail['homme'];


                            $total_femmes_places +=
                                (int)$detail['femme'];


                            $total_mixte_places +=
                                (int)$detail['mixte'];

                            ?>


                            <tr
                                class="<?= stripos(
                                    $detail['type_place'],
                                    'Cabine'
                                ) !== false
                                    ? 'cabine-row'
                                    : ''
                                ?>"
                            >


                                <td class="text-start fw-bold">

                                    <?= afficher_type_place(
                                        $detail['type_place']
                                    ) ?>

                                </td>


                                <td class="fw-bold">

                                    <?= (int)$detail['homme'] ?>

                                </td>


                                <td class="fw-bold">

                                    <?= (int)$detail['femme'] ?>

                                </td>


                                <td class="fw-bold">

                                    <?= (int)$detail['mixte'] ?>

                                </td>


                                <td class="fw-bold">

                                    <?= (int)$detail['nombre'] ?>

                                </td>


                                <td class="fw-bold">

                                    <?= fcfa(
                                        $detail['montant']
                                    ) ?>

                                </td>


                            </tr>


                        <?php endforeach; ?>


                        <tr class="total-row">


                            <td class="text-start">

                                Total Billets

                            </td>


                            <td>

                                <?= $total_hommes_places ?>

                            </td>


                            <td>

                                <?= $total_femmes_places ?>

                            </td>


                            <td>

                                <?= $total_mixte_places ?>

                            </td>


                            <td>

                                <?= number_format(
                                    $total_billets,
                                    0,
                                    ',',
                                    ' '
                                ) ?>

                            </td>


                            <td>

                                <?= fcfa(
                                    $total_prix
                                ) ?>

                            </td>


                        </tr>


                    <?php else: ?>


                        <tr>


                            <td
                                colspan="7"
                                class="empty-message"
                            >

                                Aucun billet trouvé
                                pour ce voyage.

                            </td>


                        </tr>


                    <?php endif; ?>


                    </tbody>


                </table>


            </div>


        </div>


        <!-- =================================================
             SITUATION DES EMBARQUEMENTS
        ================================================== -->

        <div class="card shadow-sm mb-4">


            <div class="section-title">

                Situation des embarquements

            </div>


            <div class="table-responsive">


                <table
                    class="table table-bordered text-center mb-0"
                >


                    <thead class="table-dark">


                        <tr>


                            <th rowspan="2">

                                Type de place

                            </th>


                            <th colspan="4">

                                Embarqués

                            </th>


                            <th colspan="3">

                                Non effectués

                            </th>


                            <th rowspan="2">

                                Total

                            </th>


                        </tr>


                        <tr>


                            <th>

                                Homme

                            </th>


                            <th>

                                Femme

                            </th>


                            <th>

                                Mixte

                            </th>


                            <th>

                                Total

                            </th>


                            <th>

                                Homme

                            </th>


                            <th>

                                Femme

                            </th>


                            <th>

                                Mixte

                            </th>


                        </tr>


                    </thead>


                    <tbody>


                    <?php

                    $total_embarques = 0;

                    $total_non_embarques = 0;


                    $total_hommes_embarques = 0;

                    $total_femmes_embarques = 0;

                    $total_mixte_embarques = 0;


                    $total_hommes_non = 0;

                    $total_femmes_non = 0;

                    $total_mixte_non = 0;

                    ?>


                    <?php if (
                        count($embarques_places) > 0
                    ): ?>


                        <?php foreach (
                            $embarques_places
                            as $row
                        ): ?>


                            <?php

                            $nb_embarques =
                                (int)$row['embarques'];


                            $nb_non =
                                (int)$row['non_embarques'];


                            $total_embarques +=
                                $nb_embarques;


                            $total_non_embarques +=
                                $nb_non;


                            $total_hommes_embarques +=
                                (int)$row[
                                    'homme_embarques'
                                ];


                            $total_femmes_embarques +=
                                (int)$row[
                                    'femme_embarques'
                                ];


                            $total_mixte_embarques +=
                                (int)$row[
                                    'mixte_embarques'
                                ];


                            $total_hommes_non +=
                                (int)$row[
                                    'homme_non_embarques'
                                ];


                            $total_femmes_non +=
                                (int)$row[
                                    'femme_non_embarques'
                                ];


                            $total_mixte_non +=
                                (int)$row[
                                    'mixte_non_embarques'
                                ];

                            ?>


                            <tr
                                class="<?= stripos(
                                    $row['type_place'],
                                    'Cabine'
                                ) !== false
                                    ? 'cabine-row'
                                    : ''
                                ?>"
                            >


                                <td class="text-start fw-bold">

                                    <?= afficher_type_place(
                                        $row['type_place']
                                    ) ?>

                                </td>


                                <td class="fw-bold text-success">

                                    <?= (int)$row[
                                        'homme_embarques'
                                    ] ?>

                                </td>


                                <td class="fw-bold text-success">

                                    <?= (int)$row[
                                        'femme_embarques'
                                    ] ?>

                                </td>


                                <td class="fw-bold text-success">

                                    <?= (int)$row[
                                        'mixte_embarques'
                                    ] ?>

                                </td>


                                <td class="fw-bold text-success">

                                    <?= $nb_embarques ?>

                                </td>


                                <td class="fw-bold text-danger">

                                    <?= (int)$row[
                                        'homme_non_embarques'
                                    ] ?>

                                </td>


                                <td class="fw-bold text-danger">

                                    <?= (int)$row[
                                        'femme_non_embarques'
                                    ] ?>

                                </td>


                                <td class="fw-bold text-danger">

                                    <?= (int)$row[
                                        'mixte_non_embarques'
                                    ] ?>

                                </td>


                                <td class="fw-bold">

                                    <?= $nb_embarques +
                                        $nb_non ?>

                                </td>


                            </tr>


                        <?php endforeach; ?>


                        <tr class="total-row">


                            <td class="text-start">

                                Total

                            </td>


                            <td>

                                <?= $total_hommes_embarques ?>

                            </td>


                            <td>

                                <?= $total_femmes_embarques ?>

                            </td>


                            <td>

                                <?= $total_mixte_embarques ?>

                            </td>


                            <td>

                                <?= $total_embarques ?>

                            </td>


                            <td>

                                <?= $total_hommes_non ?>

                            </td>


                            <td>

                                <?= $total_femmes_non ?>

                            </td>


                            <td>

                                <?= $total_mixte_non ?>

                            </td>


                            <td>

                                <?= $total_embarques +
                                    $total_non_embarques ?>

                            </td>


                        </tr>


                    <?php else: ?>


                        <tr>


                            <td
                                colspan="8"
                                class="empty-message"
                            >

                                Aucun embarquement
                                enregistré.

                            </td>


                        </tr>


                    <?php endif; ?>


                    </tbody>


                </table>


            </div>


        </div>


        <!-- =================================================
             RÉPARTITION DES PASSAGERS EMBARQUÉS
        ================================================== -->

        <div class="card shadow-sm mb-4">


            <div class="section-title">

                Répartition des embarquements

            </div>


            <div class="table-responsive">


                <table
                    class="table table-bordered mb-0"
                >


                    <tbody>


                        <tr>

                            <th>

                                Hommes :

                            </th>

                            <td class="fw-bold">

                                <?= $hommes ?>

                            </td>


                            <th>

                                Femmes :

                            </th>

                            <td class="fw-bold">

                                <?= $femmes ?>

                            </td>

                        </tr>


                        <tr>

                            <th>

                                Bébés :

                            </th>

                            <td class="fw-bold">

                                <?= $bebes ?>

                            </td>


                            <th>

                                Enfants :

                            </th>

                            <td class="fw-bold">

                                <?= $enfants ?>

                            </td>

                        </tr>


                        <tr>

                            <th>

                                Adultes :

                            </th>

                            <td class="fw-bold">

                                <?= $adultes ?>

                            </td>


                            <th>

                                Résidents :

                            </th>

                            <td class="fw-bold">

                                <?= $residents ?>

                            </td>

                        </tr>


                        <tr>

                            <th>

                                Étrangers :

                            </th>

                            <td class="fw-bold">

                                <?= $etrangers ?>

                            </td>


                            <th>

                                Étranger-résident :

                            </th>

                            <td class="fw-bold">

                                <?= $etranger_resident ?>

                            </td>

                        </tr>


                        <tr>

                            <th>

                                Service :

                            </th>

                            <td class="fw-bold">

                                <?= $service ?>

                            </td>


                            <th>

                                VIP :

                            </th>

                            <td class="fw-bold">

                                <?= $vip ?>

                            </td>

                        </tr>


                        <tr>

                            <th>

                                ACCP :

                            </th>

                            <td class="fw-bold">

                                <?= $accp ?>

                            </td>


                            <th>

                                Total embarqués :

                            </th>

                            <td class="fw-bold text-success">

                                <?= $total_embarques ?>

                            </td>

                        </tr>


                    </tbody>


                </table>


            </div>


        </div>


        <!-- =================================================
             SYNTHÈSE FINANCIÈRE
        ================================================== -->

        <div class="row mb-4">


            <div class="col-md-4 mb-2">


                <div
                    class="total-box bg-primary shadow-sm"
                >

                    <h6>

                        🎫 Total billets

                    </h6>


                    <h4>

                        <?= fcfa(
                            $total_prix
                        ) ?>

                    </h4>

                </div>


            </div>


            <div class="col-md-4 mb-2">


                <div
                    class="total-box bg-dark shadow-sm"
                >

                    <h6>

                        💳 Frais service

                    </h6>


                    <h4>

                        <?= fcfa(
                            $total_frais
                        ) ?>

                    </h4>

                </div>


            </div>


            <div class="col-md-4 mb-2">


                <div
                    class="total-box bg-success shadow-sm"
                >

                    <h6>

                        💰 Total général

                    </h6>


                    <h4>

                        <?= fcfa(
                            $total_general
                        ) ?>

                    </h4>

                </div>


            </div>


        </div>


    <?php else: ?>


        <div class="card shadow-sm">


            <div class="empty-message">


                <h4>

                    🚢 Détails Voyage

                </h4>


                <p class="text-muted mb-0">

                    Sélectionnez un voyage
                    pour afficher les détails du voyage.

                </p>


            </div>


        </div>


    <?php endif; ?>


</div>


</body>

</html>
