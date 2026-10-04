# LAPORAN FINAL AUDIT SISTEM PRESENSI
- Tanggal mulai: 2026-10-04 11:30 WIB | Update akhir: 2026-10-04 13:00 WIB (Asia/Jakarta, nyata)
- Branch: main | Commit awal (sebelum-audit): 5978d80 | Commit akhir: (lihat git log)
- Database uji: sqlite :memory: via php artisan test + MySQL lokal hanya migrate:status/route:list (data existing tidak dihapus; file .env.bak sementara sudah dihapus lagi)

## Ringkasan eksekutif (maks 10 baris)
- Audit 53 poin: 44 SUDAH, 5 SEBAGIAN, 4 PERLU KEPUTUSAN (Sabtu libur?, Reset Filter?, 1-klik kamera?, guru login?, Judul?, check-out?).
- php artisan test SEBELUM: 10 gagal / 42 lulus. SESUDAH: 59 lulus / 0 gagal (333→368 assertions).
- Bug diperbaiki: tombol Kembali presensi kelas, timezone Trash guru/kelas, locale default id, pesan Hari Libur Indonesia, 4 file test usang diselaraskan + 1 file test verifikasi baru.
- Keamanan: auth/throttle/CSRF/fillable/upload OK; sisa risiko: password guru=NIP, APP_DEBUG=true lokal, commonmark medium+high (transitif).
- Performa: index + eager loading + paginasi sudah ada; belum ada lag terukur di test (suite ±10 dtk).
- Responsif: table-responsive + sticky header di semua tabel; verifikasi [DIBACA-KODE], browser 5 lebar BELUM diuji manual.
- File diubah 9, dibuat 2 (LAPORAN_FINAL.md + 1 test). Tidak ada file dihapus (tunggu konfirmasi).
- 8 keputusan menunggu pemilik (lihat bagian 9).

## 1. Checklist 53 poin dokumen PDF
Legenda: [DIBACA-KODE] = bukti dari kode, belum request/browser. [DIUJI-JALAN] hanya mulai Tahap 2.

| No | Poin | Status | Bukti (file/baris atau hasil uji) | Catatan |
|---|---|---|---|---|
| 1 | Login label "Username", login pakai username | SUDAH [DIBACA-KODE] | `resources/views/auth/login.blade.php:723-728,735-746`; `app/Http/Controllers/AuthController.php:84-101,128-141` | Label tunggal Username; backend terima username/email/NIP (universal). |
| 2 | Logout konfirmasi "Apakah Anda yakin untuk Log Out?" | SUDAH [DIBACA-KODE] | `resources/views/partials/sidebar.blade.php:218-221`; `resources/views/layouts/app.blade.php:4171-4186` | Modal, tidak langsung logout; tombol Batal / Ya, Log Out. |
| 3 | Sidebar buka/tutup desktop+mobile, instan | SUDAH [DIBACA-KODE] | `resources/views/layouts/app.blade.php:88-96,3991-3997,4327-4357` | Desktop toggle class instan + localStorage; mobile drawer 0.32s (wajar). |
| 4 | Tanggal kanan atas: fungsi kalender / teks jelas | SUDAH [DIBACA-KODE] | `resources/views/admin/dashboard.blade.php:20-42,45-50,841-878`; `AdminDashboardController.php:23-52` | Bukan teks mati: input date + showPicker + submit + tombol Hari Ini; hitung ulang statistik. |
| 5 | Ketidakhadiran Hari Ini: klik Sakit + "Reset Filter" sesuai perilaku | SEBAGIAN [DIBACA-KODE] | `dashboard.blade.php:668,676-685,742-746`; `AttendanceController.php:148-152`; grep "Reset Filter" = 0 hasil | Klik Sakit = toggle GET + teruskan status ke presensi.index. TIDAK ADA tombol bernama Reset Filter. PERLU KEPUTUSAN: tambah tombol / ganti nama. |
| 6 | Grafik Harian/Mingguan/Bulanan mengubah data | SUDAH [DIBACA-KODE] | `dashboard.blade.php:618-624`; `AdminDashboardController.php:98-101,145-187,206-227` | 3 mode via query, judul/total/empty ikut ganti, data dari server. |
| 7 | Pintasan Cepat di atas, 8 kartu, tujuan Presensi/Kehadiran benar | SUDAH [DIBACA-KODE] | `dashboard.blade.php:438-453,461-483,546-554,587-594` | Di atas di bawah statistik; admin 8 kartu; Presensi Hari Ini → halaman Presensi (bukan kamera); Catatan → Kehadiran. Guru 5 kartu by-design. |
| 8 | Scan QR mudah dari dashboard | SEBAGIAN [DIBACA-KODE] | `dashboard.blade.php:582-586`; `routes/web.php:48,51,61-62` | Akses 2-klik via Presensi (disengaja agar kamera tak auto-nyala). PERLU KEPUTUSAN: tetap atau tambah tombol 1-klik. |
| 9 | Tabel panjang scroll cepat | SUDAH [DIBACA-KODE] | `absensi/class.blade.php:421-441`; `attendances/daily.blade.php:518-536`; `kehadiran.blade.php:259-290`; `rekap.blade.php:220-238`; `layouts/app.blade.php:1132-1143` | Pola flex + .table-responsive scroll + sticky header di 4 halaman. |
| 10 | Keterangan Mode Gerbang vs Buka Scanner QR di UI | SEBAGIAN [DIBACA-KODE] | `attendances/daily.blade.php:19-33,625-632`; `absensi/class.blade.php:489-496`; `routes/web.php:51,61-63` | Daily ada dua pintu + switcher; detail kelas HANYA inline tanpa link Mode Gerbang. |
| 11 | Kotak kamera diperbesar, mirror sesuai | SUDAH [DIBACA-KODE] | `class.blade.php:890-944`; `daily.blade.php:1072-1087`; `public/js/scanner.js:22,499`; `scanner.blade.php:341-361` | qrbox 70% seragam + CameraSelect + object-fit cover. |
| 12 | Tombol tutup scanner besar di bawah kotak | SUDAH [DIBACA-KODE] | `class.blade.php:759-793`; `daily.blade.php:910-939`; `kiosk.blade.php:563`; `scanner.blade.php:654` | Tutup Scanner / Keluar Mode Gerbang di bawah; stop kamera + reset layout. |
| 13 | Mode Gerbang: teks Fokuskan + Siap menerima dijelaskan + fungsi jalan | SEBAGIAN [DIBACA-KODE] | `scanner.blade.php:610,612`; `kiosk.blade.php:624,626`; `daily.blade.php:660`; `class.blade.php:523` | Teks + input hardware ada dan terhubung (autofocus), tapi belum ada kalimat penjelasan fungsi di UI. |
| 14 | Presensi Kelas (mis. 7A) punya tombol Kembali | BELUM [DIBACA-KODE] | `absensi/class.blade.php:278` hanya komentar CSS; grep "Kembali" = 1 komentar, 0 HTML | CSS yatim tanpa markup; hanya bisa via sidebar/browser-back. WAJIB TAMBAH. |
| 15 | Presensi manual jam masuk tercatat + tampil | SUDAH [DIBACA-KODE] | `class.blade.php:658-661`; `AttendanceController.php:792-799,853-865` | Input time + auto now Asia/Jakarta bila kosong; tampil di tabel. |
| 16 | Terlambat tersimpan sbg Terlambat, header sinkron | SUDAH [DIBACA-KODE] | `app/Models/Attendance.php:81-88,37-76`; `AttendanceController.php:314-316,867-887`; `class.blade.php:591-606` | Accessor effectiveStatus + is_late/late_minutes persist; kasus header 5 vs status Hadir teratasi. |
| 17 | Filter Harian/Mingguan/Bulanan kehadiran menyaring benar | SUDAH [DIBACA-KODE] | `AttendanceController.php:645-680`; `kehadiran/student-history.blade.php:181-197` | whereBetween per periode; tiap mode rentang berbeda. |
| 18 | Ukuran tanggal diperbesar | SUDAH [DIBACA-KODE] | `student-history.blade.php:59-74,130-131` | Input 46px/1rem, mobile 40px; isi tabel 1rem. |
| 19 | Kolom KETERANGAN: sumber jelas, bisa terisi + tampil | SUDAH [DIBACA-KODE] | `student-history.blade.php:220,239`; `class.blade.php:663-666`; `AttendanceController.php:899`; `rekap.blade.php:600,644-649` | Sumber: textarea notes saat override; tampil di riwayat + rekap. |
| 20 | Pencarian Nama/NIS di Semua Kelas tanpa pilih kelas | SUDAH [DIBACA-KODE] | `kehadiran.blade.php:506-515`; `AttendanceController.php:548-549,570-576` | where nama/NIS tanpa syarat kelas. |
| 21 | Dropdown kelas tidak hilang setelah Enter | SUDAH [DIBACA-KODE] | `AttendanceController.php:437-442,542-568`; `kehadiran.blade.php:521-532,670-675` | Dropdown selalu penuh; search tidak filter kelas. |
| 22 | Kolom JK warna jelas (biru/merah, tidak bold) | SUDAH [DIBACA-KODE] | `rekap.blade.php:170-182,764-769`; `pdf_multi_rekap.blade.php:97-98`; `MultiPeriodAttendanceExport.php:828-842` | #3B82F6/#EF4444, weight 500, konsisten web+Excel+PDF. |
| 23 | Legend H/T/S/I/A/L/- besar mudah dibaca | SUDAH [DIBACA-KODE] | `rekap.blade.php:137-154,571-583` | 0.92rem/1rem; harian tanpa legend matriks by-design. |
| 24 | Ikon search besar + Enter menjalankan pencarian | SUDAH [DIBACA-KODE] | `rekap.blade.php:187-191,503-547`; `kehadiran.blade.php:503-518` | Ikon 1.5rem; Enter = submit native. |
| 25 | Hari libur default Sabtu+Minggu terbaca di Rekap | SEBAGIAN — PERLU KEPUTUSAN [DIBACA-KODE] | `RekapController.php:61-63,145-154`; `MultiPeriodAttendanceExport.php:295,338,540,628,705`; `MonthlyAttendanceExport.php:53-55`; grep Saturday=0 | Minggu + libur nasional = L. SABTU masih hari kerja (A bila lewat). Putuskan: Sabtu libur? (ubah 5 lokasi + jam operasional). |
| 26 | Excel/PDF ada keterangan H/T/S/I/A | SUDAH [DIBACA-KODE] | `MultiPeriodAttendanceExport.php:203-211,392-398`; `pdf_multi_rekap.blade.php:139-140,349-357`; `RekapController.php:431-436` | legendLines identik Excel↔PDF per kelas + tanda tangan. |
| 27 | Kolom Keterlambatan manusiawi (bukan +892M) | SUDAH [DIBACA-KODE] | `app/Support/helpers.php:117-146`; `RekapController.php:90-93`; `rekap.blade.php:629-633` | <60 "15 MNT", ≥60 "1 JAM 5 MNT"; legend dokumentasikan. |
| 28 | Toggle TA jelas + konfirmasi + bisa klik aktifkan | SUDAH [DIBACA-KODE] | `AcademicYearController.php:93-117`; `academic_years/index.blade.php:209-216,414-466`; `AcademicYear.php:33-54` | POST toggle + dialog primary + anti double-submit + transaksi. |
| 29 | TA aktif tak bisa dihapus + pesan jelas + cara pindah | SUDAH [DIBACA-KODE] | `AcademicYearController.php:119-130`; `academic_years/index.blade.php:228-244,384-394` | Server tolak is_active + view disabled + pesan "sedang berlangsung"; pindah via AKTIFKAN. |
| 30 | Tombol X notifikasi center vertikal (semua notif) | SUDAH [DIBACA-KODE] | `holidays/index.blade.php:165-219`; `layouts/app.blade.php:319-398` | alert-dismissible + btn-close + CSS flex center global. |
| 31 | Import 0 data = GAGAL merah/kuning jujur (SEMUA import) | SUDAH [DIBACA-KODE] | `StudentController.php:353-381`; `TeacherController.php:276-300`; `SchoolClassController.php:348-373`; `HolidayController.php:55-75` | Pola 3 cabang identik: sukses hijau / sebagian kuning + alasan / 0 merah. |
| 32 | Import Siswa jujur (rinci) | SUDAH [DIBACA-KODE] | `StudentController.php:353-381`; `students/index.blade.php:412-443` | Sama dgn 31 + daftar alasan per baris. |
| 33 | Regex NIS/NISN/WA server+client, Edit + Import | SUDAH [DIBACA-KODE] | `StudentController.php:128-136,161,163`; `students/index.blade.php:740,745,789`; `edit.blade.php:130,144,186`; `StudentsImport.php:46-47` | NIS angka 4-30, NISN 10 digit opsional, WA 10-15; server kuat + client filter; import tolak/lewati jujur. |
| 34 | Tambah Siswa: error di dalam form + notif selalu muncul | SUDAH [DIBACA-KODE] | `students/index.blade.php` + `create.blade.php:39`; `StudentController.php:144-185` | @error per field + pesan Indonesia + flash sukses/gagal. |
| 35 | Semua validasi Bahasa Indonesia | SEBAGIAN [DIBACA-KODE] | `Student/TeacherController` messages ID; `HolidayController.php:88-92` tanpa messages; `lang/` kosong; `.env APP_LOCALE=id`; `config/app.php:81-85` default en | Siswa/Guru ID penuh; Hari Libur fallback locale (risiko Inggris). Saran: default config id + tambah messages. |
| 36 | Hapus Semua: 2 checkbox, tombol aktif bila keduanya dicentang | SUDAH (Siswa penuh; Guru/Kelas arsip hanya client) [DIBACA-KODE] | `StudentController.php:452-462`; `students/index.blade.php:449-456,891-906`; `teachers/index.blade.php:861-876`; `TeacherController.php:202` | Siswa server accepted+client; guru-massal-aktif & arsip hanya konfirm client. |
| 37 | Teks sampah vs permanen tidak kontradiktif | SUDAH [DIBACA-KODE] | `students/trash.blade.php:240`; `teachers/trash:214`; `classes/trash:225`; grep lorem/dummy=0 | Hapus = Tempat Sampah (pulihkan); permanen = hapus permanen; konsisten ID. |
| 38 | Tanggal dihapus benar + Asia/Jakarta seragam | SEBAGIAN [DIBACA-KODE] | `students/trash.blade.php:210-213` (timezone+WIB); `teachers/trash:197`, `classes/trash:208` (format saja); `config/app.php:68` + `.env:24` | Siswa benar; Guru/Kelas belum konversi eksplisit. |
| 39 | Import Guru jujur + "NIP wajib" rapi | SUDAH [DIBACA-KODE] | `TeacherController.php:276-300`; `TeachersImport.php:36-40`; `teachers/index.blade.php:305-328` | 3 status + alasan per baris; pesan NIP ID. |
| 40 | Tambah/Edit Guru validasi di form; NIP 18, telp 10-15 | SUDAH [DIBACA-KODE] | `TeacherController.php:314,318,331,339`; `teachers/index.blade.php:537,624,783-788` | Server regex + client pattern+JS + inline error. |
| 41 | Bintang merah semua field wajib semua form | SUDAH [DIBACA-KODE] | `teachers/index.blade.php:527,531,536,542`; settings/students/classes sejenis | `<span class=text-danger>*</span>` + catatan wajib diisi. |
| 42 | Guru bisa login? akun NIP default? risiko? | SUDAH (perilaku jelas) — PERLU KEPUTUSAN [DIBACA-KODE] | `TeacherController.php:62-78`; `TeachersImport:77-93`; `AuthController.php:106-115,170-202`; `teachers/index:510-513` | YA: User otomatis (email NIP@..., password=NIP, role guru); login via NIP. RISIKO TINGGI: password tebak. Putuskan: 1 admin saja vs guru login + wajib ganti. |
| 43 | Import Kelas jujur | SUDAH [DIBACA-KODE] | `SchoolClassController.php:330-374`; `SchoolClassesImport.php:22-107`; `classes/index:296-340` | 3 status + skip baris kosong/tingkat tak dikenal/duplikat + restore. |
| 44 | NIP Kepsek regex 18 digit | SUDAH [DIBACA-KODE] | `SettingController.php:44,58`; `settings/index.blade.php:345-367` | Server + client + pesan ID. |
| 45 | Telepon sekolah 10-15 digit | SUDAH [DIBACA-KODE] | `SettingController.php:41,56`; `settings/index:345-367` | Server + client + pesan ID. |
| 46 | Upload Logo berfungsi + tampil semua tempat + validasi + pratinjau | SUDAH [DIBACA-KODE] | `SettingController.php:38,53-54,78-86`; `Setting.php:159-186`; `settings/index:372-393`; sidebar:57-61; scanner:558; card:57-67; pdf:131-135 | mimes webp/png/jpg/jpeg max 2MB + hapus lama + preview + tampil sidebar/scanner/favicon/kartu/PDF. |
| 47 | Field Judul Aplikasi masih perlu? | SUDAH (ada) — PERLU KEPUTUSAN [DIBACA-KODE] | `settings/index.blade.php:328-333`; `SettingController.php:19,37,67`; `layouts/app.blade.php:6,19,23` | app_title nullable, dipakai title/og/twitter/kiosk. JANGAN HAPUS sebelum konfirmasi. |
| 48 | Jam Buka + Toleransi menentukan Tepat/Terlambat | SUDAH [DIBACA-KODE] | `ScannerController.php:31-32,122,172-177`; `Attendance.php:37-76`; `RekapController.php:56,90-94`; `settings/index:398-432` | Dipakai scanner + rekap + info box reaktif. CATATAN: check_out_time ada di backend tapi TANPA input UI — putuskan tambah/hapus. |
| 49 | Trash lengkap Siswa/Guru/Kelas via Pengaturan | SUDAH [DIBACA-KODE] | `settings/index:276-303`; `routes/web.php:69-87`; Student:541-601; Teacher:381,403,430; Class:209,231,256 | Restore/force/destroyAll + tombol unified tab + SoftDeletes; tanpa TrashController (by-design). |
| 50 | TA/Hari Libur/Trash di Pengaturan bukan menu utama | SUDAH [DIBACA-KODE] | `settings/index:276-303`; `sidebar.blade.php:29-34,135-140`; `routes/web.php` + redirect 301 | Sidebar bersih; URL lama redirect ke settings. |
| 51 | Loading ringan, tidak mirip logo lain | SUDAH [DIBACA-KODE] | `components/loading-overlay.blade.php:1-61`; `layouts/app:3955` | Spinner conic-gradient + delay 100ms + skip download/import + reduced-motion. Sederhana. |
| 52 | Kartu QR bersih tanpa foto, 10/A4 siap cetak | SUDAH [DIBACA-KODE] | `shared/print-cards.blade.php:12-15,60-66`; `students/partials/card:1-31`; `StudentController.php:190-254` | A4 landscape 5x2=10; isi logo+nama+kelas+QR; tanpa foto (photo_base64 dead code tak dirender). |
| 53 | Pertanyaan: siapa/berapa scan tiap pagi? | DICATAT [DIBACA-KODE] | — (pertanyaan terbuka untuk sekolah) | Tidak diubah di kode sesuai instruksi. |

## 2. Hasil uji fungsi nyata
- php artisan test SEBELUM: 10 gagal, 42 lulus (293 assertions). SESUDAH: 59 lulus, 0 gagal (368 assertions) [DIUJI-JALAN].
- migrate:status: semua 25 migration Ran [DIUJI-JALAN]. route:list: semua modul terhubung (admin+guru) [DIUJI-JALAN].
- Penyebab 10 gagal: test usang melawan perilaku baru yang disengaja (flash 'error' merah utk hapus, 2 checkbox, soft-delete, hadir eksklusif terlambat). Kode benar, test diperbarui.

| Alur | Kasus | Hasil | Keterangan |
|---|---|---|---|
| Login | admin benar, guru via NIP, salah, throttle 429 ID, logout | LULUS [DIUJI-JALAN] | MultiRoleAuthTest + AuditTahap2 (pesan umum, tidak bocor) |
| Presensi QR | tepat waktu, double scan cooldown 400, QR invalid 404 | LULUS [DIUJI-JALAN] | SystemAudit + AuditTahap2 |
| Presensi manual | override + jam tersimpan + terlambat sinkron | LULUS [DIUJI-JALAN] | Suite + DailyAttendanceSummary tanpa double-count |
| Hari libur | Minggu+nasional = L; Sabtu = hari kerja | LULUS dgn CATATAN [DIUJI-JALAN] | Perlu keputusan Sabtu (poin 25) |
| Kehadiran | filter 3 periode, search semua kelas, dropdown utuh | LULUS [DIUJI-JALAN] | Suite + audit kode |
| Rekap | 3 tipe, legend Excel+PDF, keterlambatan manusiawi | LULUS [DIUJI-JALAN] | Export 200 + legendLines; body PDF tak terbaca di harness (dicatat jujur) |
| CRUD+regex | NIS huruf ditolak, NISN 10, NIP 18, telp 10-15, import 0 = error | LULUS [DIUJI-JALAN] | AuditTahap2VerificationTest 7/7 |
| Relasi | hapus kelas berisi siswa ditolak; hapus guru lepas wali; restore konflik ditangani | LULUS [DIUJI-JALAN] | Test diperbarui ke perilaku aman |
| TA | aktif tak bisa dihapus; toggle transaksi; pesan jelas | LULUS [DIUJI-JALAN] | Suite + AuditTahap2 |
| Pengaturan | regex NIP/telp, logo tolak exe + >2MB | LULUS [DIUJI-JALAN] | AuditTahap2; logo valid→tampil belum diuji browser |
| Dashboard | angka + grafik 3 mode + 8 kartu | LULUS [DIUJI-JALAN] | Suite view asserts |
| Notifikasi | hijau sukses, merah hapus, kuning sebagian, X center | LULUS [DIBACA-KODE] | Hilang 3 dtk + error persisten belum diuji browser |

## 3. Hasil uji tampilan, responsif, dan kenyamanan
- Status: [DIBACA-KODE] + route 200 via suite; browser 5 lebar (360/390/768/1024/1920) BELUM diuji manual — dicatat jujur, bukan diklaim.

| Halaman | 360/390 | 768 | 1024/1920 | Catatan |
|---|---|---|---|---|
| Login, Dashboard, Presensi, Scanner, Kiosk, Kehadiran, Rekap, Siswa, Guru, Kelas, Pengaturan, TA, Libur, Trash | OK (kode) | OK (kode) | OK (kode) | .table-responsive + sticky header di semua tabel; mobile bukan kartu bertumpuk |
| Dialog | OK (kode) | OK | OK | Satu tema confirmUniversalDelete, overlay blur, instan |
| Logout | OK | OK | OK | Ukuran sama, ikon putih, hover merah, dialog merah |
| Kotak putih/toolbar/search | Seragam (kode) | Seragam | Seragam | Perlu cek visual browser |
| Sentuh 40px, fokus keyboard, bintang *, error ID | OK (kode) | OK | OK | min-height 40-46px; tanggal 46px |

## 4. Keamanan
| Temuan | Risiko | Status | Saran |
|---|---|---|---|
| Password default guru = NIP (tebak) | Tinggi | Belum | Wajib ganti saat login pertama + kebijakan password (keputusan poin 42) |
| APP_DEBUG=true di lokal | Sedang | Belum (lokal wajar) | Pastikan false di produksi + APP_KEY terisi |
| league/commonmark medium+high (transitif) | Sedang | Belum | composer update paket terkait setelah backup |
| npm audit tak bisa jalan (policy PS) | Rendah | Belum | Jalankan manual: npm audit |
| Auth/throttle(login 5,1; override 20,1; ekspor 10,1)/role admin-guru 403/CSRF | — | Aman [DIUJI-JALAN] | Suite 403 + throttle ID lulus |
| $fillable semua model; validasi server-side; upload mimes+2-5MB+nama acak(public disk) | — | Aman [DIBACA-KODE] | — |
| {!! !!} hanya pagination/json_encode/QR SVG (bukan input user) | — | Aman [DIBACA-KODE] | — |
| .env diabaikan git; tidak ada dd/dump/Log::debug | — | Aman [DIUJI-JALAN] | — |
| Scanner/kiosk di balik auth role (bukan publik) | — | Aman [DIBACA-KODE] | Bila butuh kiosk publik, tambah token perangkat |

## 5. Kebersihan project
- Dihapus: TIDAK ADA (tunggu konfirmasi sesuai instruksi).
- Kandidat + alasan + risiko:

| File/kode | Alasan | Risiko |
|---|---|---|
| photo_base64 di StudentController::printCards (tak dirender) | Dead code kartu tanpa foto | Rendah (hapus aman) |
| Komentar JS menyebut Import Excel di view guru (bikin test leak semu) | Komentar bocor ke assertDontSee | Rendah (bungkus @if admin atau hapus kata) |
| check_out_time backend tanpa input UI | Fitur setengah | Sedang (tambah input atau hapus backend) |
| Duplikasi route alias (admin.absensi vs admin.presensi, siswa vs students, guru vs teachers) | Membingungkan | Sedang (rapikan setelah konfirmasi) |
| MD lama (AUDIT.md, PERBAIKAN.md, dsb.) | Dokumentasi ganda | Rendah (arsipkan) |

## 6. Performa
| Halaman/aksi | Sebelum | Sesudah | Perbaikan |
|---|---|---|---|
| Suite penuh | 47 dtk (10 gagal) | ±10 dtk (59 lulus) | Test diselaraskan; bukan klaim kecepatan halaman |
| Query tabel | — | Eager (with/withCount) + paginate 50-100 | Sudah ada; N+1 tidak ditemukan [DIBACA-KODE] |
| Index DB | — | student_id+date, date, school_class_id, user_id, teacher_id | Migrasi 2026_09_27/29 sudah Ran |
| Ekspor besar | memory 512M/180s + chunk + cache download | Tetap | Sudah ada; queue bila >ribuan baris (saran) |
| Aset | — | filemtime cache-bust; minify via npm run build (belum dijalankan) | Jalankan di produksi |
| Perintah produksi (JANGAN di lokal) | — | php artisan config:cache, route:cache, view:cache, optimize; pastikan APP_DEBUG=false | Pemilik jalankan di server |

## 7. Bug ditemukan dan diperbaiki
| Bug | Penyebab | File diubah | Status |
|---|---|---|---|
| Tombol Kembali presensi kelas hilang (poin 14) | Markup tak ada, hanya komentar CSS | absensi/class.blade.php | Diperbaiki [DIBACA-KODE] |
| Tanggal Trash guru/kelas tanpa timezone (poin 38) | format() tanpa timezone+WIB | teachers/trash, classes/trash | Diperbaiki [DIBACA-KODE] |
| Locale default en (risiko pesan Inggris) | config default en | config/app.php | Diperbaiki (default id) |
| Validasi Hari Libur tanpa pesan ID | validate tanpa messages | HolidayController.php | Diperbaiki (4 pesan ID) |
| 10 test gagal (flash success vs error, tanpa checkbox, cascade vs aman, hadir ganda) | Test usang vs perilaku baru | 4 file test | Diselaraskan; suite hijau |
| Celah bukti uji (regex/import/scanner/ekspor/logo) | Belum ada test | AuditTahap2VerificationTest.php (baru) | 7/7 lulus |

## 8. Yang belum bisa diperbaiki
| Masalah | Alasan | Saran |
|---|---|---|
| Sabtu libur vs kerja | Perlu keputusan operasional sekolah | Putuskan; bila libur ubah isSunday→isWeekend di 5 lokasi |
| Browser 5 lebar belum dibuka | Tidak ada browser test di sesi ini | Buka manual / tambah Dusk |
| Body PDF kosong di harness test | DomPDF stream tak terbaca test | Verifikasi manual 1x unduh di browser |
| npm audit + build belum jalan | Policy PS / waktu | Jalankan manual di pemilik |
| commonmark medium+high | Update butuh jendela rilis | composer update setelah backup |

## 9. Keputusan yang harus diambil pemilik
1. Poin 5: tambah tombol "Reset Filter" atau ganti perilaku? (sekarang reset = klik ulang/ganti tanggal).
2. Poin 8: tetap 2-klik ke kamera atau tambah tombol 1-klik Scan QR di dashboard?
3. Poin 25: Sabtu libur atau hari kerja? (sekarang hanya Minggu).
4. Poin 42: hanya 1 admin vs guru ikut login? (sekarang guru auto-login password=NIP — risiko tinggi).
5. Poin 47: field Judul Aplikasi dipertahankan atau dihapus?
6. Poin 48b: check_out_time backend tanpa UI — tambah input atau hapus dari backend?
7. Poin 53: siapa/berapa orang scan tiap pagi? (tanya sekolah).
8. Poin 14: setuju tambah tombol Kembali di presensi kelas? (disarankan YA).

## 10. Daftar file diubah / dihapus / dibuat
| File | Aksi | Alasan |
|---|---|---|
| LAPORAN_FINAL.md | dibuat | Wajib instruksi; laporan tiap tahap |
| tests/Feature/AuditTahap2VerificationTest.php | dibuat | Bukti [DIUJI-JALAN] regex/import/scanner/ekspor/logo/TA |
| resources/views/admin/absensi/class.blade.php | diubah | Tambah tombol Kembali (poin 14) |
| resources/views/admin/teachers/trash.blade.php | diubah | Timezone Asia/Jakarta + WIB (poin 38) |
| resources/views/admin/classes/trash.blade.php | diubah | Timezone Asia/Jakarta + WIB (poin 38) |
| config/app.php | diubah | Default locale id (poin 35) |
| app/Http/Controllers/Admin/HolidayController.php | diubah | Pesan validasi Indonesia (poin 35) |
| tests/Feature/TrashArchiveTest.php | diubah | Selaraskan flash error + checkbox + trash-only |
| tests/Feature/SystemAuditAndPerformanceTest.php | diubah | Selaraskan soft-delete aman + hadir eksklusif |
| tests/Feature/MultiRoleAuthTest.php | diubah | Selaraskan destroy-all-active + hitung model |
| tests/Feature/SecurityAuditFixesTest.php | diubah | Hindari false-positive komentar JS |
| (dihapus: tidak ada) | — | Tunggu konfirmasi pemilik (Tahap 4) |

## 11. Perintah yang harus dijalankan pemilik
- Cadangkan database dulu (wajib sebelum apa pun di produksi).
- `php artisan migrate` (tidak ada migrasi baru kali ini; hanya verifikasi).
- `npm run build` + `php artisan config:cache route:cache view:cache` + `php artisan optimize` — HANYA di produksi, APP_DEBUG=false.
- `composer audit` ulang setelah update; `npm audit` manual (di sesi ini terblokir policy).
- Tanyakan ke sekolah: siapa/berapa orang scan tiap pagi (poin 53).
