<?php
session_start();

require_once '../config/database.php';

if (!isset($_SESSION['user'])) {
    header("Location: index.php");
    exit;
}

/* =========================================================
   1. VOYAGES ZIGUINCHOR → DAKAR
========================================================= */

$stmt_voyages = $pdo->query("
    SELECT
        t.id,
        t.reference_voyage,
        t.bateau,
        t.depart,
        t.destination,
        t.date_depart,

        COUNT(
            CASE
                WHEN b.statut = 'embarque'
                AND b.depart_client = 'Carabane'
                THEN b.id
            END
        ) AS nb_carabane

    FROM traversees t

    LEFT JOIN billets b
        ON b.traversee_id = t.id

    WHERE t.depart = 'Ziguinchor'
      AND t.destination = 'Dakar'

    GROUP BY
        t.id,
        t.reference_voyage,
        t.bateau,
        t.depart,
        t.destination,
        t.date_depart

    ORDER BY t.date_depart DESC
");

$voyages = $stmt_voyages->fetchAll(PDO::FETCH_ASSOC);


/* =========================================================
   2. INITIALISATION
========================================================= */

$billets = [];
$traversee = null;
$traversee_id = 0;

if (!empty($_GET['traversee_id'])) {

    $traversee_id = (int) $_GET['traversee_id'];

    /* =====================================================
       INFOS VOYAGE
    ===================================================== */

    $stmt = $pdo->prepare("
        SELECT *
        FROM traversees
        WHERE id = ?
          AND depart = 'Ziguinchor'
          AND destination = 'Dakar'
    ");

    $stmt->execute([$traversee_id]);

    $traversee = $stmt->fetch(PDO::FETCH_ASSOC);


    /* =====================================================
       PASSAGERS EMBARQUÉS À CARABANE
    ===================================================== */

    if ($traversee) {

        $stmt = $pdo->prepare("
            SELECT
                b.*,
                p.numero_place,
                p.type_place

            FROM billets b

            LEFT JOIN places p
                ON b.id_place = p.id_place

            WHERE b.traversee_id = ?

              AND b.statut = 'embarque'

              AND b.depart_client = 'Carabane'

            ORDER BY
                p.numero_place ASC,
                b.nom ASC,
                b.prenom ASC
        ");

        $stmt->execute([$traversee_id]);

        $billets = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}


/* =========================================================
   3. STATISTIQUES
========================================================= */

$total_passagers = count($billets);

$homme = 0;
$femme = 0;
$non_renseigne = 0;

$chaise = 0;
$cabine2 = 0;
$cabine4 = 0;
$cabine8 = 0;
$autres_places = 0;

$senegalais = 0;
$residents = 0;
$etrangers = 0;


/* =========================================================
   4. CALCUL DES STATISTIQUES
========================================================= */

foreach ($billets as $b) {

    /* SEXE */

    $sexe = strtoupper(trim($b['sexe'] ?? ''));

    if ($sexe === 'M') {
        $homme++;
    }
    elseif ($sexe === 'F') {
        $femme++;
    }
    else {
        $non_renseigne++;
    }


    /* TYPE DE PLACE */

    $type_place = strtolower(
        trim($b['type_place'] ?? '')
    );

    if (strpos($type_place, 'chaise') !== false) {

        $chaise++;

    }
    elseif (strpos($type_place, 'cabine 2') !== false) {

        $cabine2++;

    }
    elseif (strpos($type_place, 'cabine 4') !== false) {

        $cabine4++;

    }
    elseif (strpos($type_place, 'cabine 8') !== false) {

        $cabine8++;

    }
    else {

        $autres_places++;

    }


    /* NATIONALITÉ / TYPE CLIENT */

    $client = strtolower(
        trim($b['type_client'] ?? '')
    );

    if ($client === 'senegalais') {

        $senegalais++;

    }
    elseif ($client === 'resident') {

        $residents++;

    }
    elseif (
        $client === 'etranger' ||
        $client === 'non_resident'
    ) {

        $etrangers++;
    }
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

<title>
    Manifeste Carabane
</title>


<link
    href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css"
    rel="stylesheet"
>


<style>

/* =========================================================
   PAGE
========================================================= */

body {

    background: #f4f6f9;

    font-family: Arial, sans-serif;

}


.card {

    border: none;

    border-radius: 18px;

    overflow: hidden;

}


.table th {

    vertical-align: middle;

    white-space: nowrap;

}


.table td {

    vertical-align: middle;

}


.page-title {

    font-weight: 700;

}


/* =========================================================
   BADGE CARABANE
========================================================= */

.badge-carabane {

    background-color: #ffc107;

    color: #000;

}


/* =========================================================
   ENTÊTE VOYAGE
========================================================= */

.info-voyage {

    background: white;

    border-radius: 15px;

    padding: 20px;

    box-shadow: 0 4px 15px rgba(0,0,0,.06);

}


/* =========================================================
   RÉCAPITULATIF
========================================================= */

.recap-box {

    background: #212529;

    color: white;

    padding: 18px;

    border-radius: 15px;

    font-weight: bold;

    line-height: 2;

}


/* =========================================================
   TITRE MANIFESTE
========================================================= */

.manifeste-header {

    text-align: center;

    margin-bottom: 20px;

}


/* =========================================================
   IMPRESSION
========================================================= */

@media print {

    body {

        background: white;

    }

    .no-print {

        display: none !important;

    }

    .card {

        box-shadow: none !important;

        border-radius: 0;

    }

    .table {

        font-size: 10px;

    }

    .manifeste-header {

        margin-top: 0;

    }

    @page {

        size: A4 landscape;

        margin: 10mm;

    }

}

</style>

</head>


<body>


<div class="container-fluid mt-4 px-2 px-md-4">


    <!-- =====================================================
         EN-TÊTE
    ====================================================== -->

    <div
        class="
            d-flex
            justify-content-between
            align-items-center
            mb-4
            flex-wrap
            no-print
        "
    >

        <div>

            <h3 class="page-title mb-1">

                🚢 Manifeste Carabane

            </h3>

            <div class="text-muted">

                Passagers embarqués à
                <strong>Carabane</strong>

                pour

                <strong>Ziguinchor → Dakar</strong>

            </div>

        </div>


        <div class="d-flex gap-2">

            <button
                type="button"
                onclick="window.print()"
                class="btn btn-success"
            >

                🖨️ Imprimer

            </button>


            <a
                href="manifeste_voyage.php"
                class="btn btn-secondary"
            >

                ⬅️ Retour

            </a>

        </div>

    </div>



    <!-- =====================================================
         SÉLECTION VOYAGE
    ====================================================== -->

    <div class="card shadow-sm mb-4 no-print">

        <div class="card-body">

            <form
                method="GET"
                class="row g-3 align-items-end"
            >

                <div class="col-md-9">

                    <label class="form-label fw-bold">

                        Traversée
                        Ziguinchor → Dakar

                    </label>


                    <select
                        name="traversee_id"
                        class="form-select"
                        required
                    >

                        <option value="">

                            -- Sélectionner une traversée --

                        </option>


                        <?php foreach ($voyages as $v): ?>

                            <option
                                value="<?= (int)$v['id'] ?>"

                                <?= (
                                    (string)$traversee_id ===
                                    (string)$v['id']
                                )
                                ? 'selected'
                                : ''
                                ?>
                            >

                                <?= htmlspecialchars(
                                    $v['reference_voyage'] ?? ''
                                ) ?>

                                -

                                Ziguinchor → Dakar

                                <?php if (!empty($v['date_depart'])): ?>

                                    -

                                    <?= date(
                                        'd/m/Y H:i',
                                        strtotime(
                                            $v['date_depart']
                                        )
                                    ) ?>

                                <?php endif; ?>


                                <?php if (!empty($v['bateau'])): ?>

                                    -

                                    <?= htmlspecialchars(
                                        $v['bateau']
                                    ) ?>

                                <?php endif; ?>


                                -

                                <?php if (
                                    (int)$v['nb_carabane'] > 0
                                ): ?>

                                    <?= (int)$v['nb_carabane'] ?>
                                    passager(s) Carabane

                                <?php else: ?>

                                    aucun passager Carabane

                                <?php endif; ?>

                            </option>

                        <?php endforeach; ?>

                    </select>


                    <?php if (empty($voyages)): ?>

                        <div class="alert alert-warning mt-3">

                            Aucun voyage
                            <strong>Ziguinchor → Dakar</strong>
                            trouvé.

                        </div>

                    <?php endif; ?>

                </div>


                <div class="col-md-3">

                    <button
                        type="submit"
                        class="btn btn-warning w-100"
                    >

                        🚢 Afficher Carabane

                    </button>

                </div>

            </form>

        </div>

    </div>



    <?php if ($traversee): ?>


        <!-- =================================================
             INFORMATIONS VOYAGE
        ================================================== -->

        <div class="info-voyage mb-4">

            <div class="row g-3">

                <div class="col-md-3">

                    <small class="text-muted">

                        Référence voyage

                    </small>

                    <div class="fw-bold">

                        <?= htmlspecialchars(
                            $traversee[
                                'reference_voyage'
                            ] ?? ''
                        ) ?>

                    </div>

                </div>


                <div class="col-md-3">

                    <small class="text-muted">

                        Trajet

                    </small>

                    <div class="fw-bold">

                        <?= htmlspecialchars(
                            $traversee['depart'] ?? ''
                        ) ?>

                        →

                        <?= htmlspecialchars(
                            $traversee['destination'] ?? ''
                        ) ?>

                    </div>

                </div>


                <div class="col-md-3">

                    <small class="text-muted">

                        Date de départ

                    </small>

                    <div class="fw-bold">

                        <?php if (
                            !empty(
                                $traversee['date_depart']
                            )
                        ): ?>

                            <?= date(
                                'd/m/Y H:i',
                                strtotime(
                                    $traversee['date_depart']
                                )
                            ) ?>

                        <?php endif; ?>

                    </div>

                </div>


                <div class="col-md-3">

                    <small class="text-muted">

                        Navire

                    </small>

                    <div class="fw-bold">

                        <?= htmlspecialchars(
                            $traversee['bateau'] ?? ''
                        ) ?>

                    </div>

                </div>

            </div>


            <hr>


            <div class="text-center">

                <span
                    class="
                        badge
                        badge-carabane
                        fs-6
                        px-4
                        py-2
                    "
                >

                    🚢
                    EMBARQUEMENT CARABANE
                    →
                    DAKAR

                </span>

            </div>

        </div>



        <!-- =================================================
             MANIFESTE
        ================================================== -->

        <div class="card shadow mb-4">

            <div class="card-body">


                <!-- TITRE -->

                <div class="manifeste-header">

                    <h4 class="fw-bold mb-1">

                        MANIFESTE DES PASSAGERS
                        EMBARQUÉS À CARABANE

                    </h4>


                    <p class="mb-1">

                        Ligne :

                        <strong>

                            Ziguinchor - Carabane - Dakar

                        </strong>

                    </p>


                    <p class="mb-1">

                        Voyage du

                        <strong>

                            <?= date(
                                'd/m/Y',
                                strtotime(
                                    $traversee['date_depart']
                                )
                            ) ?>

                        </strong>

                        -

                        Navire :

                        <strong>

                            «
                            <?= htmlspecialchars(
                                $traversee['bateau'] ?? ''
                            ) ?>
                            »

                        </strong>

                    </p>


                    <p class="mb-1">

                        Numéro de voyage :

                        <strong>

                            <?= htmlspecialchars(
                                $traversee[
                                    'reference_voyage'
                                ] ?? ''
                            ) ?>

                        </strong>

                    </p>


                    <p class="mb-0">

                        Point d'embarquement :

                        <span
                            class="
                                badge
                                badge-carabane
                            "
                        >

                            CARABANE

                        </span>

                    </p>

                </div>



                <!-- =================================================
                     TABLEAU PASSAGERS
                ================================================== -->

                <div class="table-responsive">

                    <table
                        class="
                            table
                            table-bordered
                            table-hover
                            text-center
                            align-middle
                        "
                    >

                        <thead class="table-dark">

                            <tr>

                                <th>N°</th>

                                <th>Code billet</th>

                                <th>Nom</th>

                               

                                <th>Téléphone</th>

                                <th>Sexe</th>

                                <th>CNI</th>

                                <th>N° Place</th>

                                <th>Type place</th>

                                <th>Type passager</th>

                                <th>Nationalité</th>

                                <th>Départ</th>

                            </tr>

                        </thead>


                        <tbody>


                        <?php if (!empty($billets)): ?>

                            <?php $i = 1; ?>


                            <?php foreach ($billets as $b): ?>

                                <tr>

                                    <!-- N° -->

                                    <td>

                                        <?= $i++ ?>

                                    </td>


                                    <!-- CODE BILLET -->

                                    <td>

                                        <?php if (
                                            !empty(
                                                $b['code_qr']
                                            )
                                        ): ?>

                                            <span
                                                class="
                                                    badge
                                                    bg-secondary
                                                "
                                            >

                                                <?= htmlspecialchars(
                                                    $b['code_qr']
                                                ) ?>

                                            </span>

                                        <?php else: ?>

                                            -

                                        <?php endif; ?>

                                    </td>


                                    <!-- NOM -->



                                    <!-- PRÉNOM -->

                                                     <td>
    <?= trim(($b['prenom'] ?? '') . ' ' . ($b['nom'] ?? '')) ?>
</td>



                                    <!-- TÉLÉPHONE -->

                                    <td>

                                        <?= htmlspecialchars(
                                            $b['telephone'] ?? ''
                                        ) ?>

                                    </td>


                                    <!-- SEXE -->

                                    <td>

                                        <?php

                                        $sexe = strtoupper(
                                            trim(
                                                $b['sexe'] ?? ''
                                            )
                                        );

                                        if ($sexe === 'M') {

                                            echo '
                                                <span
                                                    class="badge bg-primary"
                                                >
                                                    Homme
                                                </span>
                                            ';

                                        }
                                        elseif ($sexe === 'F') {

                                            echo '
                                                <span
                                                    class="badge bg-danger"
                                                >
                                                    Femme
                                                </span>
                                            ';

                                        }
                                        else {

                                            echo '
                                                <span
                                                    class="badge bg-secondary"
                                                >
                                                    —
                                                </span>
                                            ';

                                        }

                                        ?>

                                    </td>


                                    <!-- CNI -->

                                    <td>

                                        <?= htmlspecialchars(
                                            $b['cni'] ?? ''
                                        ) ?>

                                    </td>


                                    <!-- NUMERO PLACE -->

                                    <td>

                                        <?php if (
                                            !empty(
                                                $b['numero_place']
                                            )
                                        ): ?>

                                            <strong
                                                class="text-primary"
                                            >

                                                <?= htmlspecialchars(
                                                    $b[
                                                        'numero_place'
                                                    ]
                                                ) ?>

                                            </strong>

                                        <?php else: ?>

                                            -

                                        <?php endif; ?>

                                    </td>


                                    <!-- TYPE PLACE -->

                                    <td>

                                        <?= htmlspecialchars(
                                            $b[
                                                'type_place'
                                            ] ?? '-'
                                        ) ?>

                                    </td>


                                    <!-- TYPE PASSAGER -->

                                    <td>

                                        <?= ucfirst(
                                            htmlspecialchars(
                                                $b[
                                                    'type_passager'
                                                ] ?? ''
                                            )
                                        ) ?>

                                    </td>


                                    <!-- NATIONALITÉ -->

                                    <td>

                                        <?php

                                        $client = strtolower(
                                            trim(
                                                $b[
                                                    'type_client'
                                                ] ?? ''
                                            )
                                        );


                                        if (
                                            $client ===
                                            'senegalais'
                                        ) {

                                            echo 'Sénégalais';

                                        }
                                        elseif (
                                            $client ===
                                            'resident'
                                        ) {

                                            echo
                                                'Étranger résident';

                                        }
                                        elseif (
                                            $client ===
                                            'etranger'
                                            ||
                                            $client ===
                                            'non_resident'
                                        ) {

                                            echo
                                                'Étranger non résident';

                                        }
                                        else {

                                            echo htmlspecialchars(
                                                $b[
                                                    'type_client'
                                                ] ?? ''
                                            );

                                        }

                                        ?>

                                    </td>


                                    <!-- DÉPART CLIENT -->

                                    <td>

                                        <span
                                            class="
                                                badge
                                                badge-carabane
                                            "
                                        >

                                            <?= htmlspecialchars(
                                                $b[
                                                    'depart_client'
                                                ]
                                                ??
                                                'Carabane'
                                            ) ?>

                                        </span>

                                    </td>

                                </tr>

                            <?php endforeach; ?>


                        <?php else: ?>

                            <tr>

                                <td
                                    colspan="12"
                                    class="text-center py-5"
                                >

                                    <div
                                        style="
                                            font-size:45px;
                                        "
                                    >

                                        🚢

                                    </div>


                                    <h5>

                                        Aucun passager embarqué
                                        à Carabane

                                    </h5>


                                    <p
                                        class="text-muted mb-0"
                                    >

                                        Aucun billet avec :

                                        <strong>
                                            statut = embarque
                                        </strong>

                                        et

                                        <strong>
                                            depart_client = Carabane
                                        </strong>

                                        n'a été trouvé pour
                                        ce voyage.

                                    </p>

                                </td>

                            </tr>

                        <?php endif; ?>


                        </tbody>

                    </table>

                </div>



                <!-- =================================================
                     RÉCAPITULATIF
                ================================================== -->

                <div class="recap-box text-center mt-3">

                    <div>

                        👨 Hommes :
                        <?= $homme ?>

                        &nbsp; | &nbsp;

                        👩 Femmes :
                        <?= $femme ?>

                        <?php if (
                            $non_renseigne > 0
                        ): ?>

                            &nbsp; | &nbsp;

                            Non renseigné :
                            <?= $non_renseigne ?>

                        <?php endif; ?>

                    </div>


                    <div>

                        Chaise :
                        <?= $chaise ?>

                        &nbsp; | &nbsp;

                        Cabine 2p :
                        <?= $cabine2 ?>

                        &nbsp; | &nbsp;

                        Cabine 4p :
                        <?= $cabine4 ?>

                        &nbsp; | &nbsp;

                        Cabine 8p :
                        <?= $cabine8 ?>

                        <?php if ($autres_places > 0): ?>

                            &nbsp; | &nbsp;

                            Autres :
                            <?= $autres_places ?>

                        <?php endif; ?>

                    </div>


                    <div>

                        Sénégalais :
                        <?= $senegalais ?>

                        &nbsp; | &nbsp;

                        Étrangers résidents :
                        <?= $residents ?>

                        &nbsp; | &nbsp;

                        Étrangers non résidents :
                        <?= $etrangers ?>

                    </div>


                    <div class="fs-5 mt-2">

                        🚢

                        TOTAL PASSAGERS
                        EMBARQUÉS À CARABANE :

                        <strong>

                            <?= $total_passagers ?>

                        </strong>

                    </div>

                </div>

            </div>

        </div>

    <?php endif; ?>


</div>


<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"
></script>

</body>

</html>
```
