<?php
require_once __DIR__ . '/includes/auth.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user'])) {
    header("Location: index.php");
    exit;
}

$user = $_SESSION['user'];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Dashboard</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

    <style>
        /* ===== BACKGROUND IMAGE ===== */
        body {
            background: url('../assets/ASD1.jfif') no-repeat center center fixed;
            background-size: cover;
            animation: zoomBg 20s infinite alternate;
        }

        /* overlay sombre */
        body::before {
            content: "";
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.65);
            z-index: -1;
        }

        /* animation zoom */
        @keyframes zoomBg {
            from { background-size: 100%; }
            to   { background-size: 110%; }
        }

        /* ===== HEADER ===== */
        .topbar {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(8px);
            color: black;
            padding: 15px;
            border-radius: 10px;
        }

        /* ===== CARDS ===== */
        .card-box {
            border-radius: 15px;
            transition: 0.3s;
            cursor: pointer;
            backdrop-filter: blur(10px);
            background: rgba(255, 255, 255, 0.1);
            color: white;
            text-align: center;
            padding: 30px 15px;
            height: 100%;
        }

        .card-box:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.4);
        }

        .icon {
            font-size: 35px;
            margin-bottom: 10px;
        }

        a {
            text-decoration: none;
        }

        /* MOBILE */
        @media (max-width: 768px) {
            .card-box {
                padding: 20px 10px;
            }
            .icon {
                font-size: 28px;
            }
        }
    </style>
</head>
<body>

<div class="container mt-4">

    <!-- HEADER -->
    <div class="topbar d-flex justify-content-between align-items-center mb-4 shadow flex-wrap gap-2">
        <h4 class="mb-0">🚢 Gestion Billetterie</h4>

        <div>
            Bienvenue, <strong><?= $user['nom'] ?></strong>
            <span class="badge bg-dark"><?= $user['role'] ?></span>

            <a href="logout.php" class="btn btn-danger btn-sm ms-3">
                Déconnexion
            </a>
        </div>
    </div>

    <div class="row g-4">

        <!-- ADMIN -->
        <?php if ($user['role'] == 'admin' || $user['role'] == 'exploitation'): ?>

            <div class="col-md-4">
                <a href="add_traversee.php">
                    <div class="card-box">
                        <div class="icon">➕</div>
                        <h5>Ajouter voyage</h5>
                    </div>
                </a>
            </div>

            <div class="col-md-4">
                <a href="liste_traversees.php">
                    <div class="card-box">
                        <div class="icon">📋</div>
                        <h5>Voir voyages</h5>
                    </div>
                </a>
            </div>

            <div class="col-md-4">
                <a href="utilisateurs.php">
                    <div class="card-box">
                        <div class="icon">👥</div>
                        <h5>Utilisateurs</h5>
                    </div>
                </a>
            </div>

        <?php endif; ?>

        <!-- AGENT + ADMIN -->
        <?php if ($user['role'] == 'admin' || $user['role'] == 'agent' || $user['role'] == 'exploitation'): ?>

            <div class="col-6 col-md-3">
                <a href="controle.php">
                    <div class="card-box">
                        <div class="icon">🎫</div>
                        <h5>Embarquement</h5>
                    </div>
                </a>
            </div>

            <div class="col-6 col-md-3">
                <a href="reservations.php">
                    <div class="card-box">
                        <div class="icon">📅</div>
                        <h5>Réservations</h5>
                    </div>
                </a>
            </div>

            <div class="col-6 col-md-3">
                <a href="rechercher_billet.php">
                    <div class="card-box">
                        <div class="icon">🔍</div>
                        <h5>Rechercher billet</h5>
                    </div>
                </a>
            </div>

            <div class="col-6 col-md-3">
                <a href="billets_embarques.php">
                    <div class="card-box">
                        <div class="icon">🚢</div>
                        <h5>Billets embarqués</h5>
                    </div>
                </a>
            </div>

                      <div class="col-6 col-md-3">
                <a href="billets_reportes.php">
                    <div class="card-box">
                        <div class="icon">🔁</div>
                        <h5>Billets reportés</h5>
                    </div>
                </a>
            </div>
        <?php endif; ?>

        <!-- EXPLOITATION + ADMIN + FINANCE -->
        <?php if ($user['role'] == 'admin' || $user['role'] == 'exploitation' || $user['role'] == 'finance'): ?>

            <div class="col-6 col-md-3">
                <a href="billets_emis.php">
                    <div class="card-box">
                        <div class="icon">🎟️</div>
                        <h5>Billets émis</h5>
                    </div>
                </a>
            </div>

            <div class="col-6 col-md-3">
                <a href="ca.php">
                    <div class="card-box">
                        <div class="icon">💹</div>
                        <h5>CA</h5>
                    </div>
                </a>
            </div>

            <div class="col-6 col-md-3">
                <a href="historique.php">
                    <div class="card-box">
                        <div class="icon">🕘</div>
                        <h5>Historique</h5>
                    </div>
                </a>
            </div>

        <?php endif; ?>

        <!-- FINANCE + ADMIN -->
        <?php if ($user['role'] == 'admin' || $user['role'] == 'finance'): ?>

            <div class="col-md-12">
                <a href="comptabilite.php">
                    <div class="card-box">
                        <div class="icon">💰</div>
                        <h5>Comptabilité</h5>
                    </div>
                </a>
            </div>

        <?php endif; ?>

        <!-- ADMIN -->
        <?php if ($user['role'] == 'admin'): ?>

            <div class="col-md-12">
                <a href="kpi_dashboard.php">
                    <div class="card-box">
                        <div class="icon">📊</div>
                        <h5>KPI Direction</h5>
                    </div>
                </a>
            </div>

        <?php endif; ?>

    </div>

</div>

</body>
</html>
