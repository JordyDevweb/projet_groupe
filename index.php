<?php
include __DIR__ . '/includes/header.php';
require __DIR__ . '/config/database.php';

function e($v){
    return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8');
}

/* =========================
   6 prochains événements
========================= */
$stmt = $pdo->prepare("
    SELECT id, titre, description, date_event, created_at
    FROM events
    WHERE date_event >= NOW()
    ORDER BY date_event ASC
    LIMIT 6
");
$stmt->execute();
$eventsHome = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* =========================
   Témoignages accueil
========================= */
$stmt = $pdo->prepare("
    SELECT t.message, t.created_at, u.nom, u.promotion, u.photo
    FROM testimonials t
    INNER JOIN users u ON u.id = t.user_id
    ORDER BY t.created_at DESC
    LIMIT 4
");
$stmt->execute();
$testimonialsHome = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* =========================
   Compteur alumni visibles
========================= */
$stmt = $pdo->prepare("
    SELECT COUNT(*) 
    FROM users
    WHERE directory_visible = 1
      AND directory_approved = 1
");
$stmt->execute();
$totalAlumni = (int)$stmt->fetchColumn();
?>

<header class="hero-pro">

   <img src="assets/images/iesig.jpeg" class="hero-img" alt="IESIG Alumni">

  <div class="container py-5 hero-content">
    <div class="row align-items-end g-4">
      <div class="col-lg-6 text-white">
<!--
        <span class="badge rounded-pill text-bg-light text-dark px-3 py-2 mb-3">
          Plateforme officielle Alumni
        </span>
-->
<br><br><br><br><br><br><br>
        <h1 class="display-5 fw-bold lh-1 mb-3">
          IESIG Alumni — Retrouve ton réseau, avance plus vite
        </h1>

        <p class="lead opacity-75 mb-4">
          Connecte-toi avec les anciens élèves : annuaire, profils, échanges, opportunités.
        </p>
<!-- 
        <div class="d-flex flex-wrap gap-3 mt-4">
          <div class="d-flex align-items-center gap-2">
            <span class="icon-bubble">🔒</span>
            <small class="opacity-75">Sécurité (hash + sessions)</small>
          </div>

          <div class="d-flex align-items-center gap-2">
            <span class="icon-bubble">⚡</span>
            <small class="opacity-75">Rapide & responsive</small>
          </div>

          <div class="d-flex align-items-center gap-2">
            <span class="icon-bubble">🤝</span>
            <small class="opacity-75">Réseau & opportunités</small>
          </div>
        </div>

        <div class="d-flex flex-wrap gap-3 mt-4">
          <a href="register.php" class="btn btn-primary rounded-pill px-4">Rejoindre maintenant</a>
          <a href="annuaire.php" class="btn btn-outline-light rounded-pill px-4">Explorer l’annuaire</a>
        </div>

      </div>

      <div class="col-lg-6">
        <div class="row g-3 mt-3 mt-lg-0">

          <div class="col-6">
            <div class="card border-0 shadow-sm rounded-4 h-100">
              <div class="card-body p-3">
                <div class="fw-bold">Annuaire</div>
                <div class="text-muted small">Recherche par promo / filière / métier</div>
              </div>
            </div>
          </div>

          <div class="col-6">
            <div class="card border-0 shadow-sm rounded-4 h-100">
              <div class="card-body p-3">
                <div class="fw-bold">Profils</div>
                <div class="text-muted small">Bio + métier + photo + recommandations</div>
              </div>
            </div>
          </div>

          <div class="col-12">
            <div class="card border-0 shadow-sm rounded-4 h-100">
              <div class="card-body p-3">
                <div class="fw-bold">Événements</div>
                <div class="text-muted small">Les prochains événements s’affichent directement sur l’accueil</div>
              </div>
            </div>
          </div>
          -->
        

        </div>
      </div>

    </div>
  </div>
</header>

<!-- ✅ SECTION ÉVÉNEMENTS SUR L'ACCUEIL -->
<section class="py-5 bg-white">
  <div class="container">
    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-start align-items-lg-end gap-3 mb-4">
      <div>
        <h2 class="fw-bold mb-1">Prochains événements</h2>
        <p class="text-muted mb-0">Dès qu’un événement est publié, il apparaît ici automatiquement.</p>
      </div>
      <a href="events.php" class="btn btn-outline-primary rounded-pill px-4">Voir tous les événements</a>
    </div>

    <?php if (empty($eventsHome)): ?>
      <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body p-4 text-muted">
          Aucun événement à venir pour le moment.
        </div>
      </div>
    <?php else: ?>
      <div class="row g-4">
        <?php foreach ($eventsHome as $ev): ?>
          <div class="col-md-6 col-lg-4">
            <div class="card h-100 border-0 shadow-sm rounded-4 event-card">
              <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-start gap-2">
                  <div class="small text-muted">
                    <?= e(date('d/m/Y H:i', strtotime($ev['date_event']))) ?>
                  </div>
                  <span class="badge rounded-pill text-bg-light border">À venir</span>
                </div>

                <h3 class="h6 fw-bold mt-2 mb-2"><?= e($ev['titre']) ?></h3>

                <p class="text-muted small mb-0 event-desc">
                  <?= e($ev['description'] ?: 'Aucune description.') ?>
                </p>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</section>
<!-- ✅ SECTION FONCTIONNALITÉS -->
<section class="py-5 bg-white">
  <div class="container">

    <div class="row align-items-end g-3 mb-4">
      <div class="col-lg-6">
        <h2 class="fw-bold mb-2">Tout pour une communauté Alumni solide</h2>
        <p class="text-muted mb-0">
          Un espace centralisé pour garder le lien après l’école et booster le networking.
        </p>
      </div>

      <div class="col-lg-6 text-lg-end">
        <a href="register.php" class="btn btn-dark rounded-pill px-4">Rejoindre maintenant</a>
      </div>
    </div>

    <!-- ✅ LIGNE DES CARTES -->
    <div class="row g-4">

      <!-- PROFIL -->
      <div class="col-md-6 col-lg-3">
        <a href="profile.php" class="text-decoration-none">
          <div class="card h-100 border-0 shadow-sm rounded-4 hover-lift">
            <div class="card-body p-4 text-center">
              <div class="feature-icon mb-2">🧑‍💼</div>
              <h3 class="h6 fw-bold mt-3 text-dark">Profils complets</h3>
              <p class="text-muted small mb-0">
                Bio, métier, photo et recommandations.
              </p>
            </div>
          </div>
        </a>
      </div>

      <!-- OPPORTUNITÉS -->
      <div class="col-md-6 col-lg-3">
        <a href="offres.php" class="text-decoration-none">
          <div class="card h-100 border-0 shadow-sm rounded-4 hover-lift">
            <div class="card-body p-4 text-center">
              <div class="feature-icon mb-2">💼</div>
              <h3 class="h6 fw-bold mt-3 text-dark">Opportunités</h3>
              <p class="text-muted small mb-0">
                Offres d’emploi, stages et projets.
              </p>
            </div>
          </div>
        </a>
      </div>

    </div>

  </div>
</section>
<!-- ✅ SECTION STATISTIQUES -->
<section class="py-5 bg-light">
  <div class="container">
    <div class="text-center mb-5">
      <h2 class="fw-bold">Nos statistiques Alumni</h2>
      <p class="text-muted">Découvrez en quelques chiffres l’impact, le dynamisme et l’évolution de notre réseau d’anciens. Notre communauté continue de grandir et de contribuer activement dans divers secteurs professionnels.<p>
    </div>

    <div class="row text-center g-4">
      <!-- Total Alumni -->
      <div class="col-md-6 col-lg-3">
        <div class="card shadow-sm border-0 rounded-4 h-100 p-4">
          <div class="display-4 text-primary fw-bold mb-2 counter" data-target="<?= e($totalAlumni) ?>">0</div>
          <div class="fw-semibold">Alumni inscrits</div>
          <p class="text-muted small mb-0">Profils validés dans l'annuaire</p>
        </div>
      </div>

      <!-- Événements -->
      <div class="col-md-6 col-lg-3">
        <div class="card shadow-sm border-0 rounded-4 h-100 p-4">
          <div class="display-4 text-success fw-bold mb-2 counter" data-target="<?= e(count($eventsHome)) ?>">0</div>
          <div class="fw-semibold">Événements à venir</div>
          <p class="text-muted small mb-0">Prochains événements publiés</p>
        </div>
      </div>

      <!-- Témoignages -->
      <div class="col-md-6 col-lg-3">
        <div class="card shadow-sm border-0 rounded-4 h-100 p-4">
          <div class="display-4 text-warning fw-bold mb-2 counter" data-target="<?= e(count($testimonialsHome)) ?>">0</div>
          <div class="fw-semibold">Témoignages</div>
          <p class="text-muted small mb-0">Partages des anciens élèves</p>
        </div>
      </div>

      <!-- Offres / Opportunités -->
      <div class="col-md-6 col-lg-3">
        <div class="card shadow-sm border-0 rounded-4 h-100 p-4">
          <div class="display-4 text-danger fw-bold mb-2 counter" data-target="25">0</div>
          <div class="fw-semibold">Opportunités</div>
          <p class="text-muted small mb-0">Offres d'emploi, stages et projets</p>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ==============================
JS pour les compteurs animés
============================== -->
<script>
  document.addEventListener("DOMContentLoaded", () => {
    const counters = document.querySelectorAll(".counter");
    counters.forEach(counter => {
      const updateCount = () => {
        const target = +counter.getAttribute('data-target');
        const count = +counter.innerText;
        const speed = 50; // plus petit = plus rapide
        const increment = Math.ceil(target / speed);

        if(count < target) {
          counter.innerText = count + increment;
          setTimeout(updateCount, 20);
        } else {
          counter.innerText = target;
        }
      };
      updateCount();
    });
  });
</script>

<style>
  .counter {
    font-size: 2.5rem;
  }
  .card p {
    font-size: 0.85rem;
  }
</style>

<!-- ✅ SECTION CHIFFRES + TÉMOIGNAGES + FAQ -->
<section class="py-5 bg-light">
  <div class="container">
    <div class="row g-4">
      
      <div class="col-lg-4">
        <div class="card border-0 shadow-sm rounded-4 h-100">
          <div class="card-body p-4">
            <h3 class="h5 fw-bold mb-3">Quelques chiffres</h3>

            <div class="d-flex gap-3 align-items-center mb-3">
              <div class="stat-pill">🎓</div>
              <div>
                <div class="fw-bold"><?= e($totalAlumni) ?> alumni visibles</div>
                <div class="text-muted small">Profils validés dans l’annuaire</div>
              </div>
            </div>

            <div class="d-flex gap-3 align-items-center mb-3">
              <div class="stat-pill">⚡</div>
              <div>
                <div class="fw-bold">Interface rapide</div>
                <div class="text-muted small">Tout va vite, rien à attendre grâce à une architecture optimisée.</div>
              </div>
            </div>

            <div class="d-flex gap-3 align-items-center mb-3">
              <div class="stat-pill">📱</div>
              <div>
                <div class="fw-bold">S’adapte à tous les écrans</div>
                <div class="text-muted small">Que ce soit sur téléphone, tablette ou ordinateur, tout reste parfait.</div>
              </div>
            </div>

            <div class="d-flex gap-3 align-items-center">
              <div class="stat-pill">🔒</div>
              <div>
                <div class="fw-bold">Connexion sécurisée</div>
                <div class="text-muted small">Vos accès sont protégés grâce à un système de sessions fiables et un stockage sécurisé des mots de passe.</div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="col-lg-8">
        <div class="card border-0 shadow-sm rounded-4">
          <div class="card-body p-4 p-md-5">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-3">
              <h3 class="h5 fw-bold mb-0">Témoignages Alumni</h3>
              <a href="testimonials.php" class="btn btn-sm btn-outline-primary rounded-pill">Voir tous les témoignages</a>
            </div>

            <div class="row g-3">
              <?php if (empty($testimonialsHome)): ?>
                <div class="col-12">
                  <div class="p-3 rounded-4 bg-white border text-muted">
                    Aucun témoignage pour le moment.
                  </div>
                </div>
              <?php else: ?>
                <?php foreach ($testimonialsHome as $t): ?>
                  <div class="col-md-6">
                    <div class="p-3 rounded-4 bg-white border h-100">
                      <div class="fw-bold mb-2">
                        “<?= e($t['message']) ?>”
                      </div>

                      <div class="d-flex align-items-center gap-2 mt-3">
                        <img
                          src="assets/images/<?= e(!empty($t['photo']) ? $t['photo'] : 'default.png') ?>"
                          alt="<?= e($t['nom']) ?>"
                          width="44"
                          height="44"
                          class="rounded-circle"
                          style="object-fit:cover;"
                        >

                        <div class="text-muted small">
                          <div class="fw-semibold text-dark"><?= e($t['nom']) ?></div>
                          <?php if (!empty($t['promotion'])): ?>
                            <div>Promotion <?= e($t['promotion']) ?></div>
                          <?php else: ?>
                            <div>Ancien élève</div>
                          <?php endif; ?>
                        </div>
                      </div>
                    </div>
                  </div>
                <?php endforeach; ?>
              <?php endif; ?>
            </div>
            

          </div>
        </div>
      </div>

    </div>
  </div>
</section>

<?php
include __DIR__ . '/includes/footer.php';
?>