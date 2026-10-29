@extends('layouts.app')

@section('title', __('Edit Siswa'))
@section('page_title', __('Edit Data Siswa') . ': ' . $student->name)
@section('page_subtitle', __('Perbarui biodata siswa dan data rombongan belajar'))

@section('page_header_right')
<div class="d-flex gap-2">
    <a href="{{ panel_route('students.show', $student->id) }}" class="btn btn-light border rounded-3 px-3 py-1.5 d-inline-flex align-items-center gap-1.5 shadow-2xs text-secondary fw-semibold" style="font-size: 0.85rem;">
        <i class='bx bx-chevron-left'></i> {{ __('Lihat Detail') }}
    </a>
</div>
@endsection

@push('styles')
<style>
    .card-modern {
        background: #ffffff;
        border-radius: 16px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 1px 4px rgba(15, 23, 42, 0.02);
        padding: 2rem;
    }

    .form-label {
        font-size: 0.88rem;
        font-weight: 600;
        color: #334155;
        margin-bottom: 0.35rem;
    }

    .form-control, .form-select {
        border-radius: var(--clean-radius);
        border: 1px solid #cbd5e1;
        padding: 0.65rem 0.9rem;
        font-size: 0.9rem;
        color: #1e293b;
    }

    .form-control:focus, .form-select:focus {
        border-color: #3b82f6;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15);
    }

    /* Gaya error field (sama dengan modal Tambah): border merah tipis,
       pesan merah kecil di bawah input, tanpa ikon di dalam input. */
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
        background-image: none !important;
    }
    .invalid-feedback.error-msg {
        display: block;
        margin-top: 0.2rem;
        margin-bottom: 0;
        padding: 0;
        font-size: 0.75rem;
        font-weight: 400;
        line-height: 1.55;
        color: #dc3545;
        background: none;
        border: 0;
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
        border-radius: var(--clean-radius);
        border: none;
        padding: 0.6rem 1.5rem;
    }
    .btn-submit-blue:hover { background-color: #2563eb; color: #ffffff; }

    .btn-cancel-gray {
        background-color: #ffffff;
        color: #475569;
        border: 1px solid #cbd5e1;
        font-weight: 600;
        font-size: 0.9rem;
        border-radius: var(--clean-radius);
        padding: 0.6rem 1.5rem;
        text-decoration: none;
    }
    .btn-cancel-gray:hover { background-color: #f1f5f9; color: #0f172a; }
</style>
@endpush

@section('content')
    {{-- Notifikasi: [ .flash-notice-body (ikon + teks) ] [ tombol X ].
         Tombol X center vertikal oleh CSS notifikasi global di layout. --}}
    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show border-0 rounded-3 shadow-xs small mb-4" role="alert">
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
    <div class="alert alert-danger alert-dismissible fade show border-0 rounded-3 shadow-xs small mb-4" role="alert">
        <div class="flash-notice-body">
            <div class="d-flex align-items-center">
                <i class='bx bx-x-circle fs-5 me-2 text-danger'></i>
                <span>{{ session('error') }}</span>
            </div>
        </div>
        <button type="button" class="btn-close btn-close-sm" data-bs-dismiss="alert" aria-label="{{ __('Tutup') }}"></button>
    </div>
    @endif

    @if($errors->any())
    <div class="alert alert-danger py-2 px-3 small mb-4 border-0 rounded-3" role="alert">
        <i class='bx bx-error-circle me-1'></i> {{ __('Data belum bisa disimpan. Periksa isian yang bertanda merah.') }}
    </div>
    @endif

    <div class="card-modern">
        <form action="{{ panel_route('students.update', $student->id) }}" method="POST">
            @csrf
            @method('PUT')

            <!-- Kelas -->
            <div class="mb-3">
                <label class="form-label">{{ __('Kelas') }}</label>
                <select name="school_class_id" id="inputClass" class="form-select @error('school_class_id') is-invalid @enderror" required>
                    @foreach($classes as $c)
                    <option value="{{ $c->id }}" {{ old('school_class_id', $student->school_class_id) == $c->id ? 'selected' : '' }}>
                        {{ $c->name }}
                    </option>
                    @endforeach
                </select>
                <div class="invalid-feedback d-block error-msg" id="msgClass">@error('school_class_id'){{ $message }}@enderror</div>
            </div>

            <!-- Nama Siswa -->
            <div class="row g-3 mb-3">
                <div class="col-12 col-md-6">
                    <label class="form-label">{{ __('Nama Siswa') }}</label>
                    <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" id="inputName" value="{{ old('name', $student->name) }}" required>
                    <div class="invalid-feedback d-block error-msg" id="msgName">@error('name'){{ $message }}@enderror</div>
                </div>
            </div>

            <!-- NISN -->
            <div class="row g-3 mb-3">
                <div class="col-12 col-md-6">
                    <label class="form-label">{{ __('NISN') }}</label>
                    <input type="text" name="nisn" id="inputNisn" class="form-control @error('nisn') is-invalid @enderror" value="{{ old('nisn', $student->nisn) }}" placeholder="{{ __('Contoh: 0081234567') }}" required maxlength="10" inputmode="numeric" pattern="[0-9]*" autocomplete="off" oninput="this.value=this.value.replace(/[^0-9]/g,'')">
                    <div class="invalid-feedback d-block error-msg" id="msgNisn">@error('nisn'){{ $message }}@enderror</div>
                </div>
            </div>

            <!-- Jenis Kelamin -->
            <div class="row g-3 mb-3">
                <div class="col-12 col-md-6">
                    <label class="form-label">{{ __('Jenis Kelamin') }}</label>
                    <select name="gender" id="inputGender" class="form-select @error('gender') is-invalid @enderror" required>
                        <option value="">{{ __('Pilih Jenis Kelamin') }}</option>
                        <option value="Laki-laki" {{ old('gender', $student->gender) == 'Laki-laki' || old('gender', $student->gender) == 'L' ? 'selected' : '' }}>{{ __('Laki-laki') }}</option>
                        <option value="Perempuan" {{ old('gender', $student->gender) == 'Perempuan' || old('gender', $student->gender) == 'P' ? 'selected' : '' }}>{{ __('Perempuan') }}</option>
                    </select>
                    <div class="invalid-feedback d-block error-msg" id="msgGender">@error('gender'){{ $message }}@enderror</div>
                </div>
            </div>

            <!-- Tombol Simpan & Batal -->
            <div class="d-flex align-items-center gap-2">
                <button type="submit" class="btn-submit-blue">{{ __('Simpan Perubahan') }}</button>
                <a href="{{ panel_route('students.index', array_filter(request()->only(['search', 'class_id', 'page']))) }}" class="btn-cancel-gray">{{ __('Batal') }}</a>
            </div>

<script>
    // Aturan SAMA PERSIS dengan controller studentRules():
    //  - name: hanya huruf, spasi, titik, apostrof, strip.
    //  - nisn: tepat 10 digit angka (/^\d{10}$/).
    function validateName(v) { return /^(?=.*\p{L})[\p{L}\s.'’\u2013\u2014]+$/u.test(v); }
    function validateNisn(v) { return /^[0-9]{10}$/.test(v); }

    // Gaya error: border merah + pesan merah kecil di bawah input.
    // (tanpa ikon tambahan, font kecil, margin atas kecil)
    function showFieldError(input, msgId, msg) {
        if (!input) return;
        input.classList.add('is-invalid');
        var e = document.getElementById(msgId);
        if (e) e.textContent = msg;
    }

    function hideFieldError(input, msgId) {
        if (!input) return;
        input.classList.remove('is-invalid');
        var e = document.getElementById(msgId);
        if (e) e.textContent = '';
    }

    // Filter ketik/paste via event delegation: bilang angka, blokir.
    document.addEventListener('beforeinput', function (e) {
        if (e.target.matches('#inputName') && e.inputType === 'insertText') {
            if (/[0-9]/.test(e.data)) e.preventDefault();
        }
    });
    document.addEventListener('input', function (e) {
        if (e.target.matches('#inputName')) {
            e.target.value = e.target.value.replace(/\p{Nd}/gu, '');
        }
    });

    // Validasi form Edit: satu sumber aturan untuk state tombol, pesan
    // realtime, dan blokir submit (klik maupun Enter).
    (function () {
        var form = document.querySelector('.card-modern form');
        if (!form) return;

        var btn = form.querySelector('button[type="submit"]');
        var nameEl = form.querySelector('#inputName');
        var nisnEl = form.querySelector('#inputNisn');
        var classEl = form.querySelector('#inputClass');
        var genderEl = form.querySelector('#inputGender');
        var submitting = false;

        function nameCheck() {
            var v = nameEl ? nameEl.value.trim() : '';
            if (!v) return { ok: false, msg: 'Nama siswa wajib diisi.' };
            if (/\p{Nd}/u.test(v)) return { ok: false, msg: 'Nama siswa tidak boleh mengandung angka.' };
            return { ok: true };
        }
        function nisnCheck() {
            var v = nisnEl ? nisnEl.value.trim() : '';
            if (!v) return { ok: false, msg: 'NISN wajib diisi.' };
            if (!/^[0-9]{10}$/.test(v)) return { ok: false, msg: 'NISN harus berupa 10 digit angka.' };
            return { ok: true };
        }
        function classCheck() {
            return classEl && classEl.value !== ''
                ? { ok: true }
                : { ok: false, msg: 'Pilih kelas terlebih dahulu.' };
        }
        function genderCheck() {
            return genderEl && genderEl.value !== ''
                ? { ok: true }
                : { ok: false, msg: 'Pilih jenis kelamin.' };
        }

        var checks = {
            name: { el: nameEl, msg: 'msgName', check: nameCheck },
            nisn: { el: nisnEl, msg: 'msgNisn', check: nisnCheck },
            class: { el: classEl, msg: 'msgClass', check: classCheck },
            gender: { el: genderEl, msg: 'msgGender', check: genderCheck }
        };

        function validate(key, force) {
            var item = checks[key];
            if (!item.el) return true;
            var result = item.check();
            var shown = item.el.classList.contains('is-invalid');
            if (result.ok) {
                hideFieldError(item.el, item.msg);
            } else if (force || shown) {
                showFieldError(item.el, item.msg, result.msg);
            }
            return result.ok;
        }

        function allValid() {
            var ok = true;
            Object.keys(checks).forEach(function (key) {
                if (!checks[key].check().ok) ok = false;
            });
            return ok;
        }

        function refreshButton() {
            if (!btn || submitting) return;
            var ok = allValid();
            if (ok) {
                btn.removeAttribute('aria-disabled');
                btn.style.opacity = '';
                btn.style.cursor = '';
            } else {
                btn.setAttribute('aria-disabled', 'true');
                btn.style.opacity = '0.55';
                btn.style.cursor = 'not-allowed';
            }
        }

        function showAllErrors() {
            Object.keys(checks).forEach(function (key) { validate(key, true); });
        }

        function focusFirstInvalid() {
            var first = form.querySelector('.is-invalid');
            if (first) {
                try { first.focus(); } catch (e) { /* abaikan */ }
            }
        }

        // Input Nama sudah dibersihkan handler filter di atas, jadi validasi
        // di sini selalu membaca nilai yang sudah bebas angka.
        document.addEventListener('input', function (e) {
            if (!form.contains(e.target)) return;
            Object.keys(checks).forEach(function (key) {
                if (checks[key].el === e.target) validate(key, false);
            });
            refreshButton();
        });

        [nameEl, nisnEl, classEl, genderEl].forEach(function (el) {
            if (!el) return;
            el.addEventListener('blur', function () {
                Object.keys(checks).forEach(function (key) {
                    if (checks[key].el === el) validate(key, true);
                });
                refreshButton();
            });
        });

        if (classEl) {
            classEl.addEventListener('change', function () { validate('class', true); refreshButton(); });
        }
        if (genderEl) {
            genderEl.addEventListener('change', function () { validate('gender', true); refreshButton(); });
        }

        // Handler validasi didaftarkan lebih dulu (capture) agar selalu
        // berjalan sebelum handler loading global. Jika tidak valid, event
        // dihentikan total: form tidak terkirim dan loading tidak aktif.
        document.addEventListener('submit', function (e) {
            if (e.target !== form) return;
            if (submitting) {
                e.preventDefault();
                e.stopImmediatePropagation();
                return;
            }
            if (!allValid()) {
                e.preventDefault();
                e.stopImmediatePropagation();
                showAllErrors();
                refreshButton();
                focusFirstInvalid();
                return;
            }
            submitting = true;
            if (btn) {
                btn.setAttribute('aria-disabled', 'true');
                btn.style.opacity = '0.65';
                btn.style.cursor = 'wait';
            }
        }, true);

        refreshButton();
    })();
</script>
@endsection