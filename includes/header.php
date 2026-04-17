<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$isLogged = !empty($_SESSION['user_id']);
$userName = $_SESSION['user_nom'] ?? $_SESSION['user_name'] ?? '';
$current  = basename($_SERVER['PHP_SELF']);

function active($file, $current) {
    return $file === $current ? 'active' : '';
}
?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>IESIG Alumni</title>
 

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="css/style.css?v=<?= time() ?>">

  
</head>

<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-dark bg-dark sticky-top shadow-sm navbar-iesig">
  <div class="container">

    <a class="navbar-brand d-flex align-items-center gap-2 fw-bold" href="index.php">
      <span class="brand-badge" aria-hidden="true">IA</span>
      <span>IESIG Alumni</span>
    </a>

    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#nav"
            aria-controls="nav" aria-expanded="false" aria-label="Ouvrir le menu">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="nav">
      <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-2 mt-3 mt-lg-0">

        <li class="nav-item">
          <a class="nav-link <?= active('index.php', $current) ?>" href="index.php">Accueil</a>
        </li>

        <li class="nav-item">
          <a class="nav-link <?= active('annuaire.php', $current) ?>" href="annuaire.php">Annuaire</a>
        </li>

        <?php if ($isLogged): ?>
          <?php if (!empty($userName)): ?>
            <li class="nav-item d-none d-lg-block">
              <span class="nav-user">👤 <?= htmlspecialchars($userName, ENT_QUOTES, 'UTF-8') ?></span>
            </li>
          <?php endif; ?>

          <li class="nav-item">
            <a class="btn btn-outline-light btn-sm nav-btn" href="logout.php">Déconnexion</a>
          </li>
        <?php else: ?>
          <li class="nav-item">
            <a class="nav-link <?= active('login.php', $current) ?>" href="login.php">Connexion</a>
          </li>

          <li class="nav-item">
            <a class="btn btn-primary btn-sm nav-btn" href="register.php">Inscription</a>
          </li>
        <?php endif; ?>

      </ul>
    </div>
  </div>
</nav>