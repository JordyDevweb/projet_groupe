[19:13, 19/02/2026] JB: And
[19:13, 19/02/2026] JB: <?php
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

// ✅ Requête préparée (plus sécurisé que concaténer)
$stmt = $pdo->prepare("SELECT id, nom, promotion, filiere, metier, photo FROM users WHERE id != ? ORDER BY nom ASC");
$stmt->execute([$userId]);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ✅ helper photo
function photo_web_path(array $u): string {
    $file = trim((string)($u['photo'] ?? ''));
    $web = ($file !== '') ? ('assets/images/' . $file) : 'assets/images/default.png';
    if (!is_file(__DIR__ . '/' . $web)) return 'assets/images/default.png';
    return $web;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Annuaire</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/annuaire.css">
</head>
<body class="bg-soft">
<?php include 'includes/header.php'; ?>

<div class="container my-4 my-lg-5">

    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
        <div>
            <h2 class="mb-1">Annuaire des anciens élèves</h2>
            <div class="text-muted">Clique sur un profil pour voir ses informations</div>
        </div>
        <a href="profile.php" class="btn btn-outline-secondary">Mon profil</a>
    </div>

    <div class="row g-4">
        <?php foreach ($users as $u): ?>
            <div class="col-sm-6 col-lg-4">
                <div class="card card-elevated h-100">
                    <div class="annuaire-cover">
                        <img src="<?= e(photo_web_path($u)) ?>" class="annuaire-img" alt="Photo profil">
                    </div>
                    <div class="card-body p-4">
                        <h5 class="card-title mb-1"><?= e($u['nom']) ?></h5>
                        <div class="text-muted small mb-3">
                            <?= $u['filiere'] ? e($u['filiere']) : 'Filière —' ?>
                            <?php if (!empty($u['promotion'])): ?>
                                · Promotion <?= e($u['promotion']) ?>
                            <?php endif; ?>
                        </div>

                        <div class="mb-3">
                            <div class="small text-muted">Métier</div>
                            <div class="fw-semibold"><?= $u['metier'] ? e($u['metier']) : '—' ?></div>
                        </div>

                        <a href="user_profile.php?id=<?= (int)$u['id'] ?>" class="btn btn-primary w-100">
                            Voir profil
                        </a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>

        <?php if (empty($users)): ?>
            <div class="col-12">
                <div class="alert alert-info mb-0">
                    Aucun autre profil disponible pour le moment.
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</ht