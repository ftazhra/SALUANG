# Dokumentasi Kode SALUANG

Dokumen ini merangkum alur yang ditemukan pada kode saat ini. Keterangan yang belum
terhubung atau belum dapat diuji ditandai sebagai catatan, bukan dianggap sudah
berfungsi.

## 1. Struktur folder Laravel

| Folder | Fungsi |
| --- | --- |
| `app/Http/Controllers/` | Menerima request, memeriksa input dan hak akses, lalu mengatur respons halaman atau API. |
| `app/Models/` | Model Eloquent yang mewakili data dan relasi database. |
| `app/Services/` | Logika yang dipakai bersama, misalnya penghitungan nilai, akses siswa, chat, dan notifikasi. |
| `app/Console/Commands/` | Perintah Artisan untuk pekerjaan administrasi. |
| `bootstrap/` | Inisialisasi aplikasi Laravel. |
| `config/` | Konfigurasi aplikasi dan layanan. |
| `database/migrations/` | Riwayat definisi tabel dan perubahan struktur database. |
| `database/seeders/` | Kode pengisian data awal; tidak dijalankan dalam pemeriksaan ini. |
| `public/css/`, `public/js/`, `public/images/` | Aset yang dimuat browser. |
| `resources/views/` | Template Blade dan partial untuk landing page, autentikasi, dan dashboard. |
| `routes/web.php` | Route halaman, API berbasis web, middleware, dan pengarah dashboard. |
| `storage/` | File yang diunggah dan file runtime Laravel. |
| `tests/` | Pengujian otomatis. Pengujian database tidak dijalankan karena isolasi database testing belum diverifikasi. |

## 2. Alur aplikasi

Secara umum:

1. Browser mengirim request ke route di `routes/web.php`.
2. Middleware memeriksa sesi dan, untuk route tertentu, peran pengguna.
3. Controller memvalidasi request dan memanggil model atau service.
4. Model Eloquent membaca atau menyimpan data melalui koneksi database aplikasi.
5. Controller mengembalikan view Blade atau JSON.
6. Blade memuat CSS/JavaScript dari `public/`; JavaScript mengatur interaksi pada browser.

Dashboard dipilih melalui route bernama `dashboard`. Tampilan dashboard menggunakan
view siswa, guru, atau orang tua sesuai peran pengguna.

## 3. Autentikasi dan peran

Peran aplikasi adalah `siswa`, `guru`, dan `orang_tua`.

- Siswa masuk menggunakan NISN.
- Guru masuk menggunakan NIP/NIPK.
- Orang tua masuk menggunakan username atau nomor telepon.
- Kata sandi diproses oleh Laravel dan sesi diperbarui saat login berhasil.
- Pendaftaran dan login ditangani oleh `app/Http/Controllers/AuthController.php`.
- Form autentikasi berada di `resources/views/auth/`; interaksi pilihan peran dan
  tombol lihat kata sandi berada di `public/js/auth.js`.
- Route dashboard mengarahkan pengguna ke `resources/views/siswa/dashboard.blade.php`,
  `resources/views/guru/dashboard.blade.php`, atau
  `resources/views/orang-tua/dashboard.blade.php`.

**Catatan implementasi:** pendaftaran mengisi `is_approved` dengan `false`, tetapi
alur login yang diperiksa tidak memeriksa status tersebut. Verifikasi kebijakan
persetujuan akun perlu dilakukan sebelum mengubah autentikasi.

## 4. Pendaftaran Guru dan undangan

Form pendaftaran menampilkan kolom berbeda menurut peran. JavaScript mengatur
kolom yang terlihat dan menonaktifkan kolom yang tidak digunakan. Controller tetap
menjadi sumber validasi dan penyimpanan.

Ada dua bagian kode terkait undangan Guru:

- `app/Console/Commands/CreateTeacherInvitation.php` dapat membuat kode undangan
  acak dan menyimpan hash, sekolah, serta masa berlaku.
- `database/migrations/2026_11_15_000001_create_teacher_invitations_table.php`
  mendefinisikan tabel undangan.
- Namun, alur pendaftaran yang saat ini diperiksa masih memakai kode tetap
  `SMPN21` dan sekolah tetap “SMP Negeri 21 Banjarmasin”; alur tersebut belum
  memakai tabel undangan dinamis.

Dengan demikian, command dan tabel undangan tersedia di kode, tetapi belum menjadi
alur pendaftaran Guru yang terhubung.

## 5. Cakupan sekolah

`app/Services/TeacherStudentAccessService.php` membatasi daftar siswa Guru menurut
kelas penugasan dan kecocokan nama sekolah. Tabel penugasan yang ada menyimpan
`teacher_id` serta `class_name`, bukan ID sekolah.

Materi Guru disimpan menurut pemilik (`user_id`). Sementara itu, endpoint materi
Siswa yang diperiksa mengambil bab dan subbab terbit tanpa filter sekolah. Karena
itu, isolasi sekolah untuk feed materi Siswa belum tampak diterapkan pada endpoint
tersebut. Dokumentasi ini mencatat perilaku kode; tidak ada perubahan akses atau
database yang dilakukan.

## 6. Materi dan media pembelajaran

- Guru mengelola bab, subbab, publikasi, dan blok konten materi.
- Konten dapat berisi teks, gambar, video, atau callout.
- Gambar pengantar dan media subbab disimpan sebagai path file; unggahan yang
  dikelola aplikasi menggunakan disk `public`.
- Materi Siswa mengambil materi yang tersedia dan menyajikannya melalui halaman
  ringkasan, bab, serta subbab.
- File utama:
  - `app/Http/Controllers/Guru/MaterialController.php`
  - `app/Http/Controllers/Siswa/MaterialController.php`
  - `app/Models/Chapter.php`
  - `app/Models/SubchapterMedia.php`
  - `resources/views/guru/partials/materi.blade.php`
  - `resources/views/siswa/partials/ringkasan-materi.blade.php`
  - `resources/views/siswa/partials/subbab-materi.blade.php`
  - `public/js/guru-materi.js`
  - `public/js/siswa-materi.js`
  - `public/css/guru-materi.css`
  - `public/css/siswa-materi.css`

Tampilan media Siswa memiliki aturan responsif tersendiri. Perubahan lanjutan harus
mempertahankan aliran teks di samping gambar serta daftar bullet dan penomoran.

## 7. Kuis

Soal kuis mendukung tiga tipe:

- `multiple_choice` — pilihan ganda.
- `drag_drop` — memasangkan item dengan area tujuan melalui drag-and-drop.
- `matching` — menghubungkan pasangan item.

Guru mengelola soal melalui controller dan halaman Kuis Guru. Siswa menerima soal,
mengirim jawaban, dan hasilnya diperiksa di server. Skor kuis dihitung sebagai
`jumlah benar / jumlah soal × 100`; hasil dapat disimpan bersama jumlah soal,
jawaban benar, skor, dan waktu selesai.

File utama:

- `app/Http/Controllers/Guru/QuizQuestionController.php`
- `app/Http/Controllers/Siswa/QuizController.php`
- `app/Models/QuizQuestion.php`
- `app/Models/StudentAssessmentResult.php`
- `resources/views/guru/partials/kuis.blade.php`
- `public/js/guru-kuis.js`
- `public/js/guru-matching-preview.js`
- aset kuis Siswa dimuat dari `resources/views/siswa/dashboard.blade.php` dan
  `public/js/siswa.js`/`public/js/siswa-materi.js` sesuai alur halaman.
- stylesheet terkait: `public/css/guru-kuis.css` dan
  `public/css/siswa-materi.css`.

## 8. Evaluasi dan nilai

Bank Evaluasi Akhir dikelola per Guru. Evaluasi siap digunakan jika memiliki tepat
15 soal pilihan ganda A–D yang lengkap dan terbit. Skor dihitung dari jumlah
jawaban benar dibagi jumlah soal. Attempt disimpan pada tabel evaluasi; service
hasil mengumpulkan data valid untuk pelaporan.

File utama:

- `app/Http/Controllers/Siswa/EvaluationController.php`
- `app/Services/EvaluationResultService.php`
- `resources/views/guru/partials/evaluasi.blade.php`
- `resources/views/guru/partials/nilai.blade.php`
- `resources/views/siswa/partials/evaluasi.blade.php`
- `public/js/guru-evaluasi.js`
- `public/js/guru-nilai.js`
- `public/js/siswa-evaluasi.js`
- `public/css/guru-evaluasi.css`, `public/css/guru-nilai.css`, dan
  `public/css/siswa-evaluasi.css`

## 9. Forum diskusi

Siswa dan Guru memiliki alur daftar/detail diskusi, pembuatan, balasan, edit, dan
hapus dengan controller serta route sesuai peran. Diskusi dapat dikaitkan dengan
bab dan subbab. Balasan mendukung `parent_reply_id` untuk hubungan balasan
bertingkat.

File utama:

- `app/Http/Controllers/Siswa/DiscussionController.php`
- `app/Http/Controllers/Guru/DiscussionController.php`
- model `Discussion` dan `DiscussionReply`
- `resources/views/siswa/partials/forum.blade.php`
- `resources/views/guru/partials/forum.blade.php`
- `public/js/siswa-forum.js`, `public/js/guru-forum.js`
- `public/css/siswa-forum.css`, `public/css/guru-forum.css`
- tabel forum didefinisikan oleh migration forum; relasi balasan bertingkat
  ditambahkan oleh `database/migrations/2026_11_15_100001_add_parent_reply_to_discussion_replies.php`.

## 10. Relasi anak, progres, dan hasil belajar

Pendaftaran orang tua membuat relasi anak berstatus `pending`. Siswa dapat membuat
kode penghubung; orang tua memverifikasi kode satu kali yang kedaluwarsa setelah
10 menit. Setelah verifikasi, relasi menjadi `approved`.

Progres materi mencatat status serta waktu buka/selesai per siswa dan subbab.
Dashboard Orang Tua hanya mengambil progres dan nilai anak melalui relasi yang
disetujui. Rekap menggabungkan progres materi, kuis, evaluasi, aktivitas, dan nilai.

File utama:

- `app/Http/Controllers/ParentLinkController.php`
- `app/Http/Controllers/Siswa/ProgressController.php`
- `app/Services/ParentDashboardService.php`
- `resources/views/orang-tua/partials/aktivitas-anak.blade.php`
- `resources/views/orang-tua/partials/hasil-belajar.blade.php`
- `resources/views/orang-tua/partials/progres-belajar.blade.php`
- `resources/views/orang-tua/partials/progress-cards.blade.php`
- `public/js/orang-tua-aktivitas.js`, `public/js/orang-tua-hasil-belajar.js`,
  `public/js/orang-tua-progres.js`
- `public/css/orang-tua-aktivitas.css`,
  `public/css/orang-tua-hasil-belajar.css`, `public/css/orang-tua-progres.css`

## 11. Chat Guru dan Orang Tua

Chat memakai relasi orang tua–anak–Guru yang telah disetujui. Thread mengaitkan
identitas siswa dan Guru. Pesan disimpan dalam tabel pesan orang tua–Guru; service
mengelola thread, pesan, status baca, dan notifikasi pesan.

File utama:

- `app/Services/ParentTeacherChatService.php`
- controller chat Orang Tua dan route chat di `routes/web.php`
- `resources/views/orang-tua/partials/chat-guru.blade.php`
- `public/js/orang-tua-chat.js`
- `public/css/orang-tua-chat.css`
- migration chat: `database/migrations/2026_10_28_120000_create_parent_teacher_messages_table.php`

## 12. Notifikasi

`app/Services/WebsiteNotificationService.php` menyediakan daftar notifikasi,
jumlah belum dibaca, penandaan dibaca, dan penghapusan. Data notifikasi memiliki
penerima, peran, jenis/sumber, URL tujuan, serta waktu baca.

View dashboard menampilkan notifikasi dan `public/js/website-notifications.js`
menangani interaksi browser. Tampilan bersama berada di
`public/css/website-notifications.css`.

## 13. View, JavaScript, dan CSS utama

| Area | View | JavaScript | CSS |
| --- | --- | --- | --- |
| Landing page | `resources/views/beranda.blade.php` | `public/js/saluang.js`, `public/js/saluang-panduan.js` | `public/css/saluang.css` |
| Login/daftar/pemulihan | `resources/views/auth/` | `public/js/auth.js` | `public/css/auth.css` |
| Siswa | `resources/views/siswa/` | `public/js/siswa*.js` | `public/css/siswa*.css` |
| Guru | `resources/views/guru/` | `public/js/guru*.js` | `public/css/guru*.css` |
| Orang Tua | `resources/views/orang-tua/` | `public/js/orang-tua*.js` | `public/css/orang-tua*.css` |

Dashboard memakai satu halaman Blade dengan partial untuk tiap menu. JavaScript
dashboard berpindah antarbagian dan memanggil endpoint sesuai fiturnya. Controller
memvalidasi akses dan data; model serta service menangani relasi dan aturan
aplikasi; migration mendefinisikan tabel yang digunakan.

### Contoh hubungan model dan tabel

| Fitur | Model/data | Database dan migration yang ditemukan |
| --- | --- | --- |
| Profil dan peran | `User` | Kolom peran/profil pada migration `2026_10_16_073115_add_saluang_fields_to_users_table.php`. |
| Materi | `Chapter`, subbab, `SubchapterMedia` | Media subbab menggunakan migration `2026_10_22_000001_create_subchapter_media_table.php`; file media disimpan di disk `public`. |
| Penugasan kelas Guru | Guru dan penugasan kelas | Tabel `teacher_class_assignments` dari migration `2026_10_28_140000_create_teacher_class_assignments_table.php`. |
| Kuis | `QuizQuestion`, `StudentAssessmentResult` | Tabel `quiz_questions` dan `student_assessment_results`; aktivitas kuis ditambahkan melalui migration `2026_11_01_182000_add_formative_activities_to_quiz_questions.php` dan migration hasil kuis. |
| Forum | `Discussion`, `DiscussionReply` | Tabel forum dari migration `2026_10_18_112532_create_forum_tables.php`; balasan bertingkat dari migration `2026_11_15_100001_add_parent_reply_to_discussion_replies.php`. |
| Chat | thread dan pesan | Tabel `parent_teacher_messages` dari migration `2026_10_28_120000_create_parent_teacher_messages_table.php`. |
| Progres belajar | progres siswa dan subbab | Tabel progres dari migration `2026_10_26_000001_create_student_learning_progress_tables.php`. |
| Notifikasi | `WebsiteNotification` | Tabel `website_notifications` dari migration `2026_11_02_214000_create_website_notifications_table.php`. |
| Undangan Guru dinamis | undangan dan hash kode | Tabel `teacher_invitations` dari migration `2026_11_15_000001_create_teacher_invitations_table.php`; belum digunakan alur registrasi saat ini. |

## 14. Responsive design, perapian, dan komentar

Audit browser pada landing page, login, daftar, dan lupa kata sandi memeriksa lebar
320, 360, 390, 430, 768, 1024, 1366, dan 1920 piksel. Tidak ditemukan horizontal
overflow pada halaman-halaman tersebut. Form daftar juga diperiksa saat memilih
masing-masing peran pada lebar 320 piksel.

Pemeriksaan komponen memakai stylesheet aktual untuk modal Guru (materi, kuis,
evaluasi, forum) dan tabel Siswa/Guru/Orang Tua. Pada delapan lebar di atas, modal
memiliki area gulir vertikal; tabel lebar tetap berada dalam wrapper dengan
`overflow-x: auto`, tanpa memperlebar halaman.

Drawer Guru dan Orang Tua menggunakan breakpoint mobile dan state yang berbeda
dari collapse desktop. Sidebar Siswa tidak diubah. CSS materi, kuis, evaluasi,
forum, chat, serta halaman Orang Tua sudah memiliki breakpoint atau wrapper media
masing-masing; tidak ditambahkan override global baru karena tidak ada kegagalan
yang terukur pada pemeriksaan yang dapat diakses.

Komentar Bahasa Indonesia ditambahkan pada logika penonaktifan kolom form serta
sinkronisasi state drawer/collapse. Tidak dilakukan refactoring besar atau
penghapusan duplikasi yang belum terbukti aman.

## 15. Hasil pengujian dan keterbatasan

- Perintah `node --check` dicoba, tetapi Node.js tidak tersedia pada PATH
  lingkungan ini. JavaScript terkait berhasil dimuat dan dijalankan pada
  pemeriksaan browser; pemeriksaan sintaks Node khusus belum dapat dilakukan.
- Kompilasi view Blade berhasil; cache view dibersihkan kembali setelah pemeriksaan.
- `git diff --check` berhasil.
- Browser memeriksa halaman publik dan autentikasi pada delapan ukuran viewport.
- Komponen modal dan tabel diperiksa di browser menggunakan stylesheet produksi
  dan markup uji tanpa mengakses atau mengubah database.
- Route dashboard memerlukan autentikasi dan mengarahkan sesi tamu ke halaman
  masuk. Karena tidak menggunakan akun atau membuat data simulasi, seluruh konten
  dashboard terautentikasi, data materi aktual, forum, chat, dan alur kuis/evaluasi
  tidak dapat diuji end-to-end.
- PHPUnit/feature test tidak dijalankan karena isolasi database testing belum
  diverifikasi.
- Pemeriksaan ini tidak menjalankan migration, seeder, atau operasi tulis database.
