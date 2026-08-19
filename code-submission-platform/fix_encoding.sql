-- شغّل الملف ده لو عندك قاعدة بيانات شغالة بالفعل وبيظهر فيها السؤال العربي علامات استفهام (؟؟؟؟)
-- الملف ده بيظبط الترميز من غير ما يمسح أي بيانات موجودة

USE code_submissions;

ALTER DATABASE code_submissions CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

ALTER TABLE questions CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE submissions CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- ملحوظة: لو الأسئلة اللي كانت متكتبة قبل كده باينة علامات استفهام في القاعدة نفسها
-- (يعني اتخزنت غلط من الأول)، الأمر ده مش هيصلحها؛ هتحتاج تمسحها وتكتبها تاني
-- من صفحة الأدمن بعد ما تشغّل الأمر ده. أما لو كانت متخزنة صح والمشكلة في العرض بس،
-- هتظهر صح فورًا بعد التشغيل.
