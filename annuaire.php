<?php
session_start();
require 'config/database.php';

if (empty($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

function e($v){
    return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8');
}

$userId = (int)$_SESSION['user_id'];

/* Pagination */

$limit = 6; // limitation des élèves sur la page annuaire.php
$page = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page - 1) * $limit;

/* Recherche */

$search = trim($_GET['search'] ?? '');

$params = [$userId];

$sql = "
SELECT id, nom, promotion, filiere, metier, photo
FROM users
WHERE id != ?
AND directory_visible = 1
AND directory_approved = 1
";

if ($search !== '') {

$sql .= "
AND (
nom LIKE ?
OR filiere LIKE ?
OR metier LIKE ?
OR promotion LIKE ?
)
";

$term = "%$search%";
array_push($params, $term, $term, $term, $term);

}

$sql .= " ORDER BY nom ASC LIMIT $limit OFFSET $offset";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);

$users = $stmt->fetchAll(PDO::FETCH_ASSOC);


/* Total utilisateurs */

$countSql = "
SELECT COUNT(*) FROM users
WHERE id != ?
AND directory_visible = 1
AND directory_approved = 1
";

$countParams = [$userId];

if ($search !== '') {

$countSql .= "
AND (
nom LIKE ?
OR filiere LIKE ?
OR metier LIKE ?
OR promotion LIKE ?
)
";

array_push($countParams, $term, $term, $term, $term);

}

$countStmt = $pdo->prepare($countSql);
$countStmt->execute($countParams);

$totalUsers = $countStmt->fetchColumn();

$totalPages = ceil($totalUsers / $limit);


/* Photo */

function photo_web_path(array $u): string {

$file = trim((string)($u['photo'] ?? ''));

$web = ($file !== '') ? ('assets/images/' . $file) : 'assets/images/default-avatar.png';

if (!is_file(__DIR__ . '/' . $web)) return 'assets/images/default-avatar.png';

return $web;

}

?>

<!DOCTYPE html>
<html lang="fr">

<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Annuaire Alumni</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

<link rel="stylesheet" href="css/annuaire.css">

</head>

<body class="bg-soft">

<?php include 'includes/header.php'; ?>


<div class="container my-5">

<div class="d-flex justify-content-between align-items-center mb-4">

<div>

<h2 class="fw-bold mb-1">Annuaire des anciens élèves</h2>

<div class="text-muted">
<?= $totalUsers ?> alumni dans le réseau
</div>

</div>

<a href="profile.php" class="btn btn-outline-secondary">
Mon profil
</a>

</div>


<!-- Recherche -->

<form method="GET" class="mb-4">

<div class="input-group">

<input
type="text"
name="search"
value="<?= e($search) ?>"
class="form-control form-control-lg"
placeholder="🔎 Rechercher un alumni">

<button class="btn btn-primary">
Rechercher
</button>

</div>

</form>


<div class="row g-4">

<?php foreach ($users as $u): ?>

<div class="col-md-6 col-lg-4">

<div class="card card-elevated h-100">

<div class="annuaire-cover">

<img src="<?= e(photo_web_path($u)) ?>" class="annuaire-img" alt="Photo profil">

</div>

<div class="card-body p-4">

<h5 class="card-title mb-2">

<?= e($u['nom']) ?>

</h5>

<?php if (!empty($u['promotion'])): ?>

<span class="badge-promo mb-2 d-inline-block">

Promotion <?= e($u['promotion']) ?>

</span>

<?php endif; ?>

<div class="text-muted small mb-2">

<?= $u['filiere'] ? e($u['filiere']) : 'Filière —' ?>

</div>

<div class="mb-3">

<div class="small text-muted">Métier</div>

<div class="fw-semibold">

<?= $u['metier'] ? e($u['metier']) : '—' ?>

</div>

</div>

<div class="d-grid gap-2">

<a href="user_profile.php?id=<?= (int)$u['id'] ?>" class="btn btn-primary">

Voir profil

</a>

<a href="messages.php?to=<?= (int)$u['id'] ?>" class="btn btn-outline-primary">

Envoyer message

</a>

</div>

</div>

</div>

</div>

<?php endforeach; ?>

</div>


<!-- Pagination -->

<nav class="mt-5">

<ul class="pagination justify-content-center">

<?php for ($i = 1; $i <= $totalPages; $i++): ?>

<li class="page-item <?= $i == $page ? 'active' : '' ?>">

<a class="page-link" href="?page=<?= $i ?>&search=<?= urlencode($search) ?>">

<?= $i ?>

</a>

</li>

<?php endfor; ?>

</ul>

</nav>

</div>

<?php include 'includes/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>