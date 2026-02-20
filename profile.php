<?php
session_start();
require 'config/database.php';

if (empty($_SESSION['user_id'])) {
  header("Location: login.php");
  exit;
}

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
  session_destroy();
  header("Location: login.php");
  exit;
}

function e($v) {
  return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8');
}

// ✅ Image : si en base, on l'affiche, sinon default.png
$photoFile = trim((string)($user['photo'] ?? ''));
$photoWebPath = ($photoFile !== '') ? ('assets/images/' . $photoFile) : 'assets/images/default.png';

// ✅ Evite d'afficher un fichier qui n'existe pas (sécurité + confort)
$photoDiskPath = __DIR__. '/' . $photoWebPath;
if (!is_file($photoDiskPath)) {
  $photoWebPath = 'assets/images/default.png';
}

$nom = e($user['nom'] ?? '');
$email = e($user['email'] ?? '');
$promotion = e($user['promotion'] ?? '');
$filiere = e($user['filiere'] ?? '');
$metier = e($user['metier'] ?? '');

$bioRaw = trim((string)($user['bio'] ?? ''));
$bio = $bioRaw !== '' ? nl2br(e($bioRaw)) : '<span class="text-muted">Aucune bio ajoutée.</span>';

// Initiales si pas de photo
$initials = 'IA';
if (!empty($user['nom'])) {
  $parts = preg_split('/\s+/', trim((string)$user['nom']));
  $first = $parts[0] ?? '';
  $last = $parts[count($parts) - 1] ?? '';
  $i1 = $first !== '' ? mb_strtoupper(mb_substr($first, 0, 1)) : '';
  $i2 = $last !== '' ? mb_strtoupper(mb_substr($last, 0, 1)) : '';
  $initials = ($i1 . $i2) ?: 'IA';
}
?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Mon Profil - IESIG Alumni</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="css/profile.css">
</head>
<body class="bg-soft">

<?php include 'includes/header.php'; ?>

<header class="profile-hero">
  <div class="container py-4 py-lg-5">
    <div class="d-flex align-items-center gap-3 gap-lg-4 flex-wrap">
      <div class="profile-avatar-wrap">
        <?php if ($photoFile !== '' && $photoWebPath !== 'assets/images/default.png'): ?>
          <img src="<?= e($photoWebPath) ?>" class="profile-avatar" alt="Photo profil">
        <?php else: ?>
          <div class="profile-avatar profile-avatar--initials"><?= e($initials) ?></div>
        <?php endif; ?>
      </div>

      <div class="flex-grow-1">
        <h1 class="profile-title mb-1"><?= $nom ?: 'Mon profil' ?></h1>
        <div class="profile-subtitle">
          <span class="me-2"><?= $email ?: '—' ?></span>
          <?php if ($promotion !== ''): ?>
            <span class="badge rounded-pill text-bg-light badge-soft">Promotion <?= $promotion ?></span>
          <?php endif; ?>
          <?php if ($filiere !== ''): ?>
            <span class="badge rounded-pill text-bg-light badge-soft">Filière <?= $filiere ?></span>
          <?php endif; ?>
        </div>
      </div>

      <div class="ms-lg-auto d-flex gap-2">
        <a href="edit_profile.php" class="btn btn-primary btn-glow">Modifier</a>
        <a href="messages.php" class="btn btn-outline-light">Messagerie</a>
      </div>
    </div>
  </div>
</header>

<main class="container my-4 my-lg-5">
  <div class="row g-4">
    <div class="col-lg-4">
      <div class="card card-elevated">
        <div class="card-body p-4">
          <div class="d-flex align-items-start justify-content-between mb-3">
            <div>
              <h5 class="mb-1">Aperçu</h5>
              <div class="text-muted small">Informations principales</div>
            </div>
            <span class="badge text-bg-secondary rounded-pill">Profil</span>
          </div>

          <div class="list-group list-group-flush profile-list">
            <div class="list-group-item d-flex justify-content-between align-items-center">
              <span class="text-muted">Email</span>
              <span class="fw-semibold text-end ms-3"><?= $email ?: '—' ?></span>
            </div>
            <div class="list-group-item d-flex justify-content-between align-items-center">
              <span class="text-muted">Promotion</span>
              <span class="fw-semibold"><?= $promotion ?: '—' ?></span>
            </div>
            <div class="list-group-item d-flex justify-content-between align-items-center">
              <span class="text-muted">Filière</span>
              <span class="fw-semibold"><?= $filiere ?: '—' ?></span>
            </div>
            <div class="list-group-item d-flex justify-content-between align-items-center">
              <span class="text-muted">Métier</span>
              <span class="fw-semibold"><?= $metier ?: '—' ?></span>
            </div>
          </div>

          <div class="d-grid gap-2 mt-4">
            <a href="edit_profile.php" class="btn btn-primary">Compléter / Modifier</a>
            <a href="messages.php" class="btn btn-outline-secondary">Messagerie</a>
          </div>
        </div>
      </div>
    </div>

    <div class="col-lg-8">
      <div class="card card-elevated">
        <div class="card-body p-4 p-lg-5">
          <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
            <div>
              <h5 class="mb-1">À propos</h5>
              <div class="text-muted small">Présente-toi en quelques lignes</div>
            </div>
            <a href="edit_profile.php" class="btn btn-sm btn-outline-primary">Modifier</a>
          </div>

          <div class="profile-bio"><?= $bio ?></div>

          <div class="d-flex gap-2 flex-wrap mt-4">
            <a href="edit_profile.php" class="btn btn-primary btn-glow">Modifier le profil</a>
            <a href="messages.php" class="btn btn-outline-secondary">Messagerie</a>
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