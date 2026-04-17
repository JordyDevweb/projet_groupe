<?php
session_start();
require __DIR__ . '/config/database.php';

function e($v){
    return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8');
}

// Rediriger les visiteurs non connectés si tu veux
// if(empty($_SESSION['user_id'])){
//     header("Location: login.php");
//     exit;
// }

$user_id = $_SESSION['user_id'] ?? 0; // Id de l'utilisateur connecté
$flash = ['type'=>'','msg'=>''];

/* ===============================
   SUPPRESSION D'UN TEMOIGNAGE
================================ */
if(($_POST['action'] ?? '') === 'testimonial_delete'){
    $tid = (int)($_POST['testimonial_id'] ?? 0);
    if($user_id){
        $stmt = $pdo->prepare("DELETE FROM testimonials WHERE id=? AND user_id=?");
        $stmt->execute([$tid, $user_id]);
        $flash = ['type'=>'success','msg'=>'Témoignage supprimé avec succès.'];
    }
}

/* ===============================
   RÉCUPÉRER TOUS LES TÉMOIGNAGES
================================ */
$stmt = $pdo->prepare("
    SELECT t.id, t.message, t.created_at, t.user_id, u.nom, u.photo, u.promotion
    FROM testimonials t
    JOIN users u ON u.id = t.user_id
    ORDER BY t.created_at DESC
");
$stmt->execute();
$testimonials = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<?php include __DIR__ . '/includes/header.php'; ?>

<div class="container py-5">

    <h2 class="fw-bold mb-4">Tous les témoignages</h2>

    <?php if(!empty($flash['msg'])): ?>
        <div class="alert alert-<?= e($flash['type']) ?>">
            <?= e($flash['msg']) ?>
        </div>
    <?php endif; ?>

    <?php if(empty($testimonials)): ?>
        <div class="alert alert-info">
            Aucun témoignage pour le moment.
        </div>
    <?php else: ?>
        <div class="row g-4">
            <?php foreach($testimonials as $t): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="card border-0 shadow-sm rounded-4 h-100">
                        <div class="card-body p-4">

                            <div class="d-flex align-items-center gap-2 mb-3">
                                <img src="assets/images/<?= e($t['photo'] ?: 'default.png') ?>"
                                     width="50" height="50"
                                     class="rounded-circle"
                                     style="object-fit:cover">
                                <div>
                                    <div class="fw-bold"><?= e($t['nom']) ?></div>
                                    <div class="text-muted small"><?= e($t['promotion'] ?: 'Alumni') ?></div>
                                </div>
                            </div>

                            <p class="mb-3">"<?= e($t['message']) ?>"</p>

                            <?php if($user_id && $t['user_id'] == $user_id): ?>
                                <form method="post" onsubmit="return confirm('Voulez-vous vraiment supprimer ce témoignage ?');">
                                    <input type="hidden" name="action" value="testimonial_delete">
                                    <input type="hidden" name="testimonial_id" value="<?= $t['id'] ?>">
                                    <button class="btn btn-sm btn-outline-danger">Supprimer</button>
                                </form>
                            <?php endif; ?>

                            <div class="text-muted small mt-2">
                                Publié le <?= e(date('d/m/Y H:i', strtotime($t['created_at']))) ?>
                            </div>

                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

</div>

<?php include __DIR__ . '/includes/footer.php'; ?>

