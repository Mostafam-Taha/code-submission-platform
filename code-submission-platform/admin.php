<?php
require_once 'config.php';
session_start();

$error = null;
$success = null;

// تسجيل الدخول
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password'])) {
    if ($_POST['password'] === ADMIN_PASSWORD) {
        $_SESSION['is_admin'] = true;
    } else {
        $error = 'باسورد غلط.';
    }
}

$isAdmin = !empty($_SESSION['is_admin']);

if ($isAdmin && isset($_GET['logout'])) {
    session_destroy();
    header('Location: admin.php');
    exit;
}

// بترفع صورة الحل بأي صيغة شائعة، وتحوّلها تلقائي لـ webp، وترجع اسم الملف المحفوظ (أو null لو مفيش صورة اتبعتت)
function handleSolutionUpload(): ?string {
    if (empty($_FILES['solution_image']) || $_FILES['solution_image']['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if ($_FILES['solution_image']['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('حصل خطأ في رفع صورة الحل.');
    }

    $file = $_FILES['solution_image'];

    if ($file['size'] > MAX_FILE_SIZE) {
        throw new RuntimeException('حجم صورة الحل أكبر من المسموح (5 ميجا).');
    }

    $imageInfo = @getimagesize($file['tmp_name']);
    if ($imageInfo === false) {
        throw new RuntimeException('الملف اللي رفعته مش صورة صالحة.');
    }

    if (!function_exists('imagewebp')) {
        throw new RuntimeException('مكتبة الصور (GD) على السيرفر مش مفعّل فيها دعم WebP، كلّم مسؤول الاستضافة.');
    }

    // نحمّل الصورة حسب نوعها الحقيقي (مش الامتداد) عشان التحويل يبقى دقيق
    switch ($imageInfo['mime']) {
        case 'image/jpeg':
            $image = @imagecreatefromjpeg($file['tmp_name']);
            break;
        case 'image/png':
            $image = @imagecreatefrompng($file['tmp_name']);
            if ($image) {
                imagepalettetotruecolor($image);
                imagealphablending($image, true);
                imagesavealpha($image, true);
            }
            break;
        case 'image/gif':
            $image = @imagecreatefromgif($file['tmp_name']);
            break;
        case 'image/webp':
            $image = @imagecreatefromwebp($file['tmp_name']);
            break;
        case 'image/bmp':
        case 'image/x-ms-bmp':
            $image = function_exists('imagecreatefrombmp') ? @imagecreatefrombmp($file['tmp_name']) : false;
            break;
        default:
            $image = false;
    }

    if (!$image) {
        throw new RuntimeException('صيغة الصورة دي مش مدعومة. جرّب jpg أو png أو webp.');
    }

    if (!is_dir(SOLUTIONS_DIR)) {
        mkdir(SOLUTIONS_DIR, 0755, true);
    }

    $storedName = uniqid('sol_', true) . '.webp';

    // جودة 85% كفاية جدًا للعرض وبتوفر مساحة
    $saved = imagewebp($image, SOLUTIONS_DIR . $storedName, 85);
    imagedestroy($image);

    if (!$saved) {
        throw new RuntimeException('فشل تحويل/حفظ صورة الحل.');
    }

    return $storedName;
}

// ============ الأكشنات (إضافة / تعديل / حذف / تفعيل) ============
if ($isAdmin && $_SERVER['REQUEST_METHOD'] === 'POST') {

    // إضافة سؤال جديد
    if (isset($_POST['action']) && $_POST['action'] === 'add') {
        $text = trim($_POST['question_text'] ?? '');
        if ($text === '') {
            $error = 'اكتب نص السؤال الأول.';
        } else {
            try {
                $solutionImage = handleSolutionUpload();

                $makeActive = isset($_POST['make_active']);
                if ($makeActive) {
                    $pdo->exec("UPDATE questions SET is_active = 0");
                }
                // لو دي أول سؤال بيتضاف خليه نشط تلقائي
                $countRow = $pdo->query("SELECT COUNT(*) c FROM questions")->fetch(PDO::FETCH_ASSOC);
                $isFirst = ((int)$countRow['c'] === 0);

                $stmt = $pdo->prepare("INSERT INTO questions (question_text, is_active, solution_image) VALUES (?, ?, ?)");
                $stmt->execute([$text, ($makeActive || $isFirst) ? 1 : 0, $solutionImage]);
                $success = 'اتضاف السؤال بنجاح.';
            } catch (RuntimeException $e) {
                $error = $e->getMessage();
            }
        }
    }

    // تعديل سؤال موجود
    if (isset($_POST['action']) && $_POST['action'] === 'edit') {
        $id = (int)($_POST['id'] ?? 0);
        $text = trim($_POST['question_text'] ?? '');
        if ($text === '') {
            $error = 'نص السؤال متقدرش يكون فاضي.';
        } else {
            try {
                $solutionImage = handleSolutionUpload();

                if ($solutionImage !== null) {
                    // مسح صورة الحل القديمة لو موجودة قبل حفظ الجديدة
                    $old = $pdo->prepare("SELECT solution_image FROM questions WHERE id = ?");
                    $old->execute([$id]);
                    $oldImage = $old->fetchColumn();
                    if ($oldImage && file_exists(SOLUTIONS_DIR . $oldImage)) {
                        unlink(SOLUTIONS_DIR . $oldImage);
                    }
                    $stmt = $pdo->prepare("UPDATE questions SET question_text = ?, solution_image = ? WHERE id = ?");
                    $stmt->execute([$text, $solutionImage, $id]);
                } else {
                    $stmt = $pdo->prepare("UPDATE questions SET question_text = ? WHERE id = ?");
                    $stmt->execute([$text, $id]);
                }
                $success = 'اتعدّل السؤال بنجاح.';
            } catch (RuntimeException $e) {
                $error = $e->getMessage();
            }
        }
    }

    // حذف سؤال
    if (isset($_POST['action']) && $_POST['action'] === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        $old = $pdo->prepare("SELECT solution_image FROM questions WHERE id = ?");
        $old->execute([$id]);
        $oldImage = $old->fetchColumn();
        if ($oldImage && file_exists(SOLUTIONS_DIR . $oldImage)) {
            unlink(SOLUTIONS_DIR . $oldImage);
        }
        $stmt = $pdo->prepare("DELETE FROM questions WHERE id = ?");
        $stmt->execute([$id]);
        $success = 'اتمسح السؤال.';
    }

    // تفعيل سؤال (يظهر في صفحة الرفع)
    if (isset($_POST['action']) && $_POST['action'] === 'activate') {
        $id = (int)($_POST['id'] ?? 0);
        $pdo->exec("UPDATE questions SET is_active = 0");
        $stmt = $pdo->prepare("UPDATE questions SET is_active = 1 WHERE id = ?");
        $stmt->execute([$id]);
        $success = 'اتفعّل السؤال ده وهيظهر دلوقتي للمستخدمين.';
    }
}

$editingQuestion = null;
if ($isAdmin && isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM questions WHERE id = ?");
    $stmt->execute([(int)$_GET['edit']]);
    $editingQuestion = $stmt->fetch(PDO::FETCH_ASSOC);
}

$questions = $isAdmin ? $pdo->query("SELECT * FROM questions ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC) : [];

$submissions = [];
if ($isAdmin) {
    $submissions = $pdo->query(
        "SELECT s.*, q.question_text
         FROM submissions s
         LEFT JOIN questions q ON q.id = s.question_id
         ORDER BY s.id DESC"
    )->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>صفحة الأدمن</title>
<link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="style.css">
</head>
<body>

<?php if (!$isAdmin): ?>
<div class="card">
  <div class="terminal-bar"><span></span><span></span><span></span></div>
  <div class="eyebrow">دخول الأدمن</div>
  <h1>سجّل دخولك عشان تدير الأسئلة</h1>
  <?php if ($error): ?><div class="msg error">⚠️ <?= htmlspecialchars($error) ?></div><?php endif; ?>
  <form method="POST">
    <label>الباسورد</label>
    <input type="password" name="password" required style="width:100%;padding:13px 16px;background:#0d1218;border:1px solid var(--border);border-radius:8px;color:var(--text);font-family:var(--font-display);font-size:15px;margin-bottom:24px;">
    <button type="submit">دخول</button>
  </form>
</div>

<?php else: ?>
<div class="admin-wrap">
  <div class="admin-header">
    <div>
      <div class="eyebrow">لوحة التحكم</div>
      <h1 style="margin:0;">إدارة الأسئلة والتسليمات</h1>
    </div>
    <a href="admin.php?logout=1" class="ghost-btn">تسجيل خروج</a>
  </div>

  <?php if ($success): ?><div class="msg ok">✅ <?= htmlspecialchars($success) ?></div><?php endif; ?>
  <?php if ($error): ?><div class="msg error">⚠️ <?= htmlspecialchars($error) ?></div><?php endif; ?>

  <!-- إضافة / تعديل سؤال -->
  <div class="panel">
    <?php if ($editingQuestion): ?>
      <h2>تعديل السؤال #<?= (int)$editingQuestion['id'] ?></h2>
      <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="action" value="edit">
        <input type="hidden" name="id" value="<?= (int)$editingQuestion['id'] ?>">
        <textarea name="question_text" required><?= htmlspecialchars($editingQuestion['question_text']) ?></textarea>

        <label>صورة الحل <?= $editingQuestion['solution_image'] ? '— في صورة متحفوظة بالفعل' : '' ?></label>
        <?php if ($editingQuestion['solution_image']): ?>
          <img src="solutions/<?= htmlspecialchars($editingQuestion['solution_image']) ?>" class="solution-preview" alt="الحل الحالي">
        <?php endif; ?>
        <input type="file" name="solution_image" accept="image/*" class="file-field">
        <p class="hint-note">ارفع الصورة بأي صيغة (jpg, png, webp...) وهتتحول لـ webp تلقائي. سيبها فاضية لو مش عايز تغيّر الصورة الحالية.</p>

        <div class="row-btns">
          <button type="submit">حفظ التعديل</button>
          <a href="admin.php" class="ghost-btn">إلغاء</a>
        </div>
      </form>
    <?php else: ?>
      <h2>إضافة سؤال جديد</h2>
      <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="action" value="add">
        <textarea name="question_text" placeholder="اكتب نص السؤال هنا..." required></textarea>

        <label>صورة الحل — اختياري</label>
        <input type="file" name="solution_image" accept="image/*" class="file-field">
        <p class="hint-note">ارفع الصورة بأي صيغة (jpg, png, webp...) وهتتحول لـ webp تلقائي وتظهر للطالب كصورة صغيرة قابلة للتكبير فوق السؤال.</p>

        <label class="checkbox-label">
          <input type="checkbox" name="make_active" checked>
          خليه هو السؤال النشط (اللي هيظهر للمستخدمين)
        </label>
        <button type="submit">إضافة السؤال</button>
      </form>
    <?php endif; ?>
  </div>

  <!-- كل الأسئلة -->
  <div class="panel">
    <h2>كل الأسئلة (<?= count($questions) ?>)</h2>
    <?php if (empty($questions)): ?>
      <p class="empty-note">لسه مفيش أسئلة متضافة.</p>
    <?php else: ?>
      <div class="q-list">
        <?php foreach ($questions as $q): ?>
          <div class="q-item <?= $q['is_active'] ? 'active' : '' ?>">
            <div class="q-item-text"><?= nl2br(htmlspecialchars($q['question_text'])) ?></div>
            <?php if ($q['solution_image']): ?>
              <div class="q-item-solution">🖼️ فيه صورة حل مرفوعة</div>
            <?php endif; ?>
            <div class="q-item-meta">
              <?php if ($q['is_active']): ?>
                <span class="badge">نشط الآن</span>
              <?php else: ?>
                <form method="POST" style="display:inline;">
                  <input type="hidden" name="action" value="activate">
                  <input type="hidden" name="id" value="<?= (int)$q['id'] ?>">
                  <button type="submit" class="small-btn">تفعيل</button>
                </form>
              <?php endif; ?>
              <a href="admin.php?edit=<?= (int)$q['id'] ?>" class="small-btn ghost">تعديل</a>
              <form method="POST" style="display:inline;" onsubmit="return confirm('متأكد إنك عايز تمسح السؤال ده؟');">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?= (int)$q['id'] ?>">
                <button type="submit" class="small-btn danger">حذف</button>
              </form>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>

  <!-- التسليمات -->
  <div class="panel">
    <h2>التسليمات (<?= count($submissions) ?>)</h2>
    <?php if (empty($submissions)): ?>
      <p class="empty-note">لسه محدش رفع كود.</p>
    <?php else: ?>
      <div class="table-wrap">
        <table>
          <thead>
            <tr>
              <th>البريد الإلكتروني</th>
              <th>الملف</th>
              <th>السؤال</th>
              <th>التاريخ</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($submissions as $s): ?>
              <tr>
                <td><?= htmlspecialchars($s['email']) ?></td>
                <td class="mono"><?= htmlspecialchars($s['original_filename']) ?></td>
                <td class="q-cell"><?= htmlspecialchars(mb_substr($s['question_text'] ?? '—', 0, 60)) ?><?= (mb_strlen($s['question_text'] ?? '') > 60) ? '…' : '' ?></td>
                <td class="mono"><?= htmlspecialchars($s['submitted_at']) ?></td>
                <td><a href="download.php?id=<?= (int)$s['id'] ?>" class="small-btn ghost">تحميل</a></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>

  <div class="foot-link"><a href="index.php">صفحة الرفع</a></div>
</div>
<?php endif; ?>

</body>
</html>
