# Perbaikan Akses Sekolah SALUANG

## Status

Hardening kode tanpa perubahan database telah diterapkan untuk daftar dan pengelolaan Siswa, nilai, materi, kuis, evaluasi, forum, serta penguncian perubahan sekolah/kelas melalui profil mandiri. **Perlindungan antarsekolah belum dapat dianggap otoritatif atau lengkap** karena identitas sekolah dan penugasan masih berupa teks bebas.

## Perubahan kode

- `app/Services/SchoolAccessService.php` — helper pembatasan sekolah: sekolah kosong ditolak; perbandingan teks dinormalisasi.
- `app/Services/TeacherStudentAccessService.php` — cakupan Guru–Siswa mensyaratkan sekolah yang cocok dan kelas yang ditugaskan; kelas kosong serta Guru yang terhapus ditolak.
- `app/Services/StudentGradeService.php` — daftar, ringkasan, detail, dan jalur ekspor nilai memakai Siswa yang sedang ditugaskan kepada Guru.
- `app/Services/DiscussionAccessService.php` — membatasi topik Bab/Subbab terbit, diskusi, balasan, serta jumlah/status balasan ke sekolah viewer.
- `app/Http/Controllers/Guru/StudentManagementController.php` — daftar, profil, pembaruan, dan penonaktifan akun Siswa dibatasi ke penugasan; kelas tujuan harus ditugaskan dan sekolah tidak dapat dipindahkan lewat formulir ini.
- `app/Http/Controllers/Guru/StudentGradeController.php`, `app/Http/Controllers/Guru/StudentFeedbackController.php` — detail nilai dan catatan dibatasi ke penugasan aktif.
- `app/Http/Controllers/Guru/ProfileController.php`, `app/Http/Controllers/Siswa/ProfileController.php` — menolak perubahan sekolah Guru/Siswa serta kelas Siswa lewat profil mandiri; nilai yang sama tetap dapat dikirim bersama perubahan profil lain.
- `app/Http/Controllers/Siswa/MaterialController.php`, `app/Http/Controllers/Siswa/ProgressController.php` — materi dan progres hanya memuat konten dari Guru satu sekolah.
- `app/Http/Controllers/Siswa/QuizController.php` — akses subbab, attempt, dan endpoint jawaban memeriksa sekolah serta status publikasi.
- `app/Http/Controllers/Siswa/EvaluationController.php` — akses bank/evaluasi dan attempt dibatasi ke Guru satu sekolah.
- `app/Http/Controllers/Guru/DiscussionController.php`, `app/Http/Controllers/Siswa/DiscussionController.php` — daftar, detail, balasan, perubahan/penghapusan diskusi, filter terjawab, dan penghitung balasan dibatasi ke sekolah.

## Tes yang disiapkan, tetapi belum dijalankan

Ditambahkan `tests/Feature/SchoolAccessAuthorizationTest.php` untuk skenario lintas sekolah pada manajemen Siswa/nilai/ekspor, konten pembelajaran, evaluasi, dan forum. Tes terkait yang sudah ada juga disesuaikan dengan kewajiban penugasan aktif:

- `tests/Feature/TeacherStudentManagementTest.php`
- `tests/Feature/StudentLearningProgressTest.php`
- `tests/Feature/EvaluationResultsIntegrationTest.php`
- `tests/Feature/SingleEvaluationTest.php`
- `tests/Feature/ParentContactAuthorizationTest.php`
- `tests/Feature/ParentTeacherChatIntegrationTest.php`
- `tests/Feature/TeacherNotesAuthorizationTest.php`
- `tests/Feature/StudentAssessmentTeacherNotesTest.php`

**Tidak ada tes otomatis yang dijalankan.** Pemanggilan test runner sebelumnya ditolak; tidak dicoba melalui runner atau metode alternatif.

## Pemeriksaan yang benar-benar dilakukan

- `php -l` pada 24 file PHP yang berubah/ditambahkan: **lulus, tidak ada kesalahan sintaks**.
- `git diff --check`: **lulus**. Git hanya memberi peringatan normal tentang konversi CRLF ke LF pada beberapa file.
- Tidak dilakukan pemeriksaan route Laravel, kompilasi Blade, browser, maupun pengujian fungsional.
- Tidak ada database yang diubah; tidak dijalankan migration, seeder, reset, atau restore.
- Tidak ada commit atau push.

## Risiko dan keputusan yang masih diperlukan

1. **Sekolah masih berupa teks bebas nullable, bukan ID sekolah terverifikasi.** Penugasan Guru juga berupa `class_name` teks, bukan relasi kelas/sekolah. Endpoint profil mandiri kini menolak pemindahan sekolah/kelas, tetapi teks tersebut tetap tidak membuktikan keanggotaan secara otoritatif.
2. **Forum menyimpan Bab/Subbab sebagai teks**, bukan foreign key ke konten tertentu. Filter membatasi ke pasangan topik terbit pada sekolah yang sama, tetapi data teks tidak cukup untuk mengidentifikasi konten secara unik ketika nomor Bab/Subbab berulang.
3. **Chat Guru–Orang Tua sengaja tidak diubah** sesuai keputusan `pause_chat`. Pemeriksaan baca-saja sebelumnya menemukan satu thread aktif yang hanya didukung riwayat kuis dan relasi Orang Tua–Siswa berstatus approved, tanpa penugasan kelas Guru yang cocok. Fallback tersebut tetap menjadi risiko terbuka; relasi approved tetap dipertahankan.
4. Untuk jaminan yang kuat, diperlukan keputusan terpisah mengenai identitas sekolah dan alur perubahan/pemindahan yang terverifikasi; relasi sekolah-kelas, penugasan, serta keterkaitan konten/evaluasi/forum; dan kebijakan akses chat. Perubahan skema tidak dilakukan.

## Keadaan worktree

File terhapus yang telah ada sebelum pekerjaan dan `storage/app.zip` yang tidak dilacak tetap tidak disentuh. Tidak ada perubahan pada data atau akun Guru Ibu Ayu (`users.id = 3`).
