<?php
// ============================================
// إعدادات الاتصال بقاعدة البيانات - عدّل البيانات دي حسب السيرفر بتاعك
// ============================================
define('DB_HOST', 'localhost');
define('DB_NAME', 'code_submissions');
define('DB_USER', 'root');
define('DB_PASS', '');

// باسورد صفحة الأدمن (اللي بيكتب فيها السؤال) - غيّره لباسورد قوي قبل ما ترفع المشروع
define('ADMIN_PASSWORD', 'admin123');

// المجلد اللي هيتحفظ فيه ملفات الكود المرفوعة
define('UPLOAD_DIR', __DIR__ . '/uploads/');

// المجلد اللي هيتحفظ فيه صور الحل (webp) اللي بيرفعها الأدمن
define('SOLUTIONS_DIR', __DIR__ . '/solutions/');

// الامتدادات المسموح برفعها
define('ALLOWED_EXTENSIONS', ['php', 'js', 'py', 'java', 'c', 'cpp', 'cs', 'html', 'css', 'txt', 'zip', 'sql', 'ts', 'rb', 'go']);

// أقصى حجم للملف بالبايت (5 ميجا)
define('MAX_FILE_SIZE', 5 * 1024 * 1024);

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    die('فشل الاتصال بقاعدة البيانات. تأكد إنك شغّلت ملف schema.sql وضبطت بيانات الاتصال في config.php');
}
