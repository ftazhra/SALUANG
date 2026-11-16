# Rencana Penghapusan Akun Percobaan SALUANG — Dry-Run

**Status: simulasi baca-saja; tidak ada penghapusan atau perubahan database.**

## Snapshot yang diperiksa

Snapshot database MySQL dibaca dalam transaksi `READ ONLY` yang di-rollback. Database berisi 35 tabel dan 16 akun: 11 aktif serta 5 soft-deleted. Daftar berikut menggunakan ID eksplisit dari snapshot dan konfirmasi pemilik; tidak menggunakan kondisi target `id != 3`.

Semua 15 akun selain ID 3 dikonfirmasi pengguna sebagai akun percobaan, tetapi dampak relasinya tidak sama. Sebelumnya tidak ditemukan penanda percobaan yang meyakinkan pada identitas tersanitasi, jadi konfirmasi tersebut menentukan status kandidat—bukan bukti bahwa baris terkait aman dihapus tanpa pemeriksaan relasi.

## Daftar kandidat eksplisit dan dampak langsung yang terhitung

Angka di bawah adalah jumlah referensi langsung pada snapshot, bukan jumlah akhir baris yang aman dihapus.

| ID | Peran/status | Referensi penting yang terhitung |
|---:|---|---|
| 4 | Siswa, aktif | 2 relasi anak, 1 kode penghubung, 6 pesan chat sebagai siswa, 2 balasan forum, 6 hasil, 1 attempt evaluasi, 4 progres, 5 attempt kuis/20 jawaban, 1 catatan dan 5 pesan catatan, 15 notifikasi penerima serta 19 referensi `student_id` |
| 5 | Guru, aktif | Tidak ada kepemilikan Bab pada tabel konten yang dihitung; pemetaan percakapan/forum lintas akun tetap belum lengkap |
| 7 | Orang Tua, aktif | 1 relasi Orang Tua–Siswa |
| 8 | Orang Tua, aktif | 1 relasi, 6 pesan chat sebagai Orang Tua/3 sebagai pengirim, 3 notifikasi penerima |
| 9 | Guru, aktif | Tidak ada kepemilikan Bab pada tabel konten yang dihitung; pemetaan percakapan/forum lintas akun tetap belum lengkap |
| 10 | Siswa, soft-deleted | 1 attempt evaluasi |
| 11 | Siswa, soft-deleted | 1 relasi anak, 1 kode penghubung, 3 pesan chat, 1 balasan forum, 14 hasil, 3 attempt evaluasi, 4 progres, 231 attempt kuis, 5 jawaban kuis/2 jawaban aktivitas, notifikasi dan referensi `student_id` |
| 12 | Siswa, soft-deleted | Tidak ada referensi langsung pada tabel yang dihitung; tetap kandidat terkonfirmasi pengguna, tetapi relasi tak langsung belum lengkap |
| 13 | Orang Tua, aktif | 1 relasi, 3 pesan chat sebagai Orang Tua/1 sebagai pengirim, 13 notifikasi penerima |
| 14 | Siswa, aktif | 1 relasi, 1 kode penghubung, 1 pesan chat, 2 balasan forum, 15 hasil, 1 attempt evaluasi, 4 progres, 87 attempt kuis, 15 notifikasi penerima dan 43 referensi `student_id` |
| 15 | Orang Tua, aktif | 1 relasi, 1 pesan chat sebagai Orang Tua/1 sebagai pengirim, 14 notifikasi penerima |
| 16 | Siswa, soft-deleted | 1 relasi Orang Tua–Siswa dan 1 kode penghubung |
| 17 | Orang Tua, aktif | 1 relasi Orang Tua–Siswa |
| 18 | Siswa, soft-deleted | 1 relasi Orang Tua–Siswa dan 1 kode penghubung |
| 19 | Orang Tua, aktif | 1 relasi Orang Tua–Siswa |

**Ringkasan status:** 10 kandidat aktif (Siswa 4, 14; Guru 5, 9; Orang Tua 7, 8, 13, 15, 17, 19) dan 5 kandidat soft-deleted (Siswa 10, 11, 12, 16, 18). Tidak ada kandidat yang dihapus dalam dry-run.

## Akun dan konten yang wajib dilindungi

`users.id = 3` (Guru Ibu Ayu) tidak termasuk kandidat. Snapshot mengonfirmasi akunnya aktif dan belum soft-deleted. Data yang dimiliki/terkait langsung mencakup:

- 1 Bab, 4 Subbab, 76 blok konten, 95 entri media, dan 34 soal kuis termasuk yang soft-deleted.
- 15 soal evaluasi, 1 bank evaluasi, 10 arsip soal, dan 1 penugasan kelas.
- 10 pesan chat sebagai Guru, 5 pesan sebagai pengirim, 1 diskusi, 4 balasan, 1 catatan Guru, 5 pesan catatan, dan 15 notifikasi penerima.

Konten milik ID 3 tidak boleh menjadi target atau efek samping penghapusan. Pemeriksaan langsung untuk memastikan pasangan setiap pesan, balasan forum, catatan, dan notifikasi antara ID 3 dengan masing-masing kandidat **tidak dapat diselesaikan**: permintaan query relasi terperinci ditolak dan tidak dicoba ulang. Karena itu, angka hubungan di atas tidak membuktikan bahwa data ID 3 bebas dari cascade kandidat.

## Risiko foreign key dan soft delete

Metadata foreign key MySQL menunjukkan:

- **RESTRICT:** `chapters.user_id`, `evaluation_banks.teacher_id`, dan `evaluation_attempts.teacher_id`. Snapshot tidak menunjukkan baris kepemilikan/penugasan tersebut pada kandidat Guru 5 atau 9; ID 3 memiliki Bab dan bank. RESTRICT bukan pengaman lengkap untuk data yang memakai CASCADE atau relasi logis.
- **CASCADE:** termasuk diskusi dan balasan, relasi Orang Tua–Siswa, pesan chat (keempat kolom akun), hasil/attempt/jawaban/progres Siswa, catatan Guru, penugasan kelas, dan notifikasi penerima. Penghapusan akun dapat menghapus data terkait yang juga melibatkan pengguna lain.
- **SET NULL:** `teacher_invitations.used_by`.
- Sebagian kaitan adalah **relasi logis tanpa FK**. Contohnya `website_notifications.student_id`, `evaluation_question_archives.teacher_id`, `sessions.user_id`, serta Bab/Subbab forum yang disimpan sebagai teks. Baris terkait dapat tertinggal atau menjadi yatim bila hanya mengandalkan cascade.

Model `User` menggunakan `SoftDeletes`. Soft delete mempertahankan baris akun dan nilai identitasnya. Indeks unik aktual database mencakup `email`, `username`, `nisn`, dan `nip_nipk`, tanpa pengecualian `deleted_at`; registrasi juga memvalidasi keunikan kolom-kolom tersebut. Jadi, identitas non-null pada akun soft-deleted **tidak bisa dipakai ulang**. Penghapusan permanen akan membebaskan nilai unik, tetapi dapat memicu cascade di atas.

## Rencana aman sebelum eksekusi

1. Jangan mengubah atau menghapus akun ID 3, kontennya, medianya, penugasannya, atau semua relasi yang setelah pemetaan terbukti memuat kontribusi/data ID 3.
2. Sebelum menetapkan target baris anak, dapatkan pemetaan terperinci kandidat–relasi–pihak lain, khususnya chat/forum/catatan/notifikasi. Tangani relasi tanpa FK secara eksplisit; jangan mengasumsikan cascade akan membersihkannya.
3. Hitung dampak kandidat **per ID eksplisit** di tabel anak dan cocokkan semua FK serta relasi logis. Hentikan rencana bila ada pemilik tidak dikenal, referensi yatim, atau data ID 3 yang berpotensi ikut terhapus.
4. Setelah target dan penanganan data terkait disetujui terpisah, siapkan backup terverifikasi dan uji pemulihan hanya di lingkungan terisolasi.
5. Jika penghapusan permanen nanti disetujui, gunakan allowlist persis ID `4, 5, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19`; jangan gunakan klausa luas `id != 3`. Foreign key harus tetap aktif.
6. Setelah eksekusi yang kelak disetujui, validasi bahwa akun ID 3 dan seluruh kontennya tetap utuh, tidak ada referensi yatim, semua identitas target sudah bebas, dan tidak ada akun di luar allowlist yang berubah.

## Batas dry-run

Satu snapshot baca-saja berhasil mencatat jumlah akun, indeks unik, FK, kepemilikan konten, dan hitungan referensi per ID. Query lanjutan untuk menghubungkan setiap baris kandidat dengan pihak lain/ID 3 ditolak; karena itu **estimasi cascade akhir dan pemisahan data belum lengkap**. Tidak dijalankan `DELETE`, `UPDATE`, `TRUNCATE`, `DROP`, migration, seeder, reset, atau backup. Tidak ada perubahan kode, database, commit, atau push.

**Kesimpulan:** 15 akun adalah kandidat sesuai konfirmasi pengguna, tetapi saat ini belum tersedia pemetaan relasi yang cukup untuk menyatakan penghapusan permanen aman. Laporan ini hanya rencana dry-run, bukan persetujuan atau instruksi eksekusi.
