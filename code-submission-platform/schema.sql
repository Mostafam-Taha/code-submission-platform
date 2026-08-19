-- شغّل الملف ده مرة واحدة على قاعدة البيانات بتاعتك عشان تعمل الجداول

CREATE DATABASE IF NOT EXISTS code_submissions CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE code_submissions;

-- جدول السؤال (بيتخزن فيه كل الأسئلة اللي اتكتبت)
CREATE TABLE IF NOT EXISTS questions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    question_text TEXT NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 0,
    solution_image VARCHAR(255) NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- جدول التسليمات (الكود + الإيميل)
CREATE TABLE IF NOT EXISTS submissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    question_id INT,
    email VARCHAR(255) NOT NULL,
    original_filename VARCHAR(255) NOT NULL,
    stored_filename VARCHAR(255) NOT NULL,
    submitted_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (question_id) REFERENCES questions(id) ON DELETE SET NULL
) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- ==========================================================
-- لو عندك قاعدة بيانات شغالة بالفعل من قبل (اتعملت بالسكيما القديمة)
-- شغّل ملف fix_encoding.sql عشان يظبط الترميز، أو شغّل الأسطر دي يدويًا:
--
-- ALTER DATABASE code_submissions CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
-- ALTER TABLE questions CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
-- ALTER TABLE submissions CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
-- ALTER TABLE questions ADD COLUMN is_active TINYINT(1) NOT NULL DEFAULT 0;
-- ALTER TABLE questions ADD COLUMN solution_image VARCHAR(255) NULL;
-- ==========================================================
