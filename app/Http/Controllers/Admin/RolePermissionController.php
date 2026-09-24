<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\Request;

class RolePermissionController extends Controller
{
    /**
     * Definisi modul dan permission esensial aplikasi.
     */
    public static function getModuleDefinitions(): array
    {
        return [
            'dashboard' => [
                'title' => 'Dashboard',
                'description' => 'Akses pemantauan statistik, grafik kehadiran, dan ringkasan data harian.',
                'icon' => 'bx-home-alt',
                'permissions' => [
                    'dashboard.view' => [
                        'label' => 'Akses Ringkasan Statistik',
                        'desc' => 'Melihat angka rekapitulasi kehadiran total, grafik perbandingan, dan persentase kehadiran.',
                    ],
                    'dashboard.realtime' => [
                        'label' => 'Pemantauan Kehadiran Real-time',
                        'desc' => 'Melihat aliran data presensi siswa yang masuk hari ini secara langsung.',
                    ],
                    'dashboard.calendar' => [
                        'label' => 'Widget Agenda & Kalender Akademik',
                        'desc' => 'Melihat status hari efektif belajar dan jadwal kegiatan sekolah.',
                    ],
                ],
            ],
            'absensi' => [
                'title' => 'Absensi',
                'description' => 'Operasional input presensi, pemindaian QR code, dan penyesuaian status kehadiran.',
                'icon' => 'bx-calendar-check',
                'permissions' => [
                    'absensi.scan' => [
                        'label' => 'Pemindai QR Code Siswa',
                        'desc' => 'Mengoperasikan scanner kamera webcam maupun barcode scanner eksternal.',
                    ],
                    'absensi.kiosk' => [
                        'label' => 'Mode Gerbang / Kiosk Mandiri',
                        'desc' => 'Menjalankan antarmuka scanner layar penuh untuk terminal gerbang sekolah.',
                    ],
                    'absensi.manual' => [
                        'label' => 'Pencatatan Presensi Manual',
                        'desc' => 'Menginput kehadiran manual untuk siswa sakit, izin, atau tanpa keterangan.',
                    ],
                    'absensi.override' => [
                        'label' => 'Koreksi & Override Status Kehadiran',
                        'desc' => 'Mengubah rekaman log presensi yang telah tersimpan apabila terdapat kekeliruan.',
                    ],
                ],
            ],
            'rekap' => [
                'title' => 'Rekap',
                'description' => 'Laporan rekapitulasi presensi berkala serta ekspor dokumen resmi.',
                'icon' => 'bx-folder',
                'permissions' => [
                    'rekap.view' => [
                        'label' => 'Melihat Rekapitulasi Presensi',
                        'desc' => 'Membuka tabel rekap kehadiran harian, mingguan, maupun bulanan.',
                    ],
                    'rekap.export_excel' => [
                        'label' => 'Ekspor Laporan Excel (.xlsx)',
                        'desc' => 'Mengunduh rekap presensi terformat rapi dalam bentuk berkas spreadsheet.',
                    ],
                    'rekap.export_pdf' => [
                        'label' => 'Cetak & Unduh Laporan PDF',
                        'desc' => 'Menghasilkan lembar laporan presensi resmi lengkap dengan kop sekolah.',
                    ],
                ],
            ],
            'master' => [
                'title' => 'Master Data',
                'description' => 'Manajemen data pokok siswa, rombongan belajar, tenaga pendidik, dan kalender.',
                'icon' => 'bx-data',
                'permissions' => [
                    'master.students' => [
                        'label' => 'Kelola Data Siswa & Kartu QR',
                        'desc' => 'Tambah, ubah, hapus biodata siswa, dan cetak kartu presensi berbasis QR code.',
                    ],
                    'master.teachers' => [
                        'label' => 'Kelola Guru & Penugasan Wali Kelas',
                        'desc' => 'Pencatatan data pengajar dan penentuan wali kelas masing-masing rombel.',
                    ],
                    'master.classes' => [
                        'label' => 'Kelola Kelas & Kenaikan Tingkat',
                        'desc' => 'Struktur ruang kelas, mutasi rombel, serta proses kenaikan kelas massal.',
                    ],
                    'master.academic' => [
                        'label' => 'Tahun Ajaran & Kalender Libur',
                        'desc' => 'Konfigurasi semester aktif serta penetapan hari libur nasional / khusus.',
                    ],
                ],
            ],
            'settings' => [
                'title' => 'Pengaturan',
                'description' => 'Konfigurasi parameter sistem, jam kerja sekolah, dan dokumentasi petunjuk teknis.',
                'icon' => 'bx-cog',
                'permissions' => [
                    'settings.school' => [
                        'label' => 'Profil Sekolah & Jam Toleransi',
                        'desc' => 'Mengubah nama sekolah, logo, jam batas masuk, dan menit toleransi keterlambatan.',
                    ],
                    'settings.roles' => [
                        'label' => 'Manajemen Role & Otoritas Akses',
                        'desc' => 'Menyesuaikan batasan izin dan kewenangan setiap tingkatan pengguna.',
                    ],
                    'settings.documentation' => [
                        'label' => 'Akses Buku Panduan & Alur Sistem',
                        'desc' => 'Membuka panduan operasional teknis penggunaan aplikasi terpadu.',
                    ],
                ],
            ],
        ];
    }

    /**
     * Hak akses default per role.
     */
    public static function getDefaultPermissions(string $role): array
    {
        $allPermissions = [];
        foreach (self::getModuleDefinitions() as $module) {
            foreach (array_keys($module['permissions']) as $permKey) {
                $allPermissions[] = $permKey;
            }
        }

        return match ($role) {
            'admin' => $allPermissions, // Full akses ke semua modul
            'kesiswaan' => [
                'dashboard.view',
                'dashboard.realtime',
                'dashboard.calendar',
                'absensi.scan',
                'absensi.manual',
                'rekap.view',
                'rekap.export_excel',
                'rekap.export_pdf',
                'master.students',
                'master.teachers',
                'master.classes',
                'settings.documentation',
            ],
            'guru' => [
                'dashboard.view',
                'dashboard.calendar',
                'absensi.scan',
                'absensi.manual',
                'absensi.override',
                'rekap.view',
                'rekap.export_excel',
                'rekap.export_pdf',
                'master.students',
                'settings.documentation',
            ],
            default => [],
        };
    }

    /**
     * Dapatkan permissions aktif untuk role.
     */
    public static function getRolePermissions(string $role): array
    {
        $stored = Setting::get('role_perms_' . $role);
        if ($stored) {
            $decoded = json_decode($stored, true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        return self::getDefaultPermissions($role);
    }

    /**
     * Tampilkan halaman Manajemen Role & Permission.
     */
    public function index(Request $request)
    {
        $modules = self::getModuleDefinitions();
        
        $roleCounts = [
            'admin' => User::where('role', 'admin')->count(),
            'kesiswaan' => User::where('role', 'kesiswaan')->count(),
            'guru' => User::where('role', 'guru')->count(),
        ];

        $rolePermissions = [
            'admin' => self::getRolePermissions('admin'),
            'kesiswaan' => self::getRolePermissions('kesiswaan'),
            'guru' => self::getRolePermissions('guru'),
        ];

        $activeRole = $request->query('role', 'admin');
        if (!in_array($activeRole, ['admin', 'kesiswaan', 'guru'], true)) {
            $activeRole = 'admin';
        }

        $allPermissionKeys = [];
        foreach ($modules as $module) {
            foreach (array_keys($module['permissions']) as $permKey) {
                $allPermissionKeys[] = $permKey;
            }
        }

        return view('admin.roles.index', compact('modules', 'roleCounts', 'rolePermissions', 'activeRole', 'allPermissionKeys'));
    }

    /**
     * Simpan pembaruan permission role.
     */
    public function update(Request $request)
    {
        $role = $request->input('role');
        if (!in_array($role, ['admin', 'kesiswaan', 'guru'], true)) {
            return back()->with('error', 'Role pengguna tidak valid.');
        }

        // Admin selalu mempertahankan full access
        if ($role === 'admin') {
            $permissions = self::getDefaultPermissions('admin');
        } else {
            $permissions = $request->input('permissions', []);
            if (!is_array($permissions)) {
                $permissions = [];
            }
        }

        Setting::set('role_perms_' . $role, json_encode(array_values($permissions)));

        $roleLabel = match ($role) {
            'admin' => 'Administrator',
            'kesiswaan' => 'Kesiswaan',
            'guru' => 'Walikelas / Guru',
            default => $role,
        };

        return back()->with('success', "Konfigurasi permission untuk role {$roleLabel} berhasil diperbarui.")
                     ->with('active_role', $role);
    }
}
