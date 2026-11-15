# Audit Final SALUANG — File, Database, dan Rencana Reset

**Tanggal audit:** 15 November 2026
**Mode:** pemeriksaan read-only. Tidak ada file yang dihapus atau diubah selain
penambahan laporan ini. Tidak ada perubahan data/skema, migration, seeder, atau
test yang dijalankan.

## 1. Ruang lingkup dan metode

- Inventarisasi sumber proyek Laravel: `app`, `bootstrap`, `config`, `database`,
  `public`, `resources`, `routes`, `tests`, dan file tingkat root. `vendor`,
  `node_modules`, dan `.git` tidak diperlakukan sebagai kode aplikasi untuk
  kandidat penghapusan.
- Pemeriksaan referensi Blade, CSS, JavaScript, Vite, `asset()`, `@include`,
  pemuatan dinamis, serta path aset di kolom database. Referensi runtime yang
  dibentuk di luar repositori tetap menjadi keterbatasan.
- Koneksi aplikasi yang aktif pada saat audit adalah MySQL. Nama database,
  host, username, password, token, dan isi rahasia konfigurasi tidak dicatat.
- Skema, foreign key, hitungan baris, identitas akun, pemilik konten, dan path
  aset dibaca melalui query `SELECT` dan metadata `information_schema`. Tidak
  ada perintah tulis yang dijalankan.
- Database aktif memiliki **35 tabel InnoDB, 309 kolom, dan 46 foreign key**;
  tidak ditemukan view, trigger, atau routine. Tabel `migrations` berisi 44
  baris. Jumlah tersebut adalah kondisi saat audit, bukan pengesahan untuk
  menjalankan migration.
- File `database/database.sqlite` adalah database lokal yang tidak aktif menurut
  konfigurasi aplikasi saat ini. Dibuka dalam mode read-only: 9 tabel, 0 akun,
  2 sesi, dan 3 catatan migration.
- Worktree sudah memiliki banyak perubahan lokal sebelum audit, termasuk dua
  penghapusan file. Perubahan tersebut tidak dikembalikan atau diubah.

## 2. Audit file dan struktur proyek

### 2.1 Kandidat yang perlu ditinjau — bukan daftar penghapusan

| Kandidat | Bukti yang diperiksa | Risiko dan rekomendasi |
| --- | --- | --- |
| `public/css/siswa-before-inline.css` | Nama file tidak ditemukan pada referensi pemuatan Blade, `asset()`, `@vite`, JS/CSS, route, atau sumber aplikasi yang diperiksa. Pencarian kolom teks database juga tidak menemukan nama file ini. Isinya berupa CSS. | Namanya menyerupai snapshot lama, tetapi pemakaian di luar repositori atau pada deployment tidak dapat dikesampingkan. Simpan sampai pemilik memastikan tidak ada penggunaan eksternal. |
| `public/css/siswa-before-refactor.css` | Bukti pencarian sama dengan kandidat di atas; isinya juga CSS. | Jangan hapus hanya berdasarkan nama “before-refactor”. Konfirmasi pemakaian eksternal dan backup dahulu. |
| Fungsi `setupVisualEditor` dan `parseJsonInput` dalam `public/js/guru-evaluasi.js` | Pencarian lintas JS, Blade, aplikasi, dan route menemukan deklarasi, tetapi tidak menemukan pemanggilan. Fungsi berada di callback `DOMContentLoaded`; pemanggilan dinamis dari luar scope belum dapat ditiadakan. | Kandidat fungsi tidak terpakai dengan keyakinan statis menengah. Jangan hapus sebelum memastikan tidak ada pemanggil runtime/eksternal dan menguji alur evaluasi. |
| Fungsi `renderResultDetails` dalam `public/js/siswa-evaluasi.js` | Pencarian menemukan deklarasi dan referensi DOM di dalam fungsi, tetapi tidak menemukan pemanggil. | Risiko perubahan tampilan hasil evaluasi; validasi runtime sebelum tindakan apa pun. |
| `resources/js/auth.js` dan fungsi di dalamnya | Tidak ditemukan sebagai input Vite, import `resources/js/app.js`/`bootstrap.js`, atau sumber pemuatan statis. Layout autentikasi memuat `public/js/auth.js` sebagai gantinya. | Mungkin salinan sumber lama, tetapi pemuatan eksternal/dinamis tetap mungkin. Bandingkan perilakunya dan periksa pipeline deployment sebelum menganggap tidak digunakan. |
| `resources/views/dashboard.blade.php` | Memperluas `layouts.app`, tetapi tidak ditemukan pemanggilan `view('dashboard')` atau rujukan statis lain. | Bisa menjadi halaman fallback yang dipanggil dari luar route yang diperiksa. Pertahankan sampai semua integrasi/deployment memastikan tidak memakainya. |
| `resources/views/welcome.blade.php` | Tidak ditemukan pemanggilan `view('welcome')`; route `/` yang diperiksa menuju `BerandaController` dan `view('beranda')`. | File masih memuat Vite/build asset dan dapat dipakai route/integrasi luar repositori. Kandidat statis saja; jangan hapus sebelum pemakaian eksternal dipastikan. |

Semua kandidat di atas **dipertahankan**. Tidak ditemukan partial Blade yang
jelas tidak dipanggil: partial dashboard Guru, Siswa, dan Orang Tua memiliki
`@include` yang ditemukan; `progress-cards` juga disertakan dari partial
beranda Orang Tua. Halaman lain yang diperiksa mempunyai pemanggilan view statis.
Tidak ditemukan file JavaScript lain yang cukup kuat untuk disebut tidak
terpakai.

### 2.2 Gambar dan aset pembelajaran

- Tidak ada gambar dalam `public/images/` yang direkomendasikan sebagai kandidat
  tidak terpakai. `MaterialController` memindai aset publik dan membentuk daftar
  media secara dinamis; ketiadaan referensi literal bukan bukti bahwa gambar
  tidak digunakan.
- Database memiliki 84 path unggahan media subbab dan satu path gambar pengantar
  bab. **85 dari 85 path lokal yang dirujuk ditemukan pada disk**. Ada 11 entri
  video YouTube tanpa file lokal; ID video disimpan di database.
- Path foto profil Ibu Ayu tersimpan dan file-nya ditemukan. Semua path tersebut
  perlu masuk backup bersama database.
- `public/build/assets/app-CevlcUwI.css` dan
  `public/build/assets/app-Buj5iI_6.js` dirujuk manifest build dan bukan kandidat
  berdasarkan bukti saat ini.
- Worktree sudah menunjukkan `public/images/burung-rangkong.png` terhapus.
  Penghapusan itu **bukan dilakukan dalam audit ini**. Karena aset publik dapat
  ditemukan secara dinamis, status file tersebut perlu ditinjau sebelum
  deployment atau reset. Jangan simpulkan aman untuk dihapus.

### 2.3 Backup, sementara, percobaan, dan status worktree

| Item | Temuan | Rekomendasi |
| --- | --- | --- |
| `storage/app.zip` | Arsip tidak terlacak Git, sekitar 96,8 MB. Isi tidak dibuka atau dianggap sebagai backup terverifikasi. | Pertahankan. Sebelum mengandalkannya, validasi isi, checksum, dan kemampuan restore secara terisolasi. |
| `database/database.sqlite` | Diabaikan Git dan bukan koneksi aktif. Audit read-only menemukan 0 akun, 2 sesi, 3 catatan migration. | Jangan dicampur dengan MySQL aktif atau dihapus tanpa persetujuan; tetap mungkin berguna sebagai database lokal. |
| `.phpunit.result.cache` | Cache hasil PHPUnit yang diabaikan Git. Tidak ada test yang dijalankan dalam audit ini. | Artefak regeneratif, tetapi tidak dihapus. |
| `bootstrap/cache/pac*.tmp` | Ditemukan empat file sementara berawalan `pac` di cache bootstrap. | Kandidat file sementara, tetapi biarkan sampai proses/runtime yang menggunakannya dipastikan selesai. |
| `composer-temp/` | Folder kosong saat diperiksa. | Tidak ada file untuk dinilai; tidak dihapus. |
| `gambar_saluang.zip` | Terlihat sebagai file terhapus pada status Git sebelum audit; file tidak ada di worktree. | Tidak dipulihkan atau dihapus oleh audit. Tinjau perubahan lokal dan isi arsip dari backup bila perlu. |
| `public/images/burung-rangkong.png` | Terlihat sebagai file terhapus pada status Git sebelum audit; file tidak ada di worktree. | Tidak dipulihkan atau dihapus oleh audit; validasi terhadap aset media dinamis sebelum perubahan lain. |

Tidak ada komponen duplikat yang terbukti aman untuk dihapus. Ada beberapa
kemiripan yang harus diperlakukan sebagai **potensi tumpang tindih**, bukan
duplikasi mati:

- `resources/js/auth.js` dan `public/js/auth.js` memiliki peran terkait autentikasi,
  tetapi hanya file publik yang ditemukan dimuat oleh layout autentikasi.
- `evaluation_questions` dan `evaluation_bank_questions` merupakan jalur data
  evaluasi berbeda; service hasil masih memeriksa tabel bank meskipun saat ini
  tabel itu kosong.
- `student_teacher_notes` dan `student_teacher_note_messages` sama-sama dipakai
  alur catatan/komunikasi Guru dengan Siswa.

Tidak ada CSS/komponen yang diubah atau dibersihkan pada audit ini.

## 3. Identitas akun Ibu Ayu yang wajib dipertahankan

Identitas diverifikasi dari baris database, bukan nama tampilan saja:

- **Primary key `users.id`: 3**
- Nama database: **Putri Ayu S.B, S.Pd,. Gr.**
- Peran: `guru`
- Email tersamarkan: `l***@gmail.com`
- Username tersamarkan: berakhiran `Ayu`
- NIP/NIPK tersamarkan: berakhiran `009`
- Sekolah: **SMP Negeri 21 Banjarmasin**
- Mata pelajaran: **Ilmu Pengetahuan Alam**
- `deleted_at`: kosong; akun tidak di-soft-delete.
- `is_approved`: `0`. Nilai ini hanya dicatat dan tidak diubah; kebijakan
  persetujuan/login perlu dipastikan terpisah.

Akun ini **wajib dipertahankan**, termasuk seluruh profil dan path foto profil.
Foto profil yang tersimpan berhasil ditemukan di disk.

## 4. Daftar tabel dan kolom database aktif

Hitungan berikut diperoleh pada saat audit. Daftar kolom mencatat nama kolom
aktual dari MySQL; nilai data sensitif tidak dicantumkan.

| Tabel | Baris | Kolom |
| --- | ---: | --- |
| `cache` | 0 | `key`, `value`, `expiration` |
| `cache_locks` | 0 | `key`, `owner`, `expiration` |
| `chapters` | 1 | `id`, `user_id`, `nomor_bab`, `judul`, `deskripsi`, `is_published`, `created_at`, `updated_at`, `pengantar`, `tujuan_pembelajaran`, `pertanyaan_pemantik`, `gambar_pengenalan_path`, `gambar_pengenalan_judul`, `gambar_pengenalan_keterangan` |
| `discussions` | 1 | `id`, `user_id`, `chapter`, `subchapter`, `title`, `body`, `created_at`, `updated_at` |
| `discussion_replies` | 8 | `id`, `discussion_id`, `user_id`, `parent_reply_id`, `body`, `created_at`, `updated_at` |
| `evaluation_attempts` | 6 | `id`, `user_id`, `question_snapshot`, `answers`, `ends_at`, `submitted_at`, `score`, `correct_count`, `created_at`, `updated_at`, `chapter_id`, `evaluation_bank_id`, `teacher_id` |
| `evaluation_banks` | 1 | `id`, `chapter_id`, `teacher_id`, `class_name`, `title`, `is_published`, `active_slot`, `created_at`, `updated_at` |
| `evaluation_bank_questions` | 0 | `id`, `evaluation_bank_id`, `nomor_soal`, `pertanyaan`, `konten_soal`, `kunci_jawaban`, `pembahasan`, `created_at`, `updated_at` |
| `evaluation_question_archives` | 10 | `id`, `question_id`, `teacher_id`, `question_snapshot`, `created_at`, `updated_at` |
| `evaluation_questions` | 15 | `id`, `user_id`, `nomor_soal`, `tipe_soal`, `pertanyaan`, `konten_soal`, `kunci_jawaban`, `pembahasan`, `is_published`, `created_at`, `updated_at` |
| `failed_jobs` | 0 | `id`, `uuid`, `connection`, `queue`, `payload`, `exception`, `failed_at` |
| `jobs` | 0 | `id`, `queue`, `payload`, `attempts`, `reserved_at`, `available_at`, `created_at` |
| `job_batches` | 0 | `id`, `name`, `total_jobs`, `pending_jobs`, `failed_jobs`, `failed_job_ids`, `options`, `cancelled_at`, `created_at`, `finished_at` |
| `migrations` | 44 | `id`, `migration`, `batch` |
| `parent_link_codes` | 5 | `id`, `student_id`, `code_hash`, `expires_at`, `used_at`, `created_at`, `updated_at` |
| `parent_teacher_messages` | 10 | `id`, `parent_id`, `student_id`, `teacher_id`, `sender_id`, `body`, `created_at`, `updated_at`, `client_message_id` |
| `password_recovery_requests` | 0 | `id`, `user_id`, `status`, `resolved_at`, `created_at`, `updated_at` |
| `password_reset_tokens` | 0 | `email`, `token`, `created_at` |
| `quiz_questions` | 34 | `id`, `subchapter_id`, `nomor_soal`, `activity_type`, `pertanyaan`, `pilihan_a`, `pilihan_b`, `pilihan_c`, `pilihan_d`, `kunci_jawaban`, `activity_payload`, `pembahasan`, `is_published`, `created_at`, `updated_at`, `deleted_at`, `active_nomor_soal` |
| `sessions` | 6 | `id`, `user_id`, `ip_address`, `user_agent`, `payload`, `last_activity` |
| `student_assessment_results` | 35 | `id`, `user_id`, `assessment_type`, `chapter_id`, `subchapter_id`, `total_questions`, `correct_answers`, `score`, `completed_at`, `created_at`, `updated_at` |
| `student_guardians` | 6 | `id`, `student_id`, `guardian_id`, `relationship`, `status`, `verified_at`, `created_at`, `updated_at` |
| `student_quiz_activity_answers` | 2 | `id`, `user_id`, `subchapter_id`, `quiz_question_id`, `answers`, `created_at`, `updated_at` |
| `student_quiz_answers` | 25 | `id`, `user_id`, `subchapter_id`, `quiz_question_id`, `answer`, `is_correct`, `created_at`, `updated_at` |
| `student_quiz_attempts` | 321 | `id`, `user_id`, `subchapter_id`, `status`, `answers`, `review`, `score`, `correct_answers`, `total_questions`, `completed_at`, `created_at`, `updated_at` |
| `student_subchapter_progress` | 12 | `id`, `user_id`, `subchapter_id`, `status`, `opened_at`, `completed_at`, `created_at`, `updated_at` |
| `student_teacher_notes` | 1 | `id`, `student_id`, `teacher_id`, `note`, `created_at`, `updated_at`, `audience`, `published_at`, `assessment_type`, `assessment_result_id` |
| `student_teacher_note_messages` | 5 | `id`, `student_id`, `teacher_id`, `note`, `audience`, `published_at`, `created_at`, `updated_at`, `assessment_type`, `assessment_result_id` |
| `subchapters` | 4 | `id`, `chapter_id`, `nomor_subbab`, `judul`, `konten`, `content_blocks_enabled`, `is_published`, `created_at`, `updated_at` |
| `subchapter_content_blocks` | 76 | `id`, `subchapter_id`, `type`, `position`, `payload`, `media_id`, `created_at`, `updated_at` |
| `subchapter_media` | 95 | `id`, `subchapter_id`, `media_type`, `source`, `storage_path`, `library_path`, `youtube_video_id`, `title`, `caption`, `created_at`, `updated_at` |
| `teacher_class_assignments` | 1 | `id`, `teacher_id`, `class_name`, `created_at`, `updated_at` |
| `teacher_invitations` | 0 | `id`, `school_name`, `code_hash`, `expires_at`, `used_at`, `used_by`, `created_at`, `updated_at` |
| `users` | 16 | `id`, `name`, `email`, `email_verified_at`, `password`, `remember_token`, `created_at`, `updated_at`, `username`, `role`, `nisn`, `nip_nipk`, `kelas`, `sekolah`, `mata_pelajaran`, `nomor_telepon`, `hubungan_anak`, `foto_profil`, `is_approved`, `identity_verified_at`, `profile_photo_path`, `deleted_at` |
| `website_notifications` | 81 | `id`, `recipient_id`, `recipient_role`, `event_key`, `event_type`, `source_type`, `source_id`, `student_id`, `title`, `message`, `target_url`, `read_at`, `created_at`, `updated_at` |

Kolom `users.password`, `remember_token`, `password_reset_tokens.token`, sesi,
isi pesan, kunci jawaban, dan konten soal tidak dibaca atau disalin ke laporan.

## 5. Peta relasi dan foreign key

Semua FK di bawah benar-benar ditemukan di skema aktif. Aturan adalah tindakan
`ON DELETE` database saat ini; bukan instruksi untuk menghapus.

| Tabel induk | Tabel/kolom anak | `ON DELETE` |
| --- | --- | --- |
| `users` | `chapters.user_id` | RESTRICT |
| `users` | `discussions.user_id`, `discussion_replies.user_id` | CASCADE |
| `users` | `evaluation_attempts.user_id` | CASCADE |
| `users` | `evaluation_attempts.teacher_id`, `evaluation_banks.teacher_id` | RESTRICT |
| `users` | `evaluation_questions.user_id` | CASCADE |
| `users` | `parent_link_codes.student_id` | CASCADE |
| `users` | `parent_teacher_messages.parent_id`, `.student_id`, `.teacher_id`, `.sender_id` | CASCADE |
| `users` | `password_recovery_requests.user_id` | CASCADE |
| `users` | `student_assessment_results.user_id` | CASCADE |
| `users` | `student_guardians.student_id`, `.guardian_id` | CASCADE |
| `users` | `student_quiz_activity_answers.user_id`, `student_quiz_answers.user_id`, `student_quiz_attempts.user_id` | CASCADE |
| `users` | `student_subchapter_progress.user_id` | CASCADE |
| `users` | `student_teacher_notes.student_id`, `.teacher_id`; `student_teacher_note_messages.student_id`, `.teacher_id` | CASCADE |
| `users` | `teacher_class_assignments.teacher_id` | CASCADE |
| `users` | `teacher_invitations.used_by` | SET NULL |
| `users` | `website_notifications.recipient_id` | CASCADE |
| `chapters` | `subchapters.chapter_id` | CASCADE |
| `chapters` | `evaluation_banks.chapter_id`, `evaluation_attempts.chapter_id` | RESTRICT |
| `chapters` | `student_assessment_results.chapter_id` | SET NULL |
| `subchapters` | `subchapter_media.subchapter_id`, `subchapter_content_blocks.subchapter_id`, `quiz_questions.subchapter_id` | CASCADE |
| `subchapters` | `student_quiz_activity_answers.subchapter_id`, `student_quiz_answers.subchapter_id`, `student_quiz_attempts.subchapter_id`, `student_subchapter_progress.subchapter_id` | CASCADE |
| `subchapters` | `student_assessment_results.subchapter_id` | SET NULL |
| `discussions` | `discussion_replies.discussion_id` | CASCADE |
| `discussion_replies` | `discussion_replies.parent_reply_id` | SET NULL |
| `evaluation_banks` | `evaluation_bank_questions.evaluation_bank_id` | CASCADE |
| `evaluation_banks` | `evaluation_attempts.evaluation_bank_id` | RESTRICT |
| `quiz_questions` | `student_quiz_activity_answers.quiz_question_id`, `student_quiz_answers.quiz_question_id` | CASCADE |
| `subchapter_media` | `subchapter_content_blocks.media_id` | SET NULL |

### Relasi logis tanpa FK yang harus ditangani eksplisit

- `evaluation_question_archives.question_id` dan `.teacher_id` tidak mempunyai
  FK. Saat ini semua 10 arsip mempunyai soal terkait dan dimiliki Guru ID 3;
  tidak ada arsip yatim.
- `student_teacher_notes.assessment_result_id` dan
  `student_teacher_note_messages.assessment_result_id` tidak mempunyai FK.
  Saat ini ditemukan masing-masing 1 dan 2 tautan; semuanya menunjuk hasil yang
  ada. Hapus catatan terkait sebelum hasilnya pada reset yang kelak disetujui.
- `website_notifications.source_type/source_id` adalah relasi polimorfik dan
  `student_id` juga bukan FK. Semua 81 notifikasi saat ini memiliki sumber
  terpasang: 66 ke hasil siswa, 13 ke soal kuis, dan 2 ke pesan chat. Baris
  notifikasi harus ditangani eksplisit agar tidak menjadi yatim.
- `sessions.user_id` tidak mempunyai FK: ada 1 sesi milik Siswa ID 14 dan 5 sesi
  tamu. Rencana reset harus menghapus sesi yang berkaitan, bukan mengandalkan
  cascade user.
- `discussions.chapter` dan `discussions.subchapter` berupa teks, bukan FK.
- Snapshot soal pada `evaluation_attempts.question_snapshot` menyimpan salinan
  konten, bukan relasi langsung.

Tidak boleh menonaktifkan foreign key. FK `RESTRICT` pada pemilik bab, bank
evaluasi, dan attempt evaluasi adalah pengaman penting untuk mencegah konten
Ibu Ayu ikut terhapus.

## 6. Kepemilikan konten yang harus dipertahankan

Data aktif mengonfirmasi konten pembelajaran saat ini dimiliki Guru ID 3:

| Data pembelajaran | Jumlah | Tindakan rencana |
| --- | ---: | --- |
| Bab | 1 | Pertahankan |
| Subbab | 4 | Pertahankan |
| Blok konten | 76 (36 teks, 33 gambar, 3 video, 4 callout) | Pertahankan |
| Media subbab | 95 (84 unggahan, 11 YouTube) | Pertahankan |
| Path file bab/media | 85 | Pertahankan; semuanya ditemukan di disk |
| Soal/aktivitas kuis | 34 (20 aktif, 14 soft-deleted) | Pertahankan semua, termasuk 14 soft-deleted |
| Soal evaluasi | 15 | Pertahankan |
| Bank evaluasi | 1 | Pertahankan |
| Soal bank evaluasi | 0 | Tabel tetap dipertahankan karena kode masih merujuknya |
| Arsip soal evaluasi | 10 | Pertahankan; semua terkait Ibu Ayu |
| Penugasan kelas Guru | 1 | Pertahankan |

Seluruh 84 media unggahan dan satu gambar pengantar bab berada di disk. Tabel
soal evaluasi bank saat ini kosong sementara `evaluation_questions` berisi 15
soal; keadaan ini bukan alasan untuk menghapus tabel atau kolom karena service
masih mendukung tabel bank.

## 7. Data yang direncanakan untuk dihapus dan hasil dry-run

Identitas target reset ditentukan dengan `users.id <> 3`, bukan pencocokan nama.
Jumlah pengguna saat ini: 16, yaitu 7 Siswa (2 aktif dan 5 soft-deleted), 6
Orang Tua, dan 3 Guru. Dengan mempertahankan Ibu Ayu, terdapat **15 baris akun
non-Ibu-Ayu** sebagai target.

| Tabel/data | Baris yang akan terdampak | Dasar |
| --- | ---: | --- |
| Akun Siswa, Orang Tua, Guru selain ID 3 | 15 | Peran dan identitas dibaca dari tabel `users`; termasuk 5 akun Siswa soft-deleted |
| `discussions` | 1 | Forum yang ada ditargetkan dihapus sesuai permintaan |
| `discussion_replies` | 8 | Balasan komentar diskusi, termasuk 4 oleh Guru dan 4 oleh Siswa |
| `parent_teacher_messages` | 10 | Pesan chat Orang Tua–Guru terkait akun percobaan |
| `student_assessment_results` | 35 | Semua hasil saat ini bertipe kuis dan milik Siswa |
| `evaluation_attempts` | 6 | Riwayat attempt evaluasi oleh Siswa |
| `student_quiz_attempts` | 321 | Riwayat pengerjaan kuis |
| `student_quiz_answers` | 25 | Jawaban pilihan ganda/kuis |
| `student_quiz_activity_answers` | 2 | Jawaban aktivitas kuis |
| `student_subchapter_progress` | 12 | Progres siswa |
| `student_guardians` | 6 | Relasi Siswa–Orang Tua |
| `parent_link_codes` | 5 | Kode pengaitan Orang Tua–Siswa |
| `student_teacher_notes` | 1 | Catatan terkait Siswa ID 4 |
| `student_teacher_note_messages` | 5 | Pesan/catatan terkait Siswa ID 4 |
| `website_notifications` | 81 | 66 notifikasi hasil siswa, 13 notifikasi publikasi soal, 2 notifikasi chat; 15 di antaranya diterima Guru ID 3 tetapi mengacu hasil siswa yang ditarget |
| `sessions` | 6 | 1 sesi akun Siswa dan 5 sesi tamu; tabel tanpa FK ke pengguna |
| **Jumlah baris target lintas tabel** | **539** | Jumlah per tabel di atas; tidak mencakup tabel/konten yang dipertahankan |

Hitungan tersebut adalah **snapshot read-only**, dapat berubah jika aplikasi
masih digunakan. Penghapusan aktual harus didahului dry-run ulang dan
persetujuan eksplisit. Kolom `student_teacher_notes.assessment_result_id` dan
`student_teacher_note_messages.assessment_result_id` masing-masing memiliki 1
dan 2 tautan non-FK ke hasil; ketiganya harus ditangani sebelum hasil dihapus.

Tidak direncanakan untuk dihapus:

- Semua bab, subbab, blok materi, file media, soal, kunci jawaban, pembahasan,
  bank evaluasi, arsip soal, serta penugasan kelas milik Ibu Ayu.
- `evaluation_question_archives` (10 baris), walaupun tabel tidak memiliki FK
  ke soal, karena semuanya terkait Guru ID 3.
- `teacher_invitations` (0 baris), `evaluation_bank_questions` (0 baris),
  migration, cache, job, atau struktur database.
- Data lain yang belum terbukti sebagai data percobaan/aktivitas sementara.

### Mekanisme dry-run

Pemeriksaan saat ini hanya membaca metadata, identitas, relasi, path file, dan
`COUNT(*)`; tidak ada perintah perubahan data. Dry-run ulang sebelum reset harus
tetap berupa query SELECT yang menampilkan:

1. Akun dengan `id`, `role`, serta status soft-delete yang akan menjadi target,
   sambil memastikan akun ID 3 tidak masuk hasil.
2. Baris/ID dan hitungan setiap tabel target di atas.
3. Relasi non-FK dan sumber notifikasi yang akan menjadi yatim jika tidak
   ditangani.
4. Invariant konten yang wajib tetap sama: bab 1, subbab 4, blok 76, media 95,
   soal kuis 34, soal evaluasi 15, arsip 10, dan penugasan kelas 1.

Jika dry-run menemukan pemilik konten selain ID 3, sumber relasi yatim, atau
jumlah yang berbeda dari alasan yang dipahami, **hentikan rencana reset** dan
telusuri data terlebih dahulu. Tidak ada skrip reset atau operasi penghapusan
yang dibuat atau dijalankan pada tahap ini.

## 8. Rencana backup sebelum persetujuan reset

Backup **belum dibuat atau diuji** dalam audit ini. Sebelum tindakan yang
memerlukan persetujuan:

1. Jadwalkan maintenance agar tidak ada penulisan bersamaan dan verifikasi
   kembali koneksi/schema target tanpa mencetak kredensial.
2. Buat backup penuh MySQL yang konsisten. Semua 35 tabel saat ini InnoDB;
   gunakan opsi konsisten seperti `--single-transaction`. Kredensial harus
   diberikan melalui konfigurasi klien yang terlindungi, bukan argumen command
   line atau log.
3. Backup terpisah seluruh `storage/app/public`, aset `public/images`, dan file
   media lain yang dirujuk database. Database saja tidak mencadangkan file.
4. Simpan salinan terenkripsi di lokasi terpisah, catat checksum, dan uji restore
   ke database/lingkungan terisolasi. Jangan pernah menguji restore ke database
   utama.
5. Validasi jumlah akun ID 3, jumlah tabel/data konten, relasi, serta keberadaan
   path media setelah restore.

`storage/app.zip` tidak dianggap sebagai backup sampai isi, kelengkapan, dan
restore-nya diverifikasi.

## 9. Urutan pembersihan yang direkomendasikan — belum dijalankan

Setelah persetujuan eksplisit dan backup terverifikasi, rencana konseptualnya:

1. Jalankan dry-run SELECT baru dan cocokkan daftar target dengan kondisi
   aktual; konfirmasi bahwa akun ID 3 dan seluruh konten/asetnya dikecualikan.
2. Tangani referensi tanpa FK terlebih dahulu: notifikasi, sesi, catatan Guru,
   dan pesan catatan yang terhubung ke hasil.
3. Bersihkan forum secara terencana (balasan lalu diskusi), chat, kode
   pengaitan, dan relasi wali.
4. Bersihkan riwayat/hasil kuis dan evaluasi serta progres sesuai relasi FK.
5. Hapus akun non-ID-3 hanya setelah seluruh FK `RESTRICT` dan relasi lain
   memiliki hitungan nol untuk target; biarkan FK tetap aktif.
6. Verifikasi ulang bahwa konten dan path media Ibu Ayu tetap ada dan tidak ada
   orphan. Jangan mengubah tabel, kolom, migration, atau aset pada proses ini
   tanpa persetujuan terpisah.

Urutan final harus ditinjau lagi saat persetujuan diberikan; cascade hanya
terjadi pada relasi yang tercatat, dan relasi polimorfik/teks tidak terlindungi
oleh FK.

## 10. Risiko, mitigasi, dan batasan

- **Risiko tertinggi:** menghapus akun ID 3 akan memutus atau menghapus seluruh
  konten. Mitigasi: target reset memakai primary key yang sudah diverifikasi,
  dry-run harus mengecualikan ID 3, dan hitungan konten harus sama sebelum/sesudah.
- `chapters.user_id`, pemilik bank evaluasi, dan `evaluation_attempts.teacher_id`
  memiliki FK `RESTRICT`; jangan menonaktifkan FK atau mengakali constraint.
- Notifikasi, sesi, arsip soal, catatan hasil, dan beberapa relasi string tidak
  sepenuhnya terlindungi FK; bersihkan hanya berdasarkan hasil dry-run dan urutan
  yang disetujui.
- Empat belas soal kuis berstatus soft-deleted tetap bagian data Guru ID 3 dan
  tidak boleh dipurge sebagai “data lama” tanpa konfirmasi.
- Seluruh media yang dirujuk ditemukan sekarang, tetapi perubahan file lokal
  sebelumnya termasuk penghapusan gambar dinamis. Pulihkan/validasi perubahan
  worktree tersebut secara terpisah sebelum membuat backup final.
- File kandidat, fungsi kandidat, dan folder sementara di atas hanya hasil
  pemeriksaan statis. Pemanggilan dari deployment, integrasi eksternal, atau
  proses developer di luar repositori belum dapat dibuktikan.
- Tidak ada browser/runtime end-to-end, PHPUnit, atau pengujian query aplikasi
  yang dijalankan. Tidak ada test database yang dijalankan.

**Status akhir:** laporan audit selesai. Tidak ada reset data, penghapusan file,
perubahan akun/konten, ataupun perubahan skema yang dilakukan. Berhenti di tahap
laporan sampai ada persetujuan eksplisit.
