<?php
session_start();
require __DIR__ . '/config/database.php';

function e($v){ 
    return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8'); 
}

$token = trim((string)($_GET['token'] ?? ''));
$modeReset = ($token !== ''); // si token présent => formulaire nouveau mot de passe

$errors = [];
$success = '';
$devResetLink = '';

/* ===== Helpers ===== */
function randomToken(int $bytes = 32): string {
    return rtrim(strtr(base64_encode(random_bytes($bytes)), '+/', '-_'), '=');
}
function hashToken(string $token): string {
    return hash('sha256', $token);
}
function nowPlusMinutes(int $min): string {
    return date('Y-m-d H:i:s', time() + ($min * 60));
}
function isValidEmail(string $email): bool {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/* ===== Handle POST ===== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // 1) Demande de lien
    if (isset($_POST['action']) && $_POST['action'] === 'request_link') {

        $email = trim((string)($_POST['email'] ?? ''));

        // Message unique (anti-enumération)
        $success = "Si un compte existe avec cet email, un lien de réinitialisation a été envoyé.";

        if ($email === '' || !isValidEmail($email)) {
            $errors[] = "Veuillez saisir un email valide.";
        } else {

            // Chercher l’utilisateur
            $u = $pdo->prepare("SELECT id, email FROM users WHERE email = ? LIMIT 1");
            $u->execute([$email]);
            $user = $u->fetch(PDO::FETCH_ASSOC);

            if ($user) {
                $userId = (int)$user['id'];

                // Nettoyage anciens tokens expirés
                $pdo->prepare("
                    UPDATE users 
                    SET reset_token = NULL, reset_token_expire = NULL 
                    WHERE id = ? OR reset_token_expire < NOW()
                ")->execute([$userId]);

                // Créer token + hash + expiration
                $plainToken = randomToken(32);
                $tokenHash  = hashToken($plainToken);
                $expiresAt  = nowPlusMinutes(30); // 30 minutes

                // Mettre à jour l'utilisateur avec le token
                $pdo->prepare("
                    UPDATE users 
                    SET reset_token = ?, reset_token_expire = ? 
                    WHERE id = ?
                ")->execute([$tokenHash, $expiresAt, $userId]);

                // Lien de reset
                $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
                $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
                $path = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
                $resetLink = $scheme . '://' . $host . $path . '/forgot_password.php?token=' . urlencode($plainToken);

                // Envoi email (peut ne pas marcher sur XAMPP sans config SMTP)
                $subject = "Réinitialisation de mot de passe - IESIG Alumni";
                $message = "Bonjour,\n\nPour réinitialiser votre mot de passe, cliquez sur ce lien (valable 30 minutes) :\n"
                         . $resetLink . "\n\nSi vous n'êtes pas à l'origine de cette demande, ignorez ce message.\n\nIESIG Alumni";
                $headers = "From: no-reply@iesig-alumni.local\r\n";

                $sent = @mail($email, $subject, $message, $headers);

                // Mode dev: on affiche le lien si mail() ne fonctionne pas
                if (!$sent) {
                    $devResetLink = $resetLink;
                }
            }
        }
    }

    // 2) Réinitialisation via token
    if (isset($_POST['action']) && $_POST['action'] === 'reset_password') {

        $postedToken = trim((string)($_POST['token'] ?? ''));
        $pass1 = (string)($_POST['password'] ?? '');
        $pass2 = (string)($_POST['password_confirm'] ?? '');

        if ($postedToken === '') {
            $errors[] = "Lien invalide (token manquant).";
        } else {
            if (strlen($pass1) < 8) $errors[] = "Le mot de passe doit contenir au moins 8 caractères.";
            if ($pass1 !== $pass2) $errors[] = "Les mots de passe ne correspondent pas.";
        }

        if (!$errors) {
            $tokenHash = hashToken($postedToken);

            $stmt = $pdo->prepare("
                SELECT id, password, reset_token_expire
                FROM users
                WHERE reset_token = ?
                LIMIT 1
            ");
            $stmt->execute([$tokenHash]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                $errors[] = "Lien invalide ou expiré.";
            } else {
                // Vérifier expiration
                if (strtotime((string)$user['reset_token_expire']) < time()) {
                    // token expiré => on supprime les champs
                    $pdo->prepare("
                        UPDATE users 
                        SET reset_token = NULL, reset_token_expire = NULL 
                        WHERE id = ?
                    ")->execute([$user['id']]);
                    $errors[] = "Lien expiré. Recommence la demande.";
                } else {
                    $userId = (int)$user['id'];

                    // Mettre à jour mot de passe
                    $newHash = password_hash($pass1, PASSWORD_DEFAULT);

                    $upd = $pdo->prepare("
                        UPDATE users 
                        SET password = ?, reset_token = NULL, reset_token_expire = NULL
                        WHERE id = ?
                    ");
                    $upd->execute([$newHash, $userId]);

                    $success = "Mot de passe mis à jour. Tu peux te connecter.";
                    $modeReset = false;
                    $token = '';
                }
            }
        }
    }
}
?>

<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Mot de passe oublié - IESIG Alumni</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<?php include __DIR__ . '/includes/header.php'; ?>

<div class="container py-5" style="max-width: 720px;">

  <div class="text-center mb-4">
    <h1 class="h3 mb-1">Mot de passe oublié</h1>
    <p class="text-muted mb-0">
      <?= $modeReset ? "Choisis un nouveau mot de passe." : "Reçois un lien pour réinitialiser ton mot de passe." ?>
    </p>
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

  <?php if ($success !== ''): ?>
    <div class="alert alert-success"><?= e($success) ?></div>
  <?php endif; ?>

  <?php if ($devResetLink !== ''): ?>
    <div class="alert alert-warning">
      <div class="fw-semibold mb-1">Mode dev (XAMPP) :</div>
      <div class="small text-muted mb-2">L’envoi mail() n’est pas configuré. Utilise ce lien :</div>
      <a class="btn btn-sm btn-outline-dark" href="<?= e($devResetLink) ?>">Ouvrir le lien de réinitialisation</a>
    </div>
  <?php endif; ?>

  <div class="card shadow-sm">
    <div class="card-body p-4">

      <?php if (!$modeReset): ?>
        <!-- Demande de lien -->
        <form method="post" class="row g-3">
          <input type="hidden" name="action" value="request_link">

          <div class="col-12">
            <label class="form-label">Adresse email</label>
            <input type="email" name="email" class="form-control" placeholder="ex: nom@gmail.com" required>
          </div>

          <div class="col-12 d-flex gap-2 flex-wrap">
            <button class="btn btn-primary" type="submit">Envoyer le lien</button>
            <a class="btn btn-outline-secondary" href="login.php">Retour connexion</a>
          </div>

          <div class="col-12">
            <div class="text-muted small">
              Si tu ne reçois rien, vérifie tes spams. Le lien expire après 30 minutes.
            </div>
          </div>
        </form>

      <?php else: ?>
        <!-- Réinitialisation -->
        <form method="post" class="row g-3">
          <input type="hidden" name="action" value="reset_password">
          <input type="hidden" name="token" value="<?= e($token) ?>">

          <div class="col-12">
            <label class="form-label">Nouveau mot de passe</label>
            <input type="password" name="password" class="form-control" required minlength="8">
            <div class="form-text">Min 8 caractères.</div>
          </div>

          <div class="col-12">
            <label class="form-label">Confirmer le mot de passe</label>
            <input type="password" name="password_confirm" class="form-control" required minlength="8">
          </div>

          <div class="col-12 d-flex gap-2 flex-wrap">
            <button class="btn btn-success" type="submit">Mettre à jour</button>
            <a class="btn btn-outline-secondary" href="forgot_password.php">Revenir</a>
          </div>
        </form>
      <?php endif; ?>

    </div>
  </div>

</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>