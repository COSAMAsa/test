<?php

require_once '../config/database.php';
require_once __DIR__ . '/includes/auth.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if(!isset($_SESSION['user'])){
    header("Location: index.php");
    exit;
}

$message = "";

if($_SERVER['REQUEST_METHOD'] == 'POST'){

    $pass1 = $_POST['password'];
    $pass2 = $_POST['confirm'];

    if($pass1 != $pass2){
        $message = "❌ Les mots de passe ne correspondent pas";
    } else {

        $hash = password_hash($pass1, PASSWORD_DEFAULT);

        $pdo->prepare("
            UPDATE users 
            SET password=?, force_password_change=0 
            WHERE id=?
        ")->execute([$hash, $_SESSION['user']['id']]);

        session_destroy();
        header("Location: index.php");
        exit;
    }
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Changer mot de passe</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">

<div class="container mt-5">
<div class="row justify-content-center">
<div class="col-md-4">

<div class="card shadow">
<div class="card-body">

<h4 class="text-center">🔒 Nouveau mot de passe</h4>

<?php if($message): ?>
<div class="alert alert-danger"><?= $message ?></div>
<?php endif; ?>

<form method="POST">

<input type="password" name="password" class="form-control mb-3" placeholder="Nouveau mot de passe" required>

<input type="password" name="confirm" class="form-control mb-3" placeholder="Confirmer" required>

<button class="btn btn-success w-100">Valider</button>

</form>

</div>
</div>

</div>
</div>
</div>

</body>
</html>
