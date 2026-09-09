<?php
require_once '../config/database.php';
require_once __DIR__ . '/includes/auth.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 🔒 Sécurité admin
if(!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'admin'){
    die("⛔ Accès refusé");
}

$msg = "";

// 🔐 Fonction log
function logAction($pdo, $action){
    $pdo->prepare("
        INSERT INTO logs (user_id, action)
        VALUES (?, ?)
    ")->execute([$_SESSION['user']['id'], $action]);
}

// ➕ CREATE USER
if(isset($_POST['create'])){

    $nom = trim($_POST['nom']);
    $email = trim($_POST['email']);
    $role = $_POST['role'];

    // mot de passe aléatoire
$password_plain = "Cosama@26";
$password = password_hash($password_plain, PASSWORD_DEFAULT);

    $check = $pdo->prepare("SELECT id FROM users WHERE email=?");
    $check->execute([$email]);

    if($check->rowCount() > 0){
        $msg = "❌ Email déjà utilisé";
    } else {

        $pdo->prepare("
            INSERT INTO users (nom, email, password, role, force_password_change)
            VALUES (?, ?, ?, ?, 1)
        ")->execute([$nom, $email, $password, $role]);

        logAction($pdo, "Création utilisateur: $email");

        $msg = "✅ Utilisateur créé (mot de passe: $password_plain)";
    }
}

// 🔄 RESET PASSWORD
if(isset($_POST['reset'])){
    $id = $_POST['id'];

    $newPassPlain = "Cosama@26";
    $newPass = password_hash($newPassPlain, PASSWORD_DEFAULT);

    $pdo->prepare("
        UPDATE users 
        SET password=?, force_password_change=1 
        WHERE id=?
    ")->execute([$newPass, $id]);

    // 🔥 LOG intelligent
    if($id == $_SESSION['user']['id']){
        logAction($pdo, "Utilisateur a reset SON propre mot de passe");
    } else {
        logAction($pdo, "Reset password user ID: $id");
    }

    $msg = "✅ Nouveau mot de passe: $newPassPlain";
}
// ❌ DELETE
if(isset($_POST['delete'])){
    $id = $_POST['id'];

    if($id == $_SESSION['user']['id']){
        $msg = "❌ Impossible de supprimer votre compte";
    } else {

        $pdo->prepare("DELETE FROM users WHERE id=?")->execute([$id]);

        logAction($pdo, "Suppression utilisateur ID: $id");

        $msg = "✅ Utilisateur supprimé";
    }
}

// ✏️ UPDATE USER (nom, email, rôle)
if(isset($_POST['update'])){
    $id = $_POST['id'];
    $nom = trim($_POST['nom']);
    $email = trim($_POST['email']);
    $role = $_POST['role'];

    $check = $pdo->prepare("SELECT id FROM users WHERE email=? AND id!=?");
    $check->execute([$email, $id]);

    if($check->rowCount() > 0){
        $msg = "❌ Cet email est déjà utilisé par un autre utilisateur";
    } else {

        $pdo->prepare("UPDATE users SET nom=?, email=?, role=? WHERE id=?")
            ->execute([$nom, $email, $role, $id]);

        logAction($pdo, "Modification utilisateur ID: $id ($nom, $email, $role)");

        $msg = "✅ Utilisateur mis à jour";
    }
}

// 🔎 RECHERCHE
$where = "";
$params = [];

if(!empty($_GET['q'])){
    $where = "WHERE nom LIKE ? OR email LIKE ?";
    $search = "%".$_GET['q']."%";
    $params = [$search, $search];
}

// LISTE
$stmt = $pdo->prepare("SELECT * FROM users $where ORDER BY id DESC");
$stmt->execute($params);
$users = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Utilisateurs</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">

<div class="container mt-5">

<div class="d-flex justify-content-between mb-4">
    <h3>👥 Utilisateurs</h3>
    <a href="dashboard.php" class="btn btn-secondary">⬅️ Retour</a>
</div>

<?php if(!empty($msg)): ?>
<div class="alert alert-info"><?= $msg ?></div>
<?php endif; ?>

<!-- 🔍 RECHERCHE -->
<form method="GET" class="mb-3">
    <input type="text" name="q" class="form-control"
    placeholder="🔍 Rechercher par nom ou email"
    value="<?= htmlspecialchars($_GET['q'] ?? '') ?>">
</form>

<!-- ➕ CREATE USER -->
<div class="card shadow mb-4">
<div class="card-body">

<h5>➕ Créer un utilisateur</h5>

<form method="POST" class="row g-2">

<div class="col-md-3">
<input type="text" name="nom" class="form-control" placeholder="Nom" required>
</div>

<div class="col-md-3">
<input type="email" name="email" class="form-control" placeholder="Email" required>
</div>

<div class="col-md-3">
<select name="role" class="form-select">
<option value="agent">Agent</option>
<option value="admin">Admin</option>
<option value="finance">Finance</option>
<option value="exploitation">Exploitation</option>
</select>
</div>

<div class="col-md-3">
<button name="create" class="btn btn-success w-100">
➕ Créer
</button>
</div>

</form>

</div>
</div>

<!-- TABLE -->
<div class="card shadow">
<div class="card-body table-responsive">

<table class="table table-bordered text-center">

<thead class="table-dark">
<tr>
<th>ID</th>
<th>Nom</th>
<th>Email</th>
<th>Rôle</th>
<th>Actions</th>
</tr>
</thead>

<tbody>

<?php foreach($users as $u): ?>
<tr>

<td><?= htmlspecialchars($u['id']) ?></td>
<td><?= htmlspecialchars($u['nom']) ?></td>
<td><?= htmlspecialchars($u['email']) ?></td>

<td>
<span class="badge bg-dark"><?= htmlspecialchars($u['role']) ?></span>
</td>

<td class="d-flex gap-2 justify-content-center align-items-center">

<!-- ✏️ EDIT (ouvre la modale) -->
<button type="button" class="btn btn-primary btn-sm"
data-bs-toggle="modal" data-bs-target="#editModal<?= $u['id'] ?>">
✏️
</button>

<?php if($u['id'] != $_SESSION['user']['id']): ?>

<!-- RESET -->
<form method="POST">
<input type="hidden" name="id" value="<?= $u['id'] ?>">
<button name="reset" class="btn btn-warning btn-sm"
onclick="return confirm('Reset mot de passe ?')">
🔑
</button>
</form>

<!-- DELETE -->
<form method="POST">
<input type="hidden" name="id" value="<?= $u['id'] ?>">
<button name="delete" class="btn btn-danger btn-sm"
onclick="return confirm('Supprimer cet utilisateur ?')">
❌
</button>
</form>

<?php else: ?>
<span class="text-muted">Vous</span>
<?php endif; ?>

</td>

</tr>

<!-- 🪟 MODALE D'ÉDITION -->
<div class="modal fade" id="editModal<?= $u['id'] ?>" tabindex="-1">
<div class="modal-dialog">
<div class="modal-content">

<form method="POST">
<input type="hidden" name="id" value="<?= $u['id'] ?>">

<div class="modal-header">
<h5 class="modal-title">✏️ Modifier l'utilisateur #<?= htmlspecialchars($u['id']) ?></h5>
<button type="button" class="btn-close" data-bs-dismiss="modal"></button>
</div>

<div class="modal-body">

<div class="mb-3">
<label class="form-label">Nom</label>
<input type="text" name="nom" class="form-control"
value="<?= htmlspecialchars($u['nom']) ?>" required>
</div>

<div class="mb-3">
<label class="form-label">Email</label>
<input type="email" name="email" class="form-control"
value="<?= htmlspecialchars($u['email']) ?>" required>
</div>

<div class="mb-3">
<label class="form-label">Rôle</label>
<select name="role" class="form-select">
<option value="admin" <?= $u['role']=='admin'?'selected':'' ?>>admin</option>
<option value="agent" <?= $u['role']=='agent'?'selected':'' ?>>agent</option>
<option value="finance" <?= $u['role']=='finance'?'selected':'' ?>>finance</option>
<option value="exploitation" <?= $u['role']=='exploitation'?'selected':'' ?>>exploitation</option>
</select>
</div>

</div>

<div class="modal-footer">
<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
<button type="submit" name="update" class="btn btn-primary">💾 Enregistrer</button>
</div>

</form>

</div>
</div>
</div>

<?php endforeach; ?>

</tbody>

</table>

</div>
</div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
