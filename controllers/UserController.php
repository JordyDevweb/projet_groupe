<?php
declare(strict_types=1);

require_once __DIR__ . '/../models/User.php';

final class UserController {
  public function __construct(private PDO $pdo) {
    if (session_status() !== PHP_SESSION_ACTIVE) session_start();
  }

  public function get(int $id): ?array {
    return (new User($this->pdo))->findById($id);
  }

  public function update(int $id): array {
    $errors = [];
    $ok = false;

    if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
      $t = $_POST['csrf'] ?? '';
      if (!$t || empty($_SESSION['csrf']) || !hash_equals($_SESSION['csrf'], $t)) {
        http_response_code(403);
        exit("CSRF invalide.");
      }

      $promotion = trim((string)($_POST['promotion'] ?? ''));
      $filiere = trim((string)($_POST['filiere'] ?? ''));
      $metier = trim((string)($_POST['metier'] ?? ''));
      $bio = trim((string)($_POST['bio'] ?? ''));

      // Upload photo (optionnel)
      $photoPath = null;
      if (!empty($_FILES['photo']['name'])) {
        $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
        $type = (string)($_FILES['photo']['type'] ?? '');
        if (!isset($allowed[$type])) {
          $errors[] = "Photo: formats autorisés JPG/PNG/WebP.";
        } else {
          $ext = $allowed[$type];
          $dir = _DIR_ . '/../assets/images/profiles';
          if (!is_dir($dir)) mkdir($dir, 0777, true);

          $name = "u{$id}_" . bin2hex(random_bytes(6)) . "." . $ext;
          $dest = $dir . "/" . $name;

          if (!move_uploaded_file($_FILES['photo']['tmp_name'], $dest)) {
            $errors[] = "Upload impossible.";
          } else {
            $photoPath = "assets/images/profiles/" . $name;
          }
        }
      }

      if (!$errors) {
        (new User($this->pdo))->updateProfile($id, [
          'promotion' => $promotion ?: null,
          'filiere' => $filiere ?: null,
          'metier' => $metier ?: null,
          'bio' => $bio ?: null,
          'photo' => $photoPath
        ]);
        $ok = true;
      }
    }

    return ['ok' => $ok, 'errors' => $errors];
  }

  public function directory(): array {
    $q = trim((string)($_GET['q'] ?? ''));
    $promo = trim((string)($_GET['promo'] ?? ''));
    $users = (new User($this->pdo))->search($q, $promo ?: null);
    return ['users' => $users, 'q' => $q, 'promo' => $promo];
  }
}