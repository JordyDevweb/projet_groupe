<?php
session_start();
require 'config/database.php';

if (empty($_SESSION['user_id'])) {
  header("Location: login.php");
  exit;
}

function e($v) {
  return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8');
}

$userId = (int)$_SESSION['user_id'];

// Charger user
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
  session_destroy();
  header("Location: login.php");
  exit;
}

$errors = [];

$oldPhoto = trim((string)($user['photo'] ?? ''));
$currentPhotoWeb = ($oldPhoto !== '') ? ('assets/images/' . $oldPhoto) : 'assets/images/default.png';
if (!is_file(__DIR__ . '/' . $currentPhotoWeb)) {
  $currentPhotoWeb = 'assets/images/default.png';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $nom = trim((string)($_POST['nom'] ?? ''));
  $promotion = trim((string)($_POST['promotion'] ?? ''));
  $filiere = trim((string)($_POST['filiere'] ?? ''));
  $metier = trim((string)($_POST['metier'] ?? ''));
  $bio = trim((string)($_POST['bio'] ?? ''));

  if ($nom === '' || mb_strlen($nom) < 2) $errors[] = "Le nom doit contenir au moins 2 caractères.";
  if (mb_strlen($nom) > 80) $errors[] = "Le nom est trop long (max 80).";
  if (mb_strlen($bio) > 300) $errors[] = "La bio est trop longue (max 300 caractères).";

  // Photo par défaut = ancienne photo
  $newPhotoName = $oldPhoto;

  // ✅ Upload image si fourni
  if (!empty($_FILES['photo']['name'])) {
    $allowed = ['jpg','jpeg','png','webp'];

    $fileName = (string)$_FILES['photo']['name'];
    $fileTmp  = (string)$_FILES['photo']['tmp_name'];
    $fileSize = (int)$_FILES['photo']['size'];

    $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

    if (!in_array($ext, $allowed, true)) {
      $errors[] = "Format non autorisé (jpg, jpeg, png, webp).";
    } elseif ($fileSize > 2 * 1024 * 1024) {
      $errors[] = "Image trop lourde (max 2MB).";
    } else {
      $imgInfo = @getimagesize($fileTmp);
      if ($imgInfo === false) {
        $errors[] = "Le fichier envoyé n'est pas une image valide.";
      } else {
        // ✅ dossier réel sur disque
        $uploadDir = __DIR__ . '/assets/images/';
        if (!is_dir($uploadDir)) {
          mkdir($uploadDir, 0777, true);
        }

        $newPhotoName = uniqid('user_', true) . '.' . $ext;
        $uploadPath = $uploadDir . $newPhotoName;

        if (!move_uploaded_file($fileTmp, $uploadPath)) {
          $errors[] = "Erreur lors de l’upload de l’image.";
        }
      }
    }
  }

  if (!$errors) {
    // Update user
    $upd = $pdo->prepare("
      UPDATE users 
      SET nom = ?, promotion = ?, filiere = ?, metier = ?, bio = ?, photo = ?
      WHERE id = ?
    ");
    $upd->execute([
      $nom,
      $promotion !== '' ? $promotion : null,
      $filiere !== '' ? $filiere : null,
      $metier !== '' ? $metier : null,
      $bio !== '' ? $bio : null,
      $newPhotoName !== '' ? $newPhotoName : null,
      $userId
    ]);

    // ✅ Supprimer l’ancienne photo si nouvelle photo envoyée
    if (!empty($_FILES['photo']['name']) && $oldPhoto !== '' && $oldPhoto !== 'default.png') {
      $oldPath = __DIR_ . '/assets/images/' . $oldPhoto;
      if (is_file($oldPath)) {
        @unlink($oldPath);
      }
    }

    header("Location: profile.php");
    exit;
  }

  // Recharger l'aperçu de la photo (si erreur)
  if ($newPhotoName !== '' && is_file(_DIR_ . '/assets/images/' . $newPhotoName)) {
    $currentPhotoWeb = 'assets/images/' . $newPhotoName;
  }
}

$nomV = e($_POST['nom'] ?? $user['nom'] ?? '');
$promotionV = e($_POST['promotion'] ?? $user['promotion'] ?? '');
$filiereV = e($_POST['filiere'] ?? $user['filiere'] ?? '');
$metierV = e($_POST['metier'] ?? $user['metier'] ?? '');
$bioV = e($_POST['bio'] ?? $user['bio'] ?? '');
?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Modifier le Profil - IESIG Alumni</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="css/edit_profile.css">
</head>
<body class="bg-soft">

<?php include 'includes/header.php'; ?>

<main class="container my-4 my-lg-5">
  <div class="row g-4 align-items-start">
    <div class="col-lg-4">
      <div class="card card-elevated">
        <div class="card-body p-4">
          <h5 class="mb-1">Photo de profil</h5>
          <div class="text-muted small mb-3">JPG / PNG / WEBP · max 2MB</div>

          <div class="text-center">
            <img src="<?= e($currentPhotoWeb) ?>" class="edit-avatar" alt="Photo actuelle">
          </div>

          <div class="alert alert-info small mt-3 mb-0">
            Astuce : une photo carrée rend mieux.
          </div>
        </div>
      </div>

      <div class="d-grid gap-2 mt-4">
        <a href="profile.php" class="btn btn-outline-secondary">Retour au profil</a>
      </div>
    </div>

    <div class="col-lg-8">
      <div class="card card-elevated">
        <div class="card-body p-4 p-lg-5">
          <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
            <div>
              <h4 class="mb-1">Modifier mon profil</h4>
              <div class="text-muted">Mets à jour tes informations</div>
            </div>
            <span class="badge text-bg-secondary rounded-pill">Édition</span>
          </div>

          <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
              <ul class="mb-0">
                <?php foreach ($errors as $err): ?>
                  <li><?= e($err) ?></li>
                <?php endforeach; ?>
              </ul>
            </div>
          <?php endif; ?>

          <form method="POST" enctype="multipart/form-data" class="row g-3">
            <div class="col-12">
              <label class="form-label">Nouvelle photo</label>
              <input type="file" name="photo" class="form-control" accept="image/*">
            </div>

            <div class="col-md-6">
              <label class="form-label">Nom complet</label>
              <input type="text" name="nom" class="form-control" value="<?= $nomV ?>" required maxlength="80">
            </div>

            <div class="col-md-3">
              <label class="form-label">Promotion</label>
              <input type="text" name="promotion" class="form-control" value="<?= $promotionV ?>" placeholder="ex: 2024">
            </div>

            <div class="col-md-3">
              <label class="form-label">Filière</label>
              <input type="text" name="filiere" class="form-control" value="<?= $filiereV ?>" placeholder="ex: Dev Web">
            </div>

            <div class="col-12">
              <label class="form-label">Métier</label>
              <input type="text" name="metier" class="form-control" value="<?= $metierV ?>" placeholder="ex: Développeur web">
            </div>

            <div class="col-12">
              <label class="form-label">Bio</label>
              <textarea name="bio" class="form-control" rows="5" maxlength="300"><?= $bioV ?></textarea>
              <div class="form-text">Max 300 caractères.</div>
            </div>

            <div class="col-12 d-flex gap-2 flex-wrap mt-2">
              <button type="submit" class="btn btn-primary btn-glow">Enregistrer</button>
              <a href="profile.php" class="btn btn-outline-secondary">Annuler</a>
            </div>
          </form>

        </div>
      </div>
    </div>
  </div>
</main>

<?php include 'includes/footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>