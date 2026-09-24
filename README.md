# 🏫 Sistem Presensi SMP PGRI Parung Panjang

Sistem informasi presensi siswa berbasis web yang dirancang khusus untuk SMP PGRI Parung Panjang. Aplikasi ini dilengkapi dengan pemindai QR Code cerdas (anti-double scan), notifikasi WhatsApp otomatis, dan manajemen hak akses tiga lapis (Admin, Wali Kelas, Kesiswaan).

## ✨ Fitur Unggulan

- **🔐 Multi-Role Authentication:** 
  - **Admin:** Akses penuh (CRUD Data, Setup Sistem, Rekap Global).
  - **Wali Kelas:** Akses terisolasi khusus kelas yang diampu, pemindai kelas mandiri.
  - **Kesiswaan:** Akses *read-only* untuk monitoring seluruh presensi sekolah.
- **📷 QR Code Scanner (Anti-Double Scan):** Validasi ganda antara gerbang utama (Admin) dan gerbang kelas (Wali Kelas) untuk mencegah duplikasi absen.
- **💬 Notifikasi WhatsApp Asynchronous:** Pesan kehadiran dikirim ke orang tua via WhatsApp secara *background* menggunakan Laravel Queue Job sehingga tidak membuat web *lag* atau lemot.
- **📊 Import & Export Excel:** Kemudahan memindahkan data Master (Siswa, Guru, Kelas) dan cetak laporan kehadiran.
- **🎨 UI/UX Tersinkronisasi:** Tampilan responsif dan seragam di semua perangkat untuk semua *role*.

---

## 🛠️ Tech Stack

- **Framework:** Laravel 12.x / PHP 8.2+
- **Database:** MySQL
- **Frontend:** Blade, Tailwind CSS, Vue/AlpineJS (untuk interaktivitas scanner)
- **Paket Tambahan:** Laravel Excel, WhatsApp API Integration

---

## 💻 Cara Install & Menjalankan di Komputer Lokal (Local Development)

Ikuti langkah-langkah di bawah ini untuk menginstal dan menjalankan aplikasi di komputer lokal.

### 1. Persyaratan Sistem
Pastikan komputer kamu sudah terinstall aplikasi berikut:
- **PHP** (Minimal versi 8.2)
- **Composer**
- **Node.js & npm**
- **MySQL Server** (Bisa pakai XAMPP, Laragon, dsb)
- **Git**

### 2. Langkah Instalasi Lengkap

**A. Download Repositori**
Buka terminal/CMD, lalu jalankan perintah ini untuk mengunduh source code:
```bash
git clone [https://github.com/dikarahmat/sistempresensi.git](https://github.com/dikarahmat/sistempresensi.git)
cd sistempresensi
```

**B. Install Dependensi (Library pendukung)**
Install package PHP dan package Frontend:
```bash
composer install
npm install
```

**C. Konfigurasi Environment (Database & Queue)**
Duplikat file konfigurasi bawaan:
```bash
cp .env.example .env
```
Buka file `.env` di teks editor, lalu cari dan ubah bagian database dan queue menjadi seperti ini:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=sistem_presensi_db
DB_USERNAME=root
DB_PASSWORD=

QUEUE_CONNECTION=database
```
*(Catatan: Pastikan kamu sudah membuat database kosong dengan nama `sistem_presensi_db` di phpMyAdmin / MySQL kamu).*

**D. Generate Key & Build Aset Frontend**
Jalankan perintah ini untuk mengamankan aplikasi dan meng-compile file CSS/JS:
```bash
php artisan key:generate
npm run build
```

**E. Migrasi Database dan Isi Data Awal (Seeding)**
Masukkan tabel dan data akun default ke dalam database:
```bash
php artisan migrate --seed
```

### 3. Cara Menjalankan Aplikasi

Agar website dan fitur **Notifikasi WhatsApp** berjalan bersamaan dengan lancar, kamu wajib membuka **DUA** terminal/CMD secara terpisah di dalam folder project ini.

**Buka Terminal 1 (Untuk menjalankan web server):**
```bash
php artisan serve
```

**Buka Terminal 2 (Untuk menjalankan proses pengiriman WhatsApp):**
```bash
php artisan queue:work
```

Sekarang, buka browser dan akses aplikasi di: **`http://127.0.0.1:8000`**

---

## 🔑 Akun Default (Login)

Setelah menjalankan perintah `migrate --seed`, gunakan akun berikut untuk masuk ke dalam sistem:

| Role | Email / Username | Password |
| :--- | :--- | :--- |
| **Admin** | admin@smp.com | password123 |
| **Wali Kelas** | *(NIP Guru dari file Excel/Seeder)* | password123 |
| **Kesiswaan** | kesiswaan@smp.com | password123 |

*(Silakan cek file `database/seeders/UserSeeder.php` untuk melihat detail akun lainnya yang digenerate).*

---
*Developed with ❤️ by Dika Rahmat.*
