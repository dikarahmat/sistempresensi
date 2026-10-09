@extends('layouts.app')

{{-- ===========================================================================
     HALAMAN TEMPAT SAMPAH (untuk Siswa | Guru | Kelas)
     Ketiga tab memakai route yang SUDAH ADA, masing-masing punya halaman sendiri:
       Siswa -> admin.students.trash   (halaman ini)
       Guru  -> admin.teachers.trash
       Kelas -> admin.classes.trash
     Tidak ada route, controller, atau file baru yang dibuat.

     Judul halaman memakai "Tempat Sampah" + subjudul yang sama persis di
     ketiga tab, karena judul modul di dalam konten sudah dihapus (header
     halaman di layout sudah cukup). Styling .trash-tab di bawah ini juga sama
     persis dengan teachers/trash.blade.php dan classes/trash.blade.php.
     =========================================================================== --}}

@section('title', 'Tempat Sampah')
@section('page_title', 'Tempat Sampah')
@section('page_subtitle', 'Pulihkan atau hapus permanen data yang telah dihapus')

@push('styles')
<style>
    .table-zebra-custom tbody tr:nth-child(even) > td,
    .table-zebra-custom tbody tr.baris-abu > td {
        background-color: #f8fafc !important;
    }
    .table-zebra-custom tbody tr:nth-child(odd) > td,
    .table-zebra-custom tbody tr.baris-putih > td {
        background-color: #ffffff !important;
    }
    .table-zebra-custom tbody tr:hover > td {
        background-color: #f1f5f9 !important;
    }
    .table-zebra-custom thead th {
        color: #111827 !important;
        font-size: 0.75rem !important;
        font-weight: 600 !important;
        text-transform: uppercase !important;
        letter-spacing: 0.03em;
        border-bottom: 1px solid #f1f5f9 !important;
        background-color: #f8fafc !important;
        font-family: 'Poppins', 'Roboto', sans-serif;
    }
    .table-zebra-custom tbody td {
        color: #1e293b !important;
        font-weight: 400 !important;
        text-transform: uppercase !important;
        letter-spacing: 0.03em;
        font-family: 'Poppins', 'Roboto', sans-serif;
    }
    .btn-restore {
        background-color: #059669;
        color: #ffffff;
        border: none;
        font-weight: 600;
        font-size: 0.78rem;
        border-radius: var(--clean-radius);
        padding: 0.32rem 0.7rem;
        display: inline-flex;
        align-items: center;
        gap: 0.3rem;
        transition: filter 0.15s ease;
    }
    .btn-restore:hover { filter: brightness(0.94); color: #ffffff; }
    .btn-force-delete {
        background-color: #dc2626;
        color: #ffffff;
        border: none;
        font-weight: 600;
        font-size: 0.78rem;
        border-radius: var(--clean-radius);
        padding: 0.32rem 0.7rem;
        display: inline-flex;
        align-items: center;
        gap: 0.3rem;
        transition: filter 0.15s ease;
    }
    .btn-force-delete:hover { filter: brightness(0.94); color: #ffffff; }

    /* ===== Tab Siswa | Guru | Kelas =====
       Satu deretan tombol pill. Tab aktif memakai biru solid, tab tidak aktif
       memakai abu-abu terang. Lebarnya mengikuti isi (bukan full width) dan di
       layar sempit tetap membungkus ke bawah, tanpa scroll horizontal. */
    .trash-tab {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.35rem;
        padding: 0.45rem 0.95rem;
        border: 1px solid #e2e8f0;
        border-radius: 9999px;
        background-color: #ffffff;
        color: #64748b;
        font-size: 0.8rem;
        font-weight: 600;
        line-height: 1.2;
        text-decoration: none !important;
        white-space: nowrap;
        cursor: pointer;
        transition: background-color 0.15s ease, color 0.15s ease, border-color 0.15s ease;
    }

    .trash-tab:hover:not(.trash-tab-active) {
        background-color: #f1f5f9;
        color: #1e293b;
    }

    .trash-tab-active,
    .trash-tab-active:hover {
        background-color: #2563eb;
        border-color: #2563eb;
        color: #ffffff;
        cursor: default;
    }
</style>
@endpush

@section('content')
    {{-- ===========================================================================
         DERETAN TAB SAMPAH (Siswa | Guru | Kelas)
         Diletakkan di KIRI ATAS, sejajar dengan awal konten, bukan di kanan.
         Tombol "Kembali" sudah dihapus: navigasi cukup lewat menu PENGATURAN di
         sidebar, dan di ketiga halaman Tempat Sampah item PENGATURAN-lah yang
         menyala (lihat partials/sidebar.blade.php).
         Kelas utility di bawah (d-flex + gap-2 + mb-3) sama persis dipakai di
         teachers/trash.blade.php dan classes/trash.blade.php, sehingga jarak
         header -> tab -> info -> tabel seragam di ketiga tab.
         =========================================================================== --}}
    <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
        <span class="trash-tab trash-tab-active" aria-current="page">Siswa</span>
        <a href="{{ route('admin.teachers.trash') }}" class="trash-tab">Guru</a>
        <a href="{{ route('admin.classes.trash') }}" class="trash-tab">Kelas</a>
    </div>

    <!-- Alert Notifikasi: [ .flash-notice-body (ikon + teks) ] [ tombol X ].
         Tombol X center vertikal oleh CSS notifikasi global di layout. -->
    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show border-0 rounded-3 shadow-xs small mb-3" role="alert">
        <div class="flash-notice-body">
            <div class="d-flex align-items-center">
                <i class='bx bx-check-circle fs-5 me-2 text-success'></i>
                <span>{{ session('success') }}</span>
            </div>
        </div>
        <button type="button" class="btn-close btn-close-sm" data-bs-dismiss="alert" aria-label="Tutup"></button>
    </div>
    @endif

    @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show border-0 rounded-3 shadow-xs small mb-3" role="alert">
        <div class="flash-notice-body">
            <div class="d-flex align-items-center">
                <i class='bx bx-x-circle fs-5 me-2 text-danger'></i>
                <span>{{ session('error') }}</span>
            </div>
        </div>
        <button type="button" class="btn-close btn-close-sm" data-bs-dismiss="alert" aria-label="Tutup"></button>
    </div>
    @endif

    {{-- Pesan validasi dari server (mis. pernyataan konfirmasi belum lengkap).
         Dulunya tampil di dalam modal; sekarang jadi alert di atas halaman.
         Error validasi TETAP tampil (tidak hilang otomatis) sampai user
         memperbaikinya atau menekan tombol X. --}}
    @if($errors->any())
    <div class="alert alert-danger alert-dismissible fade show border-0 rounded-3 shadow-xs small mb-3" role="alert" data-flash-persist>
        <div class="flash-notice-body">
            <div class="fw-bold mb-1"><i class='bx bx-x-circle me-1'></i> Data belum bisa diproses.</div>
            <ul class="mb-0 small ps-3">
                @foreach($errors->all() as $message)
                    <li>{{ $message }}</li>
                @endforeach
            </ul>
        </div>
        <button type="button" class="btn-close btn-close-sm" data-bs-dismiss="alert" aria-label="Tutup"></button>
    </div>
    @endif

    {{-- Isi modul SISWA. Halaman ini khusus tab "Siswa"; tab "Guru" dan
         "Kelas" punya halamannya sendiri (admin.teachers.trash dan
         admin.classes.trash) yang ditautkan dari deretan tab di atas. --}}
    <!-- Info Text: satu baris kecil, sama untuk ketiga tab -->
    <div class="mb-3">
        <p class="text-secondary mb-0" style="font-size: 0.85rem;">
            Data siswa yang dihapus akan disimpan di sini. Anda dapat memulihkan atau menghapus permanen data.
        </p>
    </div>

    <!-- Tabel Trash -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-3 bg-white">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 table-zebra-custom text-nowrap">
                <thead class="bg-slate-50 border-b border-gray-100">
                    <tr class="align-middle text-blue-500 text-xs font-bold uppercase tracking-wider border-b border-gray-100">
                        <th class="text-center py-3 text-nowrap px-3 text-blue-500 text-xs font-bold uppercase border-0 border-b border-gray-100" style="width: 1%; min-width: 45px;">NO</th>
                        <th class="text-center py-3 text-nowrap px-3 text-blue-500 text-xs font-bold uppercase border-0 border-b border-gray-100" style="width: 1%; min-width: 80px;">KELAS</th>
                        <th class="text-center py-3 text-nowrap px-3 text-blue-500 text-xs font-bold uppercase border-0 border-b border-gray-100" style="width: 12%;">NISN</th>
                        <th class="text-start py-3 text-nowrap px-3 text-blue-500 text-xs font-bold uppercase border-0 border-b border-gray-100">NAMA SISWA</th>
                        <th class="text-center py-3 text-nowrap px-3 text-blue-500 text-xs font-bold uppercase border-0 border-b border-gray-100" style="width: 15%;">TANGGAL DIHAPUS</th>
                        <th class="text-center py-3 text-nowrap px-3 text-blue-500 text-xs font-bold uppercase border-0 border-b border-gray-100" style="width: 180px;">AKSI</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($students as $student)
                    <tr class="align-middle {{ $loop->odd ? 'baris-abu' : 'baris-putih' }}">
                        <td data-label="No" class="text-center text-nowrap px-3">{{ $students->firstItem() + $loop->index }}</td>
                        <td data-label="Kelas" class="text-center text-nowrap px-3">{{ $student->schoolClass->name ?? '-' }}</td>
                        <td data-label="NISN" class="text-center text-nowrap font-monospace px-3">{{ $student->nisn ?: '-' }}</td>
                        <td data-label="Nama Siswa" class="text-start text-nowrap px-3">{{ $student->name }}</td>
                        <td data-label="Tanggal Dihapus" class="text-center text-nowrap px-3">
                            {{-- Tampilan tanggal hapus: dd MMM yyyy, HH:mm WIB
                                 (deleted_at dikonversi ke zona waktu Asia/Jakarta) --}}
                            {{ $student->deleted_at->timezone('Asia/Jakarta')->format('d M Y, H:i') }} WIB
                        </td>
                        <td data-label="Aksi" class="text-center text-nowrap px-3">
                            <div class="d-inline-flex align-items-center justify-content-center gap-2">
                                <!-- Tombol Restore -->
                                <form action="{{ panel_route('students.restore', $student->id) }}" method="POST" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn-restore" title="Pulihkan">
                                        Pulihkan
                                    </button>
                                </form>

                                <!-- Tombol Hapus Permanen -->
                                <button type="button" class="btn-force-delete" title="Hapus Permanen" onclick="confirmForceDelete('{{ $student->id }}', '{{ addslashes($student->name) }}')">
                                    Hapus Permanen
                                </button>
                                <form id="forceDeleteForm-{{ $student->id }}" action="{{ panel_route('students.force-delete', $student->id) }}" method="POST" class="d-none">
                                    @csrf
                                    @method('DELETE')
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr class="align-middle">
                        <td colspan="6" class="text-center py-5 text-muted text-nowrap empty-state">
                            <i class='bx bx-error' aria-hidden='true'></i>
                            Tempat Sampah kosong. Tidak ada data siswa yang dihapus.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {!! render_compact_pagination($students, 'daftar-arsip-siswa') !!}
    </div>

    <!-- Zona Berbahaya -->
    @if(Auth::check() && Auth::user()->role === 'admin')
    <div class="card border border-danger rounded-4 p-4 bg-white">
        <h6 class="fw-bold text-danger mb-2">Zona Berbahaya</h6>
        <p class="text-secondary small mb-3">
            Tindakan ini menghapus permanen seluruh isi Tempat Sampah ({{ $trashedCount }} data) beserta riwayat presensinya.
            Siswa yang masih aktif tidak ikut terhapus. Data yang dihapus permanen tidak dapat dikembalikan.
        </p>

        {{-- Tombol HAPUS SEMUA (PERMANEN).
             Dialog-nya memakai confirmUniversalDelete() dari layout bersama,
             jadi sama persis dengan dialog hapus di halaman lain: ikon bulat
             merah muda, judul rata tengah, deskripsi abu-abu, checkbox polos,
             dua tombol berdampingan, dan overlay gelap + blur.
            Dua field confirm_* WAJIB ikut terkirim karena
             StudentController@destroyAll memvalidasinya di server. Logika
             backend tidak diubah. --}}
        <button type="button"
                id="btnDeleteAllArchive"
                class="btn btn-danger rounded-3 px-4 fw-semibold"
                onclick="confirmDeleteAllArchive()"
                title="Hapus permanen seluruh isi Tempat Sampah"
                {{ $trashedCount === 0 ? 'disabled' : '' }}>
            Hapus Semua Siswa (Permanen)
        </button>

        <form action="{{ panel_route('students.destroy-all') }}" method="POST" id="deleteAllArchiveForm" class="d-none">
            @csrf
            @method('DELETE')
            <input type="hidden" name="confirm_permanent" value="1">
            <input type="hidden" name="confirm_all" value="1">
        </form>
    </div>
    @endif
@endsection

@push('scripts')
<script>
    /**
     * Konfirmasi HAPUS PERMANEN per baris.
     *
     * Memakai confirmUniversalDelete() dari layout bersama dengan opsi `checks`
     * yang sama persis dengan dialog "Hapus Semua dari Tempat Sampah?" di bawah,
     * sehingga tampilannya identik: ikon bulat merah muda, judul rata tengah,
     * deskripsi, dua checkbox polos, tombol Batal + "Hapus Permanen" 50/50, dan
     * tombol Hapus Permanen NONAKTIF (pudar, cursor not-allowed) sampai kedua
     * checkbox dicentang. Kalau salah satu dilepas tombolnya nonaktif lagi, dan
     * saat dialog dibuka ulang keduanya kembali kosong karena checkbox dibuat
     * ulang oleh confirmUniversalDelete(). Logika backend tidak berubah.
     */
    function confirmForceDelete(id, name) {
        // Memakai confirmUniversalDelete() dari layout bersama agar tema,
        // ukuran, dan animasinya sama persis dengan dialog hapus lain.
        confirmUniversalDelete({
            title: 'Hapus Permanen?',
            icon: 'bx-error-circle',
            html: `Data siswa <strong>${name}</strong> akan dihapus permanen dan tidak dapat dikembalikan.`,
            confirmText: 'Hapus Permanen',
            cancelText: 'Batal',
            checks: [
                'Saya memahami data yang dihapus permanen tidak dapat dikembalikan.',
                'Saya yakin ingin menghapus data ini secara permanen.'
            ],
            onConfirm: function () {
                document.getElementById(`forceDeleteForm-${id}`).submit();
            }
        });
    }

    /**
     * Konfirmasi HAPUS SELURUH isi Tempat Sampah (permanen).
     * Memakai confirmUniversalDelete() dari layout bersama supaya tema, ukuran,
     * animasi, dan tombol nonaktif-hingga-checkbox-nya SAMA PERSIS dengan
     * dialog "Hapus Seluruh Data Siswa?" di halaman Data Siswa.
     *
     * Kalau Tempat Sampah kosong, dialog tidak muncul sama sekali karena
     * tombolnya sudah disabled di sisi tampilan (blade).
     */
    function confirmDeleteAllArchive() {
        const total = {{ (int) $trashedCount }};
        const btn = document.getElementById('btnDeleteAllArchive');

        // Tidak ada yang bisa dihapus -> jangan tampilkan dialog sama sekali.
        if (total <= 0 || (btn && btn.disabled)) return;

        confirmUniversalDelete({
            title: 'Hapus Semua dari Tempat Sampah?',
            icon: 'bx-error-circle',
            html: `<strong>${total} siswa</strong> di Tempat Sampah akan dihapus permanen dan tidak bisa dikembalikan.`,
            confirmText: 'Hapus Permanen',
            cancelText: 'Batal',
            checks: [
                'Saya memahami data yang dihapus permanen tidak dapat dikembalikan.',
                'Saya yakin ingin menghapus semua data di Tempat Sampah.'
            ],
            onConfirm: function () {
                const form = document.getElementById('deleteAllArchiveForm');
                if (!form) {
                    console.error('HAPUS SEMUA TEMPAT SAMPAH: form deleteAllArchiveForm tidak ditemukan. Muat ulang halaman.');
                    return;
                }
                form.submit();
            }
        });
    }
</script>
@endpush
