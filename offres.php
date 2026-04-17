<?php
session_start();
require __DIR__ . '/config/database.php';

function e($v){ return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8'); }
function isLogged(): bool { return !empty($_SESSION['user_id']); }

$logged = isLogged();
$me = $logged ? (int)$_SESSION['user_id'] : 0;

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$id     = isset($_POST['id']) ? (int)$_POST['id'] : (isset($_GET['id']) ? (int)$_GET['id'] : 0);
$viewId = isset($_GET['view']) ? (int)$_GET['view'] : 0;

$flash = ['type'=>'', 'msg'=>''];

/* ===== Helpers ===== */
$types = ['CDI','CDD','Stage','Alternance','Freelance','Projet','Mentorat'];

function validUrlOrEmpty(string $url): bool {
  if ($url === '') return true;
  return filter_var($url, FILTER_VALIDATE_URL) !== false;
}
function validEmailOrEmpty(string $email): bool {
  if ($email === '') return true;
  return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}
function validDateLocalOrEmpty(string $dt): bool {
  if ($dt === '') return true;
  return (bool)preg_match('/^\d{4}-\d{2}-\d{2}$/', $dt);
}
function dateLocalToSqlEnd(string $d): string {
  // YYYY-MM-DD -> YYYY-MM-DD 23:59:59
  return $d . ' 23:59:59';
}

/* ===== Flash via GET ===== */
if (isset($_GET['ok'])) {
  $map = [
    'created' => "Offre publiée.",
    'updated' => "Offre modifiée.",
    'deleted' => "Offre supprimée."
  ];
  $k = (string)$_GET['ok'];
  if (isset($map[$k])) $flash = ['type'=>'success', 'msg'=>$map[$k]];
}

/* ===== Voir une offre (public) ===== */
$view = null;
if ($viewId > 0) {
  $stmt = $pdo->prepare("
    SELECT o.*, u.nom AS auteur_nom
    FROM offres o
    JOIN users u ON u.id = o.created_by
    WHERE o.id = ?
  ");
  $stmt->execute([$viewId]);
  $view = $stmt->fetch(PDO::FETCH_ASSOC);

  if (!$view) {
    $flash = ['type'=>'danger', 'msg'=>"Offre introuvable."];
  }
}

/* ===== Mode édition (connecté + propriétaire) ===== */
$edit = null;
if ($logged && $action === 'edit' && $id > 0) {
  $stmt = $pdo->prepare("SELECT * FROM offres WHERE id=?");
  $stmt->execute([$id]);
  $edit = $stmt->fetch(PDO::FETCH_ASSOC);

  if (!$edit) {
    $flash = ['type'=>'danger', 'msg'=>"Offre introuvable."];
    $edit = null;
  } elseif ((int)$edit['created_by'] !== $me) {
    $flash = ['type'=>'danger', 'msg'=>"Tu ne peux pas modifier cette offre."];
    $edit = null;
  }
}

/* ===== Actions (POST) : connecté uniquement ===== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

  if (!$logged) {
    $flash = ['type'=>'danger', 'msg'=>"Tu dois être connecté pour publier/modifier/supprimer."];
  } else {

    if ($action === 'create') {
      $titre = trim($_POST['titre'] ?? '');
      $description = trim($_POST['description'] ?? '');
      $type = trim($_POST['type'] ?? 'CDI');
      $ville = trim($_POST['ville'] ?? '');
      $entreprise = trim($_POST['entreprise'] ?? '');
      $lien = trim($_POST['lien'] ?? '');
      $email = trim($_POST['email_contact'] ?? '');
      $expires = trim($_POST['expires_at'] ?? ''); // YYYY-MM-DD

      $errors = [];
      if ($titre === '' || mb_strlen($titre) < 4) $errors[] = "Titre trop court (min 4 caractères).";
      if (mb_strlen($titre) > 160) $errors[] = "Titre trop long (max 160).";
      if ($description === '' || mb_strlen($description) < 10) $errors[] = "Description trop courte (min 10).";
      if (!in_array($type, $types, true)) $errors[] = "Type invalide.";
      if (!validUrlOrEmpty($lien)) $errors[] = "Lien invalide.";
      if (!validEmailOrEmpty($email)) $errors[] = "Email invalide.";
      if (!validDateLocalOrEmpty($expires)) $errors[] = "Date limite invalide.";

      if ($errors) {
        $flash = ['type'=>'danger', 'msg'=>implode(" ", $errors)];
      } else {
        $expiresSql = ($expires !== '') ? dateLocalToSqlEnd($expires) : null;

        $stmt = $pdo->prepare("
          INSERT INTO offres (titre, description, type, ville, entreprise, lien, email_contact, created_by, expires_at)
          VALUES (:t, :d, :ty, :v, :e, :l, :m, :by, :ex)
        ");
        $stmt->execute([
          ':t'=>$titre,
          ':d'=>$description,
          ':ty'=>$type,
          ':v'=>($ville!==''?$ville:null),
          ':e'=>($entreprise!==''?$entreprise:null),
          ':l'=>($lien!==''?$lien:null),
          ':m'=>($email!==''?$email:null),
          ':by'=>$me,
          ':ex'=>$expiresSql
        ]);

        header("Location: offres.php?ok=created");
        exit;
      }
    }

    if ($action === 'update' && $id > 0) {
      // Vérif propriétaire
      $check = $pdo->prepare("SELECT created_by FROM offres WHERE id=?");
      $check->execute([$id]);
      $owner = $check->fetchColumn();

      if (!$owner) {
        $flash = ['type'=>'danger', 'msg'=>"Offre introuvable."];
      } elseif ((int)$owner !== $me) {
        $flash = ['type'=>'danger', 'msg'=>"Tu ne peux pas modifier cette offre."];
      } else {
        $titre = trim($_POST['titre'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $type = trim($_POST['type'] ?? 'CDI');
        $ville = trim($_POST['ville'] ?? '');
        $entreprise = trim($_POST['entreprise'] ?? '');
        $lien = trim($_POST['lien'] ?? '');
        $email = trim($_POST['email_contact'] ?? '');
        $expires = trim($_POST['expires_at'] ?? '');

        $errors = [];
        if ($titre === '' || mb_strlen($titre) < 4) $errors[] = "Titre trop court (min 4 caractères).";
        if (mb_strlen($titre) > 160) $errors[] = "Titre trop long (max 160).";
        if ($description === '' || mb_strlen($description) < 10) $errors[] = "Description trop courte (min 10).";
        if (!in_array($type, $types, true)) $errors[] = "Type invalide.";
        if (!validUrlOrEmpty($lien)) $errors[] = "Lien invalide.";
        if (!validEmailOrEmpty($email)) $errors[] = "Email invalide.";
        if (!validDateLocalOrEmpty($expires)) $errors[] = "Date limite invalide.";

        if ($errors) {
          $flash = ['type'=>'danger', 'msg'=>implode(" ", $errors)];
        } else {
          $expiresSql = ($expires !== '') ? dateLocalToSqlEnd($expires) : null;

          $stmt = $pdo->prepare("
            UPDATE offres 
            SET titre=:t, description=:d, type=:ty, ville=:v, entreprise=:e, lien=:l, email_contact=:m, expires_at=:ex
            WHERE id=:id
          ");
          $stmt->execute([
            ':t'=>$titre,
            ':d'=>$description,
            ':ty'=>$type,
            ':v'=>($ville!==''?$ville:null),
            ':e'=>($entreprise!==''?$entreprise:null),
            ':l'=>($lien!==''?$lien:null),
            ':m'=>($email!==''?$email:null),
            ':ex'=>$expiresSql,
            ':id'=>$id
          ]);

          header("Location: offres.php?ok=updated");
          exit;
        }
      }
    }

    if ($action === 'delete' && $id > 0) {
      // Vérif propriétaire
      $check = $pdo->prepare("SELECT created_by FROM offres WHERE id=?");
      $check->execute([$id]);
      $owner = $check->fetchColumn();

      if (!$owner) {
        $flash = ['type'=>'danger', 'msg'=>"Offre introuvable."];
      } elseif ((int)$owner !== $me) {
        $flash = ['type'=>'danger', 'msg'=>"Tu ne peux pas supprimer cette offre."];
      } else {
        $del = $pdo->prepare("DELETE FROM offres WHERE id=?");
        $del->execute([$id]);
        header("Location: offres.php?ok=deleted");
        exit;
      }
    }
  }
}

/* ===== Filtres liste ===== */
$q = trim($_GET['q'] ?? '');
$typeFilter = trim($_GET['type'] ?? '');
$villeFilter = trim($_GET['ville'] ?? '');

$where = [];
$params = [];

$where[] = "(o.expires_at IS NULL OR o.expires_at >= NOW())";

if ($q !== '') {
  $where[] = "(o.titre LIKE :q OR o.description LIKE :q OR o.entreprise LIKE :q)";
  $params[':q'] = "%$q%";
}
if ($typeFilter !== '' && in_array($typeFilter, $types, true)) {
  $where[] = "o.type = :ty";
  $params[':ty'] = $typeFilter;
}
if ($villeFilter !== '') {
  $where[] = "o.ville LIKE :v";
  $params[':v'] = "%$villeFilter%";
}

$whereSql = $where ? ("WHERE " . implode(" AND ", $where)) : "";

$listSql = "
  SELECT o.*, u.nom AS auteur_nom
  FROM offres o
  JOIN users u ON u.id = o.created_by
  $whereSql
  ORDER BY o.created_at DESC
  LIMIT 60
";
$listStmt = $pdo->prepare($listSql);
$listStmt->execute($params);
$offres = $listStmt->fetchAll(PDO::FETCH_ASSOC);

/* ===== Valeurs form ===== */
$titreV = e($_POST['titre'] ?? $edit['titre'] ?? '');
$descV  = e($_POST['description'] ?? $edit['description'] ?? '');
$typeV  = e($_POST['type'] ?? $edit['type'] ?? 'CDI');
$villeV = e($_POST['ville'] ?? $edit['ville'] ?? '');
$entrV  = e($_POST['entreprise'] ?? $edit['entreprise'] ?? '');
$lienV  = e($_POST['lien'] ?? $edit['lien'] ?? '');
$emailV = e($_POST['email_contact'] ?? $edit['email_contact'] ?? '');
$expV   = e($_POST['expires_at'] ?? (!empty($edit['expires_at']) ? substr((string)$edit['expires_at'],0,10) : ''));
?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Offres — IESIG Alumni</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<?php include __DIR__ . '/includes/header.php'; ?>

<div class="container py-4">

  <div class="d-flex flex-column flex-lg-row justify-content-between align-items-start gap-2 mb-3">
    <div>
      <h1 class="h3 mb-1">Offres</h1>
      <div class="text-muted">Emploi • Stage • Alternance • Freelance • Projet • Mentorat</div>
    </div>
    <div class="d-flex gap-2">
      <a class="btn btn-outline-secondary" href="index.php">Accueil</a>
      <?php if (!$logged): ?>
        <a class="btn btn-primary" href="login.php">Se connecter</a>
      <?php endif; ?>
    </div>
  </div>

  <?php if (!empty($flash['msg'])): ?>
    <div class="alert alert-<?= e($flash['type']) ?>"><?= e($flash['msg']) ?></div>
  <?php endif; ?>

  <?php if ($view): ?>
    <div class="card shadow-sm mb-4">
      <div class="card-body p-4">
        <div class="d-flex justify-content-between align-items-start gap-3">
          <div>
            <div class="text-muted small mb-1">
              <?= e($view['type']) ?>
              <?php if (!empty($view['ville'])): ?> • <?= e($view['ville']) ?><?php endif; ?>
              <?php if (!empty($view['entreprise'])): ?> • <?= e($view['entreprise']) ?><?php endif; ?>
            </div>
            <h2 class="h4 mb-1"><?= e($view['titre']) ?></h2>
            <div class="text-muted small">
              Publié le <?= e(date('d/m/Y H:i', strtotime($view['created_at']))) ?>
              • par <?= e($view['auteur_nom'] ?? '') ?>
              <?php if (!empty($view['expires_at'])): ?>
                • Date limite : <?= e(date('d/m/Y', strtotime($view['expires_at']))) ?>
              <?php endif; ?>
            </div>
          </div>
          <a class="btn btn-outline-secondary" href="offres.php">Retour</a>
        </div>

        <hr>
        <div style="white-space:pre-wrap"><?= e($view['description']) ?></div>

        <hr>
        <div class="d-flex flex-wrap gap-2">
          <?php if (!empty($view['lien'])): ?>
            <a class="btn btn-primary" target="_blank" rel="noopener" href="<?= e($view['lien']) ?>">Postuler (lien)</a>
          <?php endif; ?>
          <?php if (!empty($view['email_contact'])): ?>
            <a class="btn btn-outline-primary" href="mailto:<?= e($view['email_contact']) ?>">Contacter par email</a>
          <?php endif; ?>

          <?php if ($logged && (int)$view['created_by'] === $me): ?>
            <a class="btn btn-outline-secondary ms-auto" href="offres.php?action=edit&id=<?= (int)$view['id'] ?>">Modifier</a>
            <form method="post" onsubmit="return confirm('Supprimer cette offre ?');">
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="id" value="<?= (int)$view['id'] ?>">
              <button class="btn btn-outline-danger" type="submit">Supprimer</button>
            </form>
          <?php endif; ?>
        </div>
      </div>
    </div>
  <?php endif; ?>

  <div class="row g-4">

    <!-- Form publish (connecté seulement) -->
    <div class="col-lg-4">
      <div class="card shadow-sm">
        <div class="card-body p-4">
          <h2 class="h5 mb-3"><?= $edit ? "Modifier l’offre" : "Publier une offre" ?></h2>

          <?php if (!$logged): ?>
            <div class="alert alert-warning mb-0">
              Tu peux consulter les offres.
              <a href="login.php" class="alert-link">Connecte-toi</a> pour publier.
            </div>
          <?php else: ?>
            <form method="post" class="row g-3">
              <input type="hidden" name="action" value="<?= $edit ? 'update' : 'create' ?>">
              <?php if ($edit): ?>
                <input type="hidden" name="id" value="<?= (int)$edit['id'] ?>">
              <?php endif; ?>

              <div class="col-12">
                <label class="form-label">Titre *</label>
                <input class="form-control" name="titre" required maxlength="160" value="<?= $titreV ?>">
              </div>

              <div class="col-12">
                <label class="form-label">Type *</label>
                <select class="form-select" name="type" required>
                  <?php foreach ($types as $t): ?>
                    <option value="<?= e($t) ?>" <?= ($typeV === $t ? 'selected' : '') ?>><?= e($t) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>

              <div class="col-12">
                <label class="form-label">Entreprise</label>
                <input class="form-control" name="entreprise" value="<?= $entrV ?>">
              </div>

              <div class="col-12">
                <label class="form-label">Ville</label>
                <input class="form-control" name="ville" value="<?= $villeV ?>" placeholder="ex: Paris / Nice / Remote">
              </div>

              <div class="col-12">
                <label class="form-label">Description *</label>
                <textarea class="form-control" name="description" rows="6" required><?= $descV ?></textarea>
              </div>

              <div class="col-12">
                <label class="form-label">Lien de candidature</label>
                <input class="form-control" name="lien" value="<?= $lienV ?>" placeholder="https://...">
              </div>

              <div class="col-12">
                <label class="form-label">Email contact</label>
                <input class="form-control" name="email_contact" value="<?= $emailV ?>" placeholder="recruteur@...">
              </div>

              <div class="col-12">
                <label class="form-label">Date limite (optionnel)</label>
                <input type="date" class="form-control" name="expires_at" value="<?= $expV ?>">
              </div>

              <div class="col-12 d-flex gap-2 flex-wrap">
                <button class="btn btn-primary" type="submit"><?= $edit ? "Enregistrer" : "Publier" ?></button>
                <?php if ($edit): ?>
                  <a class="btn btn-outline-secondary" href="offres.php">Annuler</a>
                <?php endif; ?>
              </div>
            </form>
          <?php endif; ?>

        </div>
      </div>
    </div>

    <!-- List -->
    <div class="col-lg-8">

      <div class="card shadow-sm mb-3">
        <div class="card-body p-3">
          <form class="row g-2" method="get">
            <div class="col-md-5">
              <input class="form-control" name="q" value="<?= e($q) ?>" placeholder="Recherche (titre, entreprise...)">
            </div>
            <div class="col-md-3">
              <select class="form-select" name="type">
                <option value="">Tous types</option>
                <?php foreach ($types as $t): ?>
                  <option value="<?= e($t) ?>" <?= ($typeFilter === $t ? 'selected' : '') ?>><?= e($t) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-3">
              <input class="form-control" name="ville" value="<?= e($villeFilter) ?>" placeholder="Ville (optionnel)">
            </div>
            <div class="col-md-1 d-grid">
              <button class="btn btn-outline-primary" type="submit">OK</button>
            </div>
          </form>
        </div>
      </div>

      <?php if (empty($offres)): ?>
        <div class="card shadow-sm">
          <div class="card-body p-4 text-muted">Aucune offre trouvée.</div>
        </div>
      <?php else: ?>
        <div class="row g-3">
          <?php foreach ($offres as $o): ?>
            <div class="col-md-6">
              <div class="card shadow-sm h-100">
                <div class="card-body">
                  <div class="d-flex justify-content-between align-items-start gap-2">
                    <div class="small text-muted">
                      <?= e($o['type']) ?>
                      <?php if (!empty($o['ville'])): ?> • <?= e($o['ville']) ?><?php endif; ?>
                      <?php if (!empty($o['entreprise'])): ?> • <?= e($o['entreprise']) ?><?php endif; ?>
                    </div>
                    <span class="badge text-bg-light border">
                      <?= e(date('d/m', strtotime($o['created_at']))) ?>
                    </span>
                  </div>

                  <div class="fw-semibold mt-2"><?= e($o['titre']) ?></div>
                  <div class="text-muted small mt-2" style="white-space:pre-wrap; overflow:hidden; display:-webkit-box; -webkit-line-clamp:3; -webkit-box-orient:vertical;">
                    <?= e($o['description']) ?>
                  </div>

                  <div class="d-flex align-items-center gap-2 mt-3">
                    <a class="btn btn-sm btn-outline-primary" href="offres.php?view=<?= (int)$o['id'] ?>">Voir</a>

                    <?php if ($logged && (int)$o['created_by'] === $me): ?>
                      <a class="btn btn-sm btn-outline-secondary" href="offres.php?action=edit&id=<?= (int)$o['id'] ?>">Modifier</a>
                      <form method="post" onsubmit="return confirm('Supprimer cette offre ?');" class="ms-auto">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= (int)$o['id'] ?>">
                        <button class="btn btn-sm btn-outline-danger" type="submit">Supprimer</button>
                      </form>
                    <?php endif; ?>
                  </div>

                  <div class="text-muted small mt-3">
                    Par <?= e($o['auteur_nom'] ?? '') ?>
                    <?php if (!empty($o['expires_at'])): ?>
                      • Limite: <?= e(date('d/m/Y', strtotime($o['expires_at']))) ?>
                    <?php endif; ?>
                  </div>

                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

    </div>
  </div>

</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>