<?php
include __DIR__ . '/includes/header.php';
?>

<header class="hero-pro">
  <div class="container py-5">
    <div class="row align-items-center g-4">
      <div class="col-lg-6 text-white">
        <span class="badge rounded-pill text-bg-light text-dark px-3 py-2 mb-3">
          Plateforme officielle Alumni
        </span>

        <h1 class="display-5 fw-bold lh-1 mb-3">
          IESIG Alumni — Retrouve ton réseau, avance plus vite
        </h1>

        <p class="lead opacity-75 mb-4">
          Connecte-toi avec les anciens élèves : annuaire, profils, échanges, opportunités.
          Une interface moderne, simple et sécurisée.
        </p>

        <div class="d-flex flex-wrap gap-2">
          <a href="register.php" class="btn btn-primary btn-lg px-4">
            Créer un compte
          </a>
          <a href="login.php" class="btn btn-outline-light btn-lg px-4">
            Connexion
          </a>
            <a href="logout.php" class="btn btn-outline-light btn-lg px-4">
            Deconnexion
          </a>
          <a href="annuaire.php" class="btn btn-light btn-lg px-4">
            Explorer l’annuaire
          </a>
        </div>

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
      </div>

      <div class="col-lg-6">
        <div class="card shadow-lg border-0 overflow-hidden rounded-4">
          <img src="assets/images/hero.jpg" class="w-100" style="max-height:420px;object-fit:cover" alt="Alumni">
        </div>

        <div class="row g-3 mt-3">
          <div class="col-6">
            <div class="card border-0 shadow-sm rounded-4">
              <div class="card-body p-3">
                <div class="fw-bold">Annuaire</div>
                <div class="text-muted small">Recherche par promo / filière</div>
              </div>
            </div>
          </div>
          <div class="col-6">
            <div class="card border-0 shadow-sm rounded-4">
              <div class="card-body p-3">
                <div class="fw-bold">Profils</div>
                <div class="text-muted small">Bio + métier + photo</div>
              </div>
            </div>
          </div>
        </div>

      </div>
    </div>
  </div>
</header>

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

    <div class="row g-4">
      <div class="col-md-6 col-lg-3">
        <div class="card h-100 border-0 shadow-sm rounded-4">
          <div class="card-body p-4">
            <div class="feature-icon">📇</div>
            <h3 class="h6 fw-bold mt-3">Annuaire intelligent</h3>
            <p class="text-muted small mb-0">Trouve rapidement un alumni par nom, promo, filière ou métier.</p>
          </div>
        </div>
      </div>

      <div class="col-md-6 col-lg-3">
        <div class="card h-100 border-0 shadow-sm rounded-4">
          <div class="card-body p-4">
            <div class="feature-icon">🧑‍💼</div>
            <h3 class="h6 fw-bold mt-3">Profils complets</h3>
            <p class="text-muted small mb-0">Présentation, photo, bio et infos pro pour mieux se connecter.</p>
          </div>
        </div>
      </div>

      <div class="col-md-6 col-lg-3">
        <div class="card h-100 border-0 shadow-sm rounded-4">
          <div class="card-body p-4">
            <div class="feature-icon">💼</div>
            <h3 class="h6 fw-bold mt-3">Opportunités</h3>
            <p class="text-muted small mb-0">Partager offres d’emploi, stages, projets et événements.</p>
          </div>
        </div>
      </div>

      <div class="col-md-6 col-lg-3">
        <div class="card h-100 border-0 shadow-sm rounded-4">
          <div class="card-body p-4">
            <div class="feature-icon">🛡️</div>
            <h3 class="h6 fw-bold mt-3">Sécurité</h3>
            <p class="text-muted small mb-0">Mots de passe hashés, sessions, formulaires protégés (CSRF).</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<section class="py-5 bg-light">
  <div class="container">
    <div class="row g-4">
      <div class="col-lg-4">
        <div class="card border-0 shadow-sm rounded-4 h-100">
          <div class="card-body p-4">
            <h3 class="h5 fw-bold mb-3">Quelques chiffres</h3>
            <div class="d-flex gap-3 align-items-center mb-3">
              <div class="stat-pill">⚡</div>
              <div>
                <div class="fw-bold">Interface rapide</div>
                <div class="text-muted small">Optimisée Bootstrap</div>
              </div>
            </div>
            <div class="d-flex gap-3 align-items-center mb-3">
              <div class="stat-pill">📱</div>
              <div>
                <div class="fw-bold">100% responsive</div>
                <div class="text-muted small">Mobile / tablette / PC</div>
              </div>
            </div>
            <div class="d-flex gap-3 align-items-center">
              <div class="stat-pill">🔒</div>
              <div>
                <div class="fw-bold">Connexion sécurisée</div>
                <div class="text-muted small">Hash + sessions</div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="col-lg-8">
        <div class="card border-0 shadow-sm rounded-4">
          <div class="card-body p-4 p-md-5">
            <h3 class="h5 fw-bold mb-3">Témoignages (exemple)</h3>

            <div class="row g-3">
              <div class="col-md-6">
                <div class="p-3 rounded-4 bg-white border">
                  <div class="fw-bold">“Super pour retrouver la promo !”</div>
                  <div class="text-muted small mt-1">Ancien élève — Marketing</div>
                </div>
              </div>
              <div class="col-md-6">
                <div class="p-3 rounded-4 bg-white border">
                  <div class="fw-bold">“Simple, rapide et clair.”</div>
                  <div class="text-muted small mt-1">Ancien élève — Dev</div>
                </div>
              </div>
            </div>

            <hr class="my-4">

            <h3 class="h6 fw-bold mb-2">FAQ</h3>
            <div class="accordion" id="faq">
              <div class="accordion-item">
                <h2 class="accordion-header">
                  <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#q1">
                    Qui peut s’inscrire ?
                  </button>
                </h2>
                <div id="q1" class="accordion-collapse collapse show" data-bs-parent="#faq">
                  <div class="accordion-body text-muted">
                    Les anciens élèves et étudiants autorisés (selon les règles de l’école).
                  </div>
                </div>
              </div>

              <div class="accordion-item">
                <h2 class="accordion-header">
                  <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#q2">
                    Est-ce gratuit ?
                  </button>
                </h2>
                <div id="q2" class="accordion-collapse collapse" data-bs-parent="#faq">
                  <div class="accordion-body text-muted">
                    Oui, dans le cadre du projet étudiant (tu peux adapter plus tard).
                  </div>
                </div>
              </div>
            </div>

            <div class="d-flex flex-wrap gap-2 mt-4">
              <a href="register.php" class="btn btn-primary">Créer un compte</a>
              <a href="annuaire.php" class="btn btn-outline-dark">Voir l’annuaire</a>
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