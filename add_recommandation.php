<?php
session_start();
require 'config/database.php';

if(empty($_SESSION['user_id'])){
exit;
}

$author = $_SESSION['user_id'];
$user = $_POST['user_id'];
$message = $_POST['message'];

$stmt = $pdo->prepare("
INSERT INTO recommendations(user_id,author_id,message)
VALUES(?,?,?)
");

$stmt->execute([$user,$author,$message]);

header("Location: user_profile.php?id=".$user);