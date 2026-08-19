<?php
require_once 'config.php';

function fail($msg) {
    header('Location: index.php?error=' . urlencode($msg));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    fail('طريقة إرسال غير صحيحة.');
}

$email = trim($_POST['email'] ?? '');
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    fail('البريد الإلكتروني غير صحيح.');
}

if (!isset($_FILES['code_file']) || $_FILES['code_file']['error'] !== UPLOAD_ERR_OK) {
    fail('حصل خطأ في رفع الملف، حاول تاني.');
}

$file = $_FILES['code_file'];

if ($file['size'] > MAX_FILE_SIZE) {
    fail('حجم الملف أكبر من المسموح (5 ميجا).');
}

$originalName = basename($file['name']);
$ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

if (!in_array($ext, ALLOWED_EXTENSIONS)) {
    fail('نوع الملف غير مسموح به.');
}

if (!is_dir(UPLOAD_DIR)) {
    mkdir(UPLOAD_DIR, 0755, true);
}

// اسم فريد للملف عشان مايحصلش تعارض بين المستخدمين
$storedName = uniqid('sub_', true) . '.' . $ext;
$destination = UPLOAD_DIR . $storedName;

if (!move_uploaded_file($file['tmp_name'], $destination)) {
    fail('فشل حفظ الملف على السيرفر.');
}

// هات السؤال النشط عشان نربط بيه التسليم، ولو مفيش هات آخر واحد
$stmt = $pdo->query("SELECT id FROM questions WHERE is_active = 1 ORDER BY id DESC LIMIT 1");
$question = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$question) {
    $stmt = $pdo->query("SELECT id FROM questions ORDER BY id DESC LIMIT 1");
    $question = $stmt->fetch(PDO::FETCH_ASSOC);
}
$questionId = $question ? $question['id'] : null;

$stmt = $pdo->prepare(
    "INSERT INTO submissions (question_id, email, original_filename, stored_filename) VALUES (?, ?, ?, ?)"
);
$stmt->execute([$questionId, $email, $originalName, $storedName]);

header('Location: index.php?success=1');
exit;
