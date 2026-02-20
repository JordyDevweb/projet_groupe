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

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    header("Location: annuaire.php");
    exit;
}

// ✅ Récupère l'utilisateur demandé
$stmt = $pdo->prepare("SELECT id, nom, email, promotion, filiere, metier, bio, photo FROM users WHERE id = ? LIMIT 1");
$stmt->execute([$id]);
$u = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$u) {
    header("Location: annuaire.php");
    exit;
}

// ✅ Photo
$photoFile = trim((string)($u['photo'] ?? ''));
$photoWeb = ($photoFile !== '') ? ('assets/images/' . $photoFile) : 'assets/images/default.png';
if (!is_file(__DIR__ . '/' . $photoWeb)) $photoWeb = 'assets/images/default.png';

// ✅ Bio
$bioRaw = trim((string)($u['bio'] ?? ''));
$bio = $bioRaw !== '' ? nl2br(e($bioRaw)) : '<span class="text-muted">Aucune bio ajoutée.</span>';

// ✅ Retour : si l'utilisateur vient de l'annuaire, on renvoie là-bas
$back = 'annuaire.php';
if (!empty($_SERVER['HTTP_REFERER'])) {
    // petit contrôle : si referer contient "annuaire.php", on le garde
    if (strpos($_SERVER['HTTP_REFERER'], 'annuaire.php') !== false) {
        $back = 'annuaire.php';
    }
}
?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Profil - <?= e($u['nom']) ?> | IESIG Alumni</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="css/user_profile.css">
</head>
<body class="bg-soft">

<?php include 'includes/header.php'; ?>

<header class="public-hero">
  <div class="container py-4 py-lg-5">
    <div class="d-flex align-items-center gap-3 gap-lg-4 flex-wrap">
      <img src="<?= e($photoWeb) ?>" class="public-avatar" alt="Photo profil">

      <div class="flex-grow-1">
        <h1 class="public-title mb-1"><?= e($u['nom']) ?></h1>
        <div class="public-subtitle">
          <?php if (!empty($u['promotion'])): ?>
            <span class="badge rounded-pill text-bg-light badge-soft">Promotion <?= e($u['promotion']) ?></span>
          <?php endif; ?>
          <?php if (!empty($u['filiere'])): ?>
            <span class="badge rounded-pill text-bg-light badge-soft">Filière <?= e($u['filiere']) ?></span>
          <?php endif; ?>
          <?php if (!empty($u['metier'])): ?>
            <span class="badge rounded-pill text-bg-light badge-soft"><?= e($u['metier']) ?></span>
          <?php endif; ?>
        </div>
      </div>

      <div class="ms-lg-auto d-flex gap-2">
        <a href="<?= e($back) ?>" class="btn btn-outline-light">← Retour</a>
        <a href="messages.php?to=<?= (int)$u['id'] ?>" class="btn btn-primary btn-glow">Contacter</a>
      </div>
    </div>
  </div>
</header>

<main class="container my-4 my-lg-5">
  <div class="row g-4">
    <div class="col-lg-4">
      <div class="card card-elevated">
        <div class="card-body p-4">
          <h5 class="mb-3">Informations</h5>

          <div class="list-group list-group-flush">
            <div class="list-group-item d-flex justify-content-between align-items-center px-0">
              <span class="text-muted">Nom</span>
              <span class="fw-semibold ms-3 text-end"><?= e($u['nom']) ?></span>
            </div>
            <div class="list-group-item d-flex justify-content-between align-items-center px-0">
              <span class="text-muted">Promotion</span>
              <span class="fw-semibold"><?= !empty($u['promotion']) ? e($u['promotion']) : '—' ?></span>
            </div>
            <div class="list-group-item d-flex justify-content-between align-items-center px-0">
              <span class="text-muted">Filière</span>
              <span class="fw-semibold"><?= !empty($u['filiere']) ? e($u['filiere']) : '—' ?></span>
            </div>
            <div class="list-group-item d-flex justify-content-between align-items-center px-0">
              <span class="text-muted">Métier</span>
              <span class="fw-semibold"><?= !empty($u['metier']) ? e($u['metier']) : '—' ?></span>
            </div>
          </div>

          <div class="d-grid mt-4">
            <a href="messages.php?to=<?= (int)$u['id'] ?>" class="btn btn-primary">Envoyer un message</a>
          </div>
        </div>
      </div>
    </div>

    <div class="col-lg-8">
      <div class="card card-elevated">
        <div class="card-body p-4 p-lg-5">
          <h5 class="mb-2">À propos</h5>
          <div class="text-muted mb-3">Bio</div>

          <div class="public-bio"><?= $bio ?></div>

          <div class="d-flex gap-2 flex-wrap mt-4">
            <a href="<?= e($back) ?>" class="btn btn-outline-secondary">← Retour à l’annuaire</a>
            <a href="messages.php?to=<?= (int)$u['id'] ?>" class="btn btn-primary btn-glow">Contacter</a>
          </div>
        </div>
      </div>
    </div>

  </div>
</main>

<?php include 'includes/footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>