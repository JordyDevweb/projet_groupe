<?php
session_start();
require __DIR__ . '/config/database.php';

function e($v){ return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8'); }
function isLogged(): bool { return !empty($_SESSION['user_id']); }

$logged = isLogged();
$action = $_POST['action'] ?? $_GET['action'] ?? '';
$id     = isset($_POST['id']) ? (int)$_POST['id'] : (isset($_GET['id']) ? (int)$_GET['id'] : 0);

$flash = ['type' => '', 'msg' => ''];

/* ===== Helpers dates ===== */
function validDateTimeLocal(string $dt): bool {
    return (bool)preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/', $dt);
}
function dtLocalToSql(string $dtLocal): string {
    return str_replace('T', ' ', $dtLocal) . ':00';
}
function sqlToDtLocal(string $sqlDt): string {
    return substr(str_replace(' ', 'T', $sqlDt), 0, 16);
}

/* ===== Détail d'un event (public) ===== */
$view = null;
$viewId = isset($_GET['view']) ? (int)$_GET['view'] : 0;
if ($viewId > 0) {
    $stmt = $pdo->prepare("SELECT * FROM events WHERE id = ?");
    $stmt->execute([$viewId]);
    $view = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$view) {
        $flash = ['type' => 'danger', 'msg' => "Événement introuvable."];
    }
}

/* ===== Actions (seulement connecté) ===== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$logged) {
        $flash = ['type' => 'danger', 'msg' => "Tu dois être connecté pour faire cette action."];
    } else {

        if ($action === 'create') {
            $titre = trim($_POST['titre'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $date_local = trim($_POST['date_event'] ?? '');

            if ($titre === '' || !validDateTimeLocal($date_local)) {
                $flash = ['type' => 'danger', 'msg' => "Titre et date/heure sont obligatoires."];
            } else {
                $date_event = dtLocalToSql($date_local);

                $stmt = $pdo->prepare("INSERT INTO events (titre, description, date_event) VALUES (:t, :d, :de)");
                $stmt->execute([
                    ':t'  => $titre,
                    ':d'  => ($description === '' ? null : $description),
                    ':de' => $date_event
                ]);

                header("Location: events.php?ok=created");
                exit;
            }
        }

        if ($action === 'update' && $id > 0) {
            $titre = trim($_POST['titre'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $date_local = trim($_POST['date_event'] ?? '');

            if ($titre === '' || !validDateTimeLocal($date_local)) {
                $flash = ['type' => 'danger', 'msg' => "Titre et date/heure sont obligatoires."];
            } else {
                $check = $pdo->prepare("SELECT id FROM events WHERE id = ?");
                $check->execute([$id]);

                if (!$check->fetchColumn()) {
                    $flash = ['type' => 'danger', 'msg' => "Événement introuvable."];
                } else {
                    $date_event = dtLocalToSql($date_local);

                    $stmt = $pdo->prepare("UPDATE events 
                                           SET titre = :t, description = :d, date_event = :de 
                                           WHERE id = :id");
                    $stmt->execute([
                        ':t' => $titre,
                        ':d' => ($description === '' ? null : $description),
                        ':de' => $date_event,
                        ':id' => $id
                    ]);

                    header("Location: events.php?ok=updated");
                    exit;
                }
            }
        }

        if ($action === 'delete' && $id > 0) {
            $stmt = $pdo->prepare("DELETE FROM events WHERE id = ?");
            $stmt->execute([$id]);
            header("Location: events.php?ok=deleted");
            exit;
        }
    }
}

/* ===== Flash via GET ===== */
if (isset($_GET['ok'])) {
    $map = [
        'created' => "Événement ajouté.",
        'updated' => "Événement modifié.",
        'deleted' => "Événement supprimé."
    ];
    $k = (string)$_GET['ok'];
    if (isset($map[$k])) $flash = ['type' => 'success', 'msg' => $map[$k]];
}

/* ===== Mode édition (connecté) ===== */
$edit = null;
if ($logged && $action === 'edit' && $id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM events WHERE id = ?");
    $stmt->execute([$id]);
    $edit = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$edit) {
        $flash = ['type' => 'danger', 'msg' => "Événement introuvable."];
        $edit = null;
    }
}

/* ===== Listes ===== */
$upcomingStmt = $pdo->query("SELECT * FROM events WHERE date_event >= NOW() ORDER BY date_event ASC");
$upcoming = $upcomingStmt->fetchAll(PDO::FETCH_ASSOC);

$pastStmt = $pdo->query("SELECT * FROM events WHERE date_event < NOW() ORDER BY date_event DESC LIMIT 60");
$past = $pastStmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Événements</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body { background: #f6f7fb; }
    .hero {
      background: radial-gradient(1000px 400px at 20% -20%, rgba(13,110,253,.25), transparent 60%),
                  radial-gradient(900px 300px at 90% 0%, rgba(25,135,84,.22), transparent 55%),
                  #0b1220;
      color: #fff;
      border-radius: 18px;
      padding: 22px;
      box-shadow: 0 10px 30px rgba(0,0,0,.15);
    }
    .event-card {
      border: 0;
      border-radius: 16px;
      box-shadow: 0 10px 25px rgba(20,20,40,.06);
      transition: transform .15s ease, box-shadow .15s ease;
    }
    .event-card:hover {
      transform: translateY(-2px);
      box-shadow: 0 14px 28px rgba(20,20,40,.10);
    }
    .badge-soft {
      background: rgba(13,110,253,.10);
      color: #0d6efd;
      border: 1px solid rgba(13,110,253,.15);
    }
    .muted { color: rgba(255,255,255,.75); }
    .line-clamp-3{
      display: -webkit-box;
      -webkit-line-clamp: 3;
      -webkit-box-orient: vertical;
      overflow: hidden;
    }
  </style>
</head>
<body>

<div class="container py-4">

  <!-- HERO -->
  <div class="hero mb-4">
    <div class="d-flex flex-column flex-md-row justify-content-between gap-3">
      <div>
        <div class="d-flex align-items-center gap-2 mb-2">
          <span class="badge badge-soft px-3 py-2 rounded-pill">IESIG Alumni</span>
          <span class="muted small">Événements & rencontres</span>
        </div>
        <h1 class="h3 mb-1">Événements</h1>
        <div class="muted">Consulte les événements. Connecte-toi pour en publier.</div>
      </div>
      <div class="d-flex gap-2 align-items-start">
        <a class="btn btn-outline-light" href="index.php">Accueil</a>
        <?php if ($logged): ?>
          <a class="btn btn-light" href="profile.php">Mon profil</a>
          <a class="btn btn-danger" href="logout.php">Déconnexion</a>
        <?php else: ?>
          <a class="btn btn-light" href="login.php">Se connecter</a>
          <a class="btn btn-outline-light" href="register.php">Créer un compte</a>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <?php if (!empty($flash['msg'])): ?>
    <div class="alert alert-<?= e($flash['type']) ?>"><?= e($flash['msg']) ?></div>
  <?php endif; ?>

  <!-- DÉTAIL (PUBLIC) -->
  <?php if ($view): ?>
    <div class="card event-card mb-4">
      <div class="card-body p-4">
        <div class="d-flex justify-content-between align-items-start gap-3">
          <div>
            <h2 class="h4 mb-1"><?= e($view['titre']) ?></h2>
            <div class="text-muted small">
              <?= e(date('d/m/Y H:i', strtotime($view['date_event']))) ?>
              <?php if (!empty($view['created_at'])): ?>
                • publié le <?= e(date('d/m/Y H:i', strtotime($view['created_at']))) ?>
              <?php endif; ?>
            </div>
          </div>
          <a class="btn btn-outline-secondary" href="events.php">Retour</a>
        </div>

        <?php if (!empty($view['description'])): ?>
          <hr>
          <div class="fs-6"><?= nl2br(e($view['description'])) ?></div>
        <?php endif; ?>

        <?php if ($logged): ?>
          <hr>
          <div class="d-flex gap-2">
            <a class="btn btn-outline-primary" href="events.php?action=edit&id=<?= (int)$view['id'] ?>">Modifier</a>
            <form method="post" onsubmit="return confirm('Supprimer cet événement ?');">
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="id" value="<?= (int)$view['id'] ?>">
              <button class="btn btn-outline-danger" type="submit">Supprimer</button>
            </form>
          </div>
        <?php endif; ?>
      </div>
    </div>
  <?php endif; ?>

  <div class="row g-4">
    <!-- FORMULAIRE (CONNECTÉ UNIQUEMENT) -->
    <div class="col-lg-4">
      <div class="card event-card">
        <div class="card-body p-4">
          <h2 class="h5 mb-3">Publier</h2>

          <?php if (!$logged): ?>
            <div class="alert alert-warning mb-0">
              Tu peux consulter les événements.
              <a href="login.php" class="alert-link">Connecte-toi</a> pour publier.
            </div>
          <?php else: ?>
            <form method="post">
              <input type="hidden" name="action" value="<?= $edit ? 'update' : 'create' ?>">
              <?php if ($edit): ?>
                <input type="hidden" name="id" value="<?= (int)$edit['id'] ?>">
              <?php endif; ?>

              <div class="mb-3">
                <label class="form-label">Titre *</label>
                <input type="text" name="titre" class="form-control" required maxlength="150"
                       value="<?= e($edit['titre'] ?? '') ?>" placeholder="Ex: Rencontre Alumni à Paris">
              </div>

              <div class="mb-3">
                <label class="form-label">Date & heure *</label>
                <input type="datetime-local" name="date_event" class="form-control" required
                       value="<?= e($edit ? sqlToDtLocal($edit['date_event']) : '') ?>">
              </div>

              <div class="mb-3">
                <label class="form-label">Description</label>
                <textarea name="description" class="form-control" rows="5"
                          placeholder="Infos, programme, contact..."><?= e($edit['description'] ?? '') ?></textarea>
              </div>

              <div class="d-flex gap-2">
                <button class="btn btn-primary" type="submit">
                  <?= $edit ? "Enregistrer" : "Publier" ?>
                </button>
                <?php if ($edit): ?>
                  <a class="btn btn-outline-secondary" href="events.php">Annuler</a>
                <?php endif; ?>
              </div>

              <div class="form-text mt-3">
                Note : ta table n’a pas <code>created_by</code>, donc tous les connectés peuvent modifier/supprimer.
                Je peux te faire la version “propre” avec auteur.
              </div>
            </form>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- LISTES (PUBLIC) -->
    <div class="col-lg-8">
      <ul class="nav nav-pills mb-3" id="eventsTabs" role="tablist">
        <li class="nav-item" role="presentation">
          <button class="nav-link active" id="up-tab" data-bs-toggle="pill" data-bs-target="#up"
                  type="button" role="tab">À venir (<?= count($upcoming) ?>)</button>
        </li>
        <li class="nav-item" role="presentation">
          <button class="nav-link" id="past-tab" data-bs-toggle="pill" data-bs-target="#past"
                  type="button" role="tab">Passés (<?= count($past) ?>)</button>
        </li>
      </ul>

      <div class="tab-content">
        <!-- À venir -->
        <div class="tab-pane fade show active" id="up" role="tabpanel">
          <?php if (empty($upcoming)): ?>
            <div class="card event-card">
              <div class="card-body p-4 text-muted">Aucun événement prévu.</div>
            </div>
          <?php else: ?>
            <div class="row g-3">
              <?php foreach ($upcoming as $ev): ?>
                <div class="col-md-6">
                  <div class="card event-card h-100">
                    <div class="card-body p-4">
                      <div class="d-flex justify-content-between align-items-start gap-2">
                        <div>
                          <div class="text-muted small mb-1">
                            <?= e(date('d/m/Y H:i', strtotime($ev['date_event']))) ?>
                          </div>
                          <h3 class="h6 mb-2"><?= e($ev['titre']) ?></h3>
                        </div>
                        <span class="badge badge-soft rounded-pill">À venir</span>
                      </div>

                      <?php if (!empty($ev['description'])): ?>
                        <div class="text-muted small line-clamp-3"><?= e($ev['description']) ?></div>
                      <?php else: ?>
                        <div class="text-muted small">Aucune description.</div>
                      <?php endif; ?>

                      <div class="d-flex gap-2 mt-3">
                        <a class="btn btn-sm btn-outline-primary"
                           href="events.php?view=<?= (int)$ev['id'] ?>">Voir</a>

                        <?php if ($logged): ?>
                          <a class="btn btn-sm btn-outline-secondary"
                             href="events.php?action=edit&id=<?= (int)$ev['id'] ?>">Modifier</a>
                        <?php endif; ?>
                      </div>
                    </div>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>

        <!-- Passés -->
        <div class="tab-pane fade" id="past" role="tabpanel">
          <?php if (empty($past)): ?>
            <div class="card event-card">
              <div class="card-body p-4 text-muted">Aucun événement passé.</div>
            </div>
          <?php else: ?>
            <div class="row g-3">
              <?php foreach ($past as $ev): ?>
                <div class="col-md-6">
                  <div class="card event-card h-100">
                    <div class="card-body p-4">
                      <div class="text-muted small mb-1">
                        <?= e(date('d/m/Y H:i', strtotime($ev['date_event']))) ?>
                        <?php if (!empty($ev['created_at'])): ?>
                          • publié le <?= e(date('d/m/Y H:i', strtotime($ev['created_at']))) ?>
                        <?php endif; ?>
                      </div>
                      <h3 class="h6 mb-2"><?= e($ev['titre']) ?></h3>
                      <div class="d-flex gap-2">
                        <a class="btn btn-sm btn-outline-primary"
                           href="events.php?view=<?= (int)$ev['id'] ?>">Voir</a>
                        <?php if ($logged): ?>
                          <form method="post" class="ms-auto" onsubmit="return confirm('Supprimer cet événement ?');">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= (int)$ev['id'] ?>">
                            <button class="btn btn-sm btn-outline-danger" type="submit">Supprimer</button>
                          </form>
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
  </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>