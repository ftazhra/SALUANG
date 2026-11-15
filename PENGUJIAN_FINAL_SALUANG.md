# PENGUJIAN FINAL SALUANG

Tanggal pemeriksaan: 16 November 2026
Lingkungan: worktree lokal SALUANG, server Laravel sementara di `127.0.0.1:8000`, browser Chromium terintegrasi.

## Ringkasan

- Tidak ada gambar pengganti yang dapat dipastikan sesuai untuk sembilan path yang hilang. Tidak ada kode aplikasi atau aset yang diubah atau dipulihkan.
- Halaman publik, login, daftar, lupa kata sandi, dan pratinjau dashboard Siswa diperiksa di browser pada delapan lebar layar yang diminta.
- Dashboard berisi data nyata untuk Guru, Siswa, dan Orang Tua serta alur yang menulis data tidak diuji karena tidak tersedia sesi uji terisolasi. Tidak ada formulir yang dikirim dan tidak ada data database yang diubah.
- Worktree sudah memiliki perubahan lokal sebelum pemeriksaan ini. Perubahan tersebut dipertahankan.

## 1. Pemeriksaan sembilan referensi gambar

Pencarian mencakup referensi Blade, CSS, JavaScript, isi JSON dashboard, serta nama file gambar dengan variasi ekstensi di `public` dan `storage/app/public`. Tidak ditemukan file dengan nama dasar yang sama di lokasi lain.

| Path gambar yang tidak tersedia | Lokasi dan fitur | Temuan dan dampak |
| --- | --- | --- |
| `images/lesson-ekosistem-lahan-basah.png` | `resources/views/siswa/partials/bab.blade.php` pada gambar prompt “Yuk, Pikirkan”; juga key `ecosystem` di JSON `resources/views/siswa/dashboard.blade.php`. | Referensi Blade langsung. Elemen `<img>` memiliki `onerror="this.hidden=true"`, sehingga gambar prompt tidak tampil jika gagal dimuat. Tidak ditemukan pengganti yang terbukti sesuai. |
| `images/lesson-pelestarian-lingkungan.png` | Key `conservation` di JSON `resources/views/siswa/dashboard.blade.php`. | Tidak ditemukan pembacaan key tersebut oleh JavaScript aktif. Tidak ada dampak tampilan yang teramati. |
| `images/evaluasi/air-sungai.png` | Key `airSungai` di JSON dashboard Siswa. | Referensi konfigurasi saja; konsumen aktif tidak ditemukan. |
| `images/evaluasi/burung-rangkong.png` | Key `burungRangkong` di JSON dashboard Siswa. | Referensi konfigurasi saja; konsumen aktif tidak ditemukan. Berbeda dari file `public/images/burung-rangkong.png` yang sudah berstatus terhapus di Git; file terhapus itu tidak dipulihkan. |
| `images/evaluasi/hasil-evaluasi.png` | Key `hasilEvaluasi` di JSON dashboard Siswa. | Referensi konfigurasi saja; konsumen aktif tidak ditemukan. |
| `images/evaluasi/katak.png` | Key `katak` di JSON dashboard Siswa. | Referensi konfigurasi saja; konsumen aktif tidak ditemukan. |
| `images/evaluasi/purun.png` | Key `purun` di JSON dashboard Siswa. | Referensi konfigurasi saja; konsumen aktif tidak ditemukan. |
| `images/evaluasi/saluang.png` | Key `ikanSaluang` di JSON dashboard Siswa. | Referensi konfigurasi saja; konsumen aktif tidak ditemukan. |
| `images/evaluasi/teratai.png` | Key `teratai` di JSON dashboard Siswa. | Referensi konfigurasi saja; konsumen aktif tidak ditemukan. |

Di antara key gambar dalam JSON dashboard, pemakaian lokal yang ditemukan adalah `images.living`; `siswa-parent-link.js` membaca key `parentLinkGenerate`. Delapan key gambar lainnya tidak ditemukan sebagai konsumen aktif. Gambar materi dapat pula dimuat dinamis dari data Bab (`gambar_pengenalan_url`), dan gambar soal dari properti gambar soal; jalur dinamis tersebut berbeda dari sembilan referensi di atas dan tidak diperiksa melalui database pada sesi ini.

Tidak ditemukan referensi sembilan nama tersebut di CSS atau JavaScript aktif selain deklarasi JSON yang disebutkan. Aset dengan nama mirip tidak digunakan sebagai pengganti karena kesesuaian materi tidak dapat dibuktikan.

**Perbaikan gambar:** tidak ada. Tidak ada file yang dihapus atau dipulihkan.

## 2. File yang diubah

Tidak ada perubahan pada Blade, CSS, JavaScript, route, database, atau media. Dokumen laporan ini merupakan satu-satunya file baru.

## 3. Responsivitas browser

Lebar yang diperiksa: **320, 360, 390, 430, 768, 1024, 1366, dan 1920 px**.

| Halaman | Cakupan | Hasil browser |
| --- | --- | --- |
| Beranda `/` | Delapan lebar; navigasi header/footer dan gambar halaman | Tidak ditemukan horizontal overflow. Gambar halaman yang dimuat tidak melaporkan gambar rusak. Semua anchor internal yang diperiksa memiliki target. |
| `/masuk` | Delapan lebar; formulir dan gambar | HTTP 200; tidak ditemukan horizontal overflow atau gambar rusak. |
| `/daftar` | Delapan lebar; formulir dan gambar | HTTP 200; tidak ditemukan horizontal overflow atau gambar rusak. |
| `/lupa-kata-sandi` | Delapan lebar; formulir dan gambar | HTTP 200; tidak ditemukan horizontal overflow atau gambar rusak. |
| `/siswa-preview` | Delapan lebar; shell dashboard dan konten statis pratinjau | Tidak ditemukan horizontal overflow. Data materi/forum tidak tersedia karena permintaan API terproteksi menerima 401 tanpa login. |
| Dashboard Guru dan Orang Tua dengan sesi pengguna | Tidak tersedia | Belum dapat diperiksa secara visual di browser. Pemeriksaan Blade/CSS/JavaScript hanya statis. |

Pada pratinjau Siswa, drawer dibuka di 320, 390, dan 768 px. Lebarnya 250 px, nama menu tampil, overlay muncul, dan halaman tidak overflow. Tombol X, overlay, pilihan menu, serta Escape saat fokus berada pada drawer menutupnya. Pada 320 px perilaku overlay dan pilihan menu juga diuji langsung. Ini menguji pratinjau frontend, bukan dashboard berisi data akun.

Form login dan daftar diuji pada 320 px dengan memilih ketiga peran tanpa mengirim formulir. Placeholder login berubah untuk Siswa, Guru, dan Orang Tua; field pendaftaran yang sesuai peran tampil. Tidak ditemukan horizontal overflow.

## 4. Navigasi

| Pemeriksaan | Status | Bukti / batasan |
| --- | --- | --- |
| Anchor navigasi beranda | Lulus browser | Target untuk Beranda, Tentang, Fitur, Panduan, dan fitur terkait ditemukan. |
| Link Masuk/Daftar/Lupa Kata Sandi | Lulus browser | Ketiga halaman terbuka dengan HTTP 200. |
| Pratinjau dashboard Siswa | Lulus browser untuk shell UI | Tombol menu membuka drawer; X, overlay, Escape, dan pemilihan menu menutupnya. |
| Akses `/dashboard` sebagai tamu | Lulus browser untuk pengalihan | Tamu diarahkan ke `/masuk`; konten dashboard tidak diakses. |
| Menu, Bab/Subbab, dan link internal dashboard Siswa dengan data aktual | Belum dapat diuji | Pratinjau tidak memiliki sesi dan API materi/forum menerima 401. |
| Navigasi dashboard Guru dan Orang Tua | Pemeriksaan statis saja | Handler drawer, tombol tutup/backdrop, dan state desktop/mobile ditemukan di file terkait; tidak ada sesi untuk pengujian browser. |
| Alur setelah login untuk setiap role | Belum dapat diuji | Tidak menggunakan kredensial atau akun aktif. |

## 5. Pengujian fitur Guru

Semua hasil di bawah adalah **pemeriksaan statis saja**, bukan pengujian nyata menggunakan akun Guru.

| Fitur | Status | Batasan |
| --- | --- | --- |
| Materi dan media | Statis saja | Tidak mengunggah, mengubah, atau memublikasikan materi. |
| Pengelolaan kuis | Statis saja | Tidak membuat atau mengubah soal/aktivitas. |
| Pengelolaan evaluasi | Statis saja | Tidak mengubah soal, kunci, atau pengaturan evaluasi. |
| Nilai dan rekap siswa | Statis saja | Tidak membuka data siswa atau mengekspor rekap. |
| Forum | Statis saja | Tidak mengirim atau mengubah diskusi. |
| Kontak dan chat Orang Tua | Statis saja | Tidak membuka percakapan atau mengirim pesan. |
| Notifikasi dan profil | Statis saja | Tidak mengubah profil, foto, atau status notifikasi. |

## 6. Pengujian fitur Siswa

| Fitur | Status | Bukti / batasan |
| --- | --- | --- |
| Shell dashboard dan ringkasan | Lulus browser untuk UI pratinjau; data belum dapat diuji | Shell tampil. Angka dan konten aktual tidak tersedia tanpa login. |
| Bab/Subbab dan materi | Statis saja; data belum dapat diuji | Permintaan materi pada pratinjau menerima 401. |
| Gambar dan media materi | Statis saja | Sembilan referensi di atas diperiksa; materi/media dinamis dari database tidak diambil. |
| Kuis pilihan ganda, drag-and-drop, dan matching | Statis saja | Tidak membuka atau mengirim attempt kuis. |
| Evaluasi Akhir | Statis saja | Tidak memulai atau menyelesaikan attempt evaluasi. |
| Hasil dan progres | UI pratinjau tampil; data belum dapat diuji | Tidak memeriksa nilai/progres akun nyata. |
| Forum | Belum dapat diuji end-to-end | API forum di pratinjau meminta autentikasi dan mengembalikan 401. Tidak ada diskusi dibuat. |
| Notifikasi dan profil | UI shell saja | Tidak tersedia data akun; tidak mengubah atau menghapus notifikasi/profil. |

## 7. Pengujian fitur Orang Tua

Semua hasil adalah **pemeriksaan statis saja**; dashboard tidak dibuka dengan akun Orang Tua.

| Fitur | Status | Batasan |
| --- | --- | --- |
| Dashboard dan profil | Statis saja | Tidak memeriksa data akun/profil aktual. |
| Aktivitas anak | Statis saja | Tidak memuat aktivitas dari database. |
| Progres belajar | Statis saja | Tidak mengubah atau mengasumsikan data progres. |
| Hasil belajar | Statis saja | Tidak membuka nilai aktual. |
| Catatan Guru | Statis saja | Tidak membuka atau mengirim catatan. |
| Chat Guru | Statis saja | Tidak membuka percakapan atau mengirim pesan. |
| Notifikasi | Statis saja | Tidak memeriksa data notifikasi akun. |

## 8. Masalah dan prioritas

- **P3 — Gambar prompt materi tidak tersedia.** `lesson-ekosistem-lahan-basah.png` tidak ditemukan dan gambar prompt disembunyikan oleh handler `onerror`. Dampak terbatas pada ilustrasi; teks dan alur materi tidak diubah. Tidak ada pengganti yang aman untuk dipilih.
- **P3 — Delapan key gambar JSON tidak memiliki file.** Key tersebut tidak ditemukan dipakai oleh JavaScript lokal aktif. Tidak ada perubahan karena pemakai dinamis/eksternal belum dapat dikesampingkan.
- **Bukan kegagalan fitur — API 401 pada `/siswa-preview`.** Halaman pratinjau tidak memiliki sesi autentikasi; data materi/forum terproteksi tidak tersedia. Ini membatasi pengujian, bukan bukti kegagalan akun yang telah login.
- Tidak ada masalah baru yang terverifikasi pada halaman publik, formulir autentikasi, atau drawer Siswa yang diuji di browser.

## 9. Fitur yang belum dapat diuji

Pengujian end-to-end untuk Guru, Siswa, dan Orang Tua—termasuk materi aktual, kuis/evaluasi, nilai, forum, chat, notifikasi, progres, dan penyimpanan profil—belum dilakukan. Sesi uji yang aman dan database testing terisolasi belum tersedia. Tidak ada kredensial akun aktif yang digunakan, tidak ada akun/data percobaan dibuat, dan tidak ada form yang dikirim.

Modal, tabel, formulir, media, dan navigasi di dashboard Guru/Orang Tua belum diperiksa secara visual pada delapan ukuran layar. Dokumen terdahulu mencatat pemeriksaan komponen tertentu, tetapi hasil historis tersebut tidak dihitung sebagai pengujian browser sesi ini.

## 10. Pemeriksaan teknis

| Pemeriksaan | Hasil |
| --- | --- |
| Sintaks PHP (`app` dan `routes`) | Lulus: 59 file diperiksa dengan `php -l`. |
| Sintaks JavaScript dengan Node | Tidak dijalankan: `node` tidak tersedia pada PATH. Browser berhasil menjalankan sebagian JavaScript halaman yang dimuat, tetapi itu bukan pemeriksaan sintaks penuh seluruh file. |
| Daftar route Laravel | Lulus: `php artisan route:list --path=dashboard` menampilkan route dashboard. |
| Kompilasi Blade | Lulus: 33 template dikompilasi dalam memori tanpa menulis cache atau mengakses database. |
| Referensi gambar | Sembilan path target tidak ditemukan; pencarian variasi ekstensi dan lokasi pengganti tidak menemukan nama dasar gambar tersebut. |
| `git diff --check` | Lulus dengan exit code 0. Git hanya menampilkan peringatan normalisasi CRLF pada file worktree yang sudah berubah. File laporan baru diperiksa terpisah dan tidak memiliki trailing whitespace. |
| PHPUnit, migration, seeder, dan reset | Tidak dijalankan. |

## 11. Rekomendasi

1. Cari sumber asli ilustrasi prompt ekosistem dari arsip/desainer atau backup yang telah diverifikasi; jangan menggantinya dengan gambar yang hanya mirip.
2. Setelah memastikan tidak ada konsumen eksternal, tinjau key gambar legacy di JSON dashboard untuk menentukan apakah perlu dipertahankan atau diperbarui pada perubahan terpisah.
3. Siapkan lingkungan staging dengan database terisolasi dan akun uji per role. Ulangi uji navigasi, modal, tabel, media, kuis/evaluasi, forum, chat, notifikasi, dan profil tanpa menyentuh database utama.
4. Jalankan pemeriksaan sintaks JavaScript melalui Node/CI yang disetujui sebelum rilis.
