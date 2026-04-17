<?php
session_start();
require __DIR__ . '/config/database.php';

if (empty($_SESSION['user_id'])) {
  header("Location: login.php");
  exit;
}

function e($v) {
  return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8');
}

$userId = (int)$_SESSION['user_id'];

/* ===== Charger utilisateur ===== */
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
  session_destroy();
  header("Location: login.php");
  exit;
}

$errors = [];

/* ===== Avatar / Photo ===== */
$oldPhoto = trim((string)($user['photo'] ?? ''));
$photoDisk = ($oldPhoto !== '') ? (__DIR__ . '/assets/images/' . $oldPhoto) : '';
$hasPhoto = ($oldPhoto !== '' && is_file($photoDisk));
$currentPhotoWeb = $hasPhoto ? 'assets/images/' . $oldPhoto : null;

$initial = mb_strtoupper(mb_substr(trim((string)($user['nom'] ?? 'U')), 0, 1));
if ($initial === '') $initial = 'U';

/* ===== Dossier upload ===== */
$uploadDir = __DIR__ . '/assets/images/';
if (!is_dir($uploadDir)) {
  @mkdir($uploadDir, 0777, true);
}

$action = $_POST['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

  /* ===== SUPPRESSION COMPTE ===== */
  if ($action === 'delete_account') {

    $confirm = (string)($_POST['confirm_delete'] ?? '');
    $passwordConfirm = (string)($_POST['password_confirm'] ?? '');

    if ($confirm !== 'SUPPRIMER') {
      $errors[] = "Tape exactement SUPPRIMER pour confirmer.";
    } else {
      if (!password_verify($passwordConfirm, (string)$user['password'])) {
        $errors[] = "Mot de passe incorrect.";
      }
    }

    if (!$errors) {

      // Supprimer photo
      if ($oldPhoto !== '') {
        $oldPath = _DIR_ . '/assets/images/' . $oldPhoto;
        if (is_file($oldPath)) {
          @unlink($oldPath);
        }
      }

      $del = $pdo->prepare("DELETE FROM users WHERE id = ?");
      $del->execute([$userId]);

      session_destroy();
      header("Location: register.php?deleted=1");
      exit;
    }
  }

  /* ===== UPDATE PROFIL ===== */
  if ($action === 'update_profile') {

    $nom = trim((string)($_POST['nom'] ?? ''));
    $promotion = trim((string)($_POST['promotion'] ?? ''));
    $filiere = trim((string)($_POST['filiere'] ?? ''));
    $metier = trim((string)($_POST['metier'] ?? ''));
    $bio = trim((string)($_POST['bio'] ?? ''));

    if ($nom === '' || mb_strlen($nom) < 2)
      $errors[] = "Le nom doit contenir au moins 2 caractères.";

    if (mb_strlen($bio) > 300)
      $errors[] = "La bio est trop longue (max 300 caractères).";

    $newPhotoName = $oldPhoto;

    if (!empty($_FILES['photo']['name'])) {

      $allowed = ['jpg','jpeg','png','webp'];
      $fileTmp  = $_FILES['photo']['tmp_name'];
      $fileName = $_FILES['photo']['name'];
      $fileSize = $_FILES['photo']['size'];

      $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

      if (!in_array($ext, $allowed, true)) {
        $errors[] = "Format autorisé : jpg, jpeg, png, webp.";
      } elseif ($fileSize > 2 * 1024 * 1024) {
        $errors[] = "Image trop lourde (max 2MB).";
      } elseif (!@getimagesize($fileTmp)) {
        $errors[] = "Fichier image invalide.";
      } else {

        $newPhotoName = uniqid('user_', true) . '.' . $ext;
        $uploadPath = $uploadDir . $newPhotoName;

        if (!move_uploaded_file($fileTmp, $uploadPath)) {
          $errors[] = "Erreur upload image.";
        }
      }
    }

    if (!$errors) {

      $upd = $pdo->prepare("
        UPDATE users 
        SET nom=?, promotion=?, filiere=?, metier=?, bio=?, photo=?
        WHERE id=?
      ");

      $upd->execute([
        $nom,
        $promotion ?: null,
        $filiere ?: null,
        $metier ?: null,
        $bio ?: null,
        $newPhotoName ?: null,
        $userId
      ]);

      // Supprimer ancienne photo si remplacée
      if (!empty($_FILES['photo']['name']) && $oldPhoto !== '' && $oldPhoto !== $newPhotoName) {
        $oldPath = __DIR__ . '/assets/images/' . $oldPhoto;
        if (is_file($oldPath)) {
          @unlink($oldPath);
        }
      }

      header("Location: profile.php?updated=1");
      exit;
    }
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
  <title>Modifier Profil</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<?php include __DIR__ . '/includes/header.php'; ?>

<div class="container my-5">

  <div class="row g-4">

    <!-- Avatar -->
    <div class="col-lg-4">
      <div class="card shadow-sm">
        <div class="card-body text-center">

          <?php if ($hasPhoto): ?>
            <img src="<?= e($currentPhotoWeb) ?>" class="rounded-circle mb-3" width="140" height="140" style="object-fit:cover;">
          <?php else: ?>
            <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center mx-auto mb-3"
                 style="width:140px;height:140px;font-size:48px;font-weight:bold;">
              <?= e($initial) ?>
            </div>
          <?php endif; ?>

          <p class="text-muted small">JPG / PNG / WEBP · max 2MB</p>

          <a href="profile.php" class="btn btn-outline-secondary w-100">Retour au profil</a>

        </div>
      </div>
    </div>

    <!-- Formulaire -->
    <div class="col-lg-8">
      <div class="card shadow-sm">
        <div class="card-body">

          <h4 class="mb-3">Modifier mon profil</h4>

          <?php if ($errors): ?>
            <div class="alert alert-danger">
              <ul class="mb-0">
                <?php foreach ($errors as $err): ?>
                  <li><?= e($err) ?></li>
                <?php endforeach; ?>
              </ul>
            </div>
          <?php endif; ?>

          <form method="POST" enctype="multipart/form-data" class="row g-3">
            <input type="hidden" name="action" value="update_profile">

            <div class="col-12">
              <label class="form-label">Nouvelle photo</label>
              <input type="file" name="photo" class="form-control" accept="image/*">
            </div>

            <div class="col-md-6">
              <label class="form-label">Nom</label>
              <input type="text" name="nom" class="form-control" value="<?= $nomV ?>" required>
            </div>

            <div class="col-md-3">
              <label class="form-label">Promotion</label>
              <input type="text" name="promotion" class="form-control" value="<?= $promotionV ?>">
            </div>

            <div class="col-md-3">
              <label class="form-label">Filière</label>
              <input type="text" name="filiere" class="form-control" value="<?= $filiereV ?>">
            </div>

            <div class="col-12">
              <label class="form-label">Métier</label>
              <input type="text" name="metier" class="form-control" value="<?= $metierV ?>">
            </div>

            <div class="col-12">
              <label class="form-label">Bio</label>
              <textarea name="bio" class="form-control" rows="4" maxlength="300"><?= $bioV ?></textarea>
            </div>

            <div class="col-12">
              <button type="submit" class="btn btn-primary">Enregistrer</button>
              <a href="profile.php" class="btn btn-outline-secondary">Annuler</a>
            </div>
          </form>

          <hr class="my-4">

          <!-- Suppression compte -->
          <div class="border border-danger p-4 rounded bg-light">
            <h5 class="text-danger">Supprimer mon compte</h5>
            <p class="text-muted small">Action irréversible.</p>

            <form method="POST" onsubmit="return confirm('Confirmer suppression ?');" class="row g-3">
              <input type="hidden" name="action" value="delete_account">

              <div class="col-md-6">
                <label class="form-label">Tape SUPPRIMER</label>
                <input type="text" name="confirm_delete" class="form-control" required>
              </div>

              <div class="col-md-6">
                <label class="form-label">Mot de passe</label>
                <input type="password" name="password_confirm" class="form-control" required>
              </div>

              <div class="col-12">
                <button type="submit" class="btn btn-danger">Supprimer définitivement</button>
              </div>
            </form>

          </div>

        </div>
      </div>
    </div>

  </div>

</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>