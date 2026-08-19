<?php
require_once 'config.php';

// هات السؤال النشط، ولو مفيش سؤال متفعل هات آخر واحد اتضاف
$stmt = $pdo->query("SELECT * FROM questions WHERE is_active = 1 ORDER BY id DESC LIMIT 1");
$question = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$question) {
    $stmt = $pdo->query("SELECT * FROM questions ORDER BY id DESC LIMIT 1");
    $question = $stmt->fetch(PDO::FETCH_ASSOC);
}

$success = isset($_GET['success']);
$error = $_GET['error'] ?? null;
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>سلّم الكود</title>
<link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="style.css">
</head>
<body>
<div class="card">
  <div class="terminal-bar"><span></span><span></span><span></span></div>
  <div class="eyebrow">تسليم الحل</div>
  <h1>اقرأ السؤال، وسلّم الكود بتاعك</h1>

  <?php if ($success): ?>
    <div class="msg ok">✅ تم استلام الكود بنجاح. هيوصلك تأكيد على إيميلك لو محتاج.</div>
  <?php elseif ($error): ?>
    <div class="msg error">⚠️ <?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <?php if (!empty($question['solution_image'])): ?>
    <div class="solution-eyebrow">
      <span>الحل</span>
      <div class="solution-thumb" id="solutionThumb">
        <img src="solutions/<?= htmlspecialchars($question['solution_image']) ?>" alt="صورة الحل">
        <div class="solution-thumb-hint">دوس للتكبير 🔍</div>
      </div>
    </div>
  <?php endif; ?>

  <label>السؤال</label>
  <div class="question-box"><?= $question ? nl2br(htmlspecialchars($question['question_text'])) : 'لسه مفيش سؤال متكتب. تواصل مع المسؤول.' ?></div>

  <?php if ($question): ?>
    <button type="button" id="openUploadBtn">📤 ارفع الكود</button>
  <?php endif; ?>

  <div class="foot-link"><a href="admin.php">صفحة الأدمن</a></div>
</div>

<!-- الفورم الحقيقي، بيتبعت لما المستخدم يخلص الخطوتين -->
<form action="submit.php" method="POST" enctype="multipart/form-data" id="submitForm" style="display:none;">
  <input type="file" name="code_file" id="fileInput" required
    accept=".php,.js,.py,.java,.c,.cpp,.cs,.html,.css,.txt,.zip,.sql,.ts,.rb,.go">
  <input type="email" name="email" id="emailInput" required>
</form>

<!-- مودال رفع الملف -->
<div class="modal-overlay" id="uploadModal">
  <div class="modal-box">
    <div class="modal-title">ارفع ملف الكود</div>
    <div class="upload-zone" id="uploadZone">
      <div class="icon">📄</div>
      <div>اضغط هنا أو اسحب الملف عشان ترفعه</div>
      <div class="filename" id="fileNameDisplay"></div>
    </div>
    <button type="button" class="ghost-btn full" id="cancelUploadBtn">إلغاء</button>
  </div>
</div>

<!-- مودال الإيميل -->
<div class="modal-overlay" id="emailModal">
  <div class="modal-box">
    <div class="modal-title">اكتب بريدك الإلكتروني</div>
    <div class="filename" id="chosenFileLabel" style="margin-bottom:16px;"></div>
    <input type="email" id="emailField" placeholder="you@example.com" required>
    <button type="button" id="finalSubmitBtn">إرسال الكود</button>
    <button type="button" class="ghost-btn full" id="backToUploadBtn">رجوع</button>
  </div>
</div>

<!-- مودال تكبير صورة الحل -->
<?php if (!empty($question['solution_image'])): ?>
<div class="modal-overlay" id="solutionModal">
  <div class="lightbox-box">
    <button type="button" class="lightbox-close" id="closeSolutionBtn">✕</button>
    <img src="solutions/<?= htmlspecialchars($question['solution_image']) ?>" alt="صورة الحل مكبّرة" class="lightbox-img" id="lightboxImg">
    <div class="lightbox-hint">دوس على الصورة عشان تكبّر/تصغّر</div>
  </div>
</div>
<?php endif; ?>

<script>
const openUploadBtn = document.getElementById('openUploadBtn');
const uploadModal = document.getElementById('uploadModal');
const emailModal = document.getElementById('emailModal');
const uploadZone = document.getElementById('uploadZone');
const fileInput = document.getElementById('fileInput');
const fileNameDisplay = document.getElementById('fileNameDisplay');
const chosenFileLabel = document.getElementById('chosenFileLabel');
const cancelUploadBtn = document.getElementById('cancelUploadBtn');
const backToUploadBtn = document.getElementById('backToUploadBtn');
const finalSubmitBtn = document.getElementById('finalSubmitBtn');
const emailField = document.getElementById('emailField');
const emailInput = document.getElementById('emailInput');
const submitForm = document.getElementById('submitForm');

function openModal(m) { m.classList.add('show'); }
function closeModal(m) { m.classList.remove('show'); }

function selectFile(file) {
  fileNameDisplay.textContent = '📎 ' + file.name;
  chosenFileLabel.textContent = '📎 ' + file.name;
  closeModal(uploadModal);
  openModal(emailModal);
  emailField.focus();
}

if (openUploadBtn) {
  openUploadBtn.addEventListener('click', () => openModal(uploadModal));
}

uploadZone.addEventListener('click', () => fileInput.click());

fileInput.addEventListener('change', () => {
  if (fileInput.files.length) selectFile(fileInput.files[0]);
});

['dragover', 'dragleave', 'drop'].forEach(evt => {
  uploadZone.addEventListener(evt, e => {
    e.preventDefault();
    uploadZone.classList.toggle('drag', evt === 'dragover');
  });
});

uploadZone.addEventListener('drop', e => {
  if (e.dataTransfer.files.length) {
    fileInput.files = e.dataTransfer.files;
    selectFile(e.dataTransfer.files[0]);
  }
});

cancelUploadBtn.addEventListener('click', () => closeModal(uploadModal));

backToUploadBtn.addEventListener('click', () => {
  closeModal(emailModal);
  openModal(uploadModal);
});

finalSubmitBtn.addEventListener('click', () => {
  if (!emailField.value || !emailField.checkValidity()) {
    emailField.reportValidity();
    return;
  }
  emailInput.value = emailField.value;
  submitForm.submit();
});

// إغلاق المودال لو ضغط برّه الصندوق
[uploadModal, emailModal].forEach(m => {
  m.addEventListener('click', e => { if (e.target === m) closeModal(m); });
});

// مودال تكبير صورة الحل
const solutionThumb = document.getElementById('solutionThumb');
const solutionModal = document.getElementById('solutionModal');
if (solutionThumb && solutionModal) {
  const closeSolutionBtn = document.getElementById('closeSolutionBtn');
  const lightboxImg = document.getElementById('lightboxImg');

  solutionThumb.addEventListener('click', () => openModal(solutionModal));
  closeSolutionBtn.addEventListener('click', () => closeModal(solutionModal));
  solutionModal.addEventListener('click', e => { if (e.target === solutionModal) closeModal(solutionModal); });

  // دوس على الصورة نفسها عشان تكبّر/تصغّر (zoom toggle)
  lightboxImg.addEventListener('click', () => lightboxImg.classList.toggle('zoomed'));
}
</script>
</body>
</html>
