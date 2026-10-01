@extends('layouts.app')

@section('title', 'Arsip Siswa')
@section('page_title', 'Arsip Siswa')
@section('page_subtitle', 'Pulihkan atau hapus permanen data siswa yang telah dihapus')

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
</style>
@endpush

@section('content')
    <!-- Alert Notifikasi -->
    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show border-0 rounded-3 shadow-xs small mb-3 py-2.5 px-3" role="alert">
        <div class="d-flex align-items-center">
            <i class='bx bx-check-circle fs-5 me-2 text-success'></i>
            <span>{{ session('success') }}</span>
        </div>
        <button type="button" class="btn-close btn-close-sm" data-bs-dismiss="alert"></button>
    </div>
    @endif

    @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show border-0 rounded-3 shadow-xs small mb-3 py-2.5 px-3" role="alert">
        <div class="d-flex align-items-center">
            <i class='bx bx-x-circle fs-5 me-2 text-danger'></i>
            <span>{{ session('error') }}</span>
        </div>
        <button type="button" class="btn-close btn-close-sm" data-bs-dismiss="alert"></button>
    </div>
    @endif

    <!-- Info Text -->
    <div class="mb-4">
        <p class="text-secondary mb-0" style="font-size: 0.85rem;">
            Data siswa yang dihapus akan disimpan di sini. Anda dapat memulihkan atau menghapus permanen data.
        </p>
    </div>

    <!-- Tabel Trash -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4 bg-white">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 table-zebra-custom text-nowrap">
                <thead class="bg-slate-50 border-b border-gray-100">
                    <tr class="align-middle text-blue-500 text-xs font-bold uppercase tracking-wider border-b border-gray-100">
                        <th class="text-center py-3 text-nowrap px-3 text-blue-500 text-xs font-bold uppercase border-0 border-b border-gray-100" style="width: 1%; min-width: 45px;">NO</th>
                        <th class="text-center py-3 text-nowrap px-3 text-blue-500 text-xs font-bold uppercase border-0 border-b border-gray-100" style="width: 1%; min-width: 80px;">KELAS</th>
                        <th class="text-center py-3 text-nowrap px-3 text-blue-500 text-xs font-bold uppercase border-0 border-b border-gray-100" style="width: 12%;">NIS</th>
                        <th class="text-start py-3 text-nowrap px-3 text-blue-500 text-xs font-bold uppercase border-0 border-b border-gray-100">NAMA SISWA</th>
                        <th class="text-center py-3 text-nowrap px-3 text-blue-500 text-xs font-bold uppercase border-0 border-b border-gray-100" style="width: 15%;">TANGGAL DIHAPUS</th>
                        <th class="text-center py-3 text-nowrap px-3 text-blue-500 text-xs font-bold uppercase border-0 border-b border-gray-100" style="width: 180px;">AKSI</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($students as $student)
                    <tr class="align-middle {{ $loop->odd ? 'baris-abu' : 'baris-putih' }}">
                        <td class="text-center text-nowrap px-3">{{ $students->firstItem() + $loop->index }}</td>
                        <td class="text-center text-nowrap px-3">{{ $student->schoolClass->name ?? '-' }}</td>
                        <td class="text-center text-nowrap font-monospace px-3">{{ $student->nis }}</td>
                        <td class="text-start text-nowrap px-3">{{ $student->name }}</td>
                        <td class="text-center text-nowrap px-3">{{ $student->deleted_at->format('d M Y H:i') }}</td>
                        <td class="text-center text-nowrap px-3">
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
                        <td colspan="6" class="text-center py-5 text-muted text-nowrap">
                            <i class='bx bx-info-circle fs-2 d-block mb-2'></i>
                            Arsip kosong. Tidak ada data siswa yang dihapus.
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
        <p class="text-secondary small mb-3">Tindakan ini akan menghapus seluruh data siswa secara permanen. Data yang dihapus tidak dapat dikembalikan.</p>
        <button type="button" class="btn btn-danger rounded-3 px-4 fw-semibold" onclick="confirmDeleteAllStudents()">
            Hapus Semua Siswa (Permanen)
        </button>
    </div>
    @endif

    <form id="deleteAllStudentsForm" action="{{ panel_route('students.destroy-all') }}" method="POST" class="d-none">
        @csrf
        @method('DELETE')
    </form>
@endsection

@push('scripts')
<script>
    function confirmForceDelete(id, name) {
        Swal.fire({
            title: 'Hapus Permanen?',
            html: `Data siswa <b>${name}</b> akan dihapus permanen dan tidak dapat dikembalikan.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc2626',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'Ya, Hapus Permanen',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                document.getElementById(`forceDeleteForm-${id}`).submit();
            }
        });
    }

    function confirmDeleteAllStudents() {
        confirmUniversalDelete({
            title: 'Hapus Semua Siswa?',
            html: 'Seluruh data siswa akan dihapus permanen. Tindakan ini tidak dapat dibatalkan.',
            confirmText: 'Hapus Semua',
            cancelText: 'Tidak',
            onConfirm: function() {
                document.getElementById('deleteAllStudentsForm').submit();
            }
        });
    }
</script>
@endpush
