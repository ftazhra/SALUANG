# Panduan Deploy SALUANG ke InfinityFree melalui FileZilla

Panduan ini hanya menyiapkan file lokal. Tidak ada proses unggah otomatis dan tidak mengubah database hosting.

## Susunan hosting

- Document root domain: `/htdocs`
- Aplikasi Laravel: `/htdocs/saluang_app`
- Media publik: `/htdocs/storage`
- Root Laravel `/htdocs/saluang_app/storage` tetap untuk cache, session, log, dan file internal.
- `public/storage` lokal adalah junction ke storage lokal. Jangan unggah folder/link itu dan jangan membuat `public/public`.

## Konfigurasi media

Di `.env` lokal, `MATERIAL_STORAGE_PATH` harus menunjuk folder media lokal, misalnya:

```dotenv
MATERIAL_STORAGE_PATH=C:/xampp/htdocs/SALUANG_KEL7/storage/app/public
```

Di `.env` hosting yang sudah ada, pastikan nilainya menunjuk folder media hosting:

```dotenv
MATERIAL_STORAGE_PATH=/home/vol19_1/infinityfree.com/if0_43460206/htdocs/storage
```

Edit hanya baris tersebut pada `.env` remote bila memang belum sesuai. Jangan unggah `.env`, jangan mengganti `APP_KEY`/konfigurasi database hosting, dan jangan menghapus atau menimpa isi `/htdocs/storage`.

## FileZilla: file lokal ke tujuan remote

Sebelum unggah, cadangkan file remote yang akan diganti. Unggah hanya perubahan aplikasi yang sudah ditinjau.

Jangan unggah `config\MaterialController.php` lokal: file itu berada di folder konfigurasi dan mendeklarasikan class controller duplikat sehingga dapat menghentikan bootstrap Laravel. File tersebut tidak termasuk source konfigurasi aplikasi.

| Sumber lokal | Tujuan remote |
|---|---|
| Isi `C:\xampp\htdocs\SALUANG_KEL7\app\` | `/htdocs/saluang_app/app/` |
| Isi `C:\xampp\htdocs\SALUANG_KEL7\bootstrap\` | `/htdocs/saluang_app/bootstrap/` |
| Isi `C:\xampp\htdocs\SALUANG_KEL7\config\` | `/htdocs/saluang_app/config/` |
| Isi `C:\xampp\htdocs\SALUANG_KEL7\routes\` | `/htdocs/saluang_app/routes/` |
| Isi `C:\xampp\htdocs\SALUANG_KEL7\resources\` | `/htdocs/saluang_app/resources/` |
| `C:\xampp\htdocs\SALUANG_KEL7\composer.json` dan `composer.lock` | `/htdocs/saluang_app/` |
| Isi `C:\xampp\htdocs\SALUANG_KEL7\vendor\` jika dependensi tidak dipasang di hosting | `/htdocs/saluang_app/vendor/` |
| `C:\xampp\htdocs\SALUANG_KEL7\deployment\infinityfree\index.php` | `/htdocs/index.php` |
| `C:\xampp\htdocs\SALUANG_KEL7\public\.htaccess` | `/htdocs/.htaccess` |
| Isi `C:\xampp\htdocs\SALUANG_KEL7\public\build\` | `/htdocs/build/` |
| Isi `C:\xampp\htdocs\SALUANG_KEL7\public\css\` | `/htdocs/css/` |
| Isi `C:\xampp\htdocs\SALUANG_KEL7\public\images\` | `/htdocs/images/` |
| Isi `C:\xampp\htdocs\SALUANG_KEL7\public\js\` | `/htdocs/js/` |
| `C:\xampp\htdocs\SALUANG_KEL7\public\favicon.ico` dan `robots.txt` | `/htdocs/` |
| `C:\xampp\htdocs\SALUANG_KEL7\deployment\infinityfree\saluang_app.htaccess` | `/htdocs/saluang_app/.htaccess` |

Saat mengunggah folder aplikasi, jangan mengganti `.env`, database/file database, isi `/htdocs/saluang_app/storage`, atau isi `/htdocs/storage`. Jangan mengunggah `.git`, `node_modules`, `public/storage`, maupun folder `deployment` selain dua file yang dipetakan di atas. Jangan unggah keseluruhan folder `public` sebagai satu folder ke `/htdocs`.

Jika ada perubahan migrasi di masa depan, unggah hanya berkas migrasi yang memang dibutuhkan setelah meninjau dampak dan membuat cadangan database. Perubahan media pada rilis ini tidak memerlukan migrasi.

## Membuat kode undangan Guru

Admin membuat kode baru dari root Laravel pada lingkungan yang terhubung ke database tujuan:

```powershell
php artisan saluang:invite-teacher "SMP Negeri 21 Banjarmasin" --days=7
```

Argumen nama sekolah wajib diisi; masa berlaku dapat diatur 1–90 hari. Perintah mencetak kode acak satu kali pakai dan database hanya menyimpan hash-nya. Berikan kode tersebut secara privat kepada Guru. Kode lama/sekolah bukan pengganti kode undangan. Jangan menjalankan perintah dengan konfigurasi database lokal yang menunjuk database produksi tanpa sengaja.

## Urutan unggah

1. Pastikan cadangan remote tersedia dan konfirmasi `.env`, database, `/htdocs/saluang_app/storage`, serta `/htdocs/storage` tidak dipilih untuk overwrite.
2. Unggah kode aplikasi ke `/htdocs/saluang_app`, kecuali item yang dikecualikan di atas.
3. Unggah aset statis ke `/htdocs` sesuai tabel; `public/storage` tetap dikecualikan.
4. Unggah `deployment\infinityfree\index.php` ke `/htdocs/index.php`, lalu `.htaccess` aplikasi ke `/htdocs/saluang_app/.htaccess`.
5. Periksa `MATERIAL_STORAGE_PATH` pada `.env` remote tanpa mengganti nilai rahasia atau koneksi database.
6. Buka situs, uji login dan satu media baru, lalu periksa gambar/video pada URL `/storage/<path-relatif>`. Langkah ini belum dilakukan terhadap hosting oleh audit lokal ini.

Jika cache konfigurasi Laravel aktif, jalankan `php artisan config:clear` dari root `/htdocs/saluang_app` setelah mengunggah `config/database.php`, bila hosting menyediakan shell Artisan. Perintah ini hanya membersihkan cache konfigurasi; jangan menjalankan migrasi untuk perubahan ini.

Simpan dahulu `index.php` dan `.htaccess` remote sebelumnya agar dapat dipulihkan bila pemeriksaan pasca-unggah gagal.
