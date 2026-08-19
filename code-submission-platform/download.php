<?php
require_once 'config.php';
session_start();

if (empty($_SESSION['is_admin'])) {
    header('Location: admin.php');
    exit;
}

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM submissions WHERE id = ?");
$stmt->execute([$id]);
$submission = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$submission) {
    die('التسليم غير موجود.');
}

$path = UPLOAD_DIR . $submission['stored_filename'];
if (!file_exists($path)) {
    die('الملف مش موجود على السيرفر.');
}

header('Content-Description: File Transfer');
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . basename($submission['original_filename']) . '"');
header('Content-Length: ' . filesize($path));
readfile($path);
exit;
