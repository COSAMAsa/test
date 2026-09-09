<?php
session_start();

require_once '../config/database.php';

if (!isset($_SESSION['user'])) {
    header("Location: index.php");
    exit;
}


/* =========================================================
   1. RÉCUPÉRER UNIQUEMENT LES VOYAGES
      ZIGUINCHOR → DAKAR
========================================================= */

$stmt_traversees = $pdo->query("
    SELECT
        id,
        depart,
        destination,
        date_depart,
        bateau,
        prix,
        places_disponibles,
        date_jour,
        reference_voyage
    FROM traversees
    WHERE depart = 'Ziguinchor'
      AND destination = 'Dakar'
    ORDER BY date_depart DESC
");

$traversees = $stmt_traversees->fetchAll(PDO::FETCH_ASSOC);


/* =========================================================
   2. INITIALISATION
========================================================= */

$billets = [];

$total_passagers = 0;

$traversee_id = $_GET['traversee_id'] ?? '';

$traversee_selectionnee = null;


/* =========================================================
   3. RECHERCHER LA TRAVERSÉE SÉLECTIONNÉE
========================================================= */

if (!empty($traversee_id)) {

    foreach ($traversees as $t) {

        if ((string)$t['id'] === (string)$traversee_id) {

            $traversee_selectionnee = $t;

            break;
        }
    }
}


/* =========================================================
   4. RÉCUPÉRER LES PASSAGERS CARABANE
========================================================= */

if (!empty($traversee_id)) {

    $stmt = $pdo->prepare("
        SELECT
            b.*,

            p.numero_place,
            p.type_place,

            t.date_depart,
            t.depart,
            t.destination,
            t.reference_voyage,
            t.bateau

        FROM billets b

        LEFT JOIN places p
            ON b.id_place = p.id_place

        INNER JOIN traversees t
            ON b.traversee_id = t.id

        WHERE b.statut = 'valide'

          AND b.traversee_id = ?

          AND b.depart_client = 'Carabane'

          AND t.depart = 'Ziguinchor'

          AND t.destination = 'Dakar'

        ORDER BY
            p.numero_place ASC,
            b.nom ASC,
            b.prenom ASC
    ");

    $stmt->execute([$traversee_id]);

    $billets = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $total_passagers = count($billets);
}

?>

<!DOCTYPE html>

<html lang="fr">

<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>
    Passagers Carabane -
    Ziguinchor Dakar
</title>


<!-- =====================================================
     BOOTSTRAP
====================================================== -->

<link
    href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css"
    rel="stylesheet"
>


<style>

/* =====================================================
   PAGE
===================================================== */

body {

    background: #f4f6f9;

    font-family: Arial, sans-serif;

}


/* =====================================================
   CARTES
===================================================== */

.card {

    border: none;

    border-radius: 18px;

}


/* =====================================================
   STATISTIQUE
===================================================== */

.stat-card {

    background: white;

    padding: 20px;

    border-radius: 15px;

    box-shadow: 0 4px 15px rgba(0,0,0,.08);

}


/* =====================================================
   TABLEAU
===================================================== */

.table th {

    white-space: nowrap;

    vertical-align: middle;

}

.table td {

    vertical-align: middle;

}


/* =====================================================
   BADGE CARABANE
===================================================== */

.badge-carabane {

    background-color: #ffc107;

    color: #000;

}


/* =====================================================
   TITRE
===================================================== */

.page-title {

    font-weight: 700;

}


/* =====================================================
   INFOS VOYAGE
===================================================== */

.info-voyage {

    background: white;

    border-radius: 15px;

    padding: 20px;

    box-shadow: 0 4px 15px rgba(0,0,0,.06);

}


/* =====================================================
   IMPRESSION
===================================================== */

@media print {

    body {

        background: white;

    }

    .no-print {

        display: none !important;

    }

    .card {

        box-shadow: none !important;

    }

    .table {

        font-size: 11px;

    }

}

</style>

</head>


<body>


<div class="container-fluid mt-4">


    <!-- =================================================
         EN-TÊTE
    ================================================== -->

    <div
        class="d-flex justify-content-between align-items-center mb-4 no-print"
    >

        <div>

            <h3 class="page-title mb-1">

                🚢 Passagers au départ de Carabane

            </h3>

            <div class="text-muted">

                Voyages
                <strong>Ziguinchor → Dakar</strong>

            </div>

        </div>


        <div>

            <button
                type="button"
                class="btn btn-success me-2"
                onclick="window.print()"
            >

                🖨️ Imprimer

            </button>


            <a
                href="billets_emis.php"
                class="btn btn-secondary"
            >

                ⬅ Retour

            </a>

        </div>

    </div>



    <!-- =================================================
         FILTRE
    ================================================== -->

    <div class="card shadow mb-4 no-print">

        <div class="card-body">

            <form method="GET">

                <div class="row g-3">


                    <!-- ==============================
                         TRAVERSÉE
                    =============================== -->

                    <div class="col-md-9">

                        <label
                            class="form-label fw-bold"
                        >

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


                            <?php foreach ($traversees as $t): ?>

                                <option
                                    value="<?= htmlspecialchars($t['id']) ?>"
                                    <?= (
                                        (string)$traversee_id ===
                                        (string)$t['id']
                                    )
                                    ? 'selected'
                                    : ''
                                    ?>
                                >

                                    <?php if (!empty($t['reference_voyage'])): ?>

                                        <?= htmlspecialchars(
                                            $t['reference_voyage']
                                        ) ?>

                                        -

                                    <?php endif; ?>


                                    Ziguinchor → Dakar


                                    <?php if (!empty($t['date_depart'])): ?>

                                        -
                                        <?= date(
                                            'd/m/Y H:i',
                                            strtotime(
                                                $t['date_depart']
                                            )
                                        ) ?>

                                    <?php endif; ?>


                                    <?php if (!empty($t['bateau'])): ?>

                                        -
                                        <?= htmlspecialchars(
                                            $t['bateau']
                                        ) ?>

                                    <?php endif; ?>

                                </option>

                            <?php endforeach; ?>


                        </select>


                        <?php if (empty($traversees)): ?>

                            <div class="alert alert-warning mt-3 mb-0">

                                Aucun voyage
                                <strong>
                                    Ziguinchor → Dakar
                                </strong>
                                n'a été trouvé.

                            </div>

                        <?php endif; ?>


                    </div>



                    <!-- ==============================
                         BOUTON
                    =============================== -->

                    <div
                        class="col-md-3 d-flex align-items-end"
                    >

                        <button
                            type="submit"
                            class="btn btn-primary w-100"
                        >

                            🔎 Afficher

                        </button>

                    </div>


                </div>

            </form>

        </div>

    </div>



    <?php if (!empty($traversee_id)): ?>


        <!-- =================================================
             INFORMATIONS VOYAGE
        ================================================== -->

        <?php if ($traversee_selectionnee): ?>

            <div class="info-voyage mb-4">


                <div class="row g-3">


                    <!-- RÉFÉRENCE -->

                    <div class="col-md-3">

                        <small class="text-muted">
                            Référence voyage
                        </small>

                        <div class="fw-bold">

                            <?= htmlspecialchars(
                                $traversee_selectionnee[
                                    'reference_voyage'
                                ] ?? ''
                            ) ?>

                        </div>

                    </div>


                    <!-- DIRECTION -->

                    <div class="col-md-3">

                        <small class="text-muted">
                            Trajet
                        </small>

                        <div class="fw-bold">

                            <?= htmlspecialchars(
                                $traversee_selectionnee[
                                    'depart'
                                ] ?? ''
                            ) ?>

                            →

                            <?= htmlspecialchars(
                                $traversee_selectionnee[
                                    'destination'
                                ] ?? ''
                            ) ?>

                        </div>

                    </div>


                    <!-- DATE -->

                    <div class="col-md-3">

                        <small class="text-muted">
                            Date de départ
                        </small>

                        <div class="fw-bold">

                            <?php

                            if (
                                !empty(
                                    $traversee_selectionnee[
                                        'date_depart'
                                    ]
                                )
                            ) {

                                echo date(
                                    'd/m/Y H:i',
                                    strtotime(
                                        $traversee_selectionnee[
                                            'date_depart'
                                        ]
                                    )
                                );

                            }

                            ?>

                        </div>

                    </div>


                    <!-- BATEAU -->

                    <div class="col-md-3">

                        <small class="text-muted">
                            Bateau
                        </small>

                        <div class="fw-bold">

                            <?= htmlspecialchars(
                                $traversee_selectionnee[
                                    'bateau'
                                ] ?? ''
                            ) ?>

                        </div>

                    </div>


                </div>


                <hr>


                <div class="text-center">

                    <span
                        class="badge bg-primary fs-6 px-4 py-2"
                    >

                        🚢 Ziguinchor → Carabane → Dakar

                    </span>

                </div>


            </div>

        <?php endif; ?>



        <!-- =================================================
             STATISTIQUES
        ================================================== -->

        <div class="row mb-4">


            <div class="col-md-4">

                <div class="stat-card">

                    <h6 class="text-muted">

                        Total passagers Carabane

                    </h6>


                    <h2
                        class="text-primary fw-bold mb-0"
                    >

                        <?= $total_passagers ?>

                    </h2>


                    <small class="text-muted">

                        Billets valides

                    </small>

                </div>

            </div>


        </div>



        <!-- =================================================
             TABLEAU
        ================================================== -->

        <div class="card shadow">


            <div class="card-body">


                <div class="d-flex justify-content-between align-items-center mb-3">


                    <div>

                        <h5 class="fw-bold mb-1">

                            Liste des passagers

                        </h5>

                        <span class="text-muted">

                            Départ client :
                            <strong>Carabane</strong>

                        </span>

                    </div>


                    <div>

                        <span class="badge badge-carabane fs-6">

                            <?= $total_passagers ?>
                            passager(s)

                        </span>

                    </div>


                </div>



                <div class="table-responsive">


                    <table
                        class="table table-bordered table-hover align-middle"
                    >


                        <thead class="table-dark">


                            <tr>

                                <th>#</th>

                                <th>Nom</th>

                                <th>Prénom</th>

                                <th>Téléphone</th>

                                <th>CNI</th>

                                <th>N° Place</th>

                                <th>Type place</th>

                                <th>Type passager</th>

                                <th>Nationalité</th>

                                <th>Départ client</th>

                                <th>Code billet</th>

                                <th>Date réservation</th>

                            </tr>


                        </thead>


                        <tbody>


                        <?php if (!empty($billets)): ?>


                            <?php $i = 1; ?>


                            <?php foreach ($billets as $b): ?>


                                <tr>


                                    <!-- ======================
                                         #
                                    ======================= -->

                                    <td>

                                        <?= $i++ ?>

                                    </td>



                                    <!-- ======================
                                         NOM
                                    ======================= -->

                                    <td class="fw-bold">

                                        <?= htmlspecialchars(
                                            $b['nom'] ?? ''
                                        ) ?>

                                    </td>



                                    <!-- ======================
                                         PRÉNOM
                                    ======================= -->

                                    <td>

                                        <?= htmlspecialchars(
                                            $b['prenom'] ?? ''
                                        ) ?>

                                    </td>



                                    <!-- ======================
                                         TÉLÉPHONE
                                    ======================= -->

                                    <td>

                                        <?= htmlspecialchars(
                                            $b['telephone'] ?? ''
                                        ) ?>

                                    </td>



                                    <!-- ======================
                                         CNI
                                    ======================= -->

                                    <td>

                                        <?= htmlspecialchars(
                                            $b['cni'] ?? ''
                                        ) ?>

                                    </td>



                                    <!-- ======================
                                         PLACE
                                    ======================= -->

                                    <td>

                                        <?php if (
                                            !empty(
                                                $b['numero_place']
                                            )
                                        ): ?>

                                            <span
                                                class="
                                                    fw-bold
                                                    text-primary
                                                "
                                            >

                                                <?= htmlspecialchars(
                                                    $b[
                                                        'numero_place'
                                                    ]
                                                ) ?>

                                            </span>

                                        <?php else: ?>

                                            <span
                                                class="text-muted"
                                            >

                                                -

                                            </span>

                                        <?php endif; ?>

                                    </td>



                                    <!-- ======================
                                         TYPE PLACE
                                    ======================= -->

                                    <td>

                                        <?php if (
                                            !empty(
                                                $b['type_place']
                                            )
                                        ): ?>

                                            <span
                                                class="badge bg-primary"
                                            >

                                                <?= htmlspecialchars(
                                                    $b[
                                                        'type_place'
                                                    ]
                                                ) ?>

                                            </span>

                                        <?php else: ?>

                                            -

                                        <?php endif; ?>

                                    </td>



                                    <!-- ======================
                                         TYPE PASSAGER
                                    ======================= -->

                                    <td>

                                        <?= ucfirst(
                                            htmlspecialchars(
                                                $b[
                                                    'type_passager'
                                                ] ?? ''
                                            )
                                        ) ?>

                                    </td>



                                    <!-- ======================
                                         NATIONALITÉ
                                    ======================= -->

                                    <td>

                                        <?php

                                        $type_client =
                                            strtolower(
                                                trim(
                                                    $b[
                                                        'type_client'
                                                    ] ?? ''
                                                )
                                            );


                                        if (
                                            $type_client ===
                                            'senegalais'
                                        ) {

                                            echo 'Sénégalais';

                                        }

                                        elseif (
                                            $type_client ===
                                            'resident'
                                        ) {

                                            echo
                                                'Étranger résident';

                                        }

                                        elseif (
                                            $type_client ===
                                            'etranger'
                                        ) {

                                            echo
                                                'Étranger non résident';

                                        }

                                        elseif (
                                            $type_client ===
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



                                    <!-- ======================
                                         DÉPART CLIENT
                                    ======================= -->

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
                                                ] ?? 'Carabane'
                                            ) ?>

                                        </span>

                                    </td>



                                    <!-- ======================
                                         CODE BILLET
                                    ======================= -->

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



                                    <!-- ======================
                                         DATE RÉSERVATION
                                    ======================= -->

                                    <td>

                                        <?php

                                        if (
                                            !empty(
                                                $b[
                                                    'date_reservation'
                                                ]
                                            )
                                        ) {

                                            echo date(
                                                'd/m/Y H:i',
                                                strtotime(
                                                    $b[
                                                        'date_reservation'
                                                    ]
                                                )
                                            );

                                        } else {

                                            echo '-';

                                        }

                                        ?>

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
                                        class="text-muted"
                                    >

                                        <div
                                            style="
                                                font-size:45px;
                                            "
                                        >

                                            🚢

                                        </div>


                                        <h5>

                                            Aucun passager trouvé

                                        </h5>


                                        <p class="mb-0">

                                            Aucun billet valide
                                            avec

                                            <strong>
                                                depart_client =
                                                Carabane
                                            </strong>

                                            pour ce voyage

                                            <strong>
                                                Ziguinchor →
                                                Dakar
                                            </strong>.

                                        </p>

                                    </div>

                                </td>

                            </tr>


                        <?php endif; ?>


                        </tbody>


                    </table>

                </div>


            </div>

        </div>


    <?php endif; ?>


</div>


<!-- =====================================================
     BOOTSTRAP JS
====================================================== -->

<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"
></script>


</body>

</html>
