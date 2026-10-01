# Catatan Konteks Proyek GearTrack

Dokumen ini adalah catatan serah-terima untuk pengembang atau asisten AI pada obrolan berikutnya. Baca dokumen ini bersama `AGENTS.md`, lalu cocokkan dengan kode dan commit terbaru karena aplikasi dapat berubah setelah catatan ini ditulis.

## Cara Menggunakan pada Obrolan Baru

Lampirkan atau berikan file ini kepada ChatGPT/Codex, kemudian gunakan pesan awal seperti berikut:

> Lanjutkan pengembangan GearTrack. Baca `AGENTS.md` dan `GEARTRACK_CONTEXT.md` terlebih dahulu. Setelah itu periksa branch, `git status`, commit terbaru, serta kode yang berhubungan dengan permintaan saya. Jadikan kode terbaru sebagai sumber kebenaran jika berbeda dari catatan. Jangan merusak fitur yang sudah berjalan, jalankan pengujian yang relevan, lalu commit dan push perubahan setelah selesai.

Catatan ini tidak menyimpan kata sandi, token, isi `.env`, data pengguna, atau kredensial server. Jangan menambahkan informasi rahasia tersebut ke file ini.

## Ringkasan Aplikasi

GearTrack adalah aplikasi inventaris perangkat sekolah berbasis Laravel dan Filament. Aplikasi mengelola aset satuan dan paket perangkat, QR aset, peminjaman, mutasi dan serah terima, stock opname, kerusakan dan perawatan, jadwal perawatan, penghapusan aset, laporan, pengguna, aktivitas pengguna, serta backup SQLite.

- Bahasa antarmuka: Bahasa Indonesia.
- Repository: `https://github.com/alynoval10/geartrack.git`.
- Branch utama: `main`.
- Domain produksi: `https://geartrack.smkn1krangkeng.my.id`.
- Lokasi lokal Windows: `C:\laragon\www\geartrack`.
- Lokasi produksi yang pernah digunakan: `/var/www/geartrack`.
- Basis data: SQLite.
- Baseline fungsional saat dokumen dibuat: commit `f24710b` pada 1 Oktober 2026. Selalu periksa commit terbaru sebelum bekerja.

## Teknologi Utama

- PHP `^8.3`.
- Laravel `^13.17` (terpasang saat pencatatan: 13.31.0).
- Filament `^4.0` (terpasang saat pencatatan: 4.13.1).
- PHPUnit 12.
- Tailwind CSS 4, Vite 8.
- `simplesoftwareio/simple-qrcode` untuk QR.
- `html5-qrcode` untuk pemindaian kamera dan gambar.

Sebelum memakai API paket tertentu, periksa versi aktual dengan `composer show --direct`, `composer show nama/paket`, atau `package.json`.

## Pengguna dan Hak Akses

Terdapat dua role pada `App\Models\User`:

- `admin`: mengelola seluruh inventaris, master data, pengguna, pengaturan, backup, persetujuan penghapusan, dan operasional lain.
- `guru`: tetap dapat menggunakan fitur inventaris yang diperlukan, tetapi menu pengelolaan pengguna dan pengaturan disembunyikan.

Ketentuan akun yang sudah diputuskan:

- Hanya akun aktif dan belum dihapus yang dapat masuk.
- User baru tidak diwajibkan mengganti kata sandi saat login pertama.
- Mekanisme `must_change_password` tetap tersedia untuk reset kata sandi oleh admin jika memang dipilih dalam alur reset.
- Pengguna dapat mengganti kata sandinya sendiri.
- Sesi otomatis berakhir setelah tidak ada aktivitas selama 5 menit.
- User memakai soft delete agar referensi data lama tetap terjaga.
- Nama penanggung jawab juga disimpan sebagai snapshot pada transaksi/laporan agar catatan lama tetap terbaca jika nama atau akun berubah.

File utama terkait akses:

- `app/Models/User.php`
- `app/Filament/Resources/Users/`
- `app/Services/UserManagementService.php`
- `app/Http/Middleware/EnforceIdleSession.php`
- `app/Http/Middleware/RequirePasswordChange.php`
- `app/Http/Controllers/PasswordController.php`
- `app/Http/Controllers/SessionActivityController.php`
- `app/Providers/Filament/AdminPanelProvider.php`

## Alur Fitur Utama

### 1. Master Data dan Aset

Admin menyiapkan kategori, merek, lokasi, dan pengguna. Aset menyimpan kode aset, token QR, nama, kategori, merek, model, nomor seri, lokasi, penanggung jawab, kondisi, status, nilai perolehan, foto, catatan, dan spesifikasi tambahan.

Kode aset dapat dibuat otomatis oleh `AssetCodeGenerator`. Nama aset adalah identitas yang mudah dikenali, sedangkan kategori menjelaskan jenisnya. Contoh beberapa router: `Router Praktik Lab TKJ 01`, `Router Praktik Lab TKJ 02`, dan seterusnya. Nomor urut internal tetap boleh dipakai walaupun perangkat tidak memiliki nomor fisik dari pabrik.

File utama:

- `app/Models/Asset.php`
- `app/Filament/Resources/Assets/`
- `app/Services/AssetCodeGenerator.php`
- `app/Observers/AssetObserver.php`
- `app/Filament/Resources/Categories/`
- `app/Filament/Resources/Brands/`
- `app/Filament/Resources/Locations/`

### 2. Paket Perangkat

Paket mengelompokkan beberapa aset, misalnya satu PC lengkap. Anggota dapat memiliki peran `PC Utama`, `Monitor`, atau `Perangkat Tambahan`. Transaksi dapat memilih satu paket agar seluruh anggota paket saat itu ikut diproses, atau memilih aset satuan saja.

File utama:

- `app/Models/AssetSet.php`
- `app/Filament/Resources/AssetSets/`
- relasi paket pada `app/Models/Asset.php`

### 3. QR Aset dan Pemindaian

Setiap aset memiliki `qr_token`. QR publik menuju `/q/{token}` dan menampilkan detail aset serta informasi paketnya. Halaman `/scan` mendukung kamera langsung dan unggah/foto gambar sebagai alternatif.

Pemindai QR juga dipakai di formulir peminjaman, mutasi, laporan kerusakan, dan jadwal perawatan. Setelah QR berhasil dibaca, aset dimasukkan ke pilihan formulir. Untuk peminjaman, pemindai kembali ke formulir setelah scan berhasil; aset yang sudah dipilih atau sedang tidak layak dipinjam harus menghasilkan pesan yang jelas.

URL yang ditanam dalam QR harus memakai domain publik. Prioritas pembentukan URL ada di `AssetQrController::assetPublicUrl()`:

1. `APP_PUBLIC_URL` melalui `config('app.public_url')` jika tersedia.
2. Scheme dan host request aktif sebagai fallback.

Konfigurasi produksi yang diharapkan:

```ini
APP_URL=https://geartrack.smkn1krangkeng.my.id
APP_PUBLIC_URL=https://geartrack.smkn1krangkeng.my.id
```

QR lama yang sudah tercetak dengan alamat `172.16.20.251` tidak berubah otomatis dan harus dicetak ulang.

File utama:

- `app/Http/Controllers/AssetQrController.php`
- `app/Http/Controllers/QrScannerController.php`
- `resources/views/scanner/index.blade.php`
- `resources/views/assets/qr-detail.blade.php`
- `resources/js/scanner.js`
- `resources/js/scanner-camera.js`
- `resources/js/qr-destination.js`
- `resources/js/loan-scanner.js`
- `resources/js/transfer-scanner.js`
- `resources/js/maintenance-scanner.js`
- `resources/js/schedule-scanner.js`

Kamera browser membutuhkan halaman HTTPS dan izin kamera. Jika perubahan JavaScript tidak terlihat, jalankan build Vite dan bersihkan cache browser.

### 4. Cetak Label QR

Ada dua format cetak, baik untuk pilihan massal maupun satu aset:

- `Cetak Label QR`: untuk printer label. Ukuran awal 50 × 30 mm dan dapat diubah dari Pengaturan Sekolah.
- `Cetak Label QR A4`: untuk kertas HVS A4.

Target perangkat yang direncanakan adalah NIIMBOT B21 Pro/B1 dengan label putih 50 × 30 mm. Layout sudah dibuat sesuai ukuran fisik, tetapi printer sebenarnya belum diuji. Setelah perangkat tersedia, periksa skala cetak 100%, margin driver, keterbacaan QR, dan potongan label; lalu sesuaikan pengaturan lebar/tinggi bila diperlukan.

File utama:

- `app/Filament/Resources/Assets/Tables/AssetsTable.php`
- `resources/views/assets/partials/compact-qr-label.blade.php`
- `resources/views/assets/qr-label.blade.php`
- `resources/views/assets/qr-labels-printer.blade.php`
- `resources/views/assets/qr-label-a4.blade.php`
- `resources/views/assets/qr-labels.blade.php`
- `app/Models/SchoolSetting.php`
- `app/Filament/Pages/SchoolSettings.php`

### 5. Peminjaman

Peminjaman dapat berisi satu atau beberapa aset satuan, satu paket beserta anggotanya, atau kombinasi sesuai aturan kelayakan. Form memuat peminjam, kelas/kontak, penanggung jawab dari user, batas pengembalian, keperluan, serta perangkat. Keperluan mempunyai tombol saran tetapi tetap bisa diketik manual. Pemilihan perangkat dapat dilakukan manual atau dengan scan QR.

Saat transaksi dibuat, status aset diperbarui. Saat pengembalian, status dan kondisi diproses kembali serta riwayat dicatat. Setelah create, aplikasi kembali ke daftar peminjaman.

File utama:

- `app/Models/Loan.php`, `app/Models/LoanItem.php`
- `app/Filament/Resources/Loans/`
- `app/Services/LoanService.php`
- `app/Services/LoanAssetEligibility.php`
- `resources/views/filament/resources/loans/loan-qr-scanner.blade.php`

### 6. Mutasi dan Serah Terima

Mutasi mendukung aset satuan dan paket. Aset dapat dipilih manual atau lewat QR. Transaksi menyimpan lokasi tujuan, penerima/penanggung jawab baru, pihak yang menyerahkan, alasan, lampiran bukti, dan snapshot data. Alasan mempunyai tombol template serta tetap dapat diketik manual. Dokumen berita acara dapat dicetak.

File utama:

- `app/Models/AssetTransfer.php`, `app/Models/AssetTransferItem.php`
- `app/Filament/Resources/AssetTransfers/`
- `app/Services/AssetTransferService.php`
- `app/Services/TransferAssetEligibility.php`
- `app/Http/Controllers/AssetTransferDocumentController.php`
- `resources/views/transfers/document.blade.php`

### 7. Stock Opname

Stock opname dibuat untuk suatu lokasi. Aset dipindai melalui QR lalu ditandai ditemukan, hilang, atau berpindah. Hasil dapat memperbarui status/lokasi aset dan selalu dicatat ke histori. Stock opname yang belum selesai muncul dalam pengingat operasional.

File utama:

- `app/Models/StockTake.php`, `app/Models/StockTakeItem.php`
- `app/Filament/Resources/StockTakes/`
- `app/Services/StockTakeService.php`
- `app/Filament/Schemas/StockTakeResultFields.php`

### 8. Kerusakan, Perawatan, dan Jadwal

Laporan perangkat memilih aset secara manual atau lewat QR. Judul laporan serta keluhan/kebutuhan perawatan mempunyai tombol template dan tetap bisa diisi manual. Penanganan disimpan sebagai entri riwayat berisi tindakan, teknisi, biaya, kondisi, dan status.

Jadwal perawatan juga dapat memilih aset lewat QR. Kegiatan dan petunjuk perawatan mempunyai template yang bisa dipilih lalu diedit manual. Teknisi/penanggung jawab terhubung ke user aktif. Jadwal dapat berulang dan muncul dalam pengingat.

File utama:

- `app/Models/MaintenanceReport.php`
- `app/Models/MaintenanceEntry.php`
- `app/Models/MaintenanceSchedule.php`
- `app/Filament/Resources/MaintenanceReports/`
- `app/Filament/Resources/MaintenanceSchedules/`
- `app/Services/MaintenanceService.php`
- `app/Services/MaintenanceScheduleService.php`
- `app/Services/MaintenanceAssetEligibility.php`

### 9. Penghapusan/Pensiun Aset

Penghapusan adalah proses resmi untuk aset rusak berat, hilang permanen, dijual/dilelang, dimusnahkan, atau alasan lain. Pengajuan memuat tanggal, alasan, daftar aset, lampiran, dan pengaju. Status awal menunggu persetujuan. Admin dapat menyetujui atau menolak dan memberikan catatan. Persetujuan mengubah status aset menjadi pensiun serta menghasilkan berita acara cetak.

File utama:

- `app/Models/AssetDisposal.php`, `app/Models/AssetDisposalItem.php`
- `app/Filament/Resources/AssetDisposals/`
- `app/Services/AssetDisposalService.php`
- `app/Services/AssetDisposalEligibility.php`
- `app/Http/Controllers/AssetDisposalDocumentController.php`
- `resources/views/asset-disposals/document.blade.php`

### 10. Lampiran Bukti

Lampiran foto/dokumen tersedia untuk penghapusan, mutasi, laporan dan tindakan perawatan, serta stock opname. Format yang diterima: JPG, PNG, WebP, dan PDF. Batas saat implementasi: maksimal 10 berkas, masing-masing maksimal 10 MB. Berkas menggunakan disk publik, sehingga `php artisan storage:link` harus tersedia di server.

File utama:

- `app/Filament/Schemas/EvidenceAttachments.php`
- `resources/views/filament/components/evidence-attachments.blade.php`
- `resources/views/filament/infolists/evidence-attachments.blade.php`

### 11. Log Aktivitas dan Histori Aset

Aktivitas mencatat pengguna, waktu, jenis tindakan, aset, keterangan, data sebelum, dan data sesudah. Cakupannya termasuk tambah, ubah, pindah, hapus, stock opname, pinjam/kembali, dan perawatan. Daftar aktivitas dapat dicari, difilter tanggal, dan dicetak.

File utama:

- `app/Models/AssetHistory.php`
- `app/Filament/Resources/AssetHistories/`
- `app/Observers/AssetObserver.php`
- `app/Http/Controllers/ActivityLogPrintController.php`
- `resources/views/activity-logs/print.blade.php`

### 12. Dashboard, Pencarian, dan Laporan

Dashboard guru menampilkan aset tanggung jawabnya, pinjaman aktif, perawatan, serta tugas stock opname. Dashboard admin merangkum semua ruangan dan masalah. Peringatan operasional meliputi pinjaman terlambat, perawatan jatuh tempo, stock opname belum selesai, serta kerusakan belum ditangani.

Pencarian global mencari kode aset, nama, nomor seri, pengguna/penanggung jawab, lokasi, atau paket perangkat. Laporan inventaris mendukung filter, cetak, dan ekspor spreadsheet.

File utama:

- `app/Filament/Pages/Dashboard.php`
- `app/Filament/Widgets/`
- `app/Services/OperationalAlertService.php`
- `app/Filament/Pages/Reports.php`
- `app/Services/InventoryReportService.php`
- `app/Http/Controllers/InventoryReportController.php`
- `resources/views/reports/print.blade.php`

### 13. Impor Aset

Impor spreadsheet memakai proses pratinjau lalu konfirmasi agar kesalahan dapat dilihat sebelum data ditulis.

File utama:

- `app/Filament/Pages/ImportAssets.php`
- `app/Services/AssetImportService.php`
- `app/Services/SpreadsheetService.php`
- `app/Http/Controllers/AssetImportController.php`

### 14. Identitas Sekolah dan Tentang

Pengaturan menyimpan nama sekolah, alamat, logo, kepala sekolah, pengurus barang, tahun ajaran, penandatangan dokumen, dan ukuran label QR. Nilai tersebut digunakan pada laporan dan berita acara. Halaman Tentang menampilkan versi aplikasi dan pembuat `Noval Aly, S.T`.

File utama:

- `app/Filament/Pages/SchoolSettings.php`
- `app/Http/Controllers/SchoolSettingController.php`
- `app/Models/SchoolSetting.php`
- `app/Filament/Pages/About.php`

### 15. Backup dan Restore

Backup manual dan restore SQLite tersedia untuk admin. Daftar backup, notifikasi, unduh, hapus, dan proses restore berada di halaman Backup & Restore. Kode backup terjadwal juga tersedia melalui perintah `geartrack:backup`, dijadwalkan setiap hari berdasarkan `config('backup.automatic_time', '01:30')` di `routes/console.php`.

Keputusan saat ini: pengembangan backup otomatis dengan salinan di media/server/cloud lain ditunda. Jangan menganggap backup yang hanya berada di disk server sebagai perlindungan dari kerusakan server. Fitur mirror/off-server baru dilanjutkan jika diminta kembali.

File utama:

- `app/Filament/Pages/Backups.php`
- `app/Http/Controllers/BackupController.php`
- `app/Services/BackupService.php`
- `app/Services/BackupLock.php`
- `app/Models/BackupRun.php`
- `app/Http/Middleware/CoordinateBackups.php`
- `config/backup.php`
- `routes/console.php`

## Struktur dan Peta Kode

Gunakan peta berikut untuk mencari titik perubahan:

| Area | Lokasi |
|---|---|
| Model dan relasi data | `app/Models/` |
| Aturan bisnis/transaksi | `app/Services/` |
| Halaman Filament khusus | `app/Filament/Pages/` |
| CRUD Filament | `app/Filament/Resources/` |
| Komponen pilihan aset/lampiran | `app/Filament/Schemas/` |
| Widget dashboard | `app/Filament/Widgets/` |
| Endpoint web, cetak, QR | `app/Http/Controllers/` |
| Middleware sesi/password/backup | `app/Http/Middleware/` |
| Konfigurasi panel | `app/Providers/Filament/AdminPanelProvider.php` |
| Route web | `routes/web.php` |
| Jadwal Artisan | `routes/console.php` |
| Template Blade | `resources/views/` |
| Pemindai kamera | `resources/js/` |
| Migrasi | `database/migrations/` |
| Factory dan seeder | `database/factories/`, `database/seeders/` |
| Feature test | `tests/Feature/` |
| Tes JavaScript | `tests/Js/` |

Untuk transaksi yang memilih aset, periksa komponen dan layanan bersama sebelum membuat implementasi baru:

- `app/Filament/Schemas/AssetSelectionFields.php`
- `app/Filament/Actions/AssetOperationActions.php`
- `app/Services/AssetSelection.php`
- layanan `*Eligibility.php` sesuai transaksi.

## Model Data Utama

- `Asset` adalah pusat data inventaris.
- `Category`, `Brand`, dan `Location` adalah master data.
- `User` dapat menjadi penanggung jawab aset, transaksi, atau teknisi.
- `AssetSet` mengelompokkan anggota paket melalui `assets.asset_set_id` dan `set_role`.
- `AssetHistory` menyimpan audit aktivitas dan snapshot perubahan.
- `Loan` memiliki banyak `LoanItem`.
- `AssetTransfer` memiliki banyak `AssetTransferItem`.
- `StockTake` memiliki banyak `StockTakeItem`.
- `MaintenanceReport` memiliki banyak `MaintenanceEntry`.
- `MaintenanceSchedule` menunjuk aset dan user teknisi.
- `AssetDisposal` memiliki banyak `AssetDisposalItem`.
- `SchoolSetting` menyimpan identitas sekolah dan konfigurasi cetak.
- `BackupRun` menyimpan status proses backup.

Sebelum mengubah skema, baca seluruh migrasi terkait dan hubungan model. Gunakan migration baru; jangan mengubah migration lama yang sudah mungkin dijalankan di server.

## Route Penting

- `/` — dashboard setelah login.
- `/assets` — aset.
- `/asset-sets` — paket perangkat.
- `/loans` — peminjaman.
- `/asset-transfers` — mutasi dan serah terima.
- `/stock-takes` — stock opname.
- `/maintenance-reports` — kerusakan dan penanganan.
- `/maintenance-schedules` — jadwal perawatan.
- `/asset-disposals` — penghapusan/pensiun.
- `/asset-histories` — log aktivitas.
- `/scan` — scan QR umum.
- `/q/{token}` — detail publik aset.
- `/q/{token}/label` — label satu aset.
- `/labels/print` — label beberapa aset.
- `/reports` dan `/reports/export` — laporan.
- `/activity-logs/print` — cetak aktivitas.
- `/backups` dan `/backup-actions/*` — backup/restore.

Jangan mengandalkan daftar ini untuk nama route internal. Jalankan `php artisan route:list --except-vendor` sebelum mengubah redirect atau URL.

## Pengujian dan Pemeriksaan Mutu

Feature test sudah tersedia untuk pengguna, sesi, kata sandi, aset, paket, penanggung jawab, pencarian, impor, peminjaman, mutasi, stock opname, perawatan, jadwal, penghapusan, notifikasi, lampiran, laporan, aktivitas, backup, domain publik, proxy, pengaturan sekolah, dashboard, dan halaman Tentang.

Perintah yang umum dipakai:

```powershell
php artisan test --compact tests/Feature/NamaTest.php
node --test tests/Js/*.test.js
vendor\bin\pint --dirty --format agent
npm run build
```

Aturan kerja:

1. Jalankan test paling sempit yang membuktikan perubahan.
2. Jika file PHP berubah, jalankan Pint sebelum commit.
3. Jika Blade, CSS, atau JavaScript berubah, jalankan build Vite.
4. Tambah atau perbarui test untuk perilaku penting, terutama transaksi dan otorisasi.
5. Jangan membuat skrip verifikasi sementara jika test yang ada dapat membuktikannya.

Catatan lingkungan lokal: PHP CLI pernah tidak memiliki ekstensi `intl`. Hal ini dapat memicu error pada rendering angka, mata uang, atau pagination Filament dalam beberapa test UI penuh. Bedakan kegagalan lingkungan ini dari regresi aplikasi; tetap jalankan test target dan aktifkan `intl` bila ingin menjalankan seluruh suite dengan hasil representatif.

## Menjalankan Secara Lokal

Contoh alur umum:

```powershell
composer install
Copy-Item .env.example .env
php artisan key:generate
php artisan migrate
npm install
npm run build
php artisan serve
```

Jangan menimpa `.env` yang sudah ada. Pastikan file SQLite yang dipakai sesuai konfigurasi lokal. Pada Laragon, aplikasi juga dapat dijalankan melalui virtual host Laragon tanpa `artisan serve`.

## Deploy ke Server

Alur deploy umum setelah perubahan sudah di-push:

```bash
cd /var/www/geartrack
git pull origin main
composer install --no-dev --optimize-autoloader
php artisan migrate --force
npm ci
npm run build
php artisan storage:link
php artisan optimize:clear
php artisan optimize
```

`php artisan storage:link` cukup dibuat satu kali, tetapi aman diperiksa saat deploy. Sesuaikan kepemilikan dan hak tulis untuk `storage`, `bootstrap/cache`, database SQLite, serta direktori backup agar PHP/Nginx dapat menulis.

Konfigurasi Nginx harus memakai:

- document root `/var/www/geartrack/public`;
- `server_name geartrack.smkn1krangkeng.my.id 172.16.20.251;` jika akses LAN tetap diperlukan;
- PHP-FPM sesuai versi PHP server.

Jika yang muncul adalah halaman “Welcome to nginx”, request masuk ke server block default. Periksa `nginx -T`, DNS/Cloudflare, `server_name`, lalu reload Nginx. Domain produksi memakai HTTPS agar kamera browser dapat dibuka.

Jika scheduler diaktifkan, cron server menjalankan Laravel scheduler setiap menit, misalnya:

```cron
* * * * * cd /var/www/geartrack && php artisan schedule:run >> /dev/null 2>&1
```

## Keputusan Produk yang Harus Dipertahankan

- UI menggunakan Bahasa Indonesia dan mudah dipahami guru/admin.
- Guru tidak melihat menu Pengaturan dan Pengguna.
- Penanggung jawab dipilih dari user aktif, dengan snapshot nama untuk histori.
- Aset satuan tetap dapat diproses tanpa harus masuk paket.
- Paket adalah pilihan tambahan untuk transaksi yang memang melibatkan seluruh anggota.
- Form transaksi yang relevan menyediakan pilihan manual dan QR.
- Template teks ditampilkan sebagai tombol saran; pengguna tetap dapat mengetik atau mengubah isinya.
- Setelah membuat peminjaman, kembali ke daftar peminjaman.
- Kamera QR harus bekerja pada HTTPS; unggah/foto QR tetap menjadi alternatif.
- Format label printer dan A4 harus tetap terpisah.
- QR baru harus memakai domain publik, bukan IP lokal.
- User baru tidak dipaksa mengganti password.
- Idle timeout adalah 5 menit.
- Perubahan penting dicatat ke log aktivitas.
- Tambahkan komentar/PHPDoc pada logika yang tidak langsung jelas, mengikuti konvensi proyek; hindari komentar yang hanya mengulang kode.
- Jika permintaan terdiri dari beberapa poin terpisah, pengguna menginginkan commit terpisah untuk masing-masing poin.
- Jangan merusak fitur yang sudah berjalan.

## Hal yang Masih Perlu Diverifikasi atau Ditunda

- Uji fisik label 50 × 30 mm pada NIIMBOT B21 Pro/B1 belum dilakukan.
- QR lama yang berisi IP lokal harus dicetak ulang.
- Backup otomatis ke media/server/cloud lain sengaja ditunda.
- Catatan ini adalah peta, bukan pengganti pembacaan kode. Selalu cek `git status`, commit terbaru, migration, route, test, dan file terkait sebelum mengubah aplikasi.

## Checklist Sebelum Mengerjakan Fitur Berikutnya

1. Baca `AGENTS.md` dan, jika nanti ada, `.ai/rules/index.md` beserta rule yang cocok dengan file sasaran.
2. Jalankan `git status --short` dan `git log -1 --oneline`.
3. Cari implementasi serupa agar komponen/layanan yang ada digunakan kembali.
4. Periksa route, model, migration, policy/otorisasi, dan test terkait.
5. Kerjakan perubahan kecil yang utuh dan beri komentar hanya pada logika yang membutuhkan penjelasan.
6. Jalankan test target, Pint untuk PHP, dan build untuk frontend.
7. Periksa diff agar tidak ada `.env`, database, backup, upload, atau kredensial yang ikut ter-commit.
8. Commit dengan pesan yang menjelaskan satu perubahan, lalu push ke `origin/main` jika memang melanjutkan pola kerja proyek ini.

