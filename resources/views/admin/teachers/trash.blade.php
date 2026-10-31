@extends('layouts.app')

{{-- Judul & subjudul sengaja SAMA PERSIS dengan students/trash.blade.php dan
     classes/trash.blade.php. Judul modul di dalam konten (yang tadinya
     "Tempat Sampah Guru") sudah dihapus supaya tidak ada judul ganda: header
     dari layout sudah otomatis huruf besar. --}}
@section('title', __('Tempat Sampah'))
@section('page_title', __('Tempat Sampah'))
@section('page_subtitle', __('Pulihkan atau hapus permanen data yang telah dihapus'))

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

    /* Kontainer tabel sampah dengan jarak bawah agar tidak tertutup tombol scroll melayang */
    .trash-table-container {
        padding-bottom: 96px;
    }

    /* ===== Kartu Zona Berbahaya (Varian A - Merah Muda) ===== */
    .danger-zone-card {
        background-color: #fef2f2;
        border: 1px solid #fecaca;
        border-radius: 12px;
        padding: 16px 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 16px;
        margin-top: 24px;
    }
    .danger-zone-title {
        color: #7f1d1d;
        font-weight: 700;
        font-size: 14.5px;
        line-height: 1.35;
    }
    .danger-zone-desc {
        color: #b91c1c;
        opacity: 1;
        font-size: 13px;
        line-height: 1.4;
    }
    .danger-zone-btn {
        background-color: #dc2626;
        color: #ffffff;
        border: 1px solid #dc2626;
        font-weight: 500;
        padding: 9px 18px;
        border-radius: 10px;
        white-space: nowrap;
        font-size: 14px;
        line-height: 1.4;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        text-align: center;
        text-decoration: none;
        cursor: pointer;
        transition: background-color 0.15s ease, border-color 0.15s ease;
    }
    .danger-zone-btn:hover {
        background-color: #b91c1c;
        border-color: #b91c1c;
        color: #ffffff;
    }
    @media (max-width: 639.98px) {
        .danger-zone-card {
            flex-direction: column;
            align-items: stretch;
            gap: 14px;
        }
        .danger-zone-btn {
            width: 100%;
        }
    }

    /* Dark mode support */
    [data-bs-theme="dark"] .danger-zone-card,
    [data-theme="dark"] .danger-zone-card,
    .dark .danger-zone-card {
        background-color: rgba(220, 38, 38, 0.12) !important;
        border-color: rgba(220, 38, 38, 0.35) !important;
    }
    [data-bs-theme="dark"] .danger-zone-title,
    [data-theme="dark"] .danger-zone-title,
    .dark .danger-zone-title {
        color: #fecaca !important;
    }
    [data-bs-theme="dark"] .danger-zone-desc,
    [data-theme="dark"] .danger-zone-desc,
    .dark .danger-zone-desc {
        color: #fca5a5 !important;
    }

    /* ===== Tombol Melayang Scroll Dinamis (Ke Bawah / Ke Atas) =====
       Bulat 44px, biru tema (#2563eb), ikon putih, shadow lembut.
       Melayang di kanan bawah area konten tanpa menutupi tombol Aksi di tabel. */
    .btn-floating-scroll-down {
        position: fixed;
        bottom: 24px;
        right: 28px;
        width: 44px;
        height: 44px;
        border-radius: 50%;
        background-color: #2563eb;
        color: #ffffff !important;
        border: none;
        outline: none;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        cursor: pointer;
        z-index: 1040;
        opacity: 0;
        visibility: hidden;
        pointer-events: none;
        transform: translateY(12px) scale(0.92);
        transition: opacity 0.2s ease, transform 150ms ease, visibility 0.2s ease, background-color 150ms ease, box-shadow 150ms ease;
    }

    .btn-floating-scroll-down.is-visible {
        opacity: 1;
        visibility: visible;
        pointer-events: auto;
        transform: translateY(0) scale(1);
    }

    .btn-floating-scroll-down:hover {
        background-color: #1d4ed8;
        color: #ffffff !important;
        box-shadow: 0 6px 16px rgba(0, 0, 0, 0.2);
    }

    .btn-floating-scroll-down:active {
        transform: translateY(0) scale(0.96);
    }

    .btn-floating-scroll-down:focus-visible {
        outline: 2px solid #93c5fd;
        outline-offset: 2px;
    }

    .btn-floating-scroll-down i {
        font-size: 26px;
        line-height: 1;
        color: #ffffff;
        display: inline-block;
        transition: transform 200ms ease;
        transform: rotate(0deg);
    }

    .btn-floating-scroll-down.is-at-bottom i {
        transform: rotate(180deg);
    }

    @media (max-width: 767.98px) {
        .btn-floating-scroll-down {
            bottom: calc(var(--bottom-nav-h, 56px) + 16px);
            right: 16px;
            width: 42px;
            height: 42px;
        }
        .btn-floating-scroll-down i {
            font-size: 24px;
        }
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
        <a href="{{ route('admin.students.trash') }}" class="trash-tab">{{ __('Siswa') }}</a>
        <span class="trash-tab trash-tab-active" aria-current="page">{{ __('Guru') }}</span>
        <a href="{{ route('admin.classes.trash') }}" class="trash-tab">{{ __('Kelas') }}</a>
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
        <button type="button" class="btn-close btn-close-sm" data-bs-dismiss="alert" aria-label="{{ __('Tutup') }}"></button>
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
        <button type="button" class="btn-close btn-close-sm" data-bs-dismiss="alert" aria-label="{{ __('Tutup') }}"></button>
    </div>
    @endif

    {{-- Notifikasi sukses HAPUS: MERAH (alert-danger) + ikon tempat sampah. --}}
    @if(session('deleted'))
    <div class="alert alert-danger alert-deleted alert-dismissible fade show border-0 rounded-3 shadow-xs small mb-3" role="alert">
        <div class="flash-notice-body">
            <div class="d-flex align-items-center">
                <i class='bx bx-trash fs-5 me-2 text-danger'></i>
                <span>{{ session('deleted') }}</span>
            </div>
        </div>
        <button type="button" class="btn-close btn-close-sm" data-bs-dismiss="alert" aria-label="{{ __('Tutup') }}"></button>
    </div>
    @endif

    {{-- Error validasi: TETAP tampil (tidak hilang otomatis) sampai user
         memperbaiki isian atau menekan tombol X. --}}
    @if($errors->any())
    <div class="alert alert-danger alert-dismissible fade show border-0 rounded-3 shadow-xs small mb-3" role="alert" data-flash-persist>
        <div class="flash-notice-body">
            <div class="fw-bold mb-1"><i class='bx bx-x-circle me-1'></i> {{ __('Data belum bisa diproses.') }}</div>
            <ul class="mb-0 small ps-3">
                @foreach($errors->all() as $message)
                    <li>{{ $message }}</li>
                @endforeach
            </ul>
        </div>
        <button type="button" class="btn-close btn-close-sm" data-bs-dismiss="alert" aria-label="{{ __('Tutup') }}"></button>
    </div>
    @endif

    {{-- Judul modul di dalam konten ("Tempat Sampah Guru") + subjudulnya sudah
         DIHAPUS supaya tidak dobel dengan header halaman. Yang tersisa hanya teks
         info satu baris kecil, sama persis formatnya dengan halaman trash Siswa
         dan trash Kelas. --}}
    <div class="mb-3">
        <p class="text-secondary mb-0" style="font-size: 0.85rem;">
            {{ __('Data guru yang dihapus akan disimpan di sini. Anda dapat memulihkan atau menghapus permanen data.') }}
        </p>
    </div>

    <div class="trash-table-container">
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-3">
            <div class="table-responsive">
                <table class="table table-zebra-custom align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="text-center py-3 px-3">{{ __('No') }}</th>
                            <th class="text-center py-3 px-3">{{ __('NIP') }}</th>
                            <th class="text-center py-3 px-3">{{ __('Nama Guru') }}</th>
                            <th class="text-center py-3 px-3">{{ __('Kelas') }}</th>
                            <th class="text-center py-3 px-3">{{ __('Tanggal Dihapus') }}</th>
                            <th class="text-center py-3 px-3">{{ __('Aksi') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($teachers as $index => $teacher)
                        <tr class="{{ $loop->odd ? 'baris-abu' : 'baris-putih' }} align-middle">
                            <td data-label="{{ __('No') }}" class="text-center px-3">{{ $teachers->firstItem() + $index }}</td>
                            <td data-label="{{ __('NIP') }}" class="text-center px-3">{{ $teacher->nip }}</td>
                            <td data-label="{{ __('Nama Guru') }}" class="text-center px-3 fw-semibold">{{ $teacher->name }}</td>
                            <td data-label="{{ __('Kelas') }}" class="text-center px-3">{{ $teacher->schoolClass->name ?? '-' }}</td>
                            <td data-label="{{ __('Tanggal Dihapus') }}" class="text-center px-3">{{ $teacher->deleted_at->timezone('Asia/Jakarta')->format('d M Y, H:i') }} WIB</td>
                            <td data-label="{{ __('Aksi') }}" class="text-center px-3">
                                <div class="d-flex justify-content-center gap-2">
                                    <form action="{{ panel_route('teachers.restore', $teacher->id) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn-restore" title="{{ __('Pulihkan') }}">{{ __('Pulihkan') }}</button>
                                    </form>
                                    <button type="button" class="btn-force-delete" title="{{ __('Hapus Permanen') }}" onclick="confirmForceDeleteTeacher('{{ $teacher->id }}', '{{ addslashes($teacher->name) }}')">
                                        {{ __('Hapus Permanen') }}
                                    </button>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted empty-state">
                                <i class='bx bx-error' aria-hidden='true'></i>
                                {{ __('Tidak ada data guru di Tempat Sampah') }}
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {!! render_compact_pagination($teachers, 'daftar-arsip-guru') !!}
        </div>

        <!-- Zona Berbahaya (Gaya GitHub) -->
        @if(Auth::check() && Auth::user()->role === 'admin' && $trashedCount > 0)
        <div id="danger-zone" class="danger-zone-card">
            <div class="d-flex align-items-center" style="gap: 12px;">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#dc2626" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="flex-shrink-0" aria-hidden="true">
                    <path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"></path>
                    <line x1="12" y1="9" x2="12" y2="13"></line>
                    <line x1="12" y1="17" x2="12.01" y2="17"></line>
                </svg>
                <div>
                    <div class="danger-zone-title">{{ __('Hapus semua data guru') }}</div>
                    <div class="danger-zone-desc">{{ __('Menghapus permanen :total data. Tidak bisa dikembalikan.', ['total' => $trashedCount]) }}</div>
                </div>
            </div>

            <button type="button"
                    id="btnDeleteAllTeacherArchive"
                    class="danger-zone-btn"
                    data-bs-toggle="modal"
                    data-bs-target="#deleteAllConfirmModal"
                    title="{{ __('Hapus semua data guru') }}">
                {{ __('Hapus semua') }}
            </button>
        </div>
        @endif
    </div>

    <!-- Modal Konfirmasi Ketik HAPUS (Guru) -->
    @if(Auth::check() && Auth::user()->role === 'admin' && $trashedCount > 0)
    <div class="modal fade" id="deleteAllConfirmModal" tabindex="-1" aria-labelledby="deleteAllConfirmModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg rounded-4">
                <div class="modal-header border-bottom-0 pb-0">
                    <h5 class="fw-bold mb-0 text-dark" id="deleteAllConfirmModalLabel">
                        {{ __('Hapus semua data secara permanen?') }}
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('Tutup') }}"></button>
                </div>
                <div class="modal-body py-3">
                    <p class="text-secondary small mb-3">
                        {{ __('Menghapus permanen seluruh :total data guru di Tempat Sampah. Data yang dihapus tidak bisa dikembalikan.', ['total' => $trashedCount]) }}
                    </p>
                    <div class="mb-2">
                        <label for="confirmDeleteInput" class="form-label small fw-semibold text-secondary">
                            {{ __('Ketik HAPUS untuk melanjutkan') }}
                        </label>
                        <input type="text"
                               id="confirmDeleteInput"
                               class="form-control rounded-3"
                               placeholder="{{ __('HAPUS') }}"
                               autocomplete="off">
                    </div>
                </div>
                <div class="modal-footer border-top-0 pt-0 d-flex gap-2">
                    <button type="button" class="btn btn-light rounded-3 px-3 fw-semibold" data-bs-dismiss="modal">
                        {{ __('Batal') }}
                    </button>
                    <button type="button" id="btnConfirmDeleteAllModal" class="btn btn-danger rounded-3 px-3 fw-semibold" disabled>
                        {{ __('Ya, hapus semua') }}
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- Tombol Melayang (Floating Button) Scroll Dinamis (Ke Bawah / Ke Atas) --}}
    @if(Auth::check() && Auth::user()->role === 'admin' && $trashedCount > 0)
    <button type="button"
            id="btnScrollToDangerZone"
            class="btn-floating-scroll-down"
            title="{{ __('Ke bawah') }}"
            aria-label="{{ __('Ke bawah') }}">
        <i class='bx bx-chevron-down' aria-hidden="true"></i>
    </button>
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
            title: {!! json_encode(__('Hapus Permanen?')) !!},
            icon: 'bx-error-circle',
            html: {!! json_encode(__('Data guru')) !!} + ' <strong>' + name + '</strong> ' + {!! json_encode(__('akan dihapus permanen dan tidak dapat dikembalikan.')) !!},
            confirmText: {!! json_encode(__('Hapus Permanen')) !!},
            cancelText: {!! json_encode(__('Batal')) !!},
            checks: [
                {!! json_encode(__('Saya memahami data yang dihapus permanen tidak dapat dikembalikan.')) !!},
                {!! json_encode(__('Saya yakin ingin menghapus data ini secara permanen.')) !!}
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
     * Modal Konfirmasi HAPUS SEMUA isi Tempat Sampah Guru.
     * Menggunakan modal gaya aplikasi dengan validasi ketik "HAPUS".
     */
    document.addEventListener('DOMContentLoaded', function () {
        const modalEl = document.getElementById('deleteAllConfirmModal');
        if (!modalEl) return;

        const input = document.getElementById('confirmDeleteInput');
        const confirmBtn = document.getElementById('btnConfirmDeleteAllModal');
        const form = document.getElementById('deleteAllTeacherArchiveForm');

        modalEl.addEventListener('show.bs.modal', function () {
            if (input) {
                input.value = '';
            }
            if (confirmBtn) {
                confirmBtn.disabled = true;
            }
        });

        modalEl.addEventListener('shown.bs.modal', function () {
            if (input) {
                input.focus();
            }
        });

        if (input && confirmBtn) {
            input.addEventListener('input', function () {
                const val = this.value.trim().toUpperCase();
                confirmBtn.disabled = (val !== 'HAPUS');
            });

            input.addEventListener('keydown', function (e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    if (!confirmBtn.disabled) {
                        confirmBtn.click();
                    }
                }
            });
        }

        if (confirmBtn && form) {
            confirmBtn.addEventListener('click', function () {
                if (confirmBtn.disabled) return;
                confirmBtn.disabled = true;
                form.submit();
            });
        }
    });

    function confirmDeleteAllTeacherArchive() {
        const modalEl = document.getElementById('deleteAllConfirmModal');
        if (!modalEl) return;
        const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
        modal.show();
    }

    /**
     * Tombol Floating Tunggal Dinamis (Scroll ke Bawah / Kembali ke Atas).
     *
     * Perilaku:
     * - Tampil hanya bila konten memiliki overflow vertikal (bisa di-scroll).
     * - Belum sampai bawah: ikon panah bawah, title/aria "Ke bawah".
     *   Klik = smooth scroll ke paling bawah (Zona Berbahaya).
     * - Sudah sampai bawah (jarak ke dasar container < 40px):
     *   Ikon berputar 180° jadi panah atas (animasi 200ms), title/aria "Ke atas".
     *   Klik = smooth scroll kembali ke paling atas.
     * - Deteksi scroll menggunakan listener pada container scrollbar (.flex-1)
     *   dengan passive: true dan window.requestAnimationFrame.
     * - Cek ulang otomatis saat load, resize window, perubahan baris (MutationObserver),
     *   maupun navigasi tab.
     */
    function initDynamicScrollButton() {
        const btn = document.getElementById('btnScrollToDangerZone');
        if (!btn) return;

        function getScrollContainer() {
            const el = document.querySelector('.content-scroll-wrapper > main > .flex-1') ||
                       document.querySelector('main > .flex-1');
            if (el) return el;

            let parent = btn.parentElement;
            while (parent && parent !== document.body && parent !== document.documentElement) {
                const style = window.getComputedStyle(parent);
                if (style.overflowY === 'auto' || style.overflowY === 'scroll') {
                    return parent;
                }
                parent = parent.parentElement;
            }
            return document.documentElement;
        }

        const container = getScrollContainer();
        if (!container) return;

        let ticking = false;

        function checkPosition() {
            if (!btn || !container) return;

            const isScrollable = (container.scrollHeight - container.clientHeight) > 40;
            if (!isScrollable) {
                btn.classList.remove('is-visible');
                return;
            }

            btn.classList.add('is-visible');

            // Hitung sudah di bawah dengan: scrollTop + clientHeight >= scrollHeight - 40
            const isAtBottom = (container.scrollTop + container.clientHeight) >= (container.scrollHeight - 40);

            if (isAtBottom) {
                btn.classList.add('is-at-bottom');
                btn.setAttribute('title', {!! json_encode(__('Ke atas')) !!});
                btn.setAttribute('aria-label', {!! json_encode(__('Ke atas')) !!});
            } else {
                btn.classList.remove('is-at-bottom');
                btn.setAttribute('title', {!! json_encode(__('Ke bawah')) !!});
                btn.setAttribute('aria-label', {!! json_encode(__('Ke bawah')) !!});
            }
        }

        function requestCheck() {
            if (!ticking) {
                window.requestAnimationFrame(function () {
                    checkPosition();
                    ticking = false;
                });
                ticking = true;
            }
        }

        btn.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();

            const isAtBottom = (container.scrollTop + container.clientHeight) >= (container.scrollHeight - 40);

            if (isAtBottom) {
                // Smooth scroll kembali ke paling atas
                try {
                    container.scrollTo({ top: 0, behavior: 'smooth' });
                } catch (err) {
                    container.scrollTop = 0;
                }
            } else {
                // Smooth scroll ke paling bawah (Zona Berbahaya / tombol Hapus semua)
                try {
                    container.scrollTo({ top: container.scrollHeight, behavior: 'smooth' });
                } catch (err) {
                    container.scrollTop = container.scrollHeight;
                }
            }

            setTimeout(requestCheck, 150);
            setTimeout(requestCheck, 400);
            setTimeout(requestCheck, 800);
        });

        // Pasang listener scroll pada container yang benar, bukan window
        container.addEventListener('scroll', requestCheck, { passive: true });
        window.addEventListener('resize', requestCheck, { passive: true });
        window.addEventListener('pageshow', requestCheck, { passive: true });
        window.addEventListener('load', requestCheck, { passive: true });

        // Cek ulang saat jumlah baris berubah (setelah pulihkan atau hapus data)
        if ('MutationObserver' in window) {
            const mutObserver = new MutationObserver(function () {
                requestCheck();
            });
            mutObserver.observe(container, { childList: true, subtree: true });
        }

        // Cek ulang saat ukuran kontainer berubah
        if ('ResizeObserver' in window) {
            const resObserver = new ResizeObserver(function () {
                requestCheck();
            });
            resObserver.observe(container);
        }

        // Cek posisi saat halaman dimuat
        requestCheck();
        setTimeout(requestCheck, 100);
        setTimeout(requestCheck, 350);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initDynamicScrollButton);
    } else {
        initDynamicScrollButton();
    }
</script>
@endpush
