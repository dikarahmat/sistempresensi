@extends('layouts.app')

@section('title', __('Detail Siswa'))
@section('page_title', __('Detail Siswa'))

@push('styles')
<style>
    .card-modern {
        background: #ffffff;
        border-radius: 16px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 1px 4px rgba(15, 23, 42, 0.02);
    }

    .info-label {
        font-size: 0.85rem;
        color: #0f172a;
        margin-bottom: 0.25rem;
    }

    .info-value {
        font-size: 0.95rem;
        font-weight: 700;
        color: #0f172a;
        margin-bottom: 0;
    }

    .field-row {
        margin-bottom: 1.1rem;
    }
    .field-row:last-of-type {
        margin-bottom: 0;
    }

    .form-control, .form-select {
        border-radius: var(--clean-radius, 8px);
        border: 1px solid #cbd5e1;
        padding: 0.65rem 0.9rem;
        font-size: 0.9rem;
        color: #1e293b;
        width: 100%;
    }

    .form-control:focus, .form-select:focus {
        border-color: #3b82f6;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15);
    }

    .form-control.is-invalid,
    .form-select.is-invalid {
        border: 1px solid #dc3545 !important;
        background-image: none !important;
        box-shadow: none !important;
    }
    .form-control.is-invalid:focus,
    .form-select.is-invalid:focus {
        border-color: #dc3545 !important;
        box-shadow: 0 0 0 0.15rem rgba(220, 53, 69, 0.15) !important;
    }

    .invalid-feedback.error-msg {
        font-size: 0.8rem;
        color: #dc2626;
        margin-top: 0.35rem;
    }
    .invalid-feedback.error-msg:empty {
        display: none !important;
        margin-top: 0;
    }

    .btn-submit-blue {
        background-color: #3b82f6;
        color: #ffffff;
        font-weight: 600;
        font-size: 0.9rem;
        border-radius: var(--clean-radius, 8px);
        border: none;
        padding: 0.6rem 1.5rem;
        transition: background-color 0.15s ease;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        line-height: 1.5;
        position: relative;
        overflow: hidden;
    }
    .btn-submit-blue:hover { background-color: #2563eb; color: #ffffff; }
    .btn-submit-blue:disabled, .btn-submit-blue[aria-disabled="true"] {
        opacity: 0.65;
        cursor: not-allowed;
    }

    /* Loading progress 1-100% (biru penuh sejak persen pertama) */
    .btn-submit-blue.is-loading,
    .btn-submit-blue.is-loading:disabled,
    .btn-submit-blue.is-loading:hover {
        opacity: 1;
        background-color: #3b82f6;
        cursor: wait;
        pointer-events: none;
    }
    .btn-submit-blue .bp-fill {
        position: absolute;
        top: 0;
        left: 0;
        height: 100%;
        width: 0%;
        background-color: #1d4ed8;
        border-radius: inherit;
        z-index: 0;
        transition: width 0.15s linear;
    }
    .btn-submit-blue .bp-text {
        position: relative;
        z-index: 1;
        color: #ffffff;
        font-weight: 700;
    }

    .btn-cancel-gray {
        background-color: #ffffff;
        color: #475569;
        border: 1px solid #cbd5e1;
        font-weight: 600;
        font-size: 0.9rem;
        border-radius: var(--clean-radius, 8px);
        padding: 0.6rem 1.5rem;
        text-decoration: none;
        cursor: pointer;
        transition: background-color 0.15s ease, color 0.15s ease;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        line-height: 1.5;
    }
    .btn-cancel-gray:hover { background-color: #f1f5f9; color: #0f172a; }

    /* Tombol download: selebar isi box */
    .btn-download-green {
        background-color: #10b981;
        color: #ffffff;
        font-weight: 600;
        font-size: 0.9rem;
        border-radius: var(--clean-radius, 8px);
        border: none;
        padding: 0.75rem 1rem;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.4rem;
        cursor: pointer;
        transition: all 0.2s ease;
        text-decoration: none;
        width: 100%;
        min-height: 42px;
        box-shadow: 0 4px 6px rgba(16, 185, 129, 0.2);
    }
    .btn-download-green:hover { background-color: #059669; color: #ffffff; box-shadow: 0 6px 8px rgba(16, 185, 129, 0.3); }

    /* Preview kartu CR80 (53.98mm x 85.6mm) yang SKALANYA mengikuti lebar box.
       Skala dihitung JS (--kp-scale) supaya kartu selalu utuh, tidak terpotong
       di lebar layar berapa pun (split screen, tablet, HP). Maksimal 1.6x. */
    .kp-card-box {
        width: 100%;
        max-width: calc(53.98mm * 1.6);
        aspect-ratio: 53.98 / 85.6;
        margin: 0 auto 1.5rem auto;
        position: relative;
        overflow: hidden;
    }

    .kp-card-scale {
        width: 53.98mm;
        height: 85.6mm;
        transform: scale(var(--kp-scale, 1));
        transform-origin: top left;
    }

    .kp-card-scale > * {
        margin: 0;
    }

    /* Kolom boleh menyusut (tanpa ini isi flex/grid bisa meluber & terpotong). */
    .kp-col { min-width: 0; }

    /* Ruang napas di bawah supaya elemen terakhir selalu bisa di-scroll sampai habis. */
    .kp-page { padding-bottom: 1.5rem; }
</style>
@endpush

@section('content')
    <div id="notifInlineContainer">
        @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show border-0 rounded-3 shadow-xs small mb-4" role="alert">
            <div class="flash-notice-body">
                <div class="d-flex align-items-center">
                    <i class='bx bx-check-circle fs-5 me-2 text-success flex-shrink-0'></i>
                    <span>{{ session('success') }}</span>
                </div>
            </div>
            <button type="button" class="btn-close btn-close-sm" data-bs-dismiss="alert" aria-label="{{ __('Tutup') }}"></button>
        </div>
        @endif

        @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show border-0 rounded-3 shadow-xs small mb-4" role="alert">
            <div class="flash-notice-body">
                <div class="d-flex align-items-center">
                    <i class='bx bx-error-circle fs-5 me-2 text-danger flex-shrink-0'></i>
                    <span>{{ session('error') }}</span>
                </div>
            </div>
            <button type="button" class="btn-close btn-close-sm" data-bs-dismiss="alert" aria-label="{{ __('Tutup') }}"></button>
        </div>
        @endif
    </div>

    <div class="row g-4 align-items-start kp-page">
        <!-- Informasi Siswa (Kolom Kiri; di layar < 1200px turun jadi satu kolom) -->
        <div class="col-12 col-xl-7 kp-col">
            <div class="card card-modern p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h5 class="fw-bold mb-0" style="color: #0f172a;">{{ __('Informasi Siswa') }}</h5>
                    @if(is_admin())
                    <button type="button" id="btnEditSiswa" class="btn btn-sm border-0 bg-transparent p-0" title="{{ __('Edit') }}" aria-label="{{ __('Edit') }}" style="color: #0f172a; cursor: pointer; line-height: 1;">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="18" height="18" style="display:block; color: #0f172a;"><path d="M21.174 6.812a1 1 0 0 0-3.986-3.987L3.842 16.174a2 2 0 0 0-.5.83l-1.321 4.352a.5.5 0 0 0 .623.622l4.353-1.32a2 2 0 0 0 .83-.497z"/><path d="m15 5 4 4"/></svg>
                    </button>
                    @endif
                </div>

                @csrf

                <div class="field-row" id="rowKelas">
                    <div class="info-label" style="color: #0f172a;">{{ __('Kelas') }}</div>
                    <div id="viewKelas" class="info-value">{{ __('Kelas ') }}{{ $student->schoolClass->name ?? '-' }}</div>
                    <select id="editKelas" class="form-select d-none" style="width: 100%;">
                        @foreach($classes as $c)
                        <option value="{{ $c->id }}" {{ $student->school_class_id == $c->id ? 'selected' : '' }}>{{ __('Kelas ') }}{{ $c->name }}</option>
                        @endforeach
                    </select>
                    <div id="errKelas" class="invalid-feedback error-msg d-none"></div>
                </div>

                <div class="field-row" id="rowNisn">
                    <div class="info-label" style="color: #0f172a;">{{ __('NISN') }}</div>
                    <div id="viewNisn" class="info-value">{{ $student->nisn ?: '-' }}</div>
                    <input type="text" id="editNisn" class="form-control d-none" style="width: 100%;" value="{{ $student->nisn }}" maxlength="10" inputmode="numeric" autocomplete="off" placeholder="{{ __('10 digit angka') }}">
                    <div id="errNisn" class="invalid-feedback error-msg d-none"></div>
                </div>

                <div class="field-row" id="rowNama">
                    <div class="info-label" style="color: #0f172a;">{{ __('Nama Lengkap') }}</div>
                    <div id="viewNama" class="info-value">{{ $student->name }}</div>
                    <input type="text" id="editNama" class="form-control d-none" style="width: 100%;" value="{{ $student->name }}">
                    <div id="errNama" class="invalid-feedback error-msg d-none"></div>
                </div>

                <div class="field-row" id="rowJk">
                    <div class="info-label" style="color: #0f172a;">{{ __('Jenis Kelamin') }}</div>
                    <div id="viewJk" class="info-value mb-0">{{ __($student->gender ?? 'Laki-laki') }}</div>
                    <select id="editJk" class="form-select d-none" style="width: 100%;">
                        <option value="Laki-laki" {{ $student->gender == 'Laki-laki' || $student->gender == 'L' ? 'selected' : '' }}>{{ __('Laki-laki') }}</option>
                        <option value="Perempuan" {{ $student->gender == 'Perempuan' || $student->gender == 'P' ? 'selected' : '' }}>{{ __('Perempuan') }}</option>
                    </select>
                    <div id="errJk" class="invalid-feedback error-msg d-none"></div>
                </div>

                @if(is_admin())
                <div id="actionsEdit" class="d-none mt-3 d-flex align-items-center gap-2">
                    <button type="button" id="btnSimpanInline" class="btn-submit-blue">{{ __('Simpan Perubahan') }}</button>
                    <button type="button" id="btnBatalInline" class="btn-cancel-gray">{{ __('Batal') }}</button>
                </div>
                @endif
            </div>
        </div>

        <!-- Box Kartu Presensi (Kolom Kanan / di bawah pada layar sempit) -->
        <div class="col-12 col-xl-5 kp-col">
            <div class="card card-modern p-4">
                <h5 class="fw-bold mb-4" style="color: #0f172a;">{{ __('Kartu Presensi') }}</h5>

                <!-- Preview kartu (tengah) -->
                <div class="kp-card-box">
                    <div class="kp-card-scale">@include('admin.students.partials.card-single', [
                        'studentItem' => $student,
                        'studentName' => $student->name,
                        'studentNisn' => $student->nisn,
                        'studentClass' => $student->schoolClass->name ?? '-',
                    ])</div>
                </div>

                <!-- Tombol download (tepat di bawah kartu, selebar box, di luar kp-card-box) -->
                <a href="{{ panel_route('students.download-card', $student->id) }}" class="btn-download-green btn-progress" id="btnDownloadCard" data-download data-no-download data-dl-progress>{{ __('Download Kartu Presensi') }}</a>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
(function () {
    'use strict';

    var btnEdit = document.getElementById('btnEditSiswa');
    var btnSimpan = document.getElementById('btnSimpanInline');
    var btnBatal = document.getElementById('btnBatalInline');
    var actionsEdit = document.getElementById('actionsEdit');

    var viewKelas = document.getElementById('viewKelas');
    var viewNisn = document.getElementById('viewNisn');
    var viewNama = document.getElementById('viewNama');
    var viewJk = document.getElementById('viewJk');

    var editKelas = document.getElementById('editKelas');
    var editNisn = document.getElementById('editNisn');
    var editNama = document.getElementById('editNama');
    var editJk = document.getElementById('editJk');

    var errKelas = document.getElementById('errKelas');
    var errNisn = document.getElementById('errNisn');
    var errNama = document.getElementById('errNama');
    var errJk = document.getElementById('errJk');

    var tokenMeta = document.querySelector('meta[name="csrf-token"]');
    var tokenInput = document.querySelector('input[name="_token"]');
    var token = (tokenMeta ? tokenMeta.getAttribute('content') : '') || (tokenInput ? tokenInput.value : '');

    var updateUrl = '{{ panel_route('students.update', $student->id) }}';

    var savedData = {
        classId: '{{ $student->school_class_id }}',
        className: {!! json_encode($student->schoolClass->name ?? '-') !!},
        nisn: {!! json_encode($student->nisn ?? '') !!},
        name: {!! json_encode($student->name) !!},
        gender: {!! json_encode($student->gender ?? 'Laki-laki') !!}
    };

    function showFieldError(inputEl, errEl, msg) {
        if (inputEl) inputEl.classList.add('is-invalid');
        if (errEl) {
            errEl.textContent = msg;
            errEl.classList.remove('d-none');
        }
    }

    function hideFieldError(inputEl, errEl) {
        if (inputEl) inputEl.classList.remove('is-invalid');
        if (errEl) {
            errEl.textContent = '';
            errEl.classList.add('d-none');
        }
    }

    function clearAllErrors() {
        hideFieldError(editKelas, errKelas);
        hideFieldError(editNisn, errNisn);
        hideFieldError(editNama, errNama);
        hideFieldError(editJk, errJk);
    }

    function setMode(isEdit) {
        if (btnEdit) btnEdit.classList.toggle('d-none', isEdit);
        if (actionsEdit) actionsEdit.classList.toggle('d-none', !isEdit);

        if (viewKelas) viewKelas.classList.toggle('d-none', isEdit);
        if (viewNisn) viewNisn.classList.toggle('d-none', isEdit);
        if (viewNama) viewNama.classList.toggle('d-none', isEdit);
        if (viewJk) viewJk.classList.toggle('d-none', isEdit);

        if (editKelas) editKelas.classList.toggle('d-none', !isEdit);
        if (editNisn) editNisn.classList.toggle('d-none', !isEdit);
        if (editNama) editNama.classList.toggle('d-none', !isEdit);
        if (editJk) editJk.classList.toggle('d-none', !isEdit);

        clearAllErrors();
    }

    function nameCheck() {
        var v = editNama ? editNama.value.trim() : '';
        if (!v) return { ok: false, msg: 'Nama siswa wajib diisi.' };
        if (!/^[\p{L}\s.,'\-]+$/u.test(v)) return { ok: false, msg: 'Nama siswa hanya boleh berisi huruf, spasi, titik, koma, apostrof, dan tanda hubung.' };
        return { ok: true };
    }

    function nisnCheck() {
        var v = editNisn ? editNisn.value.trim() : '';
        if (!v) return { ok: false, msg: 'NISN wajib diisi.' };
        if (!/^\d{10}$/.test(v)) return { ok: false, msg: 'NISN harus berupa 10 digit angka.' };
        return { ok: true };
    }

    function classCheck() {
        return editKelas && editKelas.value !== ''
            ? { ok: true }
            : { ok: false, msg: 'Pilih kelas terlebih dahulu.' };
    }

    function genderCheck() {
        return editJk && editJk.value !== ''
            ? { ok: true }
            : { ok: false, msg: 'Pilih jenis kelamin.' };
    }

    if (editNama) {
        function sanitizeEditNama() {
            editNama.value = editNama.value.replace(/[^\p{L}\s.,'\-]/gu, '');
            hideFieldError(editNama, errNama);
        }
        editNama.addEventListener('input', sanitizeEditNama);
        editNama.addEventListener('paste', function () {
            setTimeout(sanitizeEditNama, 0);
        });
        editNama.addEventListener('blur', function () {
            var res = nameCheck();
            if (!res.ok) showFieldError(editNama, errNama, res.msg);
        });
    }

    if (editNisn) {
        function sanitizeEditNisn() {
            editNisn.value = editNisn.value.replace(/\D/g, '').slice(0, 10);
            hideFieldError(editNisn, errNisn);
        }
        editNisn.addEventListener('input', sanitizeEditNisn);
        editNisn.addEventListener('paste', function () {
            setTimeout(sanitizeEditNisn, 0);
        });
        editNisn.addEventListener('blur', function () {
            var res = nisnCheck();
            if (!res.ok) showFieldError(editNisn, errNisn, res.msg);
        });
    }

    if (editKelas) {
        editKelas.addEventListener('change', function () {
            var res = classCheck();
            if (res.ok) hideFieldError(editKelas, errKelas);
            else showFieldError(editKelas, errKelas, res.msg);
        });
    }

    if (editJk) {
        editJk.addEventListener('change', function () {
            var res = genderCheck();
            if (res.ok) hideFieldError(editJk, errJk);
            else showFieldError(editJk, errJk, res.msg);
        });
    }

    function escapeHtml(s) {
        return String(s).replace(/[&<>"']/g, function (c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
        });
    }

    // Notifikasi seragam: type = 'success' | 'danger'
    function showNotif(type, msg) {
        var existingAlerts = document.querySelectorAll('.alert');
        existingAlerts.forEach(function (a) { a.remove(); });

        var container = document.getElementById('notifInlineContainer');
        if (!container) {
            container = document.createElement('div');
            container.id = 'notifInlineContainer';
            var mainRow = document.querySelector('.row.g-4');
            if (mainRow && mainRow.parentNode) {
                mainRow.parentNode.insertBefore(container, mainRow);
            } else {
                document.body.prepend(container);
            }
        }

        var isDanger = type === 'danger';
        var alertClass = isDanger ? 'alert-danger' : 'alert-success';
        var iconClass = isDanger ? 'bx-error-circle text-danger' : 'bx-check-circle text-success';

        container.innerHTML = '<div class="alert ' + alertClass + ' alert-dismissible fade show border-0 rounded-3 shadow-xs small mb-4" role="alert">'
            + '<div class="flash-notice-body">'
            + '<div class="d-flex align-items-center">'
            + '<i class="bx ' + iconClass + ' fs-5 me-2 flex-shrink-0"></i>'
            + '<span>' + escapeHtml(msg) + '</span>'
            + '</div>'
            + '</div>'
            + '<button type="button" class="btn-close btn-close-sm" data-bs-dismiss="alert" aria-label="Tutup"></button>'
            + '</div>';

        try {
            if (typeof bootstrap !== 'undefined' && bootstrap.Alert) {
                var alertEl = container.querySelector('.alert');
                if (alertEl) new bootstrap.Alert(alertEl);
            }
        } catch (_) {}
    }

    // Progress 1-100% di dalam tombol
    var progressTimer = null;
    var progressValue = 0;

    function setProgress(p) {
        progressValue = p;
        if (!btnSimpan) return;
        var fill = btnSimpan.querySelector('.bp-fill');
        var txt = btnSimpan.querySelector('.bp-text');
        if (fill) fill.style.width = p + '%';
        if (txt) txt.textContent = p + '%';
    }

    function startProgress() {
        btnSimpan.style.minWidth = btnSimpan.offsetWidth + 'px';
        btnSimpan.style.minHeight = btnSimpan.offsetHeight + 'px';
        btnSimpan.classList.add('is-loading');   // sebelum disabled supaya tidak pudar
        btnSimpan.innerHTML = '<span class="bp-fill"></span><span class="bp-text">1%</span>';
        btnSimpan.disabled = true;
        setProgress(1);
        clearInterval(progressTimer);
        progressTimer = setInterval(function () {
            if (progressValue < 90) {
                var step = progressValue < 50 ? 3 : (progressValue < 75 ? 2 : 1);
                setProgress(Math.min(90, progressValue + step));
            }
        }, 100);
    }

    function stopProgress() {
        clearInterval(progressTimer);
        progressTimer = null;
    }

    function resetSaveButton() {
        stopProgress();
        btnSimpan.classList.remove('is-loading');
        btnSimpan.textContent = 'Simpan Perubahan';
        btnSimpan.style.minWidth = '';
        btnSimpan.style.minHeight = '';
        btnSimpan.disabled = false;
        progressValue = 0;
    }

    if (btnEdit) {
        btnEdit.addEventListener('click', function () {
            editKelas.value = savedData.classId;
            editNisn.value = savedData.nisn;
            editNama.value = savedData.name;
            editJk.value = savedData.gender;
            setMode(true);
            if (editNama) {
                try { editNama.focus(); } catch (_) {}
            }
        });
    }

    if (btnBatal) {
        btnBatal.addEventListener('click', function () {
            editKelas.value = savedData.classId;
            editNisn.value = savedData.nisn;
            editNama.value = savedData.name;
            editJk.value = savedData.gender;
            clearAllErrors();
            setMode(false);
        });
    }

    if (btnSimpan) {
        btnSimpan.addEventListener('click', function () {
            clearAllErrors();

            var cRes = classCheck();
            var nRes = nisnCheck();
            var mRes = nameCheck();
            var gRes = genderCheck();

            var hasErr = false;
            if (!cRes.ok) { showFieldError(editKelas, errKelas, cRes.msg); hasErr = true; }
            if (!nRes.ok) { showFieldError(editNisn, errNisn, nRes.msg); hasErr = true; }
            if (!mRes.ok) { showFieldError(editNama, errNama, mRes.msg); hasErr = true; }
            if (!gRes.ok) { showFieldError(editJk, errJk, gRes.msg); hasErr = true; }

            if (hasErr) {
                var firstInvalid = document.querySelector('.card-modern .is-invalid');
                if (firstInvalid) { try { firstInvalid.focus(); } catch (_) {} }
                return;
            }

            startProgress();
            if (btnBatal) btnBatal.disabled = true;

            var fd = new FormData();
            fd.append('_method', 'PUT');
            fd.append('_token', token);
            fd.append('school_class_id', editKelas.value);
            fd.append('name', editNama.value.trim());
            fd.append('nisn', editNisn.value.trim());
            fd.append('gender', editJk.value);

            fetch(updateUrl, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': token
                },
                body: fd
            }).then(function (res) {
                if (res.status === 422) {
                    return res.json().then(function (j) {
                        throw { status: 422, json: j };
                    });
                }
                if (!res.ok) {
                    throw new Error('Gagal menyimpan data.');
                }
                return res.json();
            }).then(function (data) {
                // Sukses: lompat ke 100%, tunggu sebentar, baru tampilkan hasil
                stopProgress();
                setProgress(100);

                return new Promise(function (resolve) {
                    setTimeout(function () {
                        var resData = (data && data.data) ? data.data : {};
                        savedData.classId = editKelas.value;
                        savedData.className = resData.school_class_name || (editKelas.options[editKelas.selectedIndex] ? editKelas.options[editKelas.selectedIndex].text.replace(/^Kelas\s+/i, '') : '');
                        savedData.nisn = resData.nisn !== undefined ? resData.nisn : editNisn.value.trim();
                        savedData.name = resData.name !== undefined ? resData.name : editNama.value.trim();
                        savedData.gender = resData.gender !== undefined ? resData.gender : editJk.value;

                        if (viewKelas) viewKelas.textContent = 'Kelas ' + savedData.className;
                        if (viewNisn) viewNisn.textContent = savedData.nisn || '-';
                        if (viewNama) viewNama.textContent = savedData.name;
                        if (viewJk) viewJk.textContent = savedData.gender;

                        var cardScale = document.querySelector('.kp-card-scale');
                        if (cardScale) {
                            var nameDiv = cardScale.querySelector('div[style*="top:16.5mm"]');
                            var nisnDiv = cardScale.querySelector('div[style*="top:20.6mm"]');
                            var classDiv = cardScale.querySelector('div[style*="top:24.6mm"]');
                            if (nameDiv) nameDiv.textContent = savedData.name.toUpperCase();
                            if (nisnDiv) nisnDiv.textContent = savedData.nisn || '';
                            if (classDiv) classDiv.textContent = savedData.className;
                            if (resData.qr_src) {
                                var qrImg = cardScale.querySelector('div[style*="top:30.5mm"] img');
                                if (qrImg) qrImg.src = resData.qr_src;
                            }
                        }

                        setMode(false);
                        showNotif('success', 'Data siswa berhasil diperbarui!');
                        resolve();
                    }, 300);
                });
            }).catch(function (err) {
                stopProgress();
                if (err && err.status === 422) {
                    var errors = (err.json && err.json.errors) ? err.json.errors : {};
                    if (errors.school_class_id) {
                        showFieldError(editKelas, errKelas, Array.isArray(errors.school_class_id) ? errors.school_class_id[0] : errors.school_class_id);
                    }
                    if (errors.nisn) {
                        showFieldError(editNisn, errNisn, Array.isArray(errors.nisn) ? errors.nisn[0] : errors.nisn);
                    }
                    if (errors.name) {
                        showFieldError(editNama, errNama, Array.isArray(errors.name) ? errors.name[0] : errors.name);
                    }
                    if (errors.gender) {
                        showFieldError(editJk, errJk, Array.isArray(errors.gender) ? errors.gender[0] : errors.gender);
                    }
                    var firstInvalid = document.querySelector('.card-modern .is-invalid');
                    if (firstInvalid) { try { firstInvalid.focus(); } catch (_) {} }
                    showNotif('danger', 'Gagal menyimpan: periksa kembali data yang diisi.');
                } else {
                    showNotif('danger', (err && err.message) ? err.message : 'Gagal menyimpan data. Silakan coba lagi.');
                }
            }).finally(function () {
                resetSaveButton();
                if (btnBatal) btnBatal.disabled = false;
            });
        });
    }
})();

    // Skala kartu otomatis mengikuti lebar box (maks 1.6x), supaya kartu
    // tidak pernah terpotong di split screen / layar sempit.
    (function () {
        var box = document.querySelector('.kp-card-box');
        var card = document.querySelector('.kp-card-scale');
        if (!box || !card) return;

        function fit() {
            var base = card.offsetWidth;          // lebar asli kartu (tanpa transform)
            var avail = box.clientWidth;
            if (!base || !avail) return;
            var scale = Math.min(1.6, avail / base);
            card.style.setProperty('--kp-scale', scale);
        }

        fit();
        window.addEventListener('resize', fit);
        window.addEventListener('load', fit);
        if (typeof ResizeObserver !== 'undefined') {
            new ResizeObserver(fit).observe(box);
        }
    })();
</script>
@endpush