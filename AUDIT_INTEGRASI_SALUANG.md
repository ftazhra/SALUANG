# Audit Integrasi SALUANG

Tanggal audit: 28 Oktober 2026  
Ruang lingkup: route, controller, model, service, middleware, migration, JavaScript dashboard, kontrak Blade, dan test Laravel. Audit ini hanya membaca kode dan menjalankan route listing serta test pada SQLite in-memory. Tidak ada kode aplikasi, CSS, markup, maupun database aplikasi yang diubah.

## Ringkasan

Fondasi integrasi materi, kuis Subbab, dan progres Siswa sudah tersedia dan memiliki test yang lulus. Integrasi belum end-to-end: hasil Evaluasi Akhir tidak masuk ke sumber nilai yang dibaca Guru/Orang Tua, modul chat Guru-Orang Tua belum memiliki API yang terdaftar, beberapa jenis aktivitas tidak pernah dibuat, dan catatan Guru saat ini juga tersedia bagi akun Siswa.

**Prioritas tertinggi** adalah menyatukan penyimpanan hasil evaluasi, menyelesaikan otorisasi dan API chat dua arah, serta menetapkan batas akses catatan. Perbaikan itu dapat diarahkan ke controller, service, route, dan JavaScript saja; tidak ada kebutuhan awal untuk mengubah CSS atau layout.

## Temuan Prioritas

### P1 - Hasil Evaluasi tidak terintegrasi dengan sumber nilai bersama

Siswa menyimpan percobaan dan nilai Evaluasi hanya ke `evaluation_attempts`. Sementara itu halaman Nilai Guru dan dashboard Orang Tua membaca `student_assessment_results`. Tidak ada penyalinan/relasi hasil akhir Evaluasi ke sumber yang dibaca kedua dashboard. Akibatnya evaluasi dapat dinilai bagi Siswa, tetapi hasilnya tidak muncul sebagai hasil yang sama untuk Guru dan Orang Tua. [EvaluationController](app/Http/Controllers/Siswa/EvaluationController.php#L19), [StudentGradeController](app/Http/Controllers/Guru/StudentGradeController.php#L45), [ParentDashboardService](app/Services/ParentDashboardService.php#L248), [migration evaluation attempts](database/migrations/2026_10_27_000001_create_evaluation_attempts_table.php)

### P1 - Chat Orang Tua-Guru tidak memiliki route dan endpoint Guru

JavaScript Orang Tua memanggil `/orang-tua/chat/teachers` dan `/messages`, tetapi `route:list --path=chat` tidak menemukan satu pun route. `ChatController` hanya mengimplementasikan endpoint Orang Tua; tidak ada endpoint yang dipakai Guru untuk membaca atau membalas percakapan. Pada sisi Guru, handler submit chat hanya mencegah submit dan form masih disabled dengan keterangan API belum tersedia. Migration tabel pesan sudah ada, tetapi belum membuat alur percakapan berfungsi. [ChatController](app/Http/Controllers/OrangTua/ChatController.php), [routes](routes/web.php), [JS Chat Orang Tua](public/js/orang-tua-chat.js#L1), [JS Hubungi Orang Tua Guru](public/js/guru-orang-tua.js#L1), [Blade Hubungi Orang Tua Guru](resources/views/guru/partials/orang-tua.blade.php)

### P1 - Catatan Guru dapat dibaca oleh Siswa

Route `siswa.teacher-notes.index` membuka endpoint yang mengembalikan catatan Guru untuk Siswa yang sedang login. Parent dashboard juga membaca catatan dari tabel yang sama, tetapi skema tidak memiliki pembeda visibilitas. Ini tidak memenuhi batas bahwa catatan yang ditujukan khusus kepada Orang Tua tidak boleh terlihat oleh Siswa. Sebelum mengubah akses, perlu dipastikan apakah seluruh catatan memang untuk Orang Tua atau ada jenis catatan yang sengaja dibagikan kepada Siswa. [TeacherNoteController](app/Http/Controllers/Siswa/TeacherNoteController.php), [route catatan](routes/web.php#L556), [StudentFeedbackController](app/Http/Controllers/Guru/StudentFeedbackController.php), [migration catatan](database/migrations/2026_10_26_160000_create_student_teacher_notes_table.php)

### P1 - Evaluasi belum mendukung seluruh jenis soal yang didefinisikan skema

Migration mengizinkan `pilih_gambar`, tetapi validasi Guru, pembagian tipe nomor soal, tampilan editor, dan penilaian Siswa hanya mendukung `pilihan_ganda`, `drag_drop`, serta `menghubungkan`. Jenis `pilih_gambar` tidak dapat dipublikasikan melalui alur aktif dan tidak punya aturan penilaian yang terverifikasi. Selain itu, pengambilan soal Siswa membaca semua soal terpublikasi lintas Guru; tabel menyimpan `user_id` Guru, tetapi tidak ada relasi Guru-mata pelajaran/kelas yang menentukan bank soal yang harus diterima seorang Siswa. [migration soal evaluasi](database/migrations/2026_10_20_034421_create_evaluation_questions_table.php), [EvaluationController Guru](app/Http/Controllers/Guru/EvaluationController.php#L43), [EvaluationController Siswa](app/Http/Controllers/Siswa/EvaluationController.php#L19), [JS evaluasi Guru](public/js/guru-evaluasi.js#L1)

### P1 - Aktivitas Evaluasi dan Diskusi tidak tersedia pada filter Aktivitas Anak

UI menyediakan filter Materi, Kuis, Evaluasi, dan Diskusi. Payload aktivitas hanya dibangun dari progres Subbab dan jawaban kuis; tidak ada event evaluasi maupun diskusi. Evaluasi disimpan terpisah di `evaluation_attempts`, sedangkan diskusi memakai tabel forum tanpa join aktivitas ke anak. Filter Evaluasi/Diskusi karenanya akan selalu kosong. [ParentDashboardService](app/Services/ParentDashboardService.php#L175), [filter Blade](resources/views/orang-tua/partials/aktivitas-anak.blade.php), [JS filter aktivitas](public/js/orang-tua-aktivitas.js#L1), [migration forum](database/migrations/2026_10_18_112532_create_forum_tables.php)

### P2 - Dashboard Orang Tua membatasi data ke Bab VI

`chaptersForStudent()` memilih nomor Bab 6 (atau nama Bab VI sebagai fallback). Aktivitas dan hasil belajar kemudian hanya dipetakan terhadap Bab/Subbab yang telah dipilih. Dengan demikian dropdown Bab pada Aktivitas Anak, Hasil Belajar, dan Progres tidak dapat menampilkan Bab lain yang dipublikasikan. Progres yang dihitung adalah benar terhadap Subbab Bab VI tersebut, tetapi cakupannya lebih sempit daripada filter Bab yang ditampilkan. [ParentDashboardService](app/Services/ParentDashboardService.php#L75), [progres Orang Tua](resources/views/orang-tua/partials/progres-belajar.blade.php)

### P2 - Definisi nilai Guru dan Orang Tua belum konsisten

Guru belum menetapkan Nilai Akhir/ketuntasan dan setiap baris daftar diberi status `belum_dinilai`. Dashboard Orang Tua, sebaliknya, menghitung rata-rata dari seluruh nilai hasil dan menetapkan status Tuntas memakai ambang 70. Ini menghasilkan arti ringkasan yang berbeda; daftar detail Guru juga hanya memilih hasil terbaru per jenis, sedangkan Orang Tua menyajikan seluruh hasil. [StudentGradeController](app/Http/Controllers/Guru/StudentGradeController.php#L45), [ParentDashboardService](app/Services/ParentDashboardService.php#L33), [ParentDashboardService hasil](app/Services/ParentDashboardService.php#L248)

### P2 - Konflik 15 dan 20 soal hanya sebagian merupakan konfigurasi aktif

Backend, editor Guru, dan dashboard evaluasi aktif konsisten meminta tepat 15 soal, nomor 1-15, dengan durasi 45 menit. Namun halaman informasi publik masih menyebut Evaluasi Akhir Bab 1 berisi 20 soal dan petunjuknya juga menyuruh mengerjakan 20 soal. Jadi tidak ditemukan konfigurasi backend 20 soal yang bersaing; konflik saat ini adalah teks petunjuk lama terhadap evaluasi aktif Bab VI. Teks tersebut merupakan konten Blade yang terlihat dan tidak akan diubah tanpa persetujuan eksplisit tentang perubahan tampilan/konten. [EvaluationController Siswa](app/Http/Controllers/Siswa/EvaluationController.php#L19), [editor Evaluasi Guru](resources/views/guru/partials/evaluasi.blade.php), [informasi publik](resources/views/beranda.blade.php#L317), [petunjuk publik](resources/views/beranda.blade.php#L420)

### P3 - Deklarasi route nilai siswa berulang

Route `GET /guru/student-grades` dan `GET /guru/student-grades/{student}` masing-masing dideklarasikan dua kali dalam `routes/web.php`. `php artisan route:list` hanya menampilkan satu entri efektif untuk masing-masing route, sehingga belum terbukti menimbulkan perilaku ganda saat runtime, tetapi duplikasi meningkatkan risiko perubahan berikutnya tertimpa/menimbulkan ambiguitas. [routes/web.php](routes/web.php#L442), [duplikasi](routes/web.php#L473)

## Alur yang Sudah Berfungsi

- Guru mengelola Bab/Subbab dan blok materi; feed Siswa hanya mengambil Bab/Subbab terpublikasi dari record database yang sama. Media dan blok konten diurutkan dan disajikan melalui kontrak materi. [Guru MaterialController](app/Http/Controllers/Guru/MaterialController.php), [Siswa MaterialController](app/Http/Controllers/Siswa/MaterialController.php), [Subchapter](app/Models/Subchapter.php)
- Kuis Subbab aktif di dashboard Siswa memakai ID Subbab, mengirim jawaban ke server untuk dicocokkan dengan kunci, dan menyimpan jawaban/progres berdasarkan user login. Uniqueness constraint mencegah jawaban duplikat per Siswa/soal. Hasil kuis masuk ke `student_assessment_results` dan terlihat pada detail nilai Guru. [QuizController](app/Http/Controllers/Siswa/QuizController.php), [migration progres dan jawaban](database/migrations/2026_10_26_000001_create_student_learning_progress_tables.php), [test progres](tests/Feature/StudentLearningProgressTest.php)
- Relasi Orang Tua-anak saat pendaftaran berstatus `pending`; service dashboard, kontak Guru, dan chat helper yang ada membatasi pembacaan data anak pada relasi `approved`. [AuthController](app/Http/Controllers/AuthController.php), [ParentDashboardService](app/Services/ParentDashboardService.php#L20), [ParentContactController](app/Http/Controllers/Guru/ParentContactController.php)
- Route utama dibatasi berdasarkan autentikasi dan role. Profil Guru dapat mengubah data/foto/kata sandi; Siswa dapat memperbarui foto. Profil Orang Tua yang terlihat saat ini hanya-baca dan tidak memiliki endpoint profil yang ditemukan.
- Materi dapat ditandai terbuka/selesai per akun dan progres siswa lain tidak ikut berubah. Persentase Siswa memakai Subbab terpublikasi di Bab prioritas (Bab VI bila tersedia). Test progres mencakup alur tersebut.

## Cakupan dan Batas Verifikasi

- Audit tidak menjalankan migrasi pada database aplikasi, tidak membaca/mengubah data live, dan tidak mengubah file aplikasi. Keberadaan serta status publikasi record Bab VI yang benar masih perlu diverifikasi lewat query baca-saja pada database target sebelum integrasi materi diuji terhadap data aktual.
- Tidak ditemukan test khusus untuk Evaluasi Akhir, dashboard/filter Orang Tua, catatan Guru, maupun chat dua arah. Pemeriksaan browser/interaksi visual juga belum dijalankan.
- Hasil test Laravel: **37 passed, 295 assertions**. PHPUnit menggunakan SQLite `:memory:`.
- `php artisan route:list --path=chat --json`: tidak ada route yang cocok.
- `php artisan route:list --path=student-grades --json`: satu route efektif per endpoint, meskipun source mendeklarasikannya berulang.

## Urutan Perbaikan yang Disarankan

1. Keamanan Catatan Guru: pisahkan audiens Orang Tua dan Siswa, batasi pembacaan Parent pada anak approved, dan uji akses role serta isolasi lintas anak. (Tahap 1 selesai; lihat hasil di bawah.)
2. Tetapkan kontrak bersama hasil evaluasi: bagaimana attempt final ditulis/diwakili sebagai hasil assessment, bagaimana riwayat retry diperlakukan, dan sumber yang dibaca Guru/Orang Tua. Tambahkan test isolasi Siswa dan konsistensi nilai sebelum mengubah controller.
3. Sebelum sinkronisasi nomor kontak dan chat, verifikasi relasi Guru-kelas yang benar. Pemeriksaan awal tidak menemukan tabel penugasan Guru ke kelas; saat ini kelas hanya ada pada record Siswa. Jangan tampilkan nomor telepon atau membuka thread berdasarkan kelas sampai kewenangan Guru dapat dibuktikan. Setelah relasi terverifikasi/tersedia, sinkronkan nomor Orang Tua approved dari `users.nomor_telepon` melalui hubungan Orang Tua-anak dan kelas anak.
4. Selesaikan chat dua arah memakai tabel `parent_teacher_messages` yang sudah ada: satu thread berdasarkan Parent, anak, dan Guru yang berwenang; route untuk kedua role; validasi pengirim/penerima dan status approved; urutan/timestamp; serta test isolasi percakapan dan persistensi. Hubungkan JavaScript yang ada tanpa mengubah struktur atau styling.
5. Bangun event Aktivitas dari progres, kuis, evaluasi, dan diskusi dengan identitas Siswa, Bab/Subbab terverifikasi, serta timestamp; pastikan semua filter menerima jenis aktivitas yang tersedia.
6. Tentukan kebijakan bank Evaluasi untuk Guru/kelas dan dukungan `pilih_gambar`. Selaraskan query, validasi, snapshot, scoring, serta test. Pertahankan 15 soal sampai ada keputusan produk berbeda.
7. Tentukan cakupan filter/progres Orang Tua (Bab VI saja atau semua Bab terpublikasi) dan satu definisi agregasi nilai/ketuntasan yang dipakai Guru dan Orang Tua.
8. Setelah persetujuan konten, perbarui petunjuk publik yang masih menyebut 20 soal. Ini berpotensi mengubah teks halaman, jadi perlu persetujuan sesuai batas tampilan.
9. Hapus deklarasi route nilai yang berulang setelah test route ditambahkan.

## Kelanjutan yang Disetujui

Tahap 1 selesai dengan pemisahan catatan berdasarkan audiens tanpa mengubah Blade, CSS, atau JavaScript. Catatan lama mendapat nilai default `orang_tua`; endpoint Siswa hanya mengembalikan catatan ber-audiens `siswa`, sedangkan Parent dashboard hanya mengambil catatan ber-audiens `orang_tua` setelah hubungan anak disetujui. Endpoint pengelolaan tetap dibatasi pada role Guru dan setiap Guru hanya membaca/memperbarui record miliknya. Test terfokus lulus (2 test, 20 assertion), dan regression suite lulus (39 test, 315 assertion) pada SQLite in-memory.

File Tahap 1 yang berubah: migration aditif audiens catatan, `StudentFeedbackController`, `TeacherNoteController`, `ParentDashboardService`, dan test `TeacherNotesAuthorizationTest`. Tidak ada perubahan Blade, CSS, JavaScript, route publik, atau data live.

Pembatasan Guru berdasarkan kelas belum dapat ditegakkan karena relasi penugasan Guru-kelas tidak ditemukan. Saat ini role Guru dan kepemilikan catatan per Guru adalah batas yang dapat dibuktikan oleh implementasi; penetapan kewenangan kelas memerlukan sumber relasi resmi sebelum tahap sinkronisasi kontak/chat.

Tahap sinkronisasi kontak dan chat harus menunggu audit/penetapan relasi Guru-kelas yang dapat diverifikasi. Tidak boleh mengarang penugasan kelas atau mengirim pesan WhatsApp.

## Tahap 2 - Integrasi Nilai Evaluasi

### Pemeriksaan Database dan Migration

- `php artisan migrate:status` dijalankan baca-saja. Pada koneksi database proyek, migration `2026_10_28_130000_add_audience_to_student_teacher_notes` masih **Pending**. Migration `2026_10_28_120000_create_parent_teacher_messages_table` juga Pending. Tidak ada migration yang dijalankan.
- Skema aktif `student_teacher_notes` masih memiliki kolom lama (`id`, `student_id`, `teacher_id`, `note`, `created_at`, `updated_at`) dan unique index `student_teacher_notes_student_id_teacher_id_unique`; kolom `audience` belum ada. Nama index cocok dengan nama yang dihapus oleh migration Tahap 1.
- Kode Catatan Guru kini kompatibel dengan skema lama: catatan legacy tetap bisa dibaca/disimpan sebagai catatan Orang Tua, tidak tampil ke Siswa, dan penulisan audiens Siswa ditolak dengan HTTP 409 sampai migration diterapkan. Setelah migration diterapkan, kedua audiens dapat disimpan terpisah.
- Rollback migration Tahap 1 memeriksa duplikasi pasangan Guru-Siswa sebelum mengubah skema. Jika catatan kedua audiens sudah ada, rollback berhenti dengan pesan yang jelas dan tidak menghapus data.
- Agregat baca-saja database aktif: terdapat 5 attempt Evaluasi selesai, 0 hasil Evaluasi pada `student_assessment_results`; 15 soal Evaluasi aktif terpublikasi dan attempt selesai yang diperiksa dapat diatribusikan melalui snapshot soal. Tidak ada data siswa atau nilai per akun yang dimasukkan ke laporan.
- Database aktif tidak diubah. Sebelum migration Catatan Guru dijalankan, buat backup penuh melalui phpMyAdmin atau `mysqldump` yang sesuai storage engine. Untuk tabel InnoDB, pola perintahnya: `mysqldump --single-transaction --routines --triggers -u <db_user> -p <db_name> > backups/saluang_pre_migration_YYYYMMDD_HHMM.sql`. Verifikasi file backup tidak kosong dan uji restore ke database scratch; setelah itu tinjau SQL migration pada staging. Jangan jalankan `php artisan migrate` pada database aktif tanpa persetujuan.

### Implementasi Tahap 2

- `evaluation_attempts` tetap menjadi sumber kanonis Evaluasi: jawaban disaring terhadap snapshot, kunci dinilai server-side, skor disimpan sekali pada attempt, dan `finish` berulang tidak menggandakan attempt.
- `EvaluationResultService` memproyeksikan attempt selesai ke ringkasan/detail nilai Guru dan Hasil Belajar Parent tanpa menyalin skor ke `student_assessment_results`. Attempt retry yang benar-benar baru tetap tampil sebagai riwayat pengerjaan tersendiri; ringkasan memakai attempt terbaru. Row Evaluasi legacy pada `student_assessment_results` diabaikan pada Parent agar tidak dihitung dua kali terhadap sumber attempt kanonis.
- Nilai kuis pada daftar/detail/ekspor Guru juga menggunakan hasil `student_assessment_results` yang Bab/Subbab-nya dimiliki Guru tersebut. Detail menampilkan seluruh riwayat relevan; Excel dan PDF memakai proyeksi yang sama. Alur pengerjaan kuis Siswa tidak diubah.
- Parent dashboard mengambil attempt untuk ID anak yang didapat melalui relasi `approved`, hanya menampilkan Evaluasi yang dapat dipetakan ke Bab VI terpublikasi, dan menghitung rata-rata bersama hasil assessment yang sudah ada.
- Pembatasan kelas Guru belum dapat dibuat karena tidak ada relasi penugasan Guru-kelas. Akses Guru dibatasi berdasarkan pemilik soal pada snapshot Evaluasi dan pemilik Bab/Subbab untuk kuis. Jika snapshot tidak memiliki tepat satu pemilik yang dapat dibuktikan, attempt tersebut tidak ditampilkan ke Guru.
- Tidak ada perubahan Blade, CSS, layout, JavaScript, atau struktur visual.

File kode Tahap 2 yang berubah: `app/Services/EvaluationResultService.php`, `app/Services/ParentDashboardService.php`, `app/Http/Controllers/Guru/StudentGradeController.php`, `app/Http/Controllers/Guru/StudentGradeExportController.php`, `app/Http/Controllers/Guru/StudentGradePdfController.php`, `app/Http/Controllers/Guru/StudentFeedbackController.php`, `app/Http/Controllers/Siswa/TeacherNoteController.php`, `database/migrations/2026_10_28_130000_add_audience_to_student_teacher_notes.php`, `tests/Feature/EvaluationResultsIntegrationTest.php`, dan `tests/Feature/TeacherNotesAuthorizationTest.php`.

Verifikasi Tahap 2: test evaluasi terfokus lulus, termasuk respons Excel/PDF; regression test kuis dan catatan lulus. Seluruh suite Laravel terakhir: **42 test passed, 362 assertions** pada SQLite in-memory. Diagnostics PHP tidak menemukan error. Migration aktif tetap Pending dan belum diterapkan.

## Tahap 3 - Kontak Orang Tua Berdasarkan Kelas

### Relasi dan Kondisi Data

- Siswa dan Orang Tua berada di tabel `users`, dibedakan oleh `role`. Kelas Siswa tersimpan pada `users.kelas`; nomor Orang Tua ada pada `users.nomor_telepon`.
- `student_guardians` menghubungkan `student_id` dan `guardian_id`, memiliki status `pending`, `approved`, atau `rejected`, dan unik per pasangan akun.
- Tidak ada tabel kelas maupun penugasan Guru-kelas. Kelas aktif yang ditemukan adalah `VII A` dan `viiA`; opsi halaman yang ada hanya `VII A` sampai `VII E`. Data `viiA` tidak dinormalisasi otomatis dan tidak diberi assignment.
- Pembacaan agregat tidak menemukan hubungan Orang Tua berstatus approved dengan nomor telepon. Nomor yang belum memiliki hubungan approved tidak dikembalikan.
- Data kelas aktif mencakup `VII A` serta `viiA`; data kedua tidak cocok dengan opsi kelas pada UI dan tidak diberi assignment otomatis. Belum ditemukan kontak approved bernomor pada database saat audit.
- Nomor disimpan saat registrasi. Dashboard Profil Orang Tua saat ini read-only dan tidak memiliki route/form untuk mengubah nomor. Kontak membaca `nomor_telepon` langsung dari row Orang Tua pada setiap request, jadi setiap perubahan yang dilakukan melalui sumber data profil resmi akan langsung tercermin; belum ada mekanisme profil untuk melakukan perubahan tersebut.

### Perubahan Tahap 3

- Migration aditif `teacher_class_assignments` menyimpan pasangan `teacher_id` dan nama kelas, memiliki unique key dan foreign key ke `users`; tidak membuat assignment default. Rollback menolak menghapus tabel jika assignment sudah tersimpan.
- Command operator `saluang:assign-teacher-class` menetapkan/mencabut kelas secara eksplisit. Command memvalidasi role Guru, kelas UI yang dikenal, dan keberadaan Siswa pada kelas itu. Command tidak dijalankan pada database aktif.
- API `ParentContactController` kini mensyaratkan assignment Guru untuk filter kelas. Endpoint detail juga memeriksa ulang assignment terhadap kelas Siswa pada relasi approved. Jika migration belum tersedia, endpoint mengembalikan 503; Guru tanpa assignment tidak menerima kontak. Nomor terbaru dibaca dari akun Parent, dan multi-anak menghasilkan satu relasi kontak per anak.
- Command revoke tetap dapat mencabut assignment lama walaupun tidak ada lagi Siswa pada kelas itu; pengecekan kelas yang masih memiliki Siswa hanya berlaku untuk assignment baru.
- Blade, CSS, JavaScript, route, dan layout tidak diubah. Endpoint yang ada tetap dipakai.

### Operasional dan Status

Untuk mengelola penugasan setelah migration disetujui dan diterapkan pada target yang telah dibackup, operator memverifikasi penugasan sekolah terlebih dahulu, lalu menjalankan `php artisan saluang:assign-teacher-class <teacher_id> "VII A"`. Beberapa kelas dapat ditetapkan dengan satu pemanggilan per kelas; pencabutan menggunakan `--revoke`. Tidak ada penugasan dibuat otomatis.

File Tahap 3 yang berubah: `app/Console/Commands/AssignTeacherClass.php`, `app/Http/Controllers/Guru/ParentContactController.php`, `database/migrations/2026_10_28_140000_create_teacher_class_assignments_table.php`, dan `tests/Feature/ParentContactAuthorizationTest.php`. Test memastikan registrasi nomor, data terbaru, akses lintas kelas, multi-kelas, multi-anak, approved/pending/rejected, autentikasi/role, command, dan rollback fail-safe.

Pemeriksaan `php artisan migrate:status` pada database aktif menunjukkan tiga migration masih Pending: `2026_10_28_120000_create_parent_teacher_messages_table`, `2026_10_28_130000_add_audience_to_student_teacher_notes`, dan `2026_10_28_140000_create_teacher_class_assignments_table`. Tidak ada migration dijalankan. Backup penuh dan uji restore ke database scratch harus dilakukan sebelum perubahan database disetujui.

Test Tahap 3 terfokus: **5 test passed, 48 assertions**. Suite Laravel penuh setelah Tahap 3: **47 test passed, 410 assertions** pada SQLite in-memory. Diagnostics PHP dan `git diff --check` dicatat setelah validasi akhir. Migration aktif tetap Pending dan belum diterapkan.

## Tahap 4 - Chat Guru dan Orang Tua

### Masalah yang Diperbaiki

- Tabel `parent_teacher_messages` dan helper Orang Tua sudah ada, tetapi belum ada route chat dan controller Guru; form Guru hanya mencegah submit. Chat Orang Tua lama hanya memilih Guru pertama dan tidak membawa konteks anak secara konsisten.
- Sistem kini memakai tabel `parent_teacher_messages` yang sama. Tidak dibuat tabel conversation/chat kedua.

### Route dan Alur

- Orang Tua: `GET /orang-tua/chat/teachers`, `GET /orang-tua/chat/messages?student_id=...&teacher_id=...`, `POST /orang-tua/chat/messages`.
- Guru: `GET /guru/parent-contacts/{studentGuardian}/messages`, `POST /guru/parent-contacts/{studentGuardian}/messages`.
- Semua route berada di middleware `auth`; route Guru juga memakai `EnsureUserIsGuru`. Controller dan service tetap memeriksa role.
- Thread memakai gabungan `parent_id`, `student_id`, dan `teacher_id`. Dropdown Orang Tua (yang dibuat oleh JS lama) membedakan pasangan Guru-anak, sehingga satu Orang Tua dengan beberapa anak memiliki konteks terpisah.
- Service memeriksa ulang hubungan `approved`, role semua akun, dan assignment Guru-kelas pada setiap daftar Guru, baca transcript, serta kirim. Assignment/approval yang dicabut langsung memblokir transcript lama; record pesan tidak dihapus.
- Pesan menggunakan tabel yang ada, disimpan dengan pengirim, Parent, Siswa, Guru, body, timestamp. Riwayat diurutkan waktu lalu ID. Konten dirender sebagai teks (`textContent`), kosong ditolak, panjang maksimal 2.000 karakter.
- Migration aditif `2026_10_28_150000_add_client_message_id_to_parent_teacher_messages_table` menambahkan `client_message_id` UUID nullable unik untuk idempotensi retry. Key yang sudah dipakai membuat rollback menolak dengan aman. JS mempertahankan UUID untuk retry request yang sama.
- JavaScript Parent dan Guru memakai kontrol yang ada. Tidak ada perubahan Blade, CSS, layout, ikon, atau struktur tampilan. Parent memuat transcript saat membuka/memuat ulang thread; tidak ada WebSocket/push atau notifikasi real-time.

### File Tahap 4

`app/Services/ParentTeacherChatService.php`, `app/Models/ParentTeacherMessage.php`, `app/Http/Controllers/OrangTua/ChatController.php`, `app/Http/Controllers/Guru/ParentContactChatController.php`, `routes/web.php`, `public/js/orang-tua-chat.js`, `public/js/guru-orang-tua.js`, `database/migrations/2026_10_28_150000_add_client_message_id_to_parent_teacher_messages_table.php`, dan `tests/Feature/ParentTeacherChatIntegrationTest.php`.

### Status Deployment dan Verifikasi

Pemeriksaan `php artisan migrate:status` menunjukkan empat migration masih Pending: `2026_10_28_120000_create_parent_teacher_messages_table`, `2026_10_28_130000_add_audience_to_student_teacher_notes`, `2026_10_28_140000_create_teacher_class_assignments_table`, dan `2026_10_28_150000_add_client_message_id_to_parent_teacher_messages_table`. Tidak ada migration dijalankan pada MySQL aktif. Karena itu endpoint chat pada database aktif akan gagal tertutup dengan HTTP 503 sampai migration terkait diterapkan setelah backup dan persetujuan; test hanya membuktikan perilaku pada SQLite in-memory.

Test chat terfokus: **5 test passed, 58 assertions**. Suite Laravel penuh setelah Tahap 4: **52 test passed, 468 assertions** pada SQLite in-memory. Test mencakup alur dua arah, persistence lintas request, idempotensi, multi-anak/multi-kelas, pengguna anonim/role salah, thread silang, pending, assignment/approval dicabut, validasi isi, dan rollback migration. JavaScript lolos `node --check`; diagnostics PHP bersih; `git diff --check` bersih. Tidak ada perubahan visual.

## Tahap 5 - Aktivitas Anak

### Sumber Aktivitas

- Materi berasal dari `student_subchapter_progress`, berdasarkan `user_id` anak dan `subchapter_id`; event memakai status serta `completed_at`/`updated_at`/`opened_at` sesuai data yang tersedia.
- Kuis berasal dari `student_quiz_answers` yang di-join ke `quiz_questions`; satu event per jawaban yang tersimpan, memuat status benar/salah dan konteks Subbab dari relasi soal.
- Evaluasi berasal langsung dari `evaluation_attempts` yang `submitted_at` dan `score`-nya terisi. Setiap attempt nyata menghasilkan satu event retry; sumber hasil lama `student_assessment_results` tidak digabungkan sehingga tidak menggandakan attempt. Nilai/benar mengikuti attempt yang sudah dihitung server.
- Diskusi berasal dari `discussions` yang dibuat oleh akun anak dan `discussion_replies` yang ditulis oleh akun anak. Balasan Guru bukan aktivitas anak. Chat Guru-Orang Tua tidak dibaca dari `parent_teacher_messages` dan tidak masuk filter Diskusi.
- Bab/Subbab forum disimpan sebagai string (`bab-N`, `N.M`), bukan foreign key. Service hanya menautkannya jika kode Bab/Subbab persis cocok dengan Bab/Subbab terpublikasi. Jika tidak cocok, aktivitas tetap ada di Semua/jenis tetapi `chapter_id`/`subchapter_id` bernilai null, sehingga tidak muncul ketika filter konteks tertentu dipilih.

### Perubahan Tahap 5

- `activityHistory` kini menggabungkan Materi, Kuis, Evaluasi, diskusi yang dibuat, dan balasan diskusi oleh anak; setiap event beridentitas record, tipe, tanggal sumber, status/hasil jika tersedia, serta konteks terverifikasi jika dapat dipetakan.
- Event diurutkan terbaru dahulu dan tidak dipotong menjadi 200 item. Filter Semua/Materi/Kuis/Evaluasi/Diskusi serta filter Bab/Subbab pada JavaScript yang ada memakai payload tersebut; tidak ada perubahan JS, route, Blade, CSS, atau struktur tampilan.
- Daftar Bab/Subbab untuk filter Parent kini berisi seluruh Bab/Subbab terpublikasi, bukan Bab VI saja. Karena `chapters` dipakai bersama dashboard progres dan hasil belajar, cakupan filter/progres/hasil yang menggunakan array tersebut juga menjadi semua Bab terpublikasi.
- Feed dihitung untuk child yang sudah dipilih oleh `ParentDashboardService` melalui relasi approved; query tidak menerima `student_id` dari request. Uji memastikan query parameter palsu tidak mengalihkan anak dan Parent pending/tidak terkait tidak menerima feed.

File Tahap 5 yang berubah: `app/Services/ParentDashboardService.php` dan `tests/Feature/ParentActivityIntegrationTest.php`. Tidak ada migration baru dan tidak ada migration MySQL aktif yang dijalankan.

Test Tahap 5 terfokus: **3 test passed, 29 assertions**. Suite Laravel penuh setelah Tahap 5: **55 test passed, 497 assertions** pada SQLite in-memory. Syntax JavaScript filter yang ada diperiksa dengan `node --check`; diagnostics PHP bersih. Hasil `git diff --check` dicatat setelah validasi akhir.

Keterbatasan: dashboard Parent masih memilih satu anak approved pertama karena UI saat ini tidak menyediakan pemilih anak. Aktivitas akun tersebut aman dan tervalidasi, tetapi memilih beberapa anak secara interaktif memerlukan perubahan UI yang tidak dilakukan pada tahap ini. Event forum dengan kode Bab/Subbab lama yang tidak cocok dengan materi terpublikasi tidak diberi FK/konteks dan hanya terlihat pada Semua/jenis.

## Prioritas - Integrasi Akun Orang Tua dan Siswa

### Penyebab Terverifikasi

- Registrasi Orang Tua mencari record `users` dengan `role = siswa` dan NISN yang sama. NISN disimpan sebagai string; kedua input NISN divalidasi sebagai 10 digit, sehingga leading zero dipertahankan dan tidak ditemukan normalisasi tipe numerik.
- Jika NISN cocok, `AuthController::register` membuat record `student_guardians`, tetapi statusnya eksplisit `pending` dan `verified_at` null. Kode registrasi tidak memiliki tahap verifikasi lain.
- `ParentDashboardService::dashboard` hanya mengisi `child` jika relasi pertama berstatus `approved`. Karena itu nama anak, aktivitas, nilai, progres, catatan, dan konteks Parent chat tidak disediakan untuk relasi pending. Ini sesuai guard keamanan yang ada.
- Login Parent mengizinkan relasi pending maupun approved agar akun dapat masuk, tetapi hal tersebut tidak mengaktifkan akses data anak.
- Pencarian kode tidak menemukan route/controller/job yang memverifikasi wali, mengirim OTP, mengisi `verified_at`, atau mengubah status relasi menjadi `approved`. Kolom `identity_verified_at`/`email_verified_at` tersedia, tetapi tidak diisi sebagai bukti wali oleh alur yang ditemukan.
- Database aktif dibaca secara agregat saja: 3 akun Siswa, 1 akun Orang Tua, 1 relasi valid-role berstatus pending; akun Orang Tua terdaftar tidak memiliki `is_approved`, `identity_verified_at`, atau `email_verified_at` terisi. Nilai identitas dan kontak tidak dicatat di laporan.

### Keputusan Keamanan

Tidak ada perubahan kode atau status relasi yang dilakukan. Mengubah relasi pending menjadi approved hanya karena NISN cocok tidak memenuhi persyaratan eksplisit bahwa NISN bukan bukti tunggal hak wali. Nomor telepon pada pendaftaran juga diisi oleh pihak yang mengklaim sebagai Orang Tua dan belum dibandingkan dengan sumber tepercaya; mengirim OTP ke nomor itu hanya membuktikan penguasaan nomor yang baru diklaim, bukan kewenangan atas Siswa.

Akibatnya relasi pending yang sudah ada tetap pending dan dashboard tetap menyembunyikan data anak. Ini bukan mismatch format NISN: kode registrasi sudah menemukan Siswa dan menyimpan pasangan relasinya, lalu menahan akses karena tidak ada bukti wali dalam sistem.

### Rancangan Aman Sebelum Auto-Aktivasi

Pilihan tanpa persetujuan manual Guru/Admin yang memenuhi batas keamanan adalah membandingkan klaim Orang Tua dengan sumber kontak wali yang sudah diverifikasi sekolah, lalu mengirim OTP ke kontak tepercaya tersebut. Setelah OTP benar, aplikasi dapat secara atomik mengaktifkan pasangan Parent-Siswa, mengisi waktu verifikasi, dan mencegah duplikasi. Sumber kontak harus berasal dari roster sekolah yang memiliki provenance tepercaya; kolom nomor Parent saat ini bukan sumber tersebut.

Alternatifnya adalah kode undangan satu kali yang dihasilkan pada akun Siswa dan diberikan kepada wali oleh Siswa; mekanisme ini membuktikan persetujuan/penguasaan akun Siswa, tetapi tidak membuktikan identitas legal wali jika kebijakan sekolah menuntutnya. Validasi identitas dari provider resmi juga dapat digunakan bila tersedia.

Sebelum implementasi diperlukan keputusan pemilik sistem tentang sumber bukti wali dan kebijakan bila kontak tidak cocok/tidak tersedia. Jika memilih OTP roster, kemungkinan file yang perlu ditambah/diubah adalah migration aditif untuk kontak wali tepercaya dan catatan challenge (hash, expiry, attempt limit, consumed timestamp), service/verifier, endpoint registrasi/verifikasi, pengiriman OTP, serta JavaScript/form registrasi untuk memasukkan kode. Form saat ini tidak menyediakan langkah kode OTP; menambahkannya berpotensi mengubah UI sehingga memerlukan persetujuan terpisah. Rekonsiliasi relasi pending lama harus menggunakan bukti yang sama, bukan update massal.

Tidak ada migration dijalankan dan Tahap 6 tidak dimulai. Menunggu keputusan tentang sumber verifikasi wali sebelum membuat perubahan akses.