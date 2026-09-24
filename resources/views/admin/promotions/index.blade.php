@extends('layouts.app')

@section('title', 'Kenaikan Kelas & Pemindahan Rombel')
@section('page_title', 'Kenaikan Kelas & Pemindahan Rombel')
@section('page_subtitle', 'Pindahkan siswa dari Kelas Asal ke Kelas Tujuan secara massal atau individual untuk pergantian tahun ajaran baru.')

@section('page_header_right')
<div class="d-flex align-items-center gap-2">
    <span class="badge bg-light text-dark border px-3 py-2 rounded-3 small">
        <i class='bx bx-calendar me-1 text-primary'></i> Tahun Ajaran: <strong>{{ $activeYear->name ?? 'Belum Diatur' }}</strong>
    </span>
</div>
@endsection

@push('styles')
<style>
    .transfer-box {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 16px;
        padding: 1.5rem;
    }
    .transfer-divider {
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.75rem;
        color: #3b82f6;
    }
    .table-students thead th {
        background-color: #f8fafc;
        color: #475569;
        font-weight: 700;
        font-size: 0.78rem;
        letter-spacing: 0.04em;
        text-transform: uppercase;
        border-bottom: 1px solid #e2e8f0;
        padding: 0.85rem 1rem;
    }
    /* Zebra Striping Khusus (Sesuai Benchmark Data Siswa) */
    .table-students tbody tr:nth-child(even) > td,
    .table-students tbody tr.baris-abu > td,
    .table-zebra-custom tbody tr:nth-child(even) > td,
    .table-zebra-custom tbody tr.baris-abu > td {
        background-color: #f1f5f9 !important;
    }
    .table-students tbody tr:nth-child(odd) > td,
    .table-students tbody tr.baris-putih > td,
    .table-zebra-custom tbody tr:nth-child(odd) > td,
    .table-zebra-custom tbody tr.baris-putih > td {
        background-color: #ffffff !important;
    }
    .table-students tbody tr:hover > td,
    .table-students tbody tr.baris-abu:hover > td,
    .table-students tbody tr.baris-putih:hover > td,
    .table-zebra-custom tbody tr:hover > td,
    .table-zebra-custom tbody tr.baris-abu:hover > td,
    .table-zebra-custom tbody tr.baris-putih:hover > td {
        background-color: #e2e8f0 !important;
    }
    .table-students tbody td {
        padding: 0.75rem 1rem;
        font-size: 0.86rem;
        color: #000000 !important;
        font-weight: 400 !important;
        border-bottom: 1px solid #f1f5f9;
        vertical-align: middle;
    }
    .student-checkbox {
        width: 18px;
        height: 18px;
        cursor: pointer;
    }
</style>
@endpush

@section('content')
    <!-- Alert Notifikasi -->
    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show border-0 rounded-3 shadow-xs small mb-3 py-2.5" role="alert">
        <div class="d-flex align-items-center">
            <i class='bx bx-check-circle fs-5 me-2 text-success'></i>
            <span>{{ session('success') }}</span>
        </div>
        <button type="button" class="btn-close btn-close-sm" data-bs-dismiss="alert"></button>
    </div>
    @endif

    @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show border-0 rounded-3 shadow-xs small mb-3 py-2.5" role="alert">
        <div class="d-flex align-items-center">
            <i class='bx bx-x-circle fs-5 me-2 text-danger'></i>
            <span>{{ session('error') }}</span>
        </div>
        <button type="button" class="btn-close btn-close-sm" data-bs-dismiss="alert"></button>
    </div>
    @endif

    @if($errors->any())
    <div class="alert alert-danger alert-dismissible fade show border-0 rounded-3 shadow-xs small mb-3 py-2.5" role="alert">
        <div class="fw-bold mb-1"><i class='bx bx-error me-1'></i> Periksa data input:</div>
        <ul class="mb-0 ps-3 small">
            @foreach($errors->all() as $err)
                <li>{{ $err }}</li>
            @endforeach
        </ul>
        <button type="button" class="btn-close btn-close-sm" data-bs-dismiss="alert"></button>
    </div>
    @endif

    <form id="promotionForm" action="{{ route('admin.kenaikan-kelas.promote') }}" method="POST">
        @csrf

        <!-- 1. KARTU PENGATURAN KELAS ASAL & TUJUAN -->
        <div class="transfer-box shadow-xs mb-4">
            <div class="row g-3 align-items-center">
                <!-- Dropdown Kelas Asal -->
                <div class="col-12 col-md-5">
                    <label class="form-label small fw-bold text-secondary text-uppercase" style="letter-spacing: 0.04em;">
                        1. Kelas Asal (Sumber Data) <span class="text-danger">*</span>
                    </label>
                    <div class="input-group">
                        <span class="input-group-text bg-light text-muted"><i class='bx bx-building-house fs-5'></i></span>
                        <select name="source_class_id" id="source_class_id" class="form-select rounded-end-3 py-2" onchange="changeSourceClass(this.value)" required>
                            <option value="">-- Pilih Rombel / Kelas Asal --</option>
                            @foreach($classes as $c)
                            <option value="{{ $c->id }}" {{ $sourceClassId == $c->id ? 'selected' : '' }}>
                                Kelas {{ $c->name }} (Tingkat {{ $c->grade ?? $c->level }}) - {{ $c->students_count ?? 0 }} Siswa Aktif
                            </option>
                            @endforeach
                        </select>
                    </div>
                    <small class="text-muted" style="font-size: 0.72rem;">Siswa yang berada pada kelas ini akan dimuat pada tabel di bawah.</small>
                </div>

                <!-- Ikon Pemindah -->
                <div class="col-12 col-md-2 text-center my-md-0 my-2">
                    <div class="transfer-divider">
                        <div class="rounded-circle bg-light border d-flex align-items-center justify-content-center mx-auto" style="width: 46px; height: 46px;">
                            <i class='bx bx-transfer-alt text-primary'></i>
                        </div>
                    </div>
                </div>

                <!-- Dropdown Kelas Tujuan -->
                <div class="col-12 col-md-5">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <label class="form-label small fw-bold text-secondary text-uppercase mb-0" style="letter-spacing: 0.04em;">
                            2. Kelas Tujuan / Status <span class="text-danger">*</span>
                        </label>
                        <div class="form-check form-check-inline m-0">
                            <input class="form-check-input" type="radio" name="action_type" id="action_promote" value="promote" checked onchange="toggleActionType()">
                            <label class="form-check-label small fw-semibold text-primary" for="action_promote">Naik / Pindah Kelas</label>
                        </div>
                        <div class="form-check form-check-inline m-0">
                            <input class="form-check-input" type="radio" name="action_type" id="action_graduate" value="graduate" onchange="toggleActionType()">
                            <label class="form-check-label small fw-semibold text-danger" for="action_graduate">Lulus / Alumni</label>
                        </div>
                    </div>

                    <div id="target_class_container">
                        <div class="input-group">
                            <span class="input-group-text bg-light text-muted"><i class='bx bx-buildings fs-5'></i></span>
                            <select name="target_class_id" id="target_class_id" class="form-select rounded-end-3 py-2">
                                <option value="">-- Pilih Kelas Tujuan --</option>
                                @foreach($classes as $c)
                                <option value="{{ $c->id }}" {{ $targetClassId == $c->id ? 'selected' : '' }}>
                                    Kelas {{ $c->name }} (Tingkat {{ $c->grade ?? $c->level }})
                                </option>
                                @endforeach
                            </select>
                        </div>
                        <small class="text-muted" style="font-size: 0.72rem;">Siswa yang dicentang akan dipindahkan ke kelas tujuan ini.</small>
                    </div>

                    <div id="graduate_info_container" class="d-none">
                        <div class="p-2.5 rounded-3 border bg-rose-50 text-rose-700 small d-flex align-items-center gap-2">
                            <i class='bx bx-info-circle fs-5 flex-shrink-0'></i>
                            <span>Status siswa yang dicentang akan diubah menjadi <strong>Lulus (Alumni)</strong> dan dilepaskan dari rombel aktif.</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 2. TABEL DAFTAR SISWA KELAS ASAL DENGAN CHECKBOX MASSAL -->
        <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
            <div class="p-3.5 border-bottom border-slate-200 d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-2">
                <div>
                    <h5 class="fw-bold text-dark mb-0.5" style="font-size: 0.95rem;">
                        Daftar Siswa: 
                        <span class="text-primary">{{ $sourceClass ? 'Kelas ' . $sourceClass->name : 'Pilih Kelas Asal Di Atas' }}</span>
                    </h5>
                    <p class="text-muted small mb-0" style="font-size: 0.76rem;">
                        Centang siswa yang akan dipindahkan atau gunakan kotak centang di judul kolom untuk memilih seluruhnya secara massal.
                    </p>
                </div>

                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-light text-secondary border px-2.5 py-1.5 rounded-2 small" id="selectedCountBadge">
                        0 siswa terpilih
                    </span>

                    <button type="button" class="btn btn-primary btn-sm px-3.5 py-2 fw-semibold rounded-3 shadow-xs d-inline-flex align-items-center gap-1.5" onclick="confirmExecutePromotion()">
                        <i class='bx bx-check-circle fs-5'></i>
                        <span>Eksekusi Pemindahan</span>
                    </button>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 table-students table-zebra-custom">
                    <thead class="bg-light">
                        <tr class="text-secondary small fw-bold text-uppercase" style="letter-spacing: 0.03em;">
                            <th class="text-center py-3" style="width: 5%;">
                                <input type="checkbox" id="selectAllCheckbox" class="student-checkbox form-check-input" onclick="toggleSelectAll(this)">
                            </th>
                            <th class="text-center py-3" style="width: 6%;">No.</th>
                            <th class="py-3" style="width: 16%;">NIS / NISN</th>
                            <th class="py-3" style="width: 35%;">Nama Lengkap Siswa</th>
                            <th class="text-center py-3" style="width: 15%;">Jenis Kelamin</th>
                            <th class="text-center py-3" style="width: 12%;">Status Saat Ini</th>
                        </tr>
                    </thead>
                    <tbody id="studentTableBody">
                        @forelse($students as $idx => $student)
                        <tr class="{{ $loop->odd ? 'baris-abu' : 'baris-putih' }}">
                            <td class="text-center">
                                <input type="checkbox" name="student_ids[]" value="{{ $student->id }}" class="student-checkbox form-check-input student-item-cb" onchange="updateSelectedCount()">
                            </td>
                            <td class="text-center text-muted fw-semibold">{{ $idx + 1 }}</td>
                            <td>
                                <div class="fw-semibold text-dark">{{ $student->nis }}</div>
                                <div class="text-muted small font-monospace">{{ $student->nisn ?? '-' }}</div>
                            </td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="rounded-circle bg-light text-primary fw-bold d-flex align-items-center justify-content-center border" style="width: 32px; height: 32px; font-size: 0.75rem;">
                                        {{ strtoupper(substr($student->name, 0, 2)) }}
                                    </div>
                                    <span class="fw-semibold text-dark">{{ $student->name }}</span>
                                </div>
                            </td>
                            <td class="text-center text-secondary small">
                                {{ ($student->gender == 'Perempuan' || $student->gender == 'P') ? 'Perempuan' : 'Laki-laki' }}
                            </td>
                            <td class="text-center">
                                <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1 rounded-2 small">
                                    {{ $student->status ?? 'Aktif' }}
                                </span>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">
                                @if($sourceClassId)
                                    <i class='bx bx-info-circle fs-2 d-block mb-1 text-slate-400'></i>
                                    Tidak ada siswa aktif terdaftar pada kelas yang dipilih.
                                @else
                                    <i class='bx bx-pointer fs-2 d-block mb-1 text-primary'></i>
                                    Silakan pilih <strong>Kelas Asal</strong> terlebih dahulu pada dropdown di atas.
                                @endif
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($students->isNotEmpty())
            <div class="p-3 bg-light/60 border-top border-slate-200 d-flex justify-content-between align-items-center small text-muted">
                <div>
                    Total siswa di kelas ini: <strong>{{ $students->count() }} orang</strong>
                </div>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-outline-secondary btn-sm rounded-2" onclick="selectAll(true)">Pilih Semua</button>
                    <button type="button" class="btn btn-outline-secondary btn-sm rounded-2" onclick="selectAll(false)">Batalkan Semua</button>
                </div>
            </div>
            @endif
        </div>
    </form>
@endsection

@push('scripts')
<script>
    function changeSourceClass(classId) {
        if (!classId) return;
        window.location.href = `{{ route('admin.kenaikan-kelas.index') }}?source_class_id=${classId}`;
    }

    function toggleActionType() {
        const isPromote = document.getElementById('action_promote').checked;
        const targetContainer = document.getElementById('target_class_container');
        const graduateContainer = document.getElementById('graduate_info_container');

        if (isPromote) {
            targetContainer.classList.remove('d-none');
            graduateContainer.classList.add('d-none');
            document.getElementById('target_class_id').required = true;
        } else {
            targetContainer.classList.add('d-none');
            graduateContainer.classList.remove('d-none');
            document.getElementById('target_class_id').required = false;
        }
    }

    function toggleSelectAll(masterCb) {
        const checkboxes = document.querySelectorAll('.student-item-cb');
        checkboxes.forEach(cb => cb.checked = masterCb.checked);
        updateSelectedCount();
    }

    function selectAll(check) {
        const masterCb = document.getElementById('selectAllCheckbox');
        if (masterCb) masterCb.checked = check;
        const checkboxes = document.querySelectorAll('.student-item-cb');
        checkboxes.forEach(cb => cb.checked = check);
        updateSelectedCount();
    }

    function updateSelectedCount() {
        const selected = document.querySelectorAll('.student-item-cb:checked').length;
        const badge = document.getElementById('selectedCountBadge');
        if (badge) {
            badge.innerText = `${selected} siswa terpilih`;
            if (selected > 0) {
                badge.className = 'badge bg-primary text-white px-2.5 py-1.5 rounded-2 small fw-semibold';
            } else {
                badge.className = 'badge bg-light text-secondary border px-2.5 py-1.5 rounded-2 small';
            }
        }
    }

    function confirmExecutePromotion() {
        const sourceSelect = document.getElementById('source_class_id');
        if (!sourceSelect.value) {
            Swal.fire({
                icon: 'warning',
                title: 'Pilih Kelas Asal',
                text: 'Silakan tentukan kelas asal terlebih dahulu.'
            });
            return;
        }

        const selected = document.querySelectorAll('.student-item-cb:checked').length;
        if (selected === 0) {
            Swal.fire({
                icon: 'warning',
                title: 'Belum Ada Siswa Dipilih',
                text: 'Silakan centang minimal satu siswa yang ingin dipindahkan atau dinaikkan kelasnya.'
            });
            return;
        }

        const isPromote = document.getElementById('action_promote').checked;
        const sourceName = sourceSelect.options[sourceSelect.selectedIndex].text;

        if (isPromote) {
            const targetSelect = document.getElementById('target_class_id');
            if (!targetSelect.value) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Pilih Kelas Tujuan',
                    text: 'Silakan pilih kelas tujuan pemindahan.'
                });
                return;
            }

            if (sourceSelect.value === targetSelect.value) {
                Swal.fire({
                    icon: 'error',
                    title: 'Kelas Tidak Valid',
                    text: 'Kelas tujuan tidak boleh sama dengan kelas asal!'
                });
                return;
            }

            const targetName = targetSelect.options[targetSelect.selectedIndex].text;

            Swal.fire({
                title: 'Konfirmasi Kenaikan / Pemindahan',
                html: `Apakah Anda yakin ingin memindahkan <strong>${selected} siswa</strong> terpilih ke <strong>${targetName}</strong>?`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#2563eb',
                cancelButtonColor: '#64748b',
                confirmButtonText: 'Ya, Proses Sekarang',
                cancelButtonText: 'Batal',
                reverseButtons: true
            }).then((res) => {
                if (res.isConfirmed) {
                    document.getElementById('promotionForm').submit();
                }
            });
        } else {
            confirmUniversalDelete({
                title: 'Konfirmasi Kelulusan Siswa?',
                html: `Tindakan ini bersifat permanen. Anda akan mengubah status <b class="text-dark">${selected} siswa</b> terpilih menjadi <b class="text-dark">Lulus / Alumni</b> dan mengarsipkan status aktif siswa.`,
                confirmText: 'Ya, Luluskan Siswa',
                cancelText: 'Batal',
                icon: 'warning',
                onConfirm: function() {
                    document.getElementById('promotionForm').submit();
                }
            });
        }
    }
</script>
@endpush
