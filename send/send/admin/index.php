<?php
session_start();
require_once '../config/database.php';

$message = "";

if($_SERVER['REQUEST_METHOD'] == 'POST'){

    $email = trim($_POST['email']);
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email=?");
    $stmt->execute([$email]);

    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if($user && password_verify($password, $user['password'])){

        $_SESSION['user'] = $user;

        // 🔥 vérifier si doit changer mot de passe
        if($user['force_password_change'] == 1){
            header("Location: change_password.php");
            exit;
        }

        header("Location: dashboard.php");
        exit;

    } else {
        $message = "Email ou mot de passe incorrect";
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Connexion Administration | Billetterie Maritime</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

<style>
:root{
    --navy-deep: #0b2a4a;
    --navy: #123a5e;
    --teal: #0d7377;
    --teal-light: #14a3a8;
    --sand: #f4ede2;
    --coral: #ff6b5b;
}

*{
    font-family: 'Poppins', sans-serif;
}

body{
    min-height: 100vh;
    margin: 0;
    background: linear-gradient(160deg, var(--navy-deep) 0%, var(--navy) 45%, var(--teal) 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
    overflow-x: hidden;
}

/* Vagues décoratives en fond */
.wave-bg{
    position: fixed;
    bottom: 0;
    left: 0;
    width: 100%;
    z-index: 0;
    opacity: 0.5;
    pointer-events: none;
}

.bubble{
    position: fixed;
    border-radius: 50%;
    background: rgba(255,255,255,0.06);
    pointer-events: none;
}
.bubble:nth-child(1){ width:120px; height:120px; top:10%; left:8%; }
.bubble:nth-child(2){ width:70px; height:70px; top:65%; left:85%; }
.bubble:nth-child(3){ width:45px; height:45px; top:20%; left:80%; }

.login-wrapper{
    position: relative;
    z-index: 2;
    width: 100%;
    max-width: 420px;
    padding: 20px;
}

.brand-badge{
    text-align: center;
    margin-bottom: 24px;
}

.brand-badge .icon-circle{
    width: 78px;
    height: 78px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--teal-light), var(--teal));
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 14px;
    box-shadow: 0 10px 25px rgba(13,115,119,0.45);
    font-size: 34px;
    color: #fff;
}

.brand-badge h1{
    color: #fff;
    font-size: 1.4rem;
    font-weight: 700;
    margin: 0;
    letter-spacing: .3px;
}

.brand-badge p{
    color: rgba(255,255,255,0.75);
    font-size: .85rem;
    margin-top: 4px;
}

.login-card{
    background: rgba(255,255,255,0.97);
    border-radius: 20px;
    box-shadow: 0 25px 50px rgba(0,0,0,0.35);
    border: 1px solid rgba(255,255,255,0.4);
    overflow: hidden;
}

.card-top-strip{
    height: 6px;
    background: linear-gradient(90deg, var(--coral), var(--teal-light), var(--navy));
}

.login-card .card-body{
    padding: 40px 34px 32px;
}

.section-title{
    text-align: center;
    color: var(--navy);
    font-weight: 600;
    font-size: 1.15rem;
    margin-bottom: 6px;
}

.section-subtitle{
    text-align: center;
    color: #7a8a99;
    font-size: .82rem;
    margin-bottom: 26px;
}

.input-group-custom{
    margin-bottom: 18px;
}

.input-group-custom label{
    font-size: .78rem;
    font-weight: 600;
    color: var(--navy);
    margin-bottom: 6px;
    display: block;
}

.input-icon-wrap{
    position: relative;
}

.input-icon-wrap i{
    position: absolute;
    left: 14px;
    top: 50%;
    transform: translateY(-50%);
    color: var(--teal);
    font-size: 1rem;
}

.form-control-custom{
    padding: 12px 14px 12px 42px;
    border-radius: 10px;
    border: 1.5px solid #e3e9ed;
    background: #fbfcfd;
    font-size: .92rem;
    transition: all .2s ease;
}

.form-control-custom:focus{
    border-color: var(--teal-light);
    box-shadow: 0 0 0 3px rgba(20,163,168,0.15);
    background: #fff;
}

.btn-login{
    background: linear-gradient(135deg, var(--teal-light), var(--navy));
    border: none;
    padding: 12px;
    border-radius: 10px;
    font-weight: 600;
    letter-spacing: .3px;
    color: #fff;
    transition: transform .15s ease, box-shadow .15s ease;
}

.btn-login:hover{
    transform: translateY(-2px);
    box-shadow: 0 10px 20px rgba(11,42,74,0.3);
    color: #fff;
}

.alert-custom{
    border-radius: 10px;
    font-size: .88rem;
    border: none;
    background: #fdecea;
    color: #b3261e;
    display: flex;
    align-items: center;
    gap: 8px;
}

.btn-create-user{
    border-radius: 10px;
    font-weight: 600;
    font-size: .85rem;
    background: var(--sand);
    color: var(--navy);
    border: 1px solid #e3d9c6;
}

.btn-create-user:hover{
    background: #ece2d0;
    color: var(--navy);
}

.footer-note{
    text-align: center;
    color: rgba(255,255,255,0.6);
    font-size: .75rem;
    margin-top: 22px;
}
</style>
</head>

<body>

<div class="bubble"></div>
<div class="bubble"></div>
<div class="bubble"></div>

<svg class="wave-bg" viewBox="0 0 1440 200" preserveAspectRatio="none">
    <path fill="#ffffff" fill-opacity="0.06" d="M0,120 C240,180 480,60 720,90 C960,120 1200,180 1440,110 L1440,200 L0,200 Z"></path>
    <path fill="#ffffff" fill-opacity="0.10" d="M0,150 C240,110 480,190 720,150 C960,110 1200,150 1440,160 L1440,200 L0,200 Z"></path>
</svg>

<div class="login-wrapper">

    <div class="brand-badge">
        <div class="icon-circle">
            <i class="bi bi-anchor"></i>
        </div>
        <h1>Billetterie Maritime</h1>
        <p><i class="bi bi-shield-lock me-1"></i>Espace Administration</p>
    </div>

    <div class="login-card">
        <div class="card-top-strip"></div>
        <div class="card-body">

            <div class="section-title">Connexion</div>
            <div class="section-subtitle">Accédez à votre tableau de bord de gestion</div>

            <?php if($message): ?>
            <div class="alert alert-custom text-center py-2 mb-3">
                <i class="bi bi-exclamation-triangle-fill"></i>
                <span><?= htmlspecialchars($message) ?></span>
            </div>
            <?php endif; ?>

            <form method="POST" novalidate>

                <div class="input-group-custom">
                    <label for="email">Adresse email</label>
                    <div class="input-icon-wrap">
                        <i class="bi bi-envelope"></i>
                        <input
                            type="email"
                            id="email"
                            name="email"
                            class="form-control form-control-custom"
                            placeholder="admin@exemple.com"
                            required
                            autofocus
                        >
                    </div>
                </div>

                <div class="input-group-custom">
                    <label for="password">Mot de passe</label>
                    <div class="input-icon-wrap">
                        <i class="bi bi-lock"></i>
                        <input
                            type="password"
                            id="password"
                            name="password"
                            class="form-control form-control-custom"
                            placeholder="••••••••"
                            required
                        >
                    </div>
                </div>

                <button type="submit" class="btn btn-login w-100 mt-2">
                    <i class="bi bi-box-arrow-in-right me-1"></i> Se connecter
                </button>

            </form>

            <?php if(isset($_SESSION['user']) && $_SESSION['user']['role'] == 'admin'): ?>
            <a href="register.php" class="btn btn-create-user w-100 mt-3">
                <i class="bi bi-person-plus me-1"></i> Créer un utilisateur
            </a>
            <?php endif; ?>

        </div>
    </div>

    <div class="footer-note">
        <i class="bi bi-water me-1"></i> © <?= date('Y') ?> Billetterie Maritime — Tous droits réservés
    </div>

</div>

</body>
</html>
