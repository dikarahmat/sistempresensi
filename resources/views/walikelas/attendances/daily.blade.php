@extends('layouts.app')

@section('title', 'Presensi & Kehadiran Harian' . ($schoolClass ? ' - Kelas ' . $schoolClass->name : ''))
@section('page_title', 'Presensi & Kehadiran Harian' . ($schoolClass ? ' - Kelas ' . $schoolClass->name : ''))
@section('page_subtitle', \Carbon\Carbon::parse($date ?? now())->translatedFormat('l, d F Y'))



@push('styles')
<style>
    /* Badge Ringkasan Minimalis: Ikon di Kanan, Background Putih (Identik Admin) */
    .status-badge-pill {
        display: inline-flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.6rem;
        padding: 0.5rem 1rem;
        border-radius: 0.5rem !important;
        font-size: 0.85rem;
        font-weight: 600;
        background-color: #ffffff;
        border: 1px solid #e2e8f0;
        color: #1e293b;
    }
    .icon-hadir { color: #10b981; }
    .icon-terlambat { color: #f59e0b; }
    .icon-sakit { color: #3b82f6; }
    .icon-izin { color: #8b5cf6; }
    .icon-alfa { color: #ef4444; }
    .icon-belum { color: #64748b; }

    /* Filter Box Clean */
    .filter-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 16px;
        padding: 1.15rem 1.35rem;
        margin-bottom: 1.25rem;
    }

    /* Tabel Enterprise & Zebra Striping (Identik Admin) */
    .table-responsive {
        -webkit-overflow-scrolling: touch;
        overflow-x: auto;
    }
    .table-enterprise {
        min-width: 720px;
    }
    .table-enterprise thead th {
        background-color: #f8fafc;
        color: #000000 !important;
        font-weight: 700 !important;
        font-size: 0.75rem;
        padding: 0.8rem 1rem;
        border-bottom: 1.5px solid #edf2f7;
        text-transform: uppercase;
        letter-spacing: 0.03em;
    }
    .table-enterprise tbody tr:nth-child(even) > td,
    .table-enterprise tbody tr.baris-abu > td,
    .table-zebra-custom tbody tr:nth-child(even) > td,
    .table-zebra-custom tbody tr.baris-abu > td {
        background-color: #f1f5f9 !important;
    }
    .table-enterprise tbody tr:nth-child(odd) > td,
    .table-enterprise tbody tr.baris-putih > td,
    .table-zebra-custom tbody tr:nth-child(odd) > td,
    .table-zebra-custom tbody tr.baris-putih > td {
        background-color: #ffffff !important;
    }
    .table-enterprise tbody tr.baris-abu:hover > td,
    .table-enterprise tbody tr.baris-putih:hover > td,
    .table-enterprise tbody tr:hover > td,
    .table-zebra-custom tbody tr.baris-abu:hover > td,
    .table-zebra-custom tbody tr.baris-putih:hover > td,
    .table-zebra-custom tbody tr:hover > td {
        background-color: #e2e8f0 !important;
    }
    .table-enterprise tbody td {
        padding: 0.7rem 1rem;
        font-size: 0.86rem;
        color: #000000 !important;
        font-weight: 400 !important;
        vertical-align: middle;
        border-bottom: 1px solid #f1f5f9;
    }
</style>
@endpush

@section('content')
<div class="pt-1 pb-4 space-y-3">
    {{-- Alert Notifikasi --}}
    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show border-0 rounded-3 shadow-xs small mb-3 py-2 px-3" role="alert">
        <div class="d-flex align-items-center">
            <i class='bx bx-check-circle fs-5 me-2 text-success'></i>
            <span>{{ session('success') }}</span>
        </div>
        <button type="button" class="btn-close btn-close-sm" data-bs-dismiss="alert"></button>
    </div>
    @endif

    @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show border-0 rounded-3 shadow-xs small mb-3 py-2 px-3" role="alert">
        <div class="d-flex align-items-center">
            <i class='bx bx-x-circle fs-5 me-2 text-danger'></i>
            <span>{{ session('error') }}</span>
        </div>
        <button type="button" class="btn-close btn-close-sm" data-bs-dismiss="alert"></button>
    </div>
    @endif

    @if(!$schoolClass)
    <div class="card border-0 shadow-sm rounded-4 p-5 text-center my-4">
        <i class='bx bx-info-circle fs-1 text-warning mb-2'></i>
        <h5 class="fw-bold mb-1">Belum Ditetapkan Sebagai Wali Kelas</h5>
        <p class="text-muted small">Hubungi Administrator untuk menetapkan kelas binaan Anda.</p>
    </div>
    @else

    <!-- BADGE RINGKASAN STATUS MINIMALIS (IKON DI KANAN, IDENTIK ADMIN) -->
    <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
        <div class="status-badge-pill shadow-2xs">
            <span><strong>{{ $countHadir ?? 0 }}</strong> Hadir</span>
            <i class='bx bx-check-circle fs-6 icon-hadir'></i>
        </div>
        <div class="status-badge-pill shadow-2xs">
            <span><strong>{{ $countTerlambat ?? 0 }}</strong> Terlambat</span>
            <i class='bx bx-time-five fs-6 icon-terlambat'></i>
        </div>
        <div class="status-badge-pill shadow-2xs">
            <span><strong>{{ $countSakit ?? 0 }}</strong> Sakit</span>
            <i class='bx bx-plus-medical fs-6 icon-sakit'></i>
        </div>
        <div class="status-badge-pill shadow-2xs">
            <span><strong>{{ $countIzin ?? 0 }}</strong> Izin</span>
            <i class='bx bx-envelope fs-6 icon-izin'></i>
        </div>
        <div class="status-badge-pill shadow-2xs">
            <span><strong>{{ $countAlfa ?? 0 }}</strong> Alfa</span>
            <i class='bx bx-x-circle fs-6 icon-alfa'></i>
        </div>
        <div class="status-badge-pill shadow-2xs">
            <span><strong>{{ $countBelumAbsen ?? $countBelumHadir ?? 0 }}</strong> Belum</span>
            <i class='bx bx-minus-circle fs-6 icon-belum'></i>
        </div>
    </div>

    <!-- Filter Bar Interaktif: Tanggal & Kelas -->
    <div class="filter-card shadow-xs">
        <form action="{{ route('guru.kehadiran') }}" method="GET" class="row g-3 align-items-end">
            <div class="col-12 col-md-5">
                <label class="form-label small fw-semibold text-secondary">Pilih Tanggal Presensi</label>
                <div class="input-group">
                    <span class="input-group-text bg-light"><i class='bx bx-calendar'></i></span>
                    <input type="date" name="date" class="form-control rounded-end-3" value="{{ $date }}" onchange="this.form.submit()">
                </div>
            </div>

            @if(isset($teacherClasses) && $teacherClasses->count() > 1)
            <div class="col-12 col-md-4">
                <label class="form-label small fw-semibold text-secondary">Pilih Kelas</label>
                <select name="school_class_id" class="form-select rounded-3" onchange="this.form.submit()">
                    @foreach($teacherClasses as $tc)
                        <option value="{{ $tc->id }}" {{ $schoolClass && $schoolClass->id == $tc->id ? 'selected' : '' }}>
                            Kelas {{ $tc->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            @elseif($schoolClass)
            <input type="hidden" name="school_class_id" value="{{ $schoolClass->id }}">
            @endif

            <div class="col-12 col-md-auto ms-auto d-flex gap-2">
                <a href="{{ route('guru.kehadiran', $schoolClass ? ['school_class_id' => $schoolClass->id] : []) }}" class="btn btn-outline-secondary px-3 rounded-3">
                    Hari Ini
                </a>
            </div>
        </form>
    </div>

    @if(isset($isHoliday) && $isHoliday)
    <div class="alert alert-warning border-0 rounded-4 p-3 mb-3 d-flex align-items-center gap-3 shadow-xs">
        <i class='bx bx-calendar-exclamation fs-3 text-warning'></i>
        <div>
            <h6 class="fw-bold mb-0">Hari Ini Merupakan Hari Libur: {{ $holidayDescription ?? 'Libur' }}</h6>
            <small class="text-muted">Presensi tidak diwajibkan untuk tanggal ini.</small>
        </div>
    </div>
    @endif

    <!-- TABEL SISWA & KEHADIRAN (STRUKTUR IDENTIK ADMIN) -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 table-enterprise table-zebra-custom text-nowrap">
                <thead class="bg-light">
                    <tr class="text-dark small fw-bold text-uppercase" style="letter-spacing: 0.03em; color: #000000 !important;">
                        <th class="text-center py-3" style="width: 5%;">No.</th>
                        <th class="py-3" style="width: 26%;">Nama Siswa</th>
                        <th class="py-3" style="width: 14%;">NIS</th>
                        <th class="text-center py-3" style="width: 12%;">Jam Masuk</th>
                        <th class="text-center py-3" style="width: 12%;">Jam Pulang</th>
                        <th class="text-center py-3" style="width: 13%;">Status</th>
                        <th class="text-start py-3" style="min-width: 130px;">Keterangan</th>
                        <th class="text-center py-3" style="width: 12%;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($processedStudents as $index => $student)
                    @php
                        $st = $student->current_status ?? 'Belum Hadir';
                        if ($st === 'Hadir' && $student->current_time_remark === 'Terlambat') {
                            $st = 'Terlambat';
                        }
                        $stLower = strtolower($st);
                        $statusColor = match($stLower) {
                            'hadir' => 'text-success',
                            'terlambat' => 'text-warning',
                            'sakit' => 'text-primary',
                            'izin' => 'text-info',
                            'alfa', 'alpha' => 'text-danger',
                            'libur' => 'text-secondary',
                            default => 'text-secondary',
                        };
                    @endphp
                    <tr class="{{ $loop->odd ? 'baris-abu' : 'baris-putih' }}">
                        <td class="text-center text-secondary fw-semibold">{{ $index + 1 }}</td>
                        <td>
                            <div class="fw-semibold text-dark">{{ $student->name ?? '-' }}</div>
                        </td>
                        <td>
                            <span class="font-monospace text-secondary">{{ $student->nis ?: '-' }}</span>
                        </td>
                        <td class="text-center text-secondary font-monospace">
                            {{ $student->jam_masuk ?? '—' }}
                        </td>
                        <td class="text-center text-secondary font-monospace">
                            {{ $student->jam_pulang ?? '—' }}
                        </td>
                        <td class="text-center">
                            {{-- Pure Text Status: Tanpa Badge atau Pill Container --}}
                            <span class="{{ $statusColor }} fw-semibold">
                                {{ $st }}
                            </span>
                        </td>
                        <td class="text-start">
                            @if($student->proof_document)
                                <a href="{{ asset('storage/' . $student->proof_document) }}" target="_blank" class="btn btn-sm btn-outline-primary py-0 px-2 rounded-2 me-1 shadow-2xs" style="font-size: 0.75rem;">
                                    <i class='bx bx-file'></i> Surat
                                </a>
                            @endif
                            <span class="small text-muted">{{ $student->notes ?: '-' }}</span>
                        </td>
                        <td class="text-center">
                            {{-- Tombol Solid Box Warning --}}
                            <button type="button" class="btn btn-sm btn-warning text-white rounded-3 px-3 py-1.5 fw-semibold d-inline-flex align-items-center gap-1.5 shadow-2xs" data-bs-toggle="modal" data-bs-target="#editAttendanceModal{{ $student->id }}" title="Ubah Presensi">
                                <i class='bx bx-edit-alt fs-6'></i>
                                <span>Ubah</span>
                            </button>

                            <!-- Modal Ubah Status Presensi -->
                            <div class="modal fade text-start" id="editAttendanceModal{{ $student->id }}" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered">
                                    <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
                                        <div class="modal-header bg-light border-bottom px-4 py-3">
                                            <div>
                                                <h5 class="modal-title fw-bold text-dark mb-0">Ubah Presensi Siswa</h5>
                                                <div class="small text-muted">{{ $student->name }} (NIS: {{ $student->nis }}) &bull; Kelas {{ $schoolClass->name }}</div>
                                            </div>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <form action="{{ route('guru.kehadiran.override') }}" method="POST" enctype="multipart/form-data">
                                            @csrf
                                            <input type="hidden" name="student_id" value="{{ $student->id }}">
                                            <input type="hidden" name="date" value="{{ $date }}">

                                            <div class="modal-body p-4 text-start">
                                                <div class="mb-3">
                                                    <label class="form-label small fw-semibold text-secondary">Status Kehadiran <span class="text-danger">*</span></label>
                                                    <select name="status" class="form-select rounded-3" required>
                                                        <option value="Hadir" {{ $student->current_status === 'Hadir' && $student->current_time_remark !== 'Terlambat' ? 'selected' : '' }}>Hadir (Tepat Waktu)</option>
                                                        <option value="Terlambat" {{ $student->current_time_remark === 'Terlambat' ? 'selected' : '' }}>Hadir (Terlambat)</option>
                                                        <option value="Sakit" {{ $student->current_status === 'Sakit' ? 'selected' : '' }}>Sakit (S)</option>
                                                        <option value="Izin" {{ $student->current_status === 'Izin' ? 'selected' : '' }}>Izin (I)</option>
                                                        <option value="Alfa" {{ $student->current_status === 'Alfa' ? 'selected' : '' }}>Alfa (A)</option>
                                                    </select>
                                                </div>

                                                <div class="mb-3">
                                                    <label class="form-label small fw-semibold text-secondary">Jam Masuk (Opsional)</label>
                                                    <input type="time" name="check_in" class="form-control rounded-3" value="{{ $student->check_in_time ? substr($student->check_in_time, 0, 5) : '' }}">
                                                </div>

                                                <div class="mb-3">
                                                    <label class="form-label small fw-semibold text-secondary">Keterangan / Catatan</label>
                                                    <textarea name="notes" class="form-control rounded-3" rows="2" placeholder="Catatan atau alasan...">{{ $student->notes }}</textarea>
                                                </div>

                                                <div class="mb-2">
                                                    <label class="form-label small fw-semibold text-secondary">Lampiran Bukti Surat (PDF/JPG/PNG)</label>
                                                    <input type="file" name="proof_document" class="form-control rounded-3 form-control-sm" accept=".pdf,.jpg,.jpeg,.png">
                                                    @if($student->proof_document)
                                                    <div class="mt-1">
                                                        <a href="{{ asset('storage/' . $student->proof_document) }}" target="_blank" class="small text-primary text-decoration-none">
                                                            <i class='bx bx-link-external'></i> Lihat bukti saat ini
                                                        </a>
                                                    </div>
                                                    @endif
                                                </div>
                                            </div>

                                            <div class="modal-footer bg-light border-top px-4 py-3">
                                                <button type="button" class="btn btn-light border rounded-3 fw-semibold px-3" data-bs-dismiss="modal">Batal</button>
                                                <button type="submit" class="btn btn-primary rounded-3 px-4 fw-semibold">Simpan Perubahan</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center py-5 text-secondary">
                            <i class='bx bx-info-circle fs-2 d-block mb-2 text-muted'></i>
                            Belum ada data siswa di kelas ini.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @endif
</div>
@endsection