@extends('layouts.app')

@section('title', 'Arsip Guru')
@section('page_title', 'Arsip Guru')
@section('page_subtitle', 'Pulihkan atau hapus permanen data guru yang telah dihapus')

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
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-1">Arsip Guru</h4>
            <p class="text-secondary small mb-0">Pulihkan atau hapus permanen data guru yang telah dihapus</p>
        </div>
        <a href="{{ panel_route('guru.index') }}" class="btn btn-primary rounded-3 px-4 py-2 fw-semibold">
            Kembali ke Data Guru
        </a>
    </div>

    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
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
                        <td class="text-center px-3">{{ $teachers->firstItem() + $index }}</td>
                        <td class="text-center px-3">{{ $teacher->nip }}</td>
                        <td class="text-center px-3 fw-semibold">{{ $teacher->name }}</td>
                        <td class="text-center px-3">{{ $teacher->schoolClass->name ?? '-' }}</td>
                        <td class="text-center px-3">{{ $teacher->deleted_at->format('d M Y H:i') }}</td>
                        <td class="text-center px-3">
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
                            Tidak ada data guru di arsip
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {!! render_compact_pagination($teachers, 'daftar-arsip-guru') !!}
    </div>
</div>

<!-- Modal Konfirmasi Hapus Permanen -->
<div class="modal fade" id="forceDeleteTeacherModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-bottom-0 pb-0">
                <h5 class="fw-bold mb-0">Konfirmasi Hapus Permanen</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body py-3">
                <p class="mb-0">Apakah Anda yakin ingin menghapus permanen data guru <strong id="forceDeleteTeacherName"></strong>? Tindakan ini tidak dapat dibatalkan.</p>
            </div>
            <div class="modal-footer border-top-0 pt-0">
                <button type="button" class="btn btn-light rounded-3 px-3 fw-semibold" data-bs-dismiss="modal">Batal</button>
                <form id="forceDeleteTeacherForm" method="POST" class="d-inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger rounded-3 px-4 fw-semibold">Hapus Permanen</button>
                </form>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
    function confirmForceDeleteTeacher(id, name) {
        document.getElementById('forceDeleteTeacherName').textContent = name;
        document.getElementById('forceDeleteTeacherForm').action = '/admin/teachers/' + id + '/force-delete';
        new bootstrap.Modal(document.getElementById('forceDeleteTeacherModal')).show();
    }
</script>
@endpush
