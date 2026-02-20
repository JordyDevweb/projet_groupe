<?php
session_start();
require 'config/database.php';

if (empty($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

function e($v){ return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8'); }

$me = (int)$_SESSION['user_id'];
$other = isset($_GET['to']) ? (int)$_GET['to'] : 0;

if ($other <= 0) {
    header("Location: conversations.php");
    exit;
}

/* ===== Destinataire ===== */
$userStmt = $pdo->prepare("SELECT id, nom, photo FROM users WHERE id = ? LIMIT 1");
$userStmt->execute([$other]);
$user = $userStmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    header("Location: conversations.php");
    exit;
}

$nom = $user['nom'] ?? 'Utilisateur';
$photoFile = trim((string)($user['photo'] ?? ''));
$photo = ($photoFile !== '') ? 'assets/images/'.$photoFile : 'assets/images/default.png';
if (!is_file(__DIR__.'/'.$photo)) $photo = 'assets/images/default.png';

/* ===== Marquer comme lu ===== */
$mark = $pdo->prepare("UPDATE messages SET is_read = 1 WHERE receiver_id = ? AND sender_id = ?");
$mark->execute([$me, $other]);

/* ===== Envoi message + fichier ===== */
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $message = trim($_POST['message'] ?? '');

    // Upload (optionnel)
    $attachment = null;
    $attachmentType = null;
    $attachmentName = null;

    if (!empty($_FILES['file']['name'])) {
        $uploadDir = __DIR__ . '/uploads/messages/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $origName = (string)$_FILES['file']['name'];
        $tmpName  = (string)$_FILES['file']['tmp_name'];
        $size     = (int)$_FILES['file']['size'];

        // Limite (ex: 5MB)
        if ($size > 5 * 1024 * 1024) {
            $errors[] = "Fichier trop lourd (max 5MB).";
        } else {
            $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
            $allowed = ['jpg','jpeg','png','webp','pdf','doc','docx'];

            if (!in_array($ext, $allowed, true)) {
                $errors[] = "Format non autorisé (jpg, png, webp, pdf, doc, docx).";
            } else {
                // Détecter si image
                $isImage = in_array($ext, ['jpg','jpeg','png','webp'], true);
                if ($isImage) {
                    $info = @getimagesize($tmpName);
                    if ($info === false) $errors[] = "L’image n’est pas valide.";
                }

                if (!$errors) {
                    $newName = uniqid('msg_', true) . '.' . $ext;
                    $dest = $uploadDir . $newName;

                    if (!move_uploaded_file($tmpName, $dest)) {
                        $errors[] = "Erreur upload fichier.";
                    } else {
                        $attachment = $newName;
                        $attachmentType = $isImage ? 'image' : 'file';
                        $attachmentName = $origName;
                    }
                }
            }
        }
    }

    // On autorise : message texte OU fichier (au moins un des deux)
    if ($message === '' && $attachment === null) {
        $errors[] = "Écris un message ou ajoute un fichier.";
    }

    if (!$errors) {
        $ins = $pdo->prepare("
            INSERT INTO messages (sender_id, receiver_id, message, attachment, attachment_type, attachment_name)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $ins->execute([$me, $other, $message, $attachment, $attachmentType, $attachmentName]);

        header("Location: messages.php?to=".$other);
        exit;
    }
}

/* ===== Récupérer conversation (avec date + fichier) ===== */
$stmt = $pdo->prepare("
    SELECT id, sender_id, receiver_id, message, sent_at, attachment, attachment_type, attachment_name
    FROM messages
    WHERE (sender_id = ? AND receiver_id = ?)
       OR (sender_id = ? AND receiver_id = ?)
    ORDER BY id ASC
");
$stmt->execute([$me, $other, $other, $me]);
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Messagerie</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<?php include 'includes/header.php'; ?>

<div class="container my-4">

  <div class="card shadow border-0 rounded-4">

    <div class="card-header bg-dark text-white d-flex align-items-center gap-3 rounded-top-4">
      <img src="<?= e($photo) ?>" alt="avatar" style="width:40px;height:40px;border-radius:50%;object-fit:cover;">
      <strong><?= e($nom) ?></strong>
      <a href="conversations.php" class="btn btn-sm btn-outline-light ms-auto">Retour</a>
    </div>

    <div class="card-body" id="msgBody" style="height:420px;overflow-y:auto;background:#f8f9fa;">

      <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
          <ul class="mb-0">
            <?php foreach ($errors as $err): ?>
              <li><?= e($err) ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>

      <?php if (empty($messages)): ?>
        <div class="text-muted text-center py-4">Aucun message pour le moment.</div>
      <?php endif; ?>

      <?php foreach ($messages as $m): ?>
        <?php $isMe = ((int)$m['sender_id'] === $me); ?>
        <div class="d-flex <?= $isMe ? 'justify-content-end' : 'justify-content-start' ?> mb-2">
          <div class="p-2 rounded <?= $isMe ? 'bg-primary text-white' : 'bg-white border' ?>" style="max-width:75%;">
            
            <?php if (!empty($m['message'])): ?>
              <div><?= nl2br(e($m['message'])) ?></div>
            <?php endif; ?>

            <?php if (!empty($m['attachment'])): ?>
              <?php $fileUrl = 'uploads/messages/' . $m['attachment']; ?>

              <?php if (($m['attachment_type'] ?? '') === 'image'): ?>
                <div class="mt-2">
                  <a href="<?= e($fileUrl) ?>" target="_blank">
                    <img src="<?= e($fileUrl) ?>" alt="image" style="max-width:260px;border-radius:12px;display:block;">
                  </a>
                </div>
              <?php else: ?>
                <div class="mt-2">
                  <a class="<?= $isMe ? 'text-white' : '' ?>" href="<?= e($fileUrl) ?>" target="_blank" download>
                    📎 Télécharger : <?= e($m['attachment_name'] ?? 'fichier') ?>
                  </a>
                </div>
              <?php endif; ?>
            <?php endif; ?>

            <div class="small mt-2" style="opacity:.75;">
              <?php
                $dt = $m['sent_at'] ?? null;
                echo $dt ? e(date('d/m/Y H:i', strtotime((string)$dt))) : '';
              ?>
            </div>

          </div>
        </div>
      <?php endforeach; ?>

    </div>

    <div class="card-footer">
      <form method="POST" enctype="multipart/form-data" class="d-flex gap-2 align-items-end">
        <div class="flex-grow-1">
          <textarea name="message" class="form-control" rows="1" placeholder="Écrire un message..."></textarea>
          <div class="d-flex justify-content-between mt-2">
            <input type="file" name="file" class="form-control form-control-sm" style="max-width: 320px;">
            <small class="text-muted">Max 5MB · jpg/png/webp/pdf/doc/docx</small>
          </div>
        </div>
        <button class="btn btn-primary">Envoyer</button>
      </form>
    </div>

  </div>
</div>

<script>
  const box = document.getElementById('msgBody');
  if (box) box.scrollTop = box.scrollHeight;
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>