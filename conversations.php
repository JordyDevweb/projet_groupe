<?php
session_start();
require 'config/database.php';

if (empty($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$me = (int)$_SESSION['user_id'];

$stmt = $pdo->prepare("
SELECT DISTINCT
CASE WHEN sender_id = ? THEN receiver_id ELSE sender_id END AS other_id
FROM messages
WHERE sender_id = ? OR receiver_id = ?
");
$stmt->execute([$me,$me,$me]);
$others = $stmt->fetchAll(PDO::FETCH_COLUMN);

$conversations = [];

foreach ($others as $otherId) {

$userStmt = $pdo->prepare("SELECT nom, photo FROM users WHERE id=?");
$userStmt->execute([$otherId]);
$user = $userStmt->fetch(PDO::FETCH_ASSOC);
if(!$user) continue;

$last = $pdo->prepare("
SELECT * FROM messages
WHERE (sender_id=? AND receiver_id=?)
OR (sender_id=? AND receiver_id=?)
ORDER BY id DESC LIMIT 1
");
$last->execute([$me,$otherId,$otherId,$me]);
$lastMsg = $last->fetch(PDO::FETCH_ASSOC);

$unread = $pdo->prepare("
SELECT COUNT(*) FROM messages
WHERE receiver_id=? AND sender_id=? AND is_read=0
");
$unread->execute([$me,$otherId]);
$count = $unread->fetchColumn();

$conversations[] = [
'id'=>$otherId,
'nom'=>$user['nom'],
'photo'=>$user['photo'],
'last'=>$lastMsg['message'] ?? '',
'unread'=>$count,
'last_id'=>$lastMsg['id'] ?? 0
];
}

usort($conversations, fn($a,$b)=>$b['last_id']<=>$a['last_id']);
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Boîte de réception</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<?php include 'includes/header.php'; ?>

<div class="container my-4">

<h3>Boîte de réception</h3>

<div class="list-group shadow">

<?php foreach($conversations as $c): ?>
<a href="messages.php?to=<?= $c['id'] ?>" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">

<div class="d-flex align-items-center gap-3">
<img src="<?= !empty($c['photo']) ? 'assets/images/'.$c['photo'] : 'assets/images/default.png' ?>" style="width:40px;height:40px;border-radius:50%;object-fit:cover;">
<div>
<div class="fw-bold"><?= htmlspecialchars($c['nom']) ?></div>
<small class="text-muted"><?= htmlspecialchars($c['last']) ?></small>
</div>
</div>

<?php if($c['unread']>0): ?>
<span class="badge bg-danger rounded-pill"><?= $c['unread'] ?></span>
<?php endif; ?>

</a>
<a href="new_message.php" class="btn btn-primary">
  Nouveau message
</a>
<?php endforeach; ?>

</div>
</div>

</body>
</html>