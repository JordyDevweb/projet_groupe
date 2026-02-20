<?php
declare(strict_types=1);

require_once __DIR__ . '/../models/User.php';

final class AuthController {
  public function __construct(private PDO $pdo) {
    if (session_status() !== PHP_SESSION_ACTIVE) session_start();
  }

  public function csrfToken(): string {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
  }

  private function verifyCsrf(): void {
    $t = $_POST['csrf'] ?? '';
    if (!$t || empty($_SESSION['csrf']) || !hash_equals($_SESSION['csrf'], $t)) {
      http_response_code(403);
      exit("CSRF invalide.");
    }
  }

  public function user(): ?array {
    return $_SESSION['user'] ?? null;
  }

  public function requireAuth(): void {
    if (!$this->user()) {
      header("Location: login.php");
      exit;
    }
  }

  public function register(): array {
    $errors = [];
    $values = [
      'nom' => '',
      'email' => '',
      'promotion' => '',
      'filiere' => '',
      'metier' => '',
      'bio' => ''
    ];

    if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
      $this->verifyCsrf();

      $values['nom'] = trim((string)($_POST['nom'] ?? ''));
      $values['email'] = strtolower(trim((string)($_POST['email'] ?? '')));
      $pass = (string)($_POST['password'] ?? '');
      $pass2 = (string)($_POST['password2'] ?? '');
      $values['promotion'] = trim((string)($_POST['promotion'] ?? ''));
      $values['filiere'] = trim((string)($_POST['filiere'] ?? ''));
      $values['metier'] = trim((string)($_POST['metier'] ?? ''));
      $values['bio'] = trim((string)($_POST['bio'] ?? ''));

      if ($values['nom'] === '') $errors[] = "Le nom est obligatoire.";
      if (!filter_var($values['email'], FILTER_VALIDATE_EMAIL)) $errors[] = "Email invalide.";
      if (strlen($pass) < 8) $errors[] = "Mot de passe: 8 caractères minimum.";
      if ($pass !== $pass2) $errors[] = "Les mots de passe ne correspondent pas.";

      $model = new User($this->pdo);
      if (!$errors && $model->findByEmail($values['email'])) {
        $errors[] = "Cet email existe déjà.";
      }

      if (!$errors) {
        $id = $model->create([
          'nom' => $values['nom'],
          'email' => $values['email'],
          // ✅ hash, même si ta colonne s'appelle password
          'password' => password_hash($pass, PASSWORD_DEFAULT),
          'promotion' => $values['promotion'] ?: null,
          'filiere' => $values['filiere'] ?: null,
          'metier' => $values['metier'] ?: null,
          'bio' => $values['bio'] ?: null,
          'photo' => null
        ]);

        session_regenerate_id(true);

        $_SESSION['user'] = [
          'id' => $id,
          'nom' => $values['nom'],
          'email' => $values['email'],
        ];

        header("Location: profile.php");
        exit;
      }
    }

    return ['errors' => $errors, 'values' => $values];
  }

  public function login(): array {
    $errors = [];
    $email = '';

    if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
      $this->verifyCsrf();

      $email = strtolower(trim((string)($_POST['email'] ?? '')));
      $pass = (string)($_POST['password'] ?? '');

      $model = new User($this->pdo);
      $u = $model->findByEmail($email);

      // ✅ colonne password = hash
      if (!$u || !password_verify($pass, $u['password'])) {
        $errors[] = "Identifiants incorrects.";
      } else {
        session_regenerate_id(true);

        $_SESSION['user'] = [
          'id' => (int)$u['id'],
          'nom' => $u['nom'],
          'email' => $u['email'],
        ];

        header("Location: profile.php");
        exit;
      }
    }

    return ['errors' => $errors, 'email' => $email];
  }

  public function logout(): void {
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
      $p = session_get_cookie_params();
      setcookie(session_name(), '', time() - 42000, $p["path"], $p["domain"], (bool)$p["secure"], (bool)$p["httponly"]);
    }
    session_destroy();
    header("Location: index.php");
    exit;
  }
}