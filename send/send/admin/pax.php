<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user'])) {
    header("Location: index.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="fr">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>PAX Passagers</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

<style>

body{
    background:#f4f6f9;
}

.card{
    border:none;
    border-radius:18px;
    overflow:hidden;
}

.nav-tabs .nav-link{
    font-weight:600;
    border-radius:12px 12px 0 0;
}

.nav-tabs .nav-link.active{
    background:#0d6efd;
    color:white;
}

iframe{
    width:100%;
    height:75vh;
    border:none;
}

@media(max-width:768px){
    h3{
        font-size:22px;
    }
}

</style>

</head>

<body>

<div class="container-fluid mt-4 px-2 px-md-4">

    <!-- HEADER -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap">

        <h3 class="fw-bold">
            👥 PAX Passagers
        </h3>

        <div class="d-flex gap-2 flex-wrap">

            <a href="billets_emis.php" class="btn btn-secondary">
                ⬅️ Retour
            </a>

        </div>

    </div>

    <!-- ONGLETS -->
    <ul class="nav nav-tabs mb-0" id="paxTabs" role="tablist">

        <li class="nav-item" role="presentation">
            <button
                class="nav-link active"
                id="embarques-tab"
                data-bs-toggle="tab"
                data-bs-target="#embarques"
                type="button"
                role="tab"
            >
                🚢 Passagers embarqués
            </button>
        </li>

        <li class="nav-item" role="presentation">
            <button
                class="nav-link"
                id="non-embarques-tab"
                data-bs-toggle="tab"
                data-bs-target="#non-embarques"
                type="button"
                role="tab"
            >
                ⏳ Passagers non embarqués
            </button>
        </li>

    </ul>

    <div class="card shadow tab-content" id="paxTabsContent">

        <!-- EMBARQUES -->
        <div class="tab-pane fade show active p-0" id="embarques" role="tabpanel">
            <iframe src="pax_embarques.php"></iframe>
        </div>

        <!-- NON EMBARQUES -->
        <div class="tab-pane fade p-0" id="non-embarques" role="tabpanel">
            <iframe src="pax_non_embarques.php"></iframe>
        </div>

    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>

</html>
