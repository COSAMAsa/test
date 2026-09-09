<?php
require_once '../config/database.php';

// 🔒 Vérifier connexion
if (!isset($_SESSION['user'])) {
    header("Location:index.php");
    exit;
}

// 🔒 Vérifier rôle admin uniquement
if($_SESSION['user']['role'] != 'admin'){
    die("⛔ Accès refusé");
}


$message = "";

if($_SERVER['REQUEST_METHOD'] == 'POST'){

    $nom = trim($_POST['nom']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    // validation simple
    if(strlen($password) < 4){
        $message = "❌ Mot de passe trop court";
    } else {

        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        // vérifier si email existe
        $check = $pdo->prepare("SELECT id FROM users WHERE email=?");
        $check->execute([$email]);

        if($check->rowCount() > 0){
            $message = "❌ Email déjà utilisé";
        } else {

            $role = $_POST['role'];

$stmt = $pdo->prepare("
INSERT INTO users (nom, email, password, role)
VALUES (?, ?, ?, ?)
");

$stmt->execute([$nom, $email, $passwordHash, $role]);

            $message = "✅ Compte créé avec succès";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Inscription</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">

<div class="container mt-5">
<div class="row justify-content-center">
<div class="col-md-5">

<div class="card shadow">
<div class="card-body">

<h3 class="text-center mb-4">📝 Créer un compte</h3>

<?php if($message): ?>
<div class="alert alert-info text-center"><?= $message ?></div>
<?php endif; ?>

<form method="POST">

<input 
type="text" 
name="nom" 
class="form-control mb-3"
placeholder="Nom complet"
required
>

<input 
type="email" 
name="email" 
class="form-control mb-3"
placeholder="Email"
required
>

<input 
type="password" 
name="password" 
class="form-control mb-3"
placeholder="Mot de passe"
required
>
<select name="role" class="form-select mb-3" required>
    <option value="">Choisir un rôle</option>
    <option value="admin">Admin</option>
    <option value="agent">Agent</option>
    <option value="finance">Finance</option>
    <option value="exploitation">Exploitation</option>
</select>

<button class="btn btn-success w-100">
S'inscrire
</button>

</form>

<a href="index.php" class="btn btn-link w-100 mt-3">
Déjà un compte ? Se connecter
</a>

</div>
</div>

</div>
</div>
</div>

</body>
</html>
