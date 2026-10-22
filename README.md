# GearTrack

GearTrack adalah aplikasi pengelolaan inventaris sekolah. Setiap instalasi menyimpan data untuk satu sekolah.

## Perintah inti pemasangan baru GearTrack

Jalankan setelah kode terpasang, `.env` sudah diatur untuk produksi, dan folder database serta penyimpanan sudah memiliki izin tulis. Perintah ini menyiapkan database, data master, tautan foto, dan aset tampilan:

```bash
cd /var/www/geartrack
touch database/database.sqlite
mkdir -p storage/app/public storage/app/backups
php artisan key:generate --force
php artisan migrate --force
php artisan db:seed --force
php artisan storage:link
npm ci && npm run build
php artisan optimize
```

`key:generate --force` hanya untuk instalasi baru. Jangan jalankan ulang pada instalasi yang sudah digunakan karena akan mengganti `APP_KEY`. Setelah langkah di atas selesai, buka domain GearTrack melalui browser. Jika belum ada Administrator, halaman pengaturan awal akan tampil sebelum halaman login. Isi nama, email, dan kata sandi Administrator utama di sana. Halaman ini terkunci setelah akun pertama berhasil dibuat.

Fitur utama:

- Inventaris aset, kategori, merek, dan lokasi bertingkat.
- Label QR aset dan lokasi, termasuk cetak melalui Niimbot pada browser yang mendukung Web Bluetooth.
- Impor dan ekspor data aset serta data peminjam.
- Peminjaman, pengembalian, mutasi, dan paket perangkat.
- Stock opname, laporan kerusakan, jadwal perawatan, dan penghapusan aset.
- Administrasi aset: pemasok, nomor faktur, dan tanggal garansi.
- Email pengingat harian untuk peminjaman terlambat, jadwal perawatan, dan garansi yang mendekati akhir.
- Laporan cetak/PDF dan Excel.
- Pengaturan sekolah, pengguna Admin/Guru, serta backup dan pemulihan data.

## Membuat arsip rilis

Buat ZIP distribusi dari commit rilis yang sudah siap menggunakan:

```bash
git archive --format=zip --output=geartrack-release.zip HEAD
```

Cara ini menerapkan aturan arsip Git dan tidak menyertakan berkas internal yang ditandai untuk dikecualikan. Jangan membuat paket jual dengan men-ZIP seluruh folder kerja secara langsung.

Untuk pemasangan baru, unggah ZIP ini ke server lalu ekstrak isinya ke folder aplikasi, misalnya `/var/www/geartrack`. Setelah itu ikuti langkah pemasangan baru di bawah. Untuk pembeli, distribusikan ZIP rilis ini melalui kanal yang Anda pilih; jangan berikan akses ke repository pengembangan.

## Persyaratan server

- PHP 8.3 atau lebih baru, sesuai batas versi pada `composer.json`.
- Composer 2.
- Ekstensi PHP untuk SQLite (`pdo_sqlite` dan `sqlite3`) serta `zip` untuk fitur backup dan pemulihan bawaan.
- Node.js dan npm yang kompatibel dengan Vite pada `package-lock.json` untuk membangun aset tampilan.
- Web server Apache atau Nginx dengan HTTPS dan akses terminal/cron.

Document root web server harus menunjuk ke folder `public`, bukan ke folder utama proyek. Folder proyek, file `.env`, database, foto, dan backup harus berada di luar akses publik kecuali jalur `public` yang memang disediakan.

> **Batasan backup bawaan:** fitur backup/pemulihan GearTrack saat ini mendukung database SQLite berbasis file, penyimpanan foto lokal, dan sesi database. Koneksi database lain mungkin tersedia di Laravel, tetapi tidak didukung oleh backup/pemulihan bawaan GearTrack.

## Pemasangan baru di server

Contoh berikut untuk server Linux dengan akses terminal. Ganti `/var/www/geartrack` dengan lokasi proyek di server Anda.

### 1. Pasang kode dan dependensi

Unggah salinan rilis GearTrack ke server, lalu jalankan:

```bash
cd /var/www/geartrack
composer install --no-dev --optimize-autoloader --no-interaction
cp .env.example .env
```

Jika shell server tidak menyediakan `cp`, salin `.env.example` secara manual menjadi `.env`.

### 2. Atur lingkungan produksi

Edit `.env`. Minimal, atur nilai berikut:

```dotenv
APP_NAME=GearTrack
APP_ENV=production
APP_DEBUG=false
APP_URL=https://inventaris.namasekolah.sch.id

DB_CONNECTION=sqlite
DB_DATABASE=/var/www/geartrack/database/database.sqlite

SESSION_DRIVER=database
SESSION_SECURE_COOKIE=true
FILESYSTEM_DISK=local
```

Gunakan alamat HTTPS yang benar-benar diarahkan ke server ini. Buat database SQLite dan siapkan folder yang digunakan aplikasi:

```bash
touch database/database.sqlite
mkdir -p storage/app/public storage/app/backups
```

Jika aplikasi berada di belakang reverse proxy seperti Cloudflare Tunnel, atur `APP_PUBLIC_URL` ke alamat publik GearTrack dan isi `TRUSTED_PROXIES` dengan alamat IP proxy yang benar-benar meneruskan permintaan ke aplikasi. Jangan mengatur proxy tepercaya ke semua alamat IP.

Pastikan pengguna PHP/web server dapat menulis ke `database/database.sqlite`, `storage`, dan `bootstrap/cache`. Jangan memberi izin tulis kepada semua pengguna server.

Atur juga email administrator yang diizinkan mengelola backup. Nilainya harus sama persis dengan email akun GearTrack yang aktif:

```dotenv
BACKUP_ADMIN_EMAILS=admin@namasekolah.sch.id
```

Atur SMTP agar email pengingat operasional dapat dikirim. Gunakan kredensial SMTP dari penyedia email sekolah/hosting, dan jangan membagikan nilainya:

```dotenv
MAIL_MAILER=smtp
MAIL_HOST=smtp.penyedia.sch.id
MAIL_PORT=587
MAIL_USERNAME=akun-smtp
MAIL_PASSWORD="kata-sandi-smtp"
MAIL_SCHEME=smtp
MAIL_FROM_ADDRESS=geartrack@namasekolah.sch.id
MAIL_FROM_NAME="GearTrack"
```

Pengingat harian dikirim ke alamat email Administrator aktif di GearTrack. Secara bawaan, jadwal perawatan yang jatuh tempo dalam 7 hari dan garansi yang berakhir dalam 30 hari akan dimasukkan, bersama seluruh peminjaman yang sudah terlambat. Atur jadwal dan rentang tersebut bila diperlukan:

```dotenv
OPERATIONAL_REMINDER_TIME=07:00
OPERATIONAL_REMINDER_MAINTENANCE_DAYS=7
OPERATIONAL_REMINDER_WARRANTY_DAYS=30
```

Opsional, atur waktu backup dan lama penyimpanan otomatis:

```dotenv
BACKUP_AUTOMATIC_TIME=01:30
BACKUP_RETENTION=14
```

Untuk menyimpan salinan backup di luar folder/server aplikasi, pasang atau mount folder penyimpanan terpisah, lalu isi:

```dotenv
BACKUP_MIRROR_DIRECTORY=/mnt/backup-geartrack
```

Folder tujuan harus dapat ditulis oleh pengguna PHP. Jika dibiarkan kosong, backup otomatis hanya disimpan di server aplikasi.

### 3. Buat kunci aplikasi dan siapkan database

```bash
php artisan key:generate --force
php artisan migrate --force
php artisan db:seed --force
php artisan storage:link
npm ci
npm run build
php artisan optimize
```

`db:seed` membuat data master dan tidak membuat akun Administrator dengan kredensial bersama. Pada lingkungan `production`, seeder juga tidak membuat data demo peminjam, lokasi, atau aset. Di lingkungan selain `production`, data demo peminjam dan lokasi bertingkat beserta aset contohnya ikut dibuat.

### 4. Buat Administrator pertama

Buka `https://inventaris.namasekolah.sch.id` setelah langkah pemasangan selesai. GearTrack akan menampilkan formulir pengaturan awal jika belum ada Administrator. Isi nama, email, dan kata sandi khusus sekolah (minimal 12 karakter). Akun langsung masuk setelah berhasil dibuat dan halaman setup akan terkunci. Perbarui `BACKUP_ADMIN_EMAILS` dengan email Administrator yang dipakai.

Selesaikan pengaturan awal segera setelah aplikasi dipasang dan pastikan domain menggunakan HTTPS. Simpan `APP_KEY` secara aman; jangan menggantinya setelah aplikasi digunakan. Kunci ini diperlukan untuk memverifikasi backup dan memulihkan data.

### 5. Jadwalkan backup otomatis

GearTrack membuat backup otomatis sesuai `BACKUP_AUTOMATIC_TIME`. Tambahkan satu tugas cron untuk menjalankan scheduler Laravel setiap menit:

```cron
* * * * * cd /var/www/geartrack && php artisan schedule:run >> /dev/null 2>&1
```

Scheduler yang sama juga mengirim email pengingat operasional sesuai `OPERATIONAL_REMINDER_TIME`. Pastikan konfigurasi SMTP sudah benar. Untuk menjalankan pengiriman saat itu juga, gunakan `php artisan geartrack:send-reminders` setelah pengaturan email siap; perintah tersebut mengirim email sungguhan jika ada pengingat.

Pastikan cron berjalan sebagai pengguna yang memiliki akses tulis ke database dan folder `storage`. Status backup dapat diperiksa di menu **Sistem → Backup & Restore**. Untuk menguji tanpa menunggu jadwal, jalankan:

```bash
php artisan geartrack:backup
```

Perintah tersebut membuat backup dan menyalin ke `BACKUP_MIRROR_DIRECTORY` jika sudah dikonfigurasi. Backup berisi database dan foto aset yang tercatat, tetapi **tidak berisi `.env`**. Simpan file backup dan salinan konfigurasi `.env`/`APP_KEY` secara terpisah di tempat aman.

## Memulihkan backup

1. Pastikan instalasi menggunakan `APP_KEY` yang sama dengan instalasi pembuat backup.
2. Buka **Sistem → Backup & Restore** menggunakan akun Administrator yang emailnya terdaftar pada `BACKUP_ADMIN_EMAILS`.
3. Unggah file backup GearTrack, masukkan kata sandi akun saat ini, lalu ketik `RESTORE` untuk konfirmasi.
4. Setelah proses selesai, GearTrack mengakhiri sesi. Masuk kembali menggunakan akun yang tersimpan dalam backup.

> Pemulihan mengganti data saat ini dengan data di backup. Buat backup kondisi terbaru sebelum memulihkan. Jangan menimpa atau menghapus satu-satunya salinan backup.

## Memperbarui aplikasi

Lakukan pembaruan pada waktu pemeliharaan. Pastikan backup terbaru tersedia sebelum mengganti kode atau menjalankan migrasi. Pilih salah satu cara untuk memasang kode rilis: `git pull` jika server memang dipasang dari repository Git yang Anda kelola, atau unggah dan ekstrak ZIP rilis jika server menerima paket file. Jangan menjalankan kedua cara sekaligus.

### Jika server menggunakan repository Git

```bash
cd /var/www/geartrack
php artisan down
php artisan geartrack:backup
git pull --ff-only
composer install --no-dev --optimize-autoloader --no-interaction
npm ci
npm run build
php artisan migrate --force
php artisan optimize:clear
php artisan optimize
php artisan up
```

Perintah `git pull --ff-only` mengambil kode terbaru dari upstream branch yang sudah dikonfigurasi di server. Gunakan cara ini hanya untuk server milik sendiri yang sudah terhubung ke repository dan branch rilis yang benar.

### Jika server menggunakan paket ZIP

Di komputer pengembang, buat ZIP dari commit rilis. Di server, aktifkan mode pemeliharaan dan buat backup terlebih dahulu:

```bash
cd /var/www/geartrack
php artisan down
php artisan geartrack:backup
```

Unggah ZIP ke server, lalu ekstrak/timpa file aplikasi. Pertahankan `.env`, `database/database.sqlite`, `storage/app`, dan backup yang sudah ada. Setelah file rilis selesai dipasang, lanjutkan:

```bash
cd /var/www/geartrack
composer install --no-dev --optimize-autoloader --no-interaction
npm ci
npm run build
php artisan migrate --force
php artisan optimize:clear
php artisan optimize
php artisan up
```

Jangan jalankan `php artisan db:seed` setiap kali memperbarui aplikasi. Biasanya pembaruan cukup menjalankan migrasi baru. Jika rilis bermasalah, kembalikan kode ke versi sebelumnya dan pulihkan backup yang dibuat sebelum pembaruan.

Jangan menghapus atau mengganti `.env`, database, foto, dan backup ketika memperbarui file aplikasi. Hindari mengganti `APP_KEY` pada instalasi yang sudah berjalan.

## Pengembangan lokal

Untuk lingkungan pengembangan, salin `.env.example` menjadi `.env`, isi koneksi database lokal, lalu jalankan:

```bash
composer install
php artisan key:generate
php artisan migrate --seed
npm ci
npm run build
```

Jalankan `php artisan serve`, lalu buka `http://localhost:8000`. Jika belum ada Administrator, formulir pengaturan awal akan tampil otomatis. Buat akun Administrator lokal melalui formulir tersebut.

`php artisan migrate --seed` membuat data master, peminjam demo, lokasi bertingkat, dan aset contoh selama aplikasi tidak berjalan di lingkungan `production`. Pada server produksi, data demo dilewati otomatis.

## Pemeriksaan sebelum aplikasi digunakan

- Pastikan HTTPS aktif dan `APP_DEBUG=false`.
- Pastikan document root hanya menunjuk ke folder `public`.
- Pastikan halaman login mewajibkan penggantian kata sandi awal.
- Pastikan akun backup tercantum di `BACKUP_ADMIN_EMAILS`.
- Buat backup percobaan, pastikan file tersimpan, dan uji pemulihan menggunakan salinan data uji.
- Pastikan cron berjalan dan backup otomatis tampil di halaman **Backup & Restore**.
- Pastikan foto aset dapat dibuka dan proses cetak QR sesuai browser/perangkat yang akan digunakan.
