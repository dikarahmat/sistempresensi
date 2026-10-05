@extends('layouts.app')

@section('title', 'Tahun Ajaran')
@section('page_title', 'Tahun Ajaran')
@section('page_subtitle', 'Kelola periode akademik dan tentukan satu tahun ajaran aktif sistem.')

@section('page_header_right')
<!-- Tombol Aksi Header Versi Desktop (>= 768px): Sejajar Horizontal Asli -->
<div class="d-none d-md-block">
    <button type="button" class="btn btn-add-year btn-sm shadow-xs" data-bs-toggle="modal" data-bs-target="#addYearModal">
        Tambah Tahun Ajaran
    </button>
</div>
@endsection

@push('styles')
<style>
    /* Tombol & Card */
    .btn-add-year {
        background-color: #3b82f6;
        color: #ffffff;
        border: none;
        font-weight: 600;
        font-size: 0.88rem;
        border-radius: var(--clean-radius);
        padding: 0.45rem 1rem;
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        box-shadow: 0 1px 2px rgba(59, 130, 246, 0.15);
        transition: all 0.15s ease;
    }
    .btn-add-year:hover { background-color: #2563eb; color: #ffffff; transform: translateY(-1px); }

    /* Tabel Tahun Ajaran */
    .table-years {
        width: 100%;
        min-width: 600px;
        margin-bottom: 0;
    }

    .table-years thead th {
        text-transform: uppercase !important;
    }

    .table-years tbody td {
        text-transform: uppercase !important;
    }

    .table-responsive {
        -webkit-overflow-scrolling: touch;
        overflow-x: auto;
        scrollbar-width: thin;
    }

    /* Zebra Striping Khusus (Sesuai Benchmark Data Siswa) */
    .table-years tbody tr:nth-child(even) > td,
    .table-years tbody tr.baris-abu > td,
    .table-zebra-custom tbody tr:nth-child(even) > td,
    .table-zebra-custom tbody tr.baris-abu > td {
        background-color: #f1f5f9 !important;
    }
    .table-years tbody tr:nth-child(odd) > td,
    .table-years tbody tr.baris-putih > td,
    .table-zebra-custom tbody tr:nth-child(odd) > td,
    .table-zebra-custom tbody tr.baris-putih > td {
        background-color: #ffffff !important;
    }
    .table-years tbody tr.baris-abu:hover > td,
    .table-years tbody tr.baris-putih:hover > td,
    .table-years tbody tr:hover > td,
    .table-zebra-custom tbody tr.baris-abu:hover > td,
    .table-zebra-custom tbody tr.baris-putih:hover > td,
    .table-zebra-custom tbody tr:hover > td {
        background-color: #e2e8f0 !important;
    }

    .table-years th,
    .table-years td {
        vertical-align: middle;
    }

    .table-years tbody td {
        color: #000000 !important;
        font-weight: 400 !important;
        font-size: 0.85rem;
        padding: 0.85rem 1rem;
    }

    .col-status-aktif {
        display: inline-block;
    }

    .crud-center-wrapper {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.35rem;
    }


    .form-check-input:checked {
        background-color: #059669;
        border-color: #059669;
    }

    .pagination-compact .pagination {
        margin-bottom: 0;
        font-size: 0.82rem;
    }
    .pagination-compact .page-link {
        padding: 0.25rem 0.6rem;
    }
</style>
@endpush

@section('content')
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

    <!-- Bantuan Singkat Aturan Tahun Ajaran Aktif (di bawah judul halaman) -->
    <div class="d-flex align-items-start gap-2 mb-3">
        <i class='bx bx-info-circle text-muted mt-1'></i>
        <span class="small text-muted" style="line-height: 1.5;">
            Hanya <b class="text-dark">satu tahun ajaran</b> yang dapat aktif dalam satu waktu.
            Untuk memindahkannya, klik tombol <b class="text-dark">AKTIFKAN</b> pada tahun ajaran lain
            &mdash; tahun ajaran yang sedang aktif otomatis dinonaktifkan.
            Tahun ajaran yang sedang berlangsung tidak dapat dihapus.
        </span>
    </div>

    <!-- ========================================================================= -->
    <!-- 1. KONTEN KHUSUS MOBILE (< 768px): KARTU KONTINER TOMBOL AKSI               -->
    <!-- ========================================================================= -->
    <div class="d-block d-md-none mb-3">
        <div class="card border border-light-subtle shadow-sm rounded-3 p-3 bg-white">
            <button type="button" class="btn btn-primary btn-sm w-100 rounded-3 shadow-xs d-inline-flex align-items-center justify-content-center gap-1.5 py-1.5 px-3 fw-semibold" data-bs-toggle="modal" data-bs-target="#addYearModal">
                <span>Tambah Tahun Ajaran</span>
            </button>
        </div>
    </div>

    <!-- Tabel Data Tahun Ajaran -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 table-years text-nowrap">
                <thead class="bg-light">
                    <tr class="text-secondary small fw-bold align-middle text-uppercase text-nowrap" style="letter-spacing: 0.03em;">
                        <th class="text-center py-3 text-nowrap px-3" style="width: 6%;">NO</th>
                        <th class="text-center py-3 text-nowrap px-3">Tahun Ajaran</th>
                        <th class="text-center py-3 text-nowrap px-3">Semester</th>
                        <th class="text-center py-3 text-nowrap px-3 d-none d-md-table-cell">Periode Tanggal</th>
                        <th class="text-center py-3 text-nowrap px-3">
                            <span class="col-status-aktif">Status Aktif</span>
                        </th>
                        <th class="text-center py-3 text-nowrap px-3" style="width: 11%;">Aksi</th>
                    </tr>
                </thead>
                <tbody class="text-uppercase">
                    @forelse($academicYears as $index => $year)
                    <tr class="{{ $loop->odd ? 'baris-abu' : 'baris-putih' }} align-middle text-nowrap">
                        <td data-label="No" class="text-center text-nowrap px-3">{{ $academicYears->firstItem() + $index }}</td>
                        <td data-label="Tahun Ajaran" class="text-center text-nowrap px-3">
                            {{-- Kolom ini hanya berisi nama tahun ajaran (mis. "2026/2027").
                                 Status "AKTIF" hanya tampil di kolom Status Aktif. --}}
                            <span>{{ $year->name }}</span>
                        </td>
                        <td data-label="Semester" class="text-center text-nowrap px-3">
                            Semester {{ $year->semester }}
                        </td>
                        <td data-label="Periode Tanggal" class="text-center text-nowrap px-3 d-none d-md-table-cell">
                            @php \Carbon\Carbon::setLocale('id'); @endphp
                            {{ \Carbon\Carbon::parse($year->start_date)->translatedFormat('d M Y') }} &mdash; 
                            {{ \Carbon\Carbon::parse($year->end_date)->translatedFormat('d M Y') }}
                        </td>
                        <td data-label="Status Aktif" class="text-center text-nowrap px-3">
                            <span class="col-status-aktif">
                                @if($year->is_active)
                                    <span class="d-inline-flex align-items-center gap-1 fw-bold text-success" style="font-size: 0.78rem;" title="Tahun ajaran ini sedang aktif di sistem">
                                        <i class='bx bx-check-circle'></i> AKTIF
                                    </span>
                                @else
                                    {{-- Konfirmasi ada di onsubmit (bukan onclick tombol) supaya
                                         tombol type="submit" tetap mengirim form walau JS gagal
                                         dimuat; lihat confirmToggleActive() di bawah. --}}
                                    <form action="{{ panel_route('academic-years.toggle-active', $year->id) }}" method="POST" id="activateYearForm-{{ $year->id }}" class="d-inline m-0"
                                          data-no-loader
                                          onsubmit="return confirmToggleActive(this, '{{ $year->id }}', '{{ $year->name }}', '{{ $year->semester }}')">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-success" title="Jadikan tahun ajaran ini sebagai tahun ajaran aktif">
                                            Aktifkan
                                        </button>
                                    </form>
                                @endif
                            </span>
                        </td>
                        <td data-label="Aksi" class="text-center text-nowrap px-3">
                            <div class="crud-center-wrapper">
                                <!-- Tombol Edit Modal -->
                                <button type="button" class="btn btn-sm btn-warning text-white" data-bs-toggle="modal" data-bs-target="#editYearModal{{ $year->id }}" title="Edit">
                                    Edit
                                </button>

                                <!-- Tombol Hapus Satuan -->
                                @if($year->is_active)
                                    <!-- Tahun ajaran aktif: tombol tampil redup/nonaktif + tooltip alasan.
                                         Pembungkus <span> ber-title karena elemen disabled sering tidak
                                         menampilkan tooltip di sebagian browser. -->
                                    <span class="d-inline-flex" title="Tahun ajaran sedang berlangsung, tidak dapat dihapus">
                                        <button type="button" class="btn btn-sm btn-danger disabled" aria-disabled="true">
                                            Hapus
                                        </button>
                                    </span>
                                @else
                                    <button type="button" class="btn btn-sm btn-danger" onclick="confirmDeleteYear('{{ $year->id }}', '{{ $year->name }}')" title="Hapus">
                                        Hapus
                                    </button>
                                @endif
                                <form id="deleteYearForm-{{ $year->id }}" action="{{ panel_route('academic-years.destroy', $year->id) }}" method="POST" class="d-none">
                                    @csrf
                                    @method('DELETE')
                                </form>
                            </div>
                        </td>
                    </tr>

                    <!-- MODAL EDIT TAHUN AJARAN -->
                    <div class="modal fade" id="editYearModal{{ $year->id }}" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content border-0 shadow-lg rounded-4">
                                <div class="modal-header border-bottom-0 pb-0">
                                    <h5 class="fw-bold mb-0">Edit Tahun Ajaran</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <form action="{{ panel_route('academic-years.update', $year->id) }}" method="POST">
                                    @csrf
                                    @method('PUT')
                                    <div class="modal-body py-3">
                                        <div class="mb-3">
                                            <label class="form-label small fw-semibold">Nama Tahun Ajaran</label>
                                            <input type="text" name="name" class="form-control rounded-3" value="{{ $year->name }}" placeholder="Contoh: 2025/2026" required>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label small fw-semibold">Semester</label>
                                            <select name="semester" class="form-select rounded-3" required>
                                                <option value="Ganjil" {{ $year->semester == 'Ganjil' ? 'selected' : '' }}>Ganjil</option>
                                                <option value="Genap" {{ $year->semester == 'Genap' ? 'selected' : '' }}>Genap</option>
                                            </select>
                                        </div>
                                        <div class="row g-2 mb-3">
                                            <div class="col-6">
                                                <label class="form-label small fw-semibold">Tanggal Mulai</label>
                                                <input type="date" name="start_date" class="form-control rounded-3" value="{{ \Carbon\Carbon::parse($year->start_date)->format('Y-m-d') }}" required>
                                            </div>
                                            <div class="col-6">
                                                <label class="form-label small fw-semibold">Tanggal Selesai</label>
                                                <input type="date" name="end_date" class="form-control rounded-3" value="{{ \Carbon\Carbon::parse($year->end_date)->format('Y-m-d') }}" required>
                                            </div>
                                        </div>
                                        @if($year->is_active)
                                            <!-- Tahun ajaran aktif: checkbox dikunci (tidak bisa dimatikan langsung),
                                                 nilai is_active tetap dikirim via hidden input agar edit lain tetap tersimpan. -->
                                            <input type="hidden" name="is_active" value="1">
                                            <div class="form-check form-switch mt-2">
                                                <input class="form-check-input" type="checkbox" name="is_active_disabled" value="1" id="editActive{{ $year->id }}" checked disabled>
                                                <label class="form-check-label small fw-semibold text-success" for="editActive{{ $year->id }}">Tahun Ajaran Aktif (tidak dapat dinonaktifkan langsung)</label>
                                            </div>
                                            <div class="form-text mt-1">
                                                Untuk memindahkan tahun ajaran aktif, gunakan tombol AKTIFKAN pada tahun ajaran lain.
                                            </div>
                                        @else
                                            <div class="form-check form-switch mt-2">
                                                <input class="form-check-input" type="checkbox" name="is_active" value="1" id="editActive{{ $year->id }}">
                                                <label class="form-check-label small fw-semibold" for="editActive{{ $year->id }}">Set sebagai Tahun Ajaran Aktif</label>
                                            </div>
                                        @endif
                                    </div>
                                    <div class="modal-footer border-top-0 pt-0">
                                        <button type="button" class="btn btn-light rounded-3 px-3 fw-semibold" data-bs-dismiss="modal">Batal</button>
                                        <button type="submit" class="btn btn-primary rounded-3 px-4 fw-semibold">Simpan Perubahan</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    @empty
                    <tr class="text-nowrap">
                        <td colspan="6" class="text-center py-5 text-muted text-nowrap empty-state">
                            <i class='bx bx-error' aria-hidden='true'></i>
                            Belum ada data tahun ajaran yang terdaftar.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($academicYears->hasPages())
        <div class="px-4 py-2 border-top d-flex flex-column flex-sm-row justify-content-between align-items-center gap-2 bg-white small">
            <div class="text-muted small">
                Menampilkan <span>{{ $academicYears->firstItem() ?? 0 }}</span> - <span>{{ $academicYears->lastItem() ?? 0 }}</span> dari <span>{{ $academicYears->total() }}</span> tahun ajaran
            </div>
            <div class="pagination-compact">
                {{ $academicYears->links('pagination::bootstrap-5') }}
            </div>
        </div>
        @endif
    </div>

<!-- MODAL TAMBAH TAHUN AJARAN -->
<div class="modal fade" id="addYearModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-bottom-0 pb-0">
                <h5 class="fw-bold mb-0">Tambah Tahun Ajaran Baru</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ panel_route('academic-years.store') }}" method="POST">
                @csrf
                <div class="modal-body py-3">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Nama Tahun Ajaran</label>
                        <input type="text" name="name" class="form-control rounded-3" placeholder="Contoh: 2025/2026" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Semester</label>
                        <select name="semester" class="form-select rounded-3" required>
                            <option value="Ganjil">Ganjil</option>
                            <option value="Genap">Genap</option>
                        </select>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Tanggal Mulai</label>
                            <input type="date" name="start_date" class="form-control rounded-3" value="{{ date('Y-m-d') }}" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Tanggal Selesai</label>
                            <input type="date" name="end_date" class="form-control rounded-3" value="{{ date('Y-m-d') }}" required>
                        </div>
                    </div>
                    <div class="form-check form-switch mt-2">
                        <input class="form-check-input" type="checkbox" name="is_active" value="1" id="addActive" checked>
                        <label class="form-check-label small fw-semibold" for="addActive">Set sebagai Tahun Ajaran Aktif Langsung</label>
                    </div>
                </div>
                <div class="modal-footer border-top-0 pt-0">
                    <button type="button" class="btn btn-light rounded-3 px-3 fw-semibold" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary rounded-3 px-4 fw-semibold">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
    function confirmDeleteYear(id, name) {
        confirmUniversalDelete({
            title: 'Hapus Tahun Ajaran?',
            html: `Tindakan ini bersifat permanen. Anda akan menghapus data tahun ajaran <b class="text-dark">${name}</b> dari sistem.`,
            confirmText: 'Hapus',
            cancelText: 'Tidak',
            onConfirm: function() {
                document.getElementById(`deleteYearForm-${id}`).submit();
            }
        });
    }

    /* Jaga klik ganda: form hanya boleh dikirim SATU KALI. Tanpa ini, klik
       ganda pada tombol "Ya, Aktifkan" (atau pada tombol AKTIFKAN itu
       sendiri) bisa mengirim POST berkali-kali. */
    let sudahDikirim = false;

    /**
     * Konfirmasi pengaktifan tahun ajaran.
     * Dipanggil dari onsubmit form AKTIFKAN (bukan onclick tombol):
     *  - confirmUniversalDelete tersedia -> tahan submit asli (return false),
     *      tampilkan dialog bertema SAMA PERSIS dengan dialog hapus (kartu,
     *      overlay blur, ikon lingkaran, judul & deskripsi rata tengah, dua
     *      tombol 50/50) dengan tone: 'primary' sehingga hanya warna aksennya
     *      yang biru, bukan merah. Kirim form hanya setelah dikonfirmasi.
     *  - helper tidak tersedia -> fallback window.confirm() bawaan browser,
     *      supaya tombol AKTIFKAN tidak pernah "mati total".
     *  - JS sama sekali mati -> tombol type="submit" tetap mengirim form.
     * Penyebab kegagalan dicatat ke console browser (tab Console) untuk diagnosis.
     */
    function confirmToggleActive(form, id, name, semester) {
        const message = `Tahun ajaran ${name} - ${semester} akan diaktifkan. Tahun ajaran aktif saat ini akan dinonaktifkan.`;

        const submitForm = () => {
            if (sudahDikirim) return; // cegah submit kedua
            sudahDikirim = true;

            const target = document.getElementById(`activateYearForm-${id}`) || form;
            if (!target) {
                sudahDikirim = false;
                console.error(`AKTIFKAN: form activateYearForm-${id} tidak ditemukan di halaman. Muat ulang halaman.`);
                return;
            }

            // Indikator singkat di tombol aslinya supaya tidak ada overlay
            // dan pengguna langsung tahu permintaannya sedang dikirim.
            const btn = target.querySelector('button[type="submit"]');
            if (btn) {
                btn.disabled = true;
                btn.textContent = 'Memproses...';
            }

            target.submit();
        };

        if (typeof window.confirmUniversalDelete !== 'function') {
            console.warn('AKTIFKAN: dialog bersama tidak termuat, memakai konfirmasi browser.');
            return window.confirm(message);
        }

        try {
            window.confirmUniversalDelete({
                title: 'Aktifkan Tahun Ajaran?',
                text: message,
                // Ikon info (bukan tanda tanya raksasa) di dalam lingkaran biru muda.
                icon: 'bx-info-circle',
                // Aksen biru; kartu/overlay/tombol tetap sama dengan dialog hapus.
                tone: 'primary',
                confirmText: 'Ya, Aktifkan',
                cancelText: 'Batal',
                onConfirm: function () {
                    submitForm();
                }
            });
        } catch (err) {
            console.error('AKTIFKAN: gagal menampilkan dialog konfirmasi.', err);
            return window.confirm(message);
        }

        // Tahan submit asli; pengiriman dilakukan manual setelah konfirmasi
        // (form.submit() tidak memicu onsubmit, jadi tidak ada pengulangan).
        return false;
    }
</script>
@endpush