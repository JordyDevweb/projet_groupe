<?php
session_start();
require __DIR__ . '/config/database.php';

function e($v){
    return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8');
}

// Vérifier si l'utilisateur est connecté
if(empty($_SESSION['user_id'])){
    header("Location: login.php");
    exit;
}

$me = (int)$_SESSION['user_id'];
$flash = ['type'=>'','msg'=>''];

// ----------------------------
// Récupérer l'ID du profil affiché
// ----------------------------
$profile_id = (int)($_GET['id'] ?? $me);

// ----------------------------
// PUBLIER UN TEMOIGNAGE
// ----------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Création témoignage
    if(($_POST['action'] ?? '') === 'testimonial_create'){
        $message = trim($_POST['message'] ?? '');
        if($message === '' || strlen($message) < 10){
            $flash = ['type'=>'danger', 'msg'=>'Le témoignage doit contenir au moins 10 caractères.'];
        } else {
            $stmt = $pdo->prepare("INSERT INTO testimonials(user_id,message,created_at) VALUES(?,?,NOW())");
            $stmt->execute([$me,$message]);
            $flash = ['type'=>'success', 'msg'=>'Merci pour ton témoignage.'];
        }
    }

    // Supprimer un témoignage
    if(($_POST['action'] ?? '') === 'testimonial_delete'){
        $tid = (int)($_POST['testimonial_id'] ?? 0);
        $stmt = $pdo->prepare("DELETE FROM testimonials WHERE id=? AND user_id=?");
        $stmt->execute([$tid, $me]);
    }

    // Création recommandation
    if(($_POST['action'] ?? '') === 'recommendation_create'){
        $message = trim($_POST['message'] ?? '');
        if($message === '' || strlen($message) < 10){
            $flash = ['type'=>'danger', 'msg'=>'La recommandation doit contenir au moins 10 caractères.'];
        } else {
            $stmt = $pdo->prepare("
                INSERT INTO recommendations(user_id, author_id, message, created_at)
                VALUES (?, ?, ?, NOW())
            ");
            $stmt->execute([$profile_id, $me, $message]);
            $flash = ['type'=>'success', 'msg'=>'Merci pour ta recommandation.'];
        }
    }

    // Supprimer une recommandation
    if(($_POST['action'] ?? '') === 'recommendation_delete'){
        $rid = (int)($_POST['recommendation_id'] ?? 0);
        $stmt = $pdo->prepare("DELETE FROM recommendations WHERE id=? AND author_id=?");
        $stmt->execute([$rid, $me]);
    }
}

// ----------------------------
// Récupérer les informations du profil
// ----------------------------
$stmt = $pdo->prepare("SELECT id, nom, email, promotion, filiere, metier, bio, photo FROM users WHERE id=?");
$stmt->execute([$profile_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
if(!$user){
    die("Utilisateur introuvable");
}

// ----------------------------
// Récupérer les témoignages
// ----------------------------
$stmt = $pdo->prepare("
    SELECT t.*, u.nom, u.photo
    FROM testimonials t
    JOIN users u ON u.id = t.user_id
    WHERE t.user_id = ?
    ORDER BY t.created_at DESC
");
$stmt->execute([$profile_id]);
$testimonials = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ----------------------------
// Récupérer les recommandations
// ----------------------------
$stmt = $pdo->prepare("
    SELECT r.*, u.nom, u.photo
    FROM recommendations r
    JOIN users u ON u.id = r.author_id
    WHERE r.user_id = ?
    ORDER BY r.created_at DESC
");
$stmt->execute([$profile_id]);
$recommendations = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Profil de <?= e($user['nom']) ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<?php include __DIR__ . '/includes/header.php'; ?>

<div class="container py-4">

<?php if(!empty($flash['msg'])): ?>
<div class="alert alert-<?= e($flash['type']) ?>"><?= e($flash['msg']) ?></div>
<?php endif; ?>

<div class="row g-4">

<!-- PROFIL -->
<div class="col-lg-4">
<div class="card shadow-sm border-0">
<div class="card-body text-center p-4">
<img src="assets/images/<?= e($user['photo'] ?: 'default.png') ?>" width="110" height="110" class="rounded-circle mb-3" style="object-fit:cover">
<h5 class="fw-bold"><?= e($user['nom']) ?></h5>
<div class="text-muted small mb-2">
<?= e($user['filiere'] ?: 'Filière non renseignée') ?>
<?php if(!empty($user['promotion'])): ?> * Promotion <?= e($user['promotion']) ?><?php endif; ?>
</div>
<div class="fw-semibold mb-2"><?= e($user['metier'] ?: 'Métier non renseigné') ?></div>
<p class="text-muted small"><?= e($user['bio'] ?: 'Aucune bio.') ?></p>
<?php if($me === $profile_id): ?>
<a href="edit_profile.php" class="btn btn-outline-primary btn-sm">Modifier profil</a>
<?php endif; ?>
</div>
</div>
</div>

<!-- Témoignages et Recommandations -->
<div class="col-lg-8">

<!-- FORMULAIRE TÉMOIGNAGE -->
<?php if($me === $profile_id): ?>
<div class="card shadow-sm border-0">
<div class="card-body p-4">
<h5 class="fw-bold mb-3">Partager ton témoignage</h5>
<form method="post">
<input type="hidden" name="action" value="testimonial_create">
<textarea class="form-control mb-3" name="message" rows="4" placeholder="Partage ton expérience..." required></textarea>
<button class="btn btn-primary">Publier mon témoignage</button>
</form>
</div>
</div>
<?php endif; ?>

<!-- LISTE DES TÉMOIGNAGES -->
<div class="card shadow-sm border-0 mt-4">
<div class="card-body p-4">
<h5 class="fw-bold mb-3">Témoignages</h5>
<?php if(empty($testimonials)): ?>
<div class="text-muted">Aucun témoignage pour le moment.</div>
<?php else: ?>
<?php foreach($testimonials as $t): ?>
<div class="border rounded-3 p-3 mb-2 bg-light d-flex align-items-start justify-content-between">
<div>
<div class="fw-bold"><?= e($t['nom']) ?></div>
<div class="text-muted small"><?= e(date('d/m/Y',strtotime($t['created_at']))) ?></div>
<p><?= e($t['message']) ?></p>
</div>
<?php if($t['user_id'] === $me): ?>
<form method="post">
<input type="hidden" name="action" value="testimonial_delete">
<input type="hidden" name="testimonial_id" value="<?= e($t['id']) ?>">
<button class="btn btn-sm btn-outline-danger" onclick="return confirm('Supprimer ce témoignage ?')">Supprimer</button>
</form>
<?php endif; ?>
</div>
<?php endforeach; ?>
<?php endif; ?>
</div>

<!-- FORMULAIRE RECOMMANDATION (si ce n'est pas le profil de soi) -->
<?php if($me !== $profile_id): ?>
<div class="card shadow-sm border-0 mt-4">
<div class="card-body p-4">
<h5 class="fw-bold mb-3">Écrire une recommandation pour <?= e($user['nom']) ?></h5>
<form method="post">
<input type="hidden" name="action" value="recommendation_create">
<textarea class="form-control mb-3" name="message" rows="3" placeholder="Rédige ta recommandation..." required></textarea>
<button class="btn btn-primary">Envoyer la recommandation</button>
</form>
</div>
</div>
<?php endif; ?>

<!-- LISTE DES RECOMMANDATIONS -->
<div class="card shadow-sm border-0 mt-4">
<div class="card-body p-4">
<h5 class="fw-bold mb-3">Recommandations reçues</h5>
<?php if(empty($recommendations)): ?>
<div class="text-muted">Aucune recommandation pour le moment.</div>
<?php else: ?>
<?php foreach($recommendations as $r): ?>
<div class="border rounded-3 p-3 mb-2 bg-light d-flex align-items-center">
<img src="assets/images/<?= e($r['photo'] ?: 'default.png') ?>" width="40" height="40" class="rounded-circle me-3" style="object-fit:cover;">
<div class="flex-grow-1">
<div class="fw-bold"><?= e($r['nom']) ?></div>
<div class="text-muted small"><?= e($r['message']) ?></div>
</div>
<?php if($r['author_id'] === $me): ?>
<form method="post" class="ms-auto">
<input type="hidden" name="action" value="recommendation_delete">
<input type="hidden" name="recommendation_id" value="<?= e($r['id']) ?>">
<button class="btn btn-sm btn-outline-danger" onclick="return confirm('Supprimer cette recommandation ?')">Supprimer</button>
</form>
<?php endif; ?>
</div>
<?php endforeach; ?>
<?php endif; ?>
</div>

</div>
</div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>