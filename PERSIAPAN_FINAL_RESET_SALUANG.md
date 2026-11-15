# Persiapan Final Reset SALUANG

**Status: persiapan saja — tidak ada reset, backup, perubahan data, atau perubahan skema yang dilakukan.**

Dokumen ini mencatat pemeriksaan kode, Git, dan dry-run baca-saja. Angka dry-run adalah snapshot dan bukan persetujuan untuk menghapus data. Ada pemeriksaan database lanjutan yang ditolak oleh izin sesi; bagian yang bergantung padanya ditandai secara jelas dan harus diverifikasi ulang sebelum reset apa pun.

## 1. Verifikasi kode undangan Guru

| Pemeriksaan | Hasil |
| --- | --- |
| Registrasi Guru memakai `teacher_invitations` | **Tidak.** `AuthController::register` masih membandingkan `guru_token` dengan teks tetap `SMPN21`. Tidak ditemukan pemanggilan tabel undangan pada alur registrasi. |
| Pembuatan kode dinamis | Ada command `saluang:invite-teacher {school} {--days=7}`. Kode 16 karakter acak disimpan sebagai hash SHA-256 dan ditampilkan satu kali. |
| Masa berlaku | Command memberi pilihan 1–90 hari dan menyimpan `expires_at`. Namun, alur registrasi tidak memeriksa masa berlaku kode itu. |
| Pemakaian satu kali | Command mengisi `used_at` dengan `null`, tetapi tidak ada consumer registrasi yang memvalidasi atau menandainya telah digunakan. |
| Sekolah Guru | Registrasi masih menetapkan `SMP Negeri 21 Banjarmasin` secara tetap, bukan sekolah dari undangan. |
| Akun setelah kode kedaluwarsa | Login tidak membaca tabel undangan, sehingga masa berlaku kode tidak membatasi akun yang sudah dibuat. |
| Pendaftaran multi-sekolah | Belum didukung secara konsisten: sekolah Guru dan Orang Tua ditetapkan tetap. Siswa menerima nama sekolah dari formulir, tetapi itu bukan relasi sekolah terverifikasi. |

Migration dan command undangan ada di kode. Audit sebelumnya mencatat tabel `teacher_invitations` ada dengan 0 baris, tetapi pemeriksaan aktif untuk memastikan keberadaan tabel dan jumlah barisnya **tidak dapat diulang** pada sesi ini. Command sendiri memeriksa `Schema::hasTable`; tidak ada migration yang dijalankan.

**Rekomendasi:** sebelum mengubah registrasi, sambungkan satu alur validasi atomik yang mengecek hash, sekolah, masa berlaku, dan status pemakaian undangan. Jangan mengaktifkannya sebelum strategi migrasi untuk pendaftaran sekolah lama dan akun ID 3 disetujui. Perubahan autentikasi/registrasi tidak dilakukan.

## 2. Isolasi akses antarsekolah

Pemeriksaan berikut berdasarkan implementasi route, controller, dan service; tidak ada perubahan akses yang dilakukan.

| Endpoint/fitur | Temuan kode | Risiko |
| --- | --- | --- |
| `GET /guru/students`, `GET/PUT/DELETE /guru/students/{student}` | Pemeriksaan role Guru ada, tetapi daftar, detail, pembaruan, dan soft-delete tidak membatasi siswa berdasarkan sekolah atau penugasan Guru. Daftar berisi nama, NISN, kelas, dan email; detail juga memuat sekolah dan nomor telepon. | **Tinggi:** Guru dapat melihat atau mengubah profil Siswa di luar kewenangannya. |
| `GET /guru/student-grades`, `/guru/student-grades/{student}`, ekspor Excel/PDF | `StudentGradeService::overview` mengambil Siswa secara global. Detail memeriksa bahwa target berperan sebagai Siswa, tetapi tidak memastikan penugasan/sekolah. | **Tinggi:** daftar, identitas, nilai, dan ekspor berpotensi mencakup siswa sekolah lain. |
| `GET /siswa/materials` dan endpoint kuis berbasis subbab | Materi dan soal dipilih berdasarkan status publikasi serta keberadaan Guru, bukan sekolah Siswa. Endpoint berbasis ID subbab juga tidak membandingkan sekolah. | **Sedang–tinggi:** materi/kuis sekolah lain dapat diakses oleh Siswa yang sudah login. |
| `/siswa/evaluations/teachers` dan endpoint per Guru | Siswa harus login, tetapi Guru evaluasi dipilih dari konfigurasi global atau satu-satunya Guru dengan soal terbit. Endpoint tidak membatasi berdasarkan sekolah/penugasan. | **Tinggi untuk integritas evaluasi:** soal evaluasi Guru lain dapat terpapar atau dikerjakan. |
| `/forum/discussions` untuk Guru dan Siswa | Opsi bab terbit dan diskusi dimuat tanpa filter sekolah. Bab/Subbab pada diskusi disimpan sebagai teks, bukan relasi sekolah atau FK. | **Sedang:** diskusi dan identitas penulis lintas sekolah berpotensi terlihat/ditanggapi. |
| `/guru/parent-contacts/*` dan `/orang-tua/chat/*` | Relasi Orang Tua–Siswa harus berstatus `approved`. Akses Guru biasanya memakai penugasan kelas dan membandingkan nama sekolah serta kelas, tetapi service juga menerima riwayat kuis selesai sebagai dasar akses. | **Sedang:** jejak hasil kuis lama dapat memberi jalur akses yang tidak sama dengan penugasan aktif; perlu memastikan atribusi hasil dan sekolahnya. |
| Dashboard Orang Tua | Data anak dibatasi oleh relasi yang disetujui, tetapi daftar bab terbit yang tersedia bersifat global. | **Sedang:** konten pembelajaran sekolah lain dapat terlihat, meski relasi anak tetap diperiksa. |

`teacher_class_assignments` menyimpan `teacher_id` dan `class_name`, bukan `school_id` atau ID kelas. `TeacherStudentAccessService` membandingkan teks nama sekolah yang dinormalisasi dan nama kelas. Nama sekolah bebas-teks serta registrasi Guru yang masih hardcoded membuatnya bukan batas isolasi sekolah yang kuat. Penugasan kelas yang terverifikasi tidak sama dengan kepemilikan sekolah yang terverifikasi.

**Kesimpulan:** belum dapat dinyatakan bahwa akses antarsekolah sudah terisolasi. Prioritas tinjauan keamanan adalah endpoint manajemen Siswa/nilai, materi/kuis, serta evaluasi. Perubahan akses memerlukan analisis dampak fitur dan keputusan skema/kebijakan tersendiri.

## 3. Status autentikasi akun Ibu Ayu

Identitas diperiksa berdasarkan baris database `users.id = 3`, bukan pencocokan nama saja. Nama pada baris tersebut adalah Putri Ayu S.B, S.Pd,. Gr.; role `guru`; sekolah `SMP Negeri 21 Banjarmasin`; mata pelajaran Ilmu Pengetahuan Alam. Identifier login tersimpan pada kolom username dan NIP/NIPK; nilainya tidak dicantumkan. Email hanya ditampilkan dalam bentuk tersamarkan.

| Kolom/status | Hasil |
| --- | --- |
| `id`, `role` | `3`, `guru` |
| `is_approved` | `0` (false) |
| `deleted_at` | `NULL` |
| Login sekarang | Login memeriksa role, identifier, dan password. Tidak ditemukan pemeriksaan `is_approved`. |
| Soft delete | Model `User` menggunakan `SoftDeletes`; query Eloquent login tidak memilih akun yang telah soft-deleted. |

Akun ID 3 tidak diubah. Akun tersebut saat ini dapat login meski `is_approved=0`, karena login tidak memeriksa status approval. Jika aturan approval ditambahkan kemudian, ID 3 berisiko kehilangan akses sampai ada keputusan eksplisit mengenai statusnya. Jangan mengubah statusnya sebagai bagian dari reset.

## 4. File yang berstatus terhapus

Kedua file tidak ada di worktree saat pemeriksaan dan ditandai `D` oleh Git; objek versi `HEAD` masih tersedia. Tidak ada pemulihan atau penghapusan tambahan.

| File | Bukti | Penilaian dan risiko |
| --- | --- | --- |
| `gambar_saluang.zip` | Tidak ada pemanggilan nama file yang ditemukan pada kode aplikasi; versi Git berukuran 52.140.402 byte. | Tidak tampak dibutuhkan saat runtime, tetapi bisa merupakan arsip/backup sumber aset. Jangan menganggapnya tidak bernilai atau menghapus riwayatnya sebelum isi dan tujuan arsip ditinjau. |
| `public/images/burung-rangkong.png` | Tidak ditemukan referensi literal dengan path tersebut. Kode dashboard Siswa merujuk path berbeda, `images/evaluasi/burung-rangkong.png`. Pustaka gambar Guru memindai file langsung di `public/images/*`, sehingga file yang hilang sebelumnya dapat menjadi pilihan pustaka. | Belum aman diklasifikasikan sebagai tidak digunakan. Referensi yang disimpan dalam database tidak berhasil diperiksa ulang pada sesi ini. File tidak dipulihkan. |

## 5. Kesiapan backup

- `C:\xampp\mysql\bin\mysqldump.exe` tersedia. Versi client yang diperiksa adalah MariaDB `10.4.32`; kompatibilitas dengan versi server aktif belum dikonfirmasi.
- Folder `storage/app/public` tersedia dengan subfolder `chapters`, `evaluation-images`, `formative-activities`, `profile-photos`, dan `subchapter-media`: 238 file, total 492.689.964 byte pada saat pemeriksaan.
- Ruang kosong drive C: 40.438.784.000 byte pada saat pemeriksaan. Ukuran database dan ukuran total aset lain di `public/images` belum dihitung.
- Tidak ada backup baru yang dibuat. `storage/app.zip` yang tampak di worktree belum diverifikasi isi, kelengkapan, checksum, atau restorasinya dan tidak dianggap backup valid.

### Prosedur backup yang disarankan (belum dijalankan)

1. Jadwalkan maintenance, hentikan penulisan aplikasi, dan konfirmasi koneksi/schema target tanpa mencetak konfigurasi rahasia.
2. Tentukan direktori backup baru di luar web root/repository, dengan nama bertanggal dan unik. Pastikan direktori/berkas tujuan belum ada; jangan menimpa backup sebelumnya.
3. Setelah kompatibilitas client/server dipastikan, buat dump penuh yang konsisten, termasuk objek database yang diperlukan. Berikan kredensial lewat konfigurasi klien yang terlindungi, bukan argumen command line atau log.
4. Arsipkan seluruh `storage/app/public`, aset `public/images`, serta semua lokasi media lain yang dirujuk database. Pastikan setiap keluaran tidak kosong dan ukurannya masuk akal.
5. Catat checksum SHA-256 untuk dump dan setiap arsip media; hitung ulang checksum setelah salinan dipindah.
6. Uji pemulihan hanya pada database dan direktori media testing yang terisolasi, dengan kredensial terpisah. Cocokkan akun ID 3, skema, konten, relasi, dan path media. Jangan pernah menguji restore pada database utama.

## 6. Dry-run terbaru

Dry-run yang berhasil dijalankan hanya menggunakan koneksi baca-saja dan `SELECT`/metadata; kredensial tidak dicetak. Tidak ada file reset, SQL mutasi, atau proses reset dibuat/dijalankan.

| Pemeriksaan | Hasil snapshot terbaru |
| --- | ---: |
| Tabel dasar / kolom / foreign key | 35 / 309 / 46 |
| Akun | 16 total: 7 Siswa (2 aktif, 5 soft-deleted), 3 Guru, 6 Orang Tua |
| Akun selain ID 3 | 15: Siswa ID 4, 10, 11, 12, 14, 16, 18; Guru ID 5 dan 9; Orang Tua ID 7, 8, 13, 15, 17, 19 |
| Diskusi / balasan | 1 / 8 |
| Pesan Guru–Orang Tua | 10 |
| Hasil penilaian Siswa | 35 |
| Attempt evaluasi | 6 |
| Attempt kuis / jawaban / jawaban aktivitas | 321 / 25 / 2 |
| Progres subbab | 12 |
| Relasi Orang Tua–Siswa / kode penghubung | 6 / 5 |
| Catatan Guru / pesan catatan | 1 / 5 |
| Notifikasi | 81 |
| Sesi | 6 |
| **Jumlah aritmetis kandidat pada tabel di atas** | **539 baris** |

Hitungan kepemilikan yang diperiksa dengan syarat `user_id <> 3` atau melalui pemilik Bab tidak menemukan pemilik selain ID 3 pada Bab, Subbab, blok materi, media, soal kuis, soal/bank evaluasi, arsip evaluasi, dan penugasan kelas. Ini bukan bukti bahwa semua kolom kepemilikan null/relasi yatim sudah diperiksa; jumlah konten lengkap dan invariant pra/pasca-reset tetap harus dihitung ulang.

### Pengecualian penting dalam angka 539

- Seluruh 6 baris `sessions` memiliki `user_id = NULL` pada snapshot terbaru. Baris itu tidak terbukti milik pengguna percobaan tertentu dan tidak boleh otomatis dimasukkan ke target penghapusan. Pemeriksaan terdahulu mencatat sesi terautentikasi; perbedaan ini berarti tabel sesi harus ditinjau ulang berdasarkan `last_activity` dan kebijakan kedaluwarsa, tanpa membaca atau menampilkan payload.
- 15 dari 81 notifikasi diterima oleh akun ID 3. Dry-run terbaru menghitung sumber notifikasi secara agregat (66 hasil kuis, 13 publikasi aktivitas, 2 pesan chat), tetapi tidak memetakan setiap notifikasi ID 3 ke sumber tertentu. Tahan 15 notifikasi tersebut sampai keterkaitannya diverifikasi; jangan hapus hanya karena jumlah kandidat total cocok.
- 1 catatan Guru dan 5 pesan catatan tidak termasuk daftar data target yang dijelaskan secara eksplisit. Catatan dapat memuat umpan balik pembelajaran; klasifikasikan dahulu sebelum memasukkannya ke rencana reset.
- Jumlah 539 adalah total kandidat lintas tabel, bukan jumlah baris yang aman untuk dihapus. Dengan pengecualian di atas, belum ada jumlah final yang disetujui.
- Pemeriksaan orphan yang berhasil pada sesi ini menemukan 0 arsip evaluasi tanpa soal, 0 catatan dengan `assessment_result_id` yatim, dan 0 pesan catatan dengan `assessment_result_id` yatim.

Audit sebelumnya melaporkan 539 baris dan rincian lebih lanjut; sesi ini mengonfirmasi kembali hitungan tabel utama, tetapi menemukan perbedaan penting pada atribusi sesi. Snapshot lama tidak boleh dipakai sebagai daftar hapus saat ini.

**Batas pemeriksaan:** permintaan query database lanjutan untuk mengulang peta FK terperinci, memastikan tabel/baris `teacher_invitations`, dan mencari referensi aset di kolom database ditolak oleh izin sesi. Tidak ada upaya ulang. Karena itu status aktif invitation, referensi DB ke file burung rangkong, peta FK terkini per constraint, serta pemilik/penulis terkini beberapa tabel forum/chat belum terverifikasi ulang.

## 7. Data yang harus dilindungi dan calon data reset

### Lindungi tanpa pengecualian

- Baris akun dan profil `users.id = 3`; jangan mengubah `is_approved`, role, atau `deleted_at`.
- Bab, Subbab, seluruh blok konten, media dan path, serta gambar/video yang dirujuk.
- Semua soal kuis termasuk yang soft-deleted, kunci jawaban, pembahasan, dan snapshot konten.
- Soal/bank evaluasi, arsip soal, kunci/pembahasan, dan pengaturan evaluasi.
- Penugasan kelas Guru yang ada.
- Struktur database, migration, file, dan tabel/kolom.

Jumlah konten rinci dalam `AUDIT_FINAL_SALUANG.md` adalah snapshot audit sebelumnya (Bab 1, Subbab 4, blok 76, media 95, soal kuis 34 termasuk 14 soft-deleted, 15 soal evaluasi, 1 bank, 10 arsip, dan 1 penugasan). Angka tersebut **belum dihitung ulang** dalam dry-run terbaru. Jadikan hanya baseline untuk dicocokkan, bukan nilai final.

### Calon data untuk ditinjau sebagai data percobaan

15 akun selain ID 3, forum, chat, hasil/attempt/jawaban, progres, relasi Orang Tua–Siswa, kode penghubung, dan notifikasi tercantum pada tabel dry-run di atas. Semua penghapusan tetap memerlukan persetujuan dan verifikasi kepemilikan/relasi baru. Baris sesi anonim dan catatan Guru ditahan sampai diklasifikasikan.

## 8. Peta relasi dan risiko database

Skema aktif terakhir mengonfirmasi jumlah 46 FK. Tabel berikut merangkum peta FK dari `AUDIT_FINAL_SALUANG.md`; karena query FK terperinci sesi ini ditolak, nama constraint dan aturan `ON DELETE` di bawah belum disegarkan terhadap snapshot terbaru.

| Induk | Tabel/kolom anak | Aturan pada audit sebelumnya |
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

Relasi logis/non-FK yang harus diperiksa eksplisit:

- `evaluation_question_archives.question_id` dan `.teacher_id` tidak memiliki FK.
- `student_teacher_notes.assessment_result_id` dan `student_teacher_note_messages.assessment_result_id` tidak memiliki FK.
- `website_notifications.source_type/source_id` adalah relasi polimorfik; `student_id` juga bukan FK.
- `sessions.user_id` tidak memiliki FK.
- `discussions.chapter` dan `.subchapter` adalah kode teks, bukan FK.
- `evaluation_attempts.question_snapshot` menyimpan snapshot soal, bukan relasi langsung.
- `teacher_class_assignments.class_name` adalah teks, bukan FK ke kelas atau sekolah.
- Path file pada media/blok materi adalah nilai string; keberadaan berkas tidak dijaga foreign key.

Jangan menonaktifkan foreign key. FK RESTRICT terkait pemilik Bab/bank evaluasi dan attempt evaluasi adalah pengaman penting. Reset akun tanpa menghapus konten milik ID 3 harus selalu ditolak oleh dry-run jika ada konflik kepemilikan atau referensi.

## 9. Urutan reset yang disarankan — belum dijalankan

1. Dapatkan persetujuan eksplisit dan lakukan backup penuh database serta media dengan checksum; uji restore hanya di testing terisolasi.
2. Aktifkan maintenance dan jalankan ulang dry-run SELECT. Verifikasi database, ID 3, semua pemilik konten, jumlah target, FK, relasi logis, notification sources, catatan, serta seluruh sesi.
3. Hentikan rencana jika ada pemilik konten selain ID 3, nilai pemilik null/tidak dikenal, referensi yatim, perubahan hitungan yang belum dijelaskan, atau path media yang hilang.
4. Tinjau manual notifikasi ID 3, catatan Guru, seluruh sesi, dan diskusi/chat yang pengarangnya Ibu Ayu sebelum menetapkan target final.
5. Setelah target disetujui, urutkan penghapusan berdasarkan FK yang benar-benar diperiksa ulang: data turunan/tautan logis, pesan dan relasi, jawaban/attempt/hasil/progres, forum, lalu akun non-ID-3. Jangan mengandalkan cascade untuk relasi tanpa FK atau memakai penonaktifan FK.
6. Jalankan pemeriksaan pasca-reset: akun ID 3 dan statusnya tidak berubah; jumlah/identitas Bab, Subbab, blok, media, soal termasuk soft-deleted, evaluasi, arsip, serta penugasan tetap sama; path media valid; target percobaan nol; dan tidak ada orphan.
7. Jika ada perbedaan, hentikan aplikasi dan pulihkan hanya ke lingkungan testing untuk analisis. Pemulihan database utama memerlukan persetujuan terpisah dan prosedur insiden.

## 10. Persetujuan yang masih diperlukan

- Keputusan untuk menghubungkan undangan dinamis dengan registrasi Guru dan kebijakan sekolah multi-sekolah.
- Persetujuan perbaikan batas akses antarsekolah, setelah dampak tiap endpoint ditinjau.
- Keputusan kebijakan `is_approved` yang menjaga akses akun ID 3.
- Peninjauan/persetujuan atas dua file yang berstatus terhapus; khusus gambar, periksa referensi database sebelum menyimpulkan.
- Izin untuk menjalankan pemeriksaan DB read-only lanjutan yang ditolak, guna memverifikasi peta FK, tabel undangan, referensi aset, dan atribusi per baris.
- Persetujuan eksplisit atas daftar target reset terbaru setelah dry-run lengkap, backup dan restore testing berhasil.
- Persetujuan terpisah sebelum menjalankan reset aktual.

## 11. Bukti dan batasan

- Kode ditinjau pada `AuthController`, `CreateTeacherInvitation`, `User`, `TeacherStudentAccessService`, `StudentManagementController`, `StudentGradeService`, controller materi/kuis/evaluasi/forum, `ParentTeacherChatService`, `ParentDashboardService`, dan `routes/web.php`.
- Database dibaca melalui koneksi aktif yang memakai driver Laravel `mysql`; tidak ada kredensial yang dicetak. Hitungan terbaru dicatat pada Bagian 6.
- Git read-only menunjukkan dua file berstatus terhapus di worktree; tidak ada file yang dipulihkan.
- Media dihitung pada `storage/app/public`; tidak semua folder aset publik dihitung.
- Tidak ada backup, restore, reset, seeder, migration, test database, perubahan kode, atau perubahan data yang dilakukan.
- Tidak ada pemeriksaan visual browser atau pengujian runtime fitur dalam tahap ini.
- Peta FK rinci, status aktual tabel undangan, referensi file burung rangkong pada database, nilai total konten saat ini, serta atribusi penulis diskusi/chat perlu diverifikasi ulang sebelum menyetujui penghapusan.

**Keputusan akhir: berhenti pada persiapan. Tidak ada tindakan reset yang aman untuk dijalankan sampai pengecualian dan pemeriksaan ulang di atas diselesaikan serta disetujui.**
