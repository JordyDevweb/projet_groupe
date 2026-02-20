<?php
session_start();
require 'config/database.php';

if (empty($_SESSION['user_id'])) {
  header("Location: login.php");
  exit;
}

function e($v){ return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8'); }

$me = (int)$_SESSION['user_id'];
$q  = trim($_GET['q'] ?? '');

/* ✅ Recherche : on utilise nom + email + filiere + promotion si dispo */
$cols = $pdo->query("SHOW COLUMNS FROM users")->fetchAll(PDO::FETCH_COLUMN);

$displayField = in_array('nom',$cols,true) ? 'nom' : (in_array('email',$cols,true) ? 'email' : 'id');
$hasFiliere = in_array('filiere',$cols,true);
$hasPromo   = in_array('promotion',$cols,true);
$hasPhoto   = in_array('photo',$cols,true);

if ($q !== '') {
  $like = '%'.$q.'%';

  // on construit des champs de recherche existants
  $searchable = [];
  foreach (['nom','prenom','email','filiere','promotion','metier'] as $f) {
    if (in_array($f,$cols,true)) $searchable[] = "$f LIKE ?";
  }
  if (empty($searchable)) $searchable[] = "$displayField LIKE ?";

  $sql = "
    SELECT id,
           $displayField AS display_name,
           ".($hasFiliere ? "filiere" : "NULL")." AS filiere,
           ".($hasPromo ? "promotion" : "NULL")." AS promotion,
           ".($hasPhoto ? "photo" : "NULL")." AS photo
    FROM users
    WHERE id != ?
      AND (".implode(" OR ", $searchable).")
    ORDER BY display_name ASC
    LIMIT 50
  ";

  $params = [$me];
  foreach ($searchable as $_) $params[] = $like;

  $stmt = $pdo->prepare($sql);
  $stmt->execute($params);
} else {
  // ✅ sans recherche: profils récents
  $sql = "
    SELECT id,
           $displayField AS display_name,
           ".($hasFiliere ? "filiere" : "NULL")." AS filiere,
           ".($hasPromo ? "promotion" : "NULL")." AS promotion,
           ".($hasPhoto ? "photo" : "NULL")." AS photo
    FROM users
    WHERE id != ?
    ORDER BY id DESC
    LIMIT 24
  ";
  $stmt = $pdo->prepare($sql);
  $stmt->execute([$me]);
}

$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

function user_photo(array $u): string {
  $file = trim((string)($u['photo'] ?? ''));
  $web  = $file !== '' ? ('assets/images/'.$file) : 'assets/images/default.png';
  if (!is_file(_DIR_.'/'.$web)) $web = 'assets/images/default.png';
  return $web;
}
?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Nouveau message</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="/anciens_eleves/css/style.css">
</head>
<body class="bg-soft">

<?php include 'includes/header.php'; ?>

<div class="container my-4 my-lg-5">

  <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
    <div>
      <h3 class="mb-1">Nouveau message</h3>
      <div class="text-muted">Tape un nom / email / filière / promo.</div>
    </div>
    <a href="conversations.php" class="btn btn-outline-secondary rounded-pill">Retour</a>
  </div>

  <div class="card shadow-soft mb-4">
    <div class="card-body">
      <form method="get" class="d-flex gap-2">
        <input class="form-control" type="text" name="q" value="<?= e($q) ?>"
               placeholder="Ex: Dupont, Finance, 2006...">
        <button class="btn btn-primary">Rechercher</button>
      </form>
    </div>
  </div>

  <div class="row g-3">
    <?php if ($q !== '' && empty($users)): ?>
      <div class="col-12">
        <div class="alert alert-warning mb-0">Aucun résultat pour “<?= e($q) ?>”.</div>
      </div>
    <?php endif; ?>

    <?php foreach ($users as $u): ?>
      <div class="col-sm-6 col-lg-4">
        <div class="card hover-lift h-100">
          <div class="card-body d-flex gap-3">
            <img src="<?= e(user_photo($u)) ?>" class="rounded-circle"
                 style="width:54px;height:54px;object-fit:cover;" alt="photo">
            <div class="flex-grow-1">
              <div class="fw-bold"><?= e($u['display_name'] ?? '') ?></div>
              <div class="text-muted small">
                <?= e($u['filiere'] ?? '') ?>
                <?= !empty($u['promotion']) ? ' · Promo '.e($u['promotion']) : '' ?>
              </div>
              <div class="mt-2 d-flex flex-wrap gap-2">
                <a class="btn btn-sm btn-primary rounded-pill" href="messages.php?to=<?= (int)$u['id'] ?>">Contacter</a>
                <a class="btn btn-sm btn-outline-secondary rounded-pill" href="user_profile.php?id=<?= (int)$u['id'] ?>">Voir profil</a>
              </div>
            </div>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>