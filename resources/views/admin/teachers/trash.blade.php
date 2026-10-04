@extends('layouts.app')

{{-- Judul & subjudul sengaja SAMA PERSIS dengan students/trash.blade.php dan
     classes/trash.blade.php. Judul modul di dalam konten (yang tadinya
     "Tempat Sampah Guru") sudah dihapus supaya tidak ada judul ganda: header
     dari layout sudah otomatis huruf besar. --}}
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
        border-radius: 6px;
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
        border-radius: 6px;
        padding: 0.32rem 0.7rem;
        display: inline-flex;
        align-items: center;
        gap: 0.3rem;
        transition: filter 0.15s ease;
    }
    .btn-force-delete:hover { filter: brightness(0.94); color: #ffffff; }

    /* ===== Tab Siswa | Guru | Kelas =====
       Deretan tombol pill; gaya ini sama persis dengan tab di
       students/trash.blade.php supaya ketiga halaman Tempat Sampah
       terasa satu kesatuan. Tab aktif biru solid, tab lain abu-abu terang. */
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
         Di KIRI ATAS, sejajar dengan awal konten. Tombol "Kembali" (baik yang
         ke Pengaturan maupun yang ke Data Guru) sudah dihapus: navigasi cukup
         lewat menu PENGATURAN di sidebar. Kelas utility dan jarak (gap-2 mb-3)
         sama persis dengan dua halaman trash lain.
         =========================================================================== --}}
    <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
        <a href="{{ route('admin.students.trash') }}" class="trash-tab">Siswa</a>
        <span class="trash-tab trash-tab-active" aria-current="page">Guru</span>
        <a href="{{ route('admin.classes.trash') }}" class="trash-tab">Kelas</a>
    </div>

    {{-- Notifikasi. Layout tidak menampilkan flash secara global, jadi blok ini
         placed di sini. Notifikasi hapus memakai flash 'error' supaya tampil
         MERAH (alert-danger); pemulihkan memakai 'success' (hijau).
         Struktur: [ .flash-notice-body (ikon + teks) ] [ tombol X ] supaya
         tombol X center vertikal oleh CSS notifikasi global di layout. --}}
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

    {{-- Error validasi: TETAP tampil (tidak hilang otomatis) sampai user
         memperbaiki isian atau menekan tombol X. --}}
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

    {{-- Judul modul di dalam konten ("Tempat Sampah Guru") + subjudulnya sudah
         DIHAPUS supaya tidak dobel dengan header halaman. Yang tersisa hanya teks
         info satu baris kecil, sama persis formatnya dengan halaman trash Siswa
         dan trash Kelas. --}}
    <div class="mb-3">
        <p class="text-secondary mb-0" style="font-size: 0.85rem;">
            Data guru yang dihapus akan disimpan di sini. Anda dapat memulihkan atau menghapus permanen data.
        </p>
    </div>

    <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-3">
        <div class="table-responsive">
            <table class="table table-zebra-custom align-middle mb-0">
                <thead>
                    <tr>
                        <th class="text-center py-3 px-3">NO</th>
                        <th class="text-center py-3 px-3">NIP</th>
                        <th class="text-center py-3 px-3">NAMA GURU</th>
                        <th class="text-center py-3 px-3">KELAS</th>
                        <th class="text-center py-3 px-3">TANGGAL DIHAPUS</th>
                        <th class="text-center py-3 px-3">AKSI</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($teachers as $index => $teacher)
                    <tr class="{{ $loop->odd ? 'baris-abu' : 'baris-putih' }} align-middle">
                        <td data-label="No" class="text-center px-3">{{ $teachers->firstItem() + $index }}</td>
                        <td data-label="NIP" class="text-center px-3">{{ $teacher->nip }}</td>
                        <td data-label="Nama Guru" class="text-center px-3 fw-semibold">{{ $teacher->name }}</td>
                        <td data-label="Kelas" class="text-center px-3">{{ $teacher->schoolClass->name ?? '-' }}</td>
                        <td data-label="Tanggal Dihapus" class="text-center px-3">{{ $teacher->deleted_at->timezone('Asia/Jakarta')->format('d M Y, H:i') }} WIB</td>
                        <td data-label="Aksi" class="text-center px-3">
                            <div class="d-flex justify-content-center gap-2">
                                <form action="{{ panel_route('teachers.restore', $teacher->id) }}" method="POST" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn-restore" title="Pulihkan">Pulihkan</button>
                                </form>
                                <button type="button" class="btn-force-delete" title="Hapus Permanen" onclick="confirmForceDeleteTeacher('{{ $teacher->id }}', '{{ addslashes($teacher->name) }}')">
                                    Hapus Permanen
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center py-5 text-muted">
                            <i class='bx bx-inbox fs-1 d-block mb-2 opacity-50'></i>
                            Tidak ada data guru di Tempat Sampah
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {!! render_compact_pagination($teachers, 'daftar-arsip-guru') !!}
    </div>

    <!-- Zona Berbahaya: HAPUS SEMUA isi Tempat Sampah Guru (permanen).
         Dialog-nya memakai confirmUniversalDelete() dari layout bersama,
         jadi sama persis dengan dialog di halaman Sampah Siswa: ikon bulat
         merah muda, judul rata tengah, dua checkbox polos, tombol Batal +
         "Hapus Permanen" 50/50, dan tombol NONAKTIF (pudar, not-allowed)
         sampai kedua checkbox dicentang. -->
    @if(Auth::check() && Auth::user()->role === 'admin')
    <div class="card border border-danger rounded-4 p-4 bg-white">
        <h6 class="fw-bold text-danger mb-2">Zona Berbahaya</h6>
        <p class="text-secondary small mb-3">
            Tindakan ini menghapus permanen seluruh isi Tempat Sampah ({{ $trashedCount }} data guru).
            Guru yang masih aktif tidak ikut terhapus. Data yang dihapus permanen tidak dapat dikembalikan.
        </p>

        <button type="button"
                id="btnDeleteAllTeacherArchive"
                class="btn btn-danger rounded-3 px-4 fw-semibold"
                onclick="confirmDeleteAllTeacherArchive()"
                title="Hapus permanen seluruh isi Tempat Sampah Guru"
                {{ $trashedCount === 0 ? 'disabled' : '' }}>
            Hapus Semua Guru (Permanen)
        </button>
    </div>
    @endif

<!-- Form tersembunyi untuk konfirmasi hapus permanen. Dialog-nya sendiri
     dibuat oleh confirmUniversalDelete() dari layout bersama, sehingga tema,
     ukuran, ikon, checkbox, tombol, dan overlay-nya sama persis dengan
     dialog hapus di halaman lain. Dipasang DI LUAR tabel supaya tidak
     terpotong atau ikut ter-scroll. -->
<form id="forceDeleteTeacherForm" method="POST" class="d-none"
      data-url-base="{{ panel_route('teachers.force-delete', '__ID__') }}">
    @csrf
    @method('DELETE')
</form>

{{-- Form tersembunyi untuk HAPUS SEMUA isi Tempat Sampah Guru. --}}
<form action="{{ route('admin.teachers.trash-destroy-all') }}" method="POST" id="deleteAllTeacherArchiveForm" class="d-none">
    @csrf
    @method('DELETE')
</form>

@endsection

@push('scripts')
<script>
    /**
     * Konfirmasi HAPUS PERMANEN per baris (guru).
     *
     * Memakai confirmUniversalDelete() dari layout bersama dengan opsi `checks`
     * yang sama persis dengan dialog "Hapus Semua dari Tempat Sampah?" di
     * halaman Tempat Sampah Siswa, sehingga tampilannya identik: ikon bulat
     * merah muda, judul rata tengah, deskripsi, dua checkbox polos, tombol
     * Batal + "Hapus Permanen" 50/50, dan tombol Hapus Permanen NONAKTIF
     * (pudar, cursor not-allowed) sampai kedua checkbox dicentang. Kalau
     * salah satu dilepas tombolnya nonaktif lagi, dan saat dialog dibuka ulang
     * keduanya kembali kosong karena checkbox dibuat ulang oleh
     * confirmUniversalDelete(). Logika backend tidak berubah.
     */
    function confirmForceDeleteTeacher(id, name) {
        confirmUniversalDelete({
            title: 'Hapus Permanen?',
            icon: 'bx-error-circle',
            html: `Data guru <strong>${name}</strong> akan dihapus permanen dan tidak dapat dikembalikan.`,
            confirmText: 'Hapus Permanen',
            cancelText: 'Batal',
            checks: [
                'Saya memahami data yang dihapus permanen tidak dapat dikembalikan.',
                'Saya yakin ingin menghapus data ini secara permanen.'
            ],
            onConfirm: function () {
                const form = document.getElementById('forceDeleteTeacherForm');
                if (!form) {
                    console.error('HAPUS PERMANEN GURU: form forceDeleteTeacherForm tidak ditemukan. Muat ulang halaman.');
                    return;
                }
                // __ID__ diganti id guru yang dipilih (URL dasar dari route).
                form.action = form.getAttribute('data-url-base').replace('__ID__', id);
                form.submit();
            }
        });
    }

    /**
     * Konfirmasi HAPUS SELURUH isi Tempat Sampah Guru (permanen).
     *
     * Memakai confirmUniversalDelete() dari layout bersama supaya tema, ukuran,
     * animasi, dan tombol nonaktif-hingga-checkbox-nya SAMA PERSIS dengan
     * dialog "Hapus Semua dari Tempat Sampah?" di halaman Sampah Siswa.
     *
     * Kalau Tempat Sampah kosong, dialog tidak muncul sama sekali karena
     * tombolnya sudah disabled di sisi tampilan (blade).
     */
    function confirmDeleteAllTeacherArchive() {
        const total = {{ (int) $trashedCount }};
        const btn = document.getElementById('btnDeleteAllTeacherArchive');

        // Tidak ada yang bisa dihapus -> jangan tampilkan dialog sama sekali.
        if (total <= 0 || (btn && btn.disabled)) return;

        confirmUniversalDelete({
            title: 'Hapus Semua dari Tempat Sampah?',
            icon: 'bx-error-circle',
            html: `<strong>${total} guru</strong> di Tempat Sampah akan dihapus permanen dan tidak bisa dikembalikan.`,
            confirmText: 'Hapus Permanen',
            cancelText: 'Batal',
            checks: [
                'Saya memahami data yang dihapus permanen tidak dapat dikembalikan.',
                'Saya yakin ingin menghapus semua data di Tempat Sampah.'
            ],
            onConfirm: function () {
                const form = document.getElementById('deleteAllTeacherArchiveForm');
                if (!form) {
                    console.error('HAPUS SEMUA TEMPAT SAMPAH GURU: form deleteAllTeacherArchiveForm tidak ditemukan. Muat ulang halaman.');
                    return;
                }
                form.submit();
            }
        });
    }
</script>
@endpush
