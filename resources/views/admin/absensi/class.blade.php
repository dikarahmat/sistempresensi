@extends('layouts.app')

@section('title', 'Presensi Kelas ' . ($selectedClass->name ?? ''))
@section('page_title', 'Presensi Kelas ' . ($selectedClass->name ?? '-'))
@section('page_subtitle', \Carbon\Carbon::parse($tanggal ?? now())->translatedFormat('l, d F Y'))

@section('page_header_right')
<div class="d-flex flex-wrap align-items-center gap-2">
    <a href="{{ route('admin.absensi.index', ['date' => $tanggal ?? date('Y-m-d')]) }}" class="btn btn-light border rounded-3 px-3 py-1.5 d-inline-flex align-items-center gap-1.5 shadow-2xs text-secondary fw-semibold" style="font-size: 0.85rem;" title="Kembali">
        <i class='bx bx-chevron-left'></i> Kembali
    </a>

    <form method="GET" action="{{ route('admin.absensi.show', $selectedClass->id ?? 1) }}" class="m-0">
        <input type="date" name="tanggal" class="form-control form-control-sm rounded-3 py-1.5 px-2.5 bg-white border text-secondary fw-semibold" value="{{ $tanggal ?? date('Y-m-d') }}" onchange="this.form.submit()" style="font-size: 0.85rem;">
    </form>

    <button type="button" id="btnToggleScanner" class="btn btn-primary fw-semibold btn-sm px-3.5 py-1.5 rounded-3 d-inline-flex align-items-center gap-1.5 shadow-2xs" style="font-size: 0.85rem;" onclick="toggleInlineScanner()">
        <i class='bx bx-camera fs-5' id="toggleScannerIcon"></i>
        <span id="toggleScannerText">Buka Scanner QR</span>
    </button>
</div>
@endsection

@push('styles')
<style>
    .page-subtitle-date {
        font-size: 0.9rem;
        font-weight: 500;
        color: #475569;
    }
    
    /* Badge Ringkasan Minimalis: Ikon di Kanan, Background Putih */
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

    /* Badge Status Tabel Solid Jelas */
    .badge-status-solid {
        padding: 0.35rem 0.75rem;
        border-radius: 6px;
        font-size: 0.8rem;
        font-weight: 700;
        letter-spacing: 0.02em;
        display: inline-block;
    }
    .badge-hadir { background-color: #10b981; color: #ffffff; }
    .badge-terlambat { background-color: #f59e0b; color: #ffffff; }
    .badge-sakit { background-color: #3b82f6; color: #ffffff; }
    .badge-izin { background-color: #8b5cf6; color: #ffffff; }
    .badge-alfa { background-color: #ef4444; color: #ffffff; }
    .badge-belum { background-color: #94a3b8; color: #ffffff; }

    /* Tabel Enterprise & Zebra */
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
    /* Zebra Striping Khusus (Sesuai Benchmark Data Siswa) */
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

    /* Dropdown & Tombol Aksi */
    .select-quick-status {
        font-size: 0.8rem;
        font-weight: 600;
        border-radius: 6px;
        padding: 0.3rem 0.5rem;
        border: 1px solid #cbd5e1;
        background-color: #ffffff;
        min-width: 95px;
    }
    .btn-save-quick {
        background-color: #2563eb;
        color: #ffffff;
        border: none;
        border-radius: 6px;
        width: 32px;
        height: 32px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        transition: all 0.15s ease;
    }
    .btn-save-quick:hover {
        background-color: #1d4ed8;
    }
    .btn-edit-modal {
        background-color: #f8fafc;
        color: #475569;
        border: 1px solid #cbd5e1;
        border-radius: 6px;
        width: 32px;
        height: 32px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        transition: all 0.15s ease;
    }
    .btn-edit-modal:hover {
        background-color: #e2e8f0;
        color: #0f172a;
    }

    /* Card Scanner Mode Gerbang Style di Halaman Detail Kelas */
    .scanner-kiosk-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 18px;
        box-shadow: 0 10px 30px rgba(15, 23, 42, 0.06);
        padding: 20px 16px;
        text-align: center;
        position: relative;
    }
    @media (min-width: 992px) {
        .scanner-kiosk-card {
            position: sticky;
            top: 24px;
            z-index: 15;
        }
    }

    /* Switcher Pill di Atas Card */
    .kiosk-switcher {
        display: inline-flex;
        background: #f8fafc;
        padding: 4px;
        border-radius: 50rem;
        border: 1px solid #e2e8f0;
        margin-bottom: 1.15rem;
    }

    .kiosk-switch-btn {
        background: transparent;
        border: none;
        padding: 0.42rem 1.1rem;
        border-radius: 50rem;
        font-size: 0.82rem;
        font-weight: 600;
        color: #64748b;
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        transition: all 0.15s ease;
        cursor: pointer;
    }

    .kiosk-switch-btn.active {
        background: #2563eb;
        color: #ffffff;
        box-shadow: 0 2px 8px rgba(37, 99, 235, 0.25);
    }

    /* Viewport / Area Scanner */
    .scanner-viewport-container {
        border: 2px dashed #cbd5e1;
        border-radius: 16px;
        background: #fafbfd;
        padding: 16px;
        min-height: 230px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        position: relative;
        overflow: hidden;
        transition: all 0.2s ease;
    }

    .scanner-viewport-container.camera-active {
        border: 2px solid #2563eb;
        background: #000000;
        padding: 0;
    }

    #reader {
        width: 100% !important;
        height: 100% !important;
        min-height: 220px;
        border: none !important;
    }

    #reader video {
        width: 100% !important;
        height: 100% !important;
        object-fit: cover;
        border-radius: 14px;
    }

    /* Overlay Notifikasi Langsung di Dalam Scanner Box */
    .overlay-status {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        z-index: 50;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 1.2rem;
        text-align: center;
        backdrop-filter: blur(6px);
        animation: fadeInScale 0.15s cubic-bezier(0.16, 1, 0.3, 1);
    }
    
    /* Sukses: Gradient Emerald */
    .overlay-success {
        background: linear-gradient(135deg, rgba(13, 148, 136, 0.96) 0%, rgba(16, 185, 129, 0.96) 100%);
        color: #ffffff;
        box-shadow: inset 0 0 20px rgba(255, 255, 255, 0.2);
        border-radius: 14px;
    }

    /* Gagal/Error: Danger Red */
    .overlay-error {
        background: #dc2626 !important;
        color: #ffffff !important;
        box-shadow: 0 0 25px rgba(220, 38, 38, 0.5);
        border-radius: 14px;
    }

    @keyframes fadeInScale {
        from { opacity: 0; transform: scale(0.95); }
        to { opacity: 1; transform: scale(1); }
    }
</style>
@endpush

@section('content')
<div class="pt-1 pb-4 space-y-3">
    
    <!-- BADGE RINGKASAN STATUS (IKON DI KANAN) -->
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
            <span><strong>{{ $countBelumAbsen ?? 0 }}</strong> Belum</span>
            <i class='bx bx-minus-circle fs-6 icon-belum'></i>
        </div>
    </div>

    <!-- MAIN CONTENT LAYOUT -->
    <div class="row g-3 align-items-start">
        <!-- Kolom Scanner Kiri -->
        <div class="col-12 col-lg-5 col-xl-4" id="scannerColumn" style="display: none;">
            <div class="scanner-kiosk-card mb-3 mb-lg-0">
                
                <!-- Switcher Button (Kamera / Alat Scanner) -->
                <div class="kiosk-switcher">
                    <button type="button" id="btnTabCamera" class="kiosk-switch-btn active" onclick="switchMode('camera')">
                        <i class='bx bx-camera fs-5'></i> Kamera
                    </button>
                    <button type="button" id="btnTabHardware" class="kiosk-switch-btn" onclick="switchMode('hardware')">
                        <i class='bx bx-barcode-reader fs-5'></i> Alat Scanner
                    </button>
                </div>

                <!-- Scanner Box Area -->
                <div id="scannerBox" class="scanner-viewport-container mb-3 camera-active">
                    
                    <!-- Mode Kamera -->
                    <div id="cameraView" class="w-100 h-100 position-relative">
                        <div id="reader" style="width: 100%; min-height: 220px; border-radius: 12px; overflow: hidden;"></div>
                        <div id="cameraPlaceholder" class="py-4 position-absolute top-50 start-50 translate-middle w-100" style="background: #fafbfd; height: 100%; display: flex; flex-direction: column; align-items: center; justify-content: center; z-index: 5;">
                            <i class='bx bx-qr-scan text-secondary' style="font-size: 3rem;"></i>
                            <p class="text-secondary small mt-2 mb-0 fw-semibold">Tempelkan kartu QR ke kamera</p>
                        </div>
                    </div>

                    <!-- Mode Alat Scanner -->
                    <div id="hardwareView" class="d-none w-100 py-3">
                        <i class='bx bx-scan text-primary' style="font-size: 3rem;"></i>
                        <p class="text-dark fw-semibold small mt-2 mb-3">Arahkan fokus tetap di sini, lalu scan kartu dengan alat</p>
                        <div class="px-3">
                            <input type="text" id="hardwareInput" class="form-control form-control-sm text-center fw-semibold font-monospace py-2" placeholder="Siap menerima scan..." autofocus autocomplete="off">
                        </div>
                    </div>

                    <!-- Overlay Sukses (Emerald Gradient) -->
                    <div id="overlaySuccess" class="overlay-status overlay-success d-none">
                        <div class="rounded-circle bg-white bg-opacity-25 p-2 mb-2 d-inline-flex">
                            <i class='bx bx-check fs-2 text-white'></i>
                        </div>
                        <h6 class="fw-bold text-white mb-0 px-2" id="successText" style="font-size: 1.05rem; letter-spacing: -0.01em;">Nama Siswa — Hadir jam 07:00</h6>
                    </div>

                    <!-- Overlay Gagal (Danger Red) -->
                    <div id="overlayError" class="overlay-status overlay-error d-none">
                        <div class="rounded-circle bg-white bg-opacity-25 p-2 mb-2 d-inline-flex">
                            <i class='bx bx-error-circle fs-2 text-white'></i>
                        </div>
                        <h6 class="fw-bold text-white mb-1" style="font-size: 1.05rem;">Peringatan Presensi</h6>
                        <p class="text-white small mb-0 fw-medium px-2" id="errorText" style="font-size: 0.82rem;">Siswa sudah presensi hari ini.</p>
                    </div>

                </div>

                <!-- Jam Operasional di Bawah Kotak -->
                <div class="text-secondary" style="font-size: 0.78rem;">
                    Jam masuk <span class="fw-semibold text-dark">{{ $formattedCheckIn ?? $jamMasuk ?? '07:00' }}</span> · Jam pulang <span class="fw-semibold text-dark">{{ $formattedCheckOut ?? $jamPulang ?? '14:00' }}</span>
                </div>

            </div>
        </div>

        <!-- Kolom Tabel Siswa Kanan -->
        <div class="col-12" id="tableColumn">
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 table-enterprise table-zebra-custom">
                        <thead class="bg-light">
                            <tr class="text-dark small fw-bold text-uppercase" style="letter-spacing: 0.03em; color: #000000 !important;">
                                <th class="text-center py-3" style="width: 5%;">No.</th>
                                <th class="py-3" style="width: 27%;">Nama Siswa</th>
                                <th class="py-3" style="width: 15%;">NIS</th>
                                <th class="text-center py-3" style="width: 13%;">Jam Masuk</th>
                                <th class="text-center py-3" style="width: 13%;">Jam Pulang</th>
                                <th class="text-center py-3" style="width: 12%;">Status</th>
                                <th class="text-center py-3" style="width: 15%;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($studentsList ?? [] as $index => $student)
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
                                    @php
                                        $st = $student->current_status ?? 'Belum';
                                        $stLower = strtolower($st);
                                        $statusColor = match($stLower) {
                                            'hadir' => 'text-success',
                                            'terlambat' => 'text-warning',
                                            'sakit' => 'text-primary',
                                            'izin' => 'text-info',
                                            'alfa', 'alpha' => 'text-danger',
                                            default => 'text-secondary',
                                        };
                                    @endphp
                                    <span class="{{ $statusColor }} fw-semibold">
                                        {{ $st }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    <form action="{{ route('admin.absensi.override') }}" method="POST" class="d-inline-flex align-items-center justify-content-center gap-1 m-0">
                                        @csrf
                                        <input type="hidden" name="student_id" value="{{ $student->id }}">
                                        <input type="hidden" name="date" value="{{ $tanggal ?? date('Y-m-d') }}">

                                        <!-- Dropdown Status Cepat -->
                                        <select name="status" class="select-quick-status">
                                            <option value="Hadir" {{ $st === 'Hadir' ? 'selected' : '' }}>Hadir</option>
                                            <option value="Terlambat" {{ $st === 'Terlambat' ? 'selected' : '' }}>Terlambat</option>
                                            <option value="Sakit" {{ $st === 'Sakit' ? 'selected' : '' }}>Sakit</option>
                                            <option value="Izin" {{ $st === 'Izin' ? 'selected' : '' }}>Izin</option>
                                            <option value="Alfa" {{ $st === 'Alfa' ? 'selected' : '' }}>Alfa</option>
                                        </select>

                                        <!-- Tombol Simpan Cepat (Checklist) -->
                                        <button type="submit" class="btn-save-quick" title="Simpan Status">
                                            <i class='bx bx-check fs-5'></i>
                                        </button>

                                        <!-- Tombol Ikon Edit / Modal Detail (Pensil) -->
                                        <button type="button" class="btn-edit-modal" data-bs-toggle="modal" data-bs-target="#modalOverrideSiswa{{ $student->id }}" title="Edit Detail / Upload Surat">
                                            <i class='bx bx-edit-alt fs-6'></i>
                                        </button>
                                    </form>

                                    <!-- MODAL DETAIL OVERRIDE UNTUK SISWA INI -->
                                    <div class="modal fade text-start" id="modalOverrideSiswa{{ $student->id }}" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog modal-dialog-centered">
                                            <div class="modal-content border-0 shadow-lg rounded-xl overflow-hidden">
                                                <div class="modal-header bg-light border-bottom px-4 py-3">
                                                    <div>
                                                        <h5 class="modal-title fw-bold text-dark mb-0">Ubah Presensi Siswa</h5>
                                                        <div class="small text-muted">{{ $student->name }} (NIS: {{ $student->nis }})</div>
                                                    </div>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <form action="{{ route('admin.absensi.override') }}" method="POST" enctype="multipart/form-data">
                                                    @csrf
                                                    <input type="hidden" name="student_id" value="{{ $student->id }}">
                                                    <input type="hidden" name="date" value="{{ $tanggal ?? date('Y-m-d') }}">

                                                    <div class="modal-body p-4 text-start">
                                                        <div class="mb-3">
                                                            <label class="form-label small fw-semibold text-secondary">Status Kehadiran <span class="text-danger">*</span></label>
                                                            <select name="status" class="form-select rounded-3" required>
                                                                <option value="Hadir" {{ $st === 'Hadir' ? 'selected' : '' }}>Hadir (Tepat Waktu)</option>
                                                                <option value="Terlambat" {{ $st === 'Terlambat' ? 'selected' : '' }}>Hadir (Terlambat)</option>
                                                                <option value="Sakit" {{ $st === 'Sakit' ? 'selected' : '' }}>Sakit (S)</option>
                                                                <option value="Izin" {{ $st === 'Izin' ? 'selected' : '' }}>Izin (I)</option>
                                                                <option value="Alfa" {{ $st === 'Alfa' ? 'selected' : '' }}>Alfa (A)</option>
                                                            </select>
                                                        </div>

                                                        <div class="mb-3">
                                                            <label class="form-label small fw-semibold text-secondary">Jam Masuk</label>
                                                            <input type="time" name="check_in" class="form-control rounded-3" value="{{ ($student->raw_check_in ?? false) ? substr($student->raw_check_in, 0, 5) : '' }}">
                                                        </div>

                                                        <div class="mb-3">
                                                            <label class="form-label small fw-semibold text-secondary">Catatan / Keterangan</label>
                                                            <textarea name="notes" rows="2" class="form-control rounded-3" placeholder="Contoh: Sakit demam ada surat dokter">{{ $student->notes ?? '' }}</textarea>
                                                        </div>

                                                        <div class="mb-2">
                                                            <label class="form-label small fw-semibold text-secondary">Upload Bukti Surat (Sakit / Izin)</label>
                                                            <input type="file" name="proof_document" class="form-control rounded-3 form-control-sm" accept=".pdf,.jpg,.jpeg,.png">
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer bg-light px-4 py-3 border-top">
                                                        <button type="button" class="btn btn-light border px-3 rounded-3" data-bs-dismiss="modal">Batal</button>
                                                        <button type="submit" class="btn btn-primary px-4 fw-semibold rounded-3">Simpan Status</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>

                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="text-center py-4 text-secondary">Belum ada data siswa di kelas ini.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script src="https://unpkg.com/html5-qrcode"></script>
<script>
    let isScannerOpen = false;
    let html5QrKiosk = null;
    let isCamRunning = false;
    let isProcessing = false;
    let resetTimer = null;
    let activeMode = 'camera';

    // Web Audio API Generator Suara Beep Browser Identik Mode Gerbang
    let audioCtx = null;

    function playBrowserBeep(success = true) {
        try {
            if (!audioCtx) {
                audioCtx = new (window.AudioContext || window.webkitAudioContext)();
            }
            if (audioCtx.state === 'suspended') {
                audioCtx.resume();
            }

            const osc = audioCtx.createOscillator();
            const gain = audioCtx.createGain();

            if (success) {
                // Suara Beep Sukses (Tit Tinggi Renyah 2500Hz)
                osc.type = 'sine';
                osc.frequency.setValueAtTime(2500, audioCtx.currentTime);
                gain.gain.setValueAtTime(0.3, audioCtx.currentTime);
                gain.gain.exponentialRampToValueAtTime(0.0001, audioCtx.currentTime + 0.08);
                osc.connect(gain);
                gain.connect(audioCtx.destination);
                osc.start();
                osc.stop(audioCtx.currentTime + 0.08);
            } else {
                // Suara Beep Error (Buzzer Rendah 150-300Hz)
                osc.type = 'sawtooth';
                osc.frequency.setValueAtTime(150, audioCtx.currentTime);
                osc.frequency.setValueAtTime(300, audioCtx.currentTime + 0.1);
                gain.gain.setValueAtTime(0.4, audioCtx.currentTime);
                gain.gain.exponentialRampToValueAtTime(0.0001, audioCtx.currentTime + 0.3);
                osc.connect(gain);
                gain.connect(audioCtx.destination);
                osc.start();
                osc.stop(audioCtx.currentTime + 0.3);
            }
        } catch (e) {
            console.warn('Audio error:', e);
        }
    }

    function toggleInlineScanner() {
        isScannerOpen = !isScannerOpen;
        const scannerCol = document.getElementById('scannerColumn');
        const tableCol = document.getElementById('tableColumn');
        const btnToggle = document.getElementById('btnToggleScanner');
        const iconToggle = document.getElementById('toggleScannerIcon');
        const textToggle = document.getElementById('toggleScannerText');

        if (isScannerOpen) {
            sessionStorage.setItem('class_scanner_open', '1');

            if (scannerCol) scannerCol.style.display = 'block';
            if (tableCol) tableCol.className = 'col-12 col-lg-7 col-xl-8';

            if (btnToggle) {
                btnToggle.className = 'btn btn-danger fw-semibold btn-sm px-3.5 py-1.5 rounded-3 d-inline-flex align-items-center gap-1.5 shadow-2xs';
            }
            if (iconToggle) {
                iconToggle.className = 'bx bx-camera-off fs-5';
            }
            if (textToggle) {
                textToggle.innerText = 'Tutup Scanner';
            }

            switchMode(activeMode);
        } else {
            sessionStorage.removeItem('class_scanner_open');

            if (scannerCol) scannerCol.style.display = 'none';
            if (tableCol) tableCol.className = 'col-12';

            if (btnToggle) {
                btnToggle.className = 'btn btn-primary fw-semibold btn-sm px-3.5 py-1.5 rounded-3 d-inline-flex align-items-center gap-1.5 shadow-2xs';
            }
            if (iconToggle) {
                iconToggle.className = 'bx bx-camera fs-5';
            }
            if (textToggle) {
                textToggle.innerText = 'Buka Scanner QR';
            }

            stopCamera();
        }
    }

    function switchMode(mode) {
        activeMode = mode;
        if (isProcessing) return;

        const btnCam = document.getElementById('btnTabCamera');
        const btnHard = document.getElementById('btnTabHardware');
        const viewCam = document.getElementById('cameraView');
        const viewHard = document.getElementById('hardwareView');
        const boxArea = document.getElementById('scannerBox');

        if (mode === 'camera') {
            if (btnCam) btnCam.classList.add('active');
            if (btnHard) btnHard.classList.remove('active');
            if (viewCam) viewCam.classList.remove('d-none');
            if (viewHard) viewHard.classList.add('d-none');
            if (boxArea) boxArea.classList.add('camera-active');
            startCamera();
        } else {
            if (btnHard) btnHard.classList.add('active');
            if (btnCam) btnCam.classList.remove('active');
            if (viewHard) viewHard.classList.remove('d-none');
            if (viewCam) viewCam.classList.add('d-none');
            if (boxArea) boxArea.classList.remove('camera-active');
            stopCamera();

            const inputEl = document.getElementById('hardwareInput');
            if (inputEl) inputEl.focus();
        }
    }

    function processCode(token) {
        const cleanToken = (token || '').trim();
        if (!cleanToken || isProcessing) return;
        isProcessing = true;

        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}';

        fetch("{{ route('admin.scanner.process') }}", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": csrfToken,
                "Accept": "application/json"
            },
            body: JSON.stringify({ qr_token: cleanToken })
        })
        .then(res => res.json().then(data => ({ status: res.status, body: data })))
        .then(({ status, body }) => {
            if (status === 200 && body.success) {
                playBrowserBeep(true);
                showOverlaySuccess(body);

                // Reload otomatis setelah notifikasi tampil agar data tabel diperbarui
                setTimeout(() => {
                    window.location.reload();
                }, 1200);
            } else {
                playBrowserBeep(false);
                showOverlayError(body.message || 'QR Code tidak valid.');
            }
        })
        .catch(err => {
            console.error(err);
            playBrowserBeep(false);
            showOverlayError('Terjadi kendala koneksi ke server.');
        });
    }

    function showOverlaySuccess(data) {
        const overlaySucc = document.getElementById('overlaySuccess');
        const successText = document.getElementById('successText');
        if (!overlaySucc || !successText) return;

        const studentName = data.student ? data.student.name : 'Siswa';
        const statusLabel = data.type === 'check_out' ? 'Pulang' : (data.remark || 'Hadir');
        const now = new Date();
        const timeStr = String(now.getHours()).padStart(2, '0') + ':' + String(now.getMinutes()).padStart(2, '0');

        successText.innerText = `${studentName} — ${statusLabel} jam ${timeStr}`;
        overlaySucc.classList.remove('d-none');

        resetOverlayState(1200);
    }

    function showOverlayError(errorMsg) {
        const overlayErr = document.getElementById('overlayError');
        const errorText = document.getElementById('errorText');
        if (!overlayErr || !errorText) return;

        errorText.innerText = errorMsg;
        overlayErr.classList.remove('d-none');

        resetOverlayState(1200);
    }

    function resetOverlayState(delay = 1000) {
        if (resetTimer) clearTimeout(resetTimer);
        resetTimer = setTimeout(() => {
            const overlaySucc = document.getElementById('overlaySuccess');
            const overlayErr = document.getElementById('overlayError');
            if (overlaySucc) overlaySucc.classList.add('d-none');
            if (overlayErr) overlayErr.classList.add('d-none');

            isProcessing = false;

            if (activeMode === 'hardware') {
                const inputEl = document.getElementById('hardwareInput');
                if (inputEl) inputEl.focus();
            }
        }, delay);
    }

    function startCamera() {
        if (isCamRunning) return;
        if (typeof Html5Qrcode === 'undefined') {
            console.warn('Html5Qrcode library not loaded');
            return;
        }

        const placeholder = document.getElementById('cameraPlaceholder');
        if (placeholder) placeholder.style.display = 'none';

        html5QrKiosk = new Html5Qrcode("reader");

        Html5Qrcode.getCameras().then(devices => {
            if (devices && devices.length) {
                html5QrKiosk.start(
                    devices[0].id,
                    { fps: 10, qrbox: { width: 180, height: 180 } },
                    (decodedText) => {
                        processCode(decodedText);
                    },
                    () => {}
                ).then(() => { isCamRunning = true; });
            }
        }).catch(err => {
            console.error(err);
            if (placeholder) placeholder.style.display = 'flex';
        });
    }

    function stopCamera() {
        if (isCamRunning && html5QrKiosk) {
            html5QrKiosk.stop().then(() => {
                isCamRunning = false;
                html5QrKiosk.clear();
                const placeholder = document.getElementById('cameraPlaceholder');
                if (placeholder) placeholder.style.display = 'flex';
            }).catch(err => {
                console.error(err);
                isCamRunning = false;
            });
        }
    }

    // Listener Hardware Barcode Scanner & Auto Resume
    document.addEventListener('DOMContentLoaded', function() {
        const hardInput = document.getElementById('hardwareInput');
        if (hardInput) {
            hardInput.addEventListener('keydown', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    processCode(this.value);
                    this.value = '';
                }
            });
        }

        window.addEventListener('click', function(e) {
            if (isScannerOpen && activeMode === 'hardware' && !isProcessing) {
                if (e.target.tagName !== 'INPUT' && e.target.tagName !== 'BUTTON') {
                    if (hardInput) hardInput.focus();
                }
            }
        });

        // Buka kembali secara otomatis jika scanner aktif sebelum reload halaman
        if (sessionStorage.getItem('class_scanner_open') === '1') {
            toggleInlineScanner();
        }
    });
</script>
@endpush
