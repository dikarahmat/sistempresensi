@extends('layouts.app')

@section('title', 'Kelola Akun')
@section('page_title', 'Kelola Akun')
@section('page_subtitle', 'Kelola akun yang bisa login ke sistem: tambah akun, ubah data, dan reset password.')

@section('page_header_right')
    <button type="button" class="btn btn-sm btn-primary akun-btn-tambah" id="btnTambahAkun">
        Tambah
    </button>
@endsection

@push('styles')
<style>
    .header-main-subtitle {
        white-space: normal !important;
        overflow: visible !important;
        text-overflow: unset !important;
        display: block !important;
        width: 100% !important;
    }

    /* ===== Tabel: sama dengan Data Siswa ===== */
    .table-zebra-custom tbody tr:nth-child(even) > td { background-color: #f8fafc !important; }
    .table-zebra-custom tbody tr:nth-child(odd) > td { background-color: #ffffff !important; }
    .table-zebra-custom tbody tr:hover > td { background-color: #f1f5f9 !important; }

    .table-zebra-custom tbody td {
        padding: 0.5rem 0.75rem !important;
        vertical-align: middle !important;
        color: #0f172a !important;
        border-top: none !important;
        border-bottom: 1px solid #eef1f6 !important;
        font-size: 0.85rem !important;
        line-height: 1.5 !important;
    }
    .table-zebra-custom tbody tr:last-child td { border-bottom: none !important; }

    .table-zebra-custom thead th {
        padding: 0.5rem 0.75rem !important;
        vertical-align: middle !important;
        color: #334155 !important;
        font-weight: 600 !important;
        font-size: 0.72rem !important;
        letter-spacing: 0.04em !important;
        text-transform: uppercase !important;
        border-top: none !important;
        border-bottom: 1px solid #e5e9f0 !important;
        background-color: #f8fafc !important;
    }
    @media (max-width: 1023.98px) {
        .table-zebra-custom thead th { background-color: #ffffff !important; }
    }

    /* ===== Ikon aksi (pensil & tong sampah) ===== */
    .btn-row-action {
        display: inline-flex; align-items: center; justify-content: center;
        border: none; border-radius: 10px !important;
        width: 40px; height: 34px; padding: 0;
        color: #ffffff; transition: filter .15s ease;
    }
    .btn-row-action svg { display: block; width: 20px; height: 20px; }
    .btn-row-action:hover { filter: brightness(0.94); color: #ffffff; }
    .btn-row-action.action-edit { background-color: #f59e0b; }
    .btn-row-action.action-delete { background-color: #dc2626; }
    .btn-row-action.action-delete.is-disabled { opacity: .4; cursor: not-allowed; filter: none; }
    @media (max-width: 767.98px) {
        .btn-row-action { width: 40px; height: 40px; }
        .btn-row-action svg { width: 20px; height: 20px; }
    }

    /* ===== Tombol Tambah ===== */
    .akun-btn-tambah {
        background-color: var(--primary-blue, #3b62f6);
        border: 1px solid var(--primary-blue, #3b62f6);
        color: #ffffff; font-weight: 600; font-size: .85rem;
        letter-spacing: .03em; text-transform: uppercase;
        border-radius: var(--clean-radius, 6px);
        padding: 0 1.1rem; min-height: 38px;
        display: inline-flex; align-items: center; justify-content: center;
    }
    .akun-btn-tambah:hover, .akun-btn-tambah:focus-visible {
        background-color: var(--primary-blue-hover, #2563eb);
        border-color: var(--primary-blue-hover, #2563eb); color: #ffffff;
    }
    @media (max-width: 767.98px) {
        .app-header-right { width: 100%; }
        .akun-btn-tambah { width: 100%; min-height: 44px; }
    }

    /* ===== Teks sel ===== */
    .akun-name { font-weight: 600; color: #0f172a; }
    .akun-email { font-size: .78rem; color: #334155; overflow-wrap: anywhere; }
    .akun-role { font-size: .85rem; font-weight: 600; }
    .akun-role.is-admin, .akun-role-static-value.is-admin { color: #1d4ed8; }
    .akun-role.is-guru, .akun-role-static-value.is-guru { color: #15803d; }
    .akun-role.is-piket, .akun-role-static-value.is-piket { color: #b45309; }
    .akun-status { display: inline-flex; align-items: center; gap: .4rem; font-size: .85rem; font-weight: 500; color: #0f172a; }
    .akun-status-dot { width: 8px; height: 8px; border-radius: 50%; display: inline-block; }
    .akun-status.is-aktif .akun-status-dot { background-color: #16a34a; }
    .akun-status.is-nonaktif .akun-status-dot { background-color: #dc2626; }

    /* ===== Dialog native ===== */
    dialog.acc-dialog {
        position: fixed;
        inset: 0;
        margin: auto;
        width: calc(100% - 32px);
        max-width: 440px;
        height: fit-content;
        max-height: calc(100dvh - 32px);
        overflow-y: auto;
        border: 0;
        border-radius: 16px;
        padding: 24px;
        box-shadow: 0 20px 50px rgba(0,0,0,.25);
        color: #0f172a;
    }
    dialog.acc-dialog::backdrop { background: rgba(15, 23, 42, .55); backdrop-filter: blur(2px); }
    /* dialog.acc-dialog sudah punya padding 24px, jadi isi hanya atur jarak vertikal. */
    .akun-dialog-body { padding: 20px 0 0; }
    .akun-dialog-head { padding: 0; }
    .akun-dialog-footer { padding: 18px 0 0; display: flex; gap: 12px; justify-content: flex-end; }
    .akun-role-static { padding: 12px 14px; background-color: #f8fafc; border-radius: 10px; }
    .akun-role-static-value { font-size: .95rem; font-weight: 600; }
    .akun-role-static-note { display: block; font-size: .72rem; color: #64748b; margin-top: 2px; }
    .akun-password-wrap { position: relative; }
    .akun-password-wrap .form-control { padding-right: 48px; }
    .akun-password-toggle {
        position: absolute; top: 50%; right: 2px; transform: translateY(-50%);
        width: 36px; height: 36px; border-radius: 50%;
        display: inline-flex; align-items: center; justify-content: center;
        border: 0; background: transparent; color: #64748b; cursor: pointer;
        transition: background-color .15s ease, color .15s ease;
    }
    .akun-password-toggle:hover, .akun-password-toggle:focus-visible { background-color: rgba(59,98,246,.12); color: #3b62f6; }
    .akun-password-toggle:focus-visible { outline: 2px solid #3b62f6; outline-offset: 2px; }

    /* ===== Hapus: checkbox + tombol ===== */
    .hapus-check { display: flex; gap: .5rem; align-items: flex-start; margin-bottom: .5rem; font-size: .85rem; }
    .hapus-icon { color: #dc2626; font-size: 2rem; line-height: 1; }

    /* ===== Mobile: baris tabel bertumpuk ===== */
    @media (max-width: 575.98px) {
        #daftar-akun .table-responsive table,
        #daftar-akun .table-responsive thead,
        #daftar-akun .table-responsive tbody,
        #daftar-akun .table-responsive tr,
        #daftar-akun .table-responsive td { display: block; width: 100%; }
        #daftar-akun .table-responsive thead { display: none; }
        #daftar-akun .table-responsive tr { border-bottom: 1px solid #eef1f6; padding: .5rem 0; }
        #daftar-akun .table-responsive td { border: none !important; padding: .25rem .75rem !important; text-align: left !important; }
        #daftar-akun .table-responsive td[data-label]::before {
            content: attr(data-label);
            display: inline-block; min-width: 72px;
            font-size: .72rem; font-weight: 600; color: #334155;
        }
        #daftar-akun .table-responsive td.col-aksi { text-align: right !important; }
    }

    /* Jarak aman dari bottom nav */
    #daftar-akun { margin-bottom: calc(var(--bottom-nav-height, 56px) + env(safe-area-inset-bottom, 0px) + .75rem) !important; }

    /* KHUSUS HP (<= 640px): ruang bawah sudah ditangani SATU sumber oleh
       layout (padding-bottom wrapper = tinggi nav + safe area, lalu padding
       .flex-1 1rem sebagai ruang napas). Margin besar di atas membuat celah
       kosong berlebih, jadi dinolkan. Tablet (641-1023) & desktop (>=1024)
       TIDAK disentuh — nilainya tetap seperti semula. */
    @media (max-width: 640px) {
        #daftar-akun { margin-bottom: 0 !important; }
    }
</style>
@endpush

@section('content')
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

    {{-- Notifikasi sukses HAPUS: kotak MERAH (alert-danger) + ikon tempat sampah.
         Struktur & kelas sama dengan notifikasi lain supaya transisi, posisi,
         ukuran, dan tombol X tetap identik - hanya warna & ikon yang beda. --}}
    @if(session('deleted'))
    <div class="alert alert-danger alert-deleted alert-dismissible fade show border-0 rounded-3 shadow-xs small mb-3" role="alert">
        <div class="flash-notice-body">
            <div class="d-flex align-items-center">
                <i class='bx bx-trash fs-5 me-2 text-danger'></i>
                <span>{{ session('deleted') }}</span>
            </div>
        </div>
        <button type="button" class="btn-close btn-close-sm" data-bs-dismiss="alert" aria-label="Tutup"></button>
    </div>
    @endif

    <div id="infoSemuaRole" class="alert alert-info border-0 rounded-3 small mb-3" style="display:none;" role="alert">
        <i class='bx bx-info-circle me-1'></i> Semua role sudah memiliki akun. Hapus salah satu akun terlebih dahulu.
    </div>

    <div class="card border-0 shadow-sm rounded-4 overflow-hidden" id="daftar-akun">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 table-zebra-custom">
                <thead>
                    <tr>
                        <th class="text-start">Nama</th>
                        <th class="text-start" style="width: 20%;">Role</th>
                        <th class="text-start" style="width: 18%;">Status</th>
                        <th class="text-center" style="width: 130px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($users as $akun)
                        @php
                            $akunRoleKelas = $akun->role === \App\Models\User::ROLE_ADMIN
                                ? 'is-admin'
                                : ($akun->role === \App\Models\User::ROLE_PIKET ? 'is-piket' : 'is-guru');
                            $akunAktif = $akun->isActive();
                            $akunAdmin = $akun->role === \App\Models\User::ROLE_ADMIN;
                        @endphp
                        <tr>
                            <td data-label="Nama">
                                <div class="akun-name">{{ $akun->name }}</div>
                                <div class="akun-email">{{ $akun->email }}</div>
                            </td>
                            <td data-label="Role"><span class="akun-role {{ $akunRoleKelas }}">{{ $akun->roleLabel() }}</span></td>
                            <td data-label="Status">
                                <span class="akun-status {{ $akunAktif ? 'is-aktif' : 'is-nonaktif' }}">
                                    <span class="akun-status-dot" aria-hidden="true"></span>
                                    <span>{{ $akunAktif ? 'Aktif' : 'Nonaktif' }}</span>
                                </span>
                            </td>
                            <td class="text-center col-aksi" data-label="Aksi">
                                <div class="d-flex align-items-center justify-content-center gap-2">
                                    <button type="button" class="btn-row-action action-edit btn-edit-akun"
                                            aria-label="Edit akun" title="Edit akun"
                                            data-id="{{ $akun->id }}"
                                            data-name="{{ $akun->name }}"
                                            data-email="{{ $akun->email }}"
                                            data-role="{{ $akun->role }}"
                                            data-role-label="{{ $akun->roleLabel() }}"
                                            data-update-url="{{ route('admin.accounts.update', $akun) }}"
                                            data-password-url="{{ route('admin.accounts.password', $akun) }}">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/><path d="m15 5 4 4"/></svg>
                                    </button>
                                    <button type="button" class="btn-row-action action-delete btn-hapus-akun {{ $akunAdmin ? 'is-disabled' : '' }}"
                                            aria-label="Hapus akun"
                                            title="{{ $akunAdmin ? 'Akun Admin tidak dapat dihapus' : 'Hapus akun' }}"
                                            data-id="{{ $akun->id }}"
                                            data-name="{{ $akun->name }}"
                                            data-email="{{ $akun->email }}"
                                            data-delete-url="{{ route('admin.accounts.destroy', $akun) }}"
                                            @if($akunAdmin) disabled @endif>
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/><line x1="10" x2="10" y1="11" y2="17"/><line x1="14" x2="14" y1="11" y2="17"/></svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- ================= DIALOG TAMBAH ================= --}}
    <dialog id="dialogTambah" class="acc-dialog">
        <form method="POST" action="{{ route('admin.accounts.store') }}">
            @csrf
            <input type="hidden" name="_form" value="create">
            <div class="akun-dialog-head d-flex align-items-start justify-content-between">
                <div>
                    <h5 class="fw-bold mb-0">Tambah Akun</h5>
                    <p class="text-secondary small mb-0">Buat akun login baru.</p>
                </div>
                <button type="button" class="btn-close" data-dialog-close="dialogTambah" aria-label="Tutup"></button>
            </div>
            <div class="akun-dialog-body">
                <div class="mb-3">
                    <label class="form-label small fw-semibold" for="tambahName">Nama <span class="text-danger">*</span></label>
                    <input type="text" name="name" id="tambahName" class="form-control rounded-3" maxlength="100" value="{{ old('_form') === 'create' ? old('name') : '' }}" required>
                    @error('name')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold" for="tambahEmail">Email <span class="text-danger">*</span></label>
                    <input type="email" name="email" id="tambahEmail" class="form-control rounded-3" value="{{ old('_form') === 'create' ? old('email') : '' }}" required autocomplete="off">
                    @error('email')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold" for="tambahRole">Role <span class="text-danger">*</span></label>
                    <select name="role" id="tambahRole" class="form-select rounded-3" required>
                        @foreach($roleOptions as $nilai => $label)
                        <option value="{{ $nilai }}" {{ old('role') === $nilai ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('role')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold" for="tambahPassword">Password <span class="text-danger">*</span></label>
                    <div class="akun-password-wrap">
                        <input type="password" name="password" id="tambahPassword" class="form-control rounded-3" minlength="8" autocomplete="new-password">
                        <button type="button" class="akun-password-toggle" data-toggle-pass="tambahPassword" aria-label="Tampilkan password">
                            <svg class="akun-password-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                        </button>
                    </div>
                    <div class="text-secondary small mt-1">(minimal 8 karakter)</div>
                    @error('password')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                </div>
            </div>
            <div class="akun-dialog-footer">
                <button type="button" class="btn btn-light rounded-3 px-3 fw-semibold" data-dialog-close="dialogTambah">Batal</button>
                <button type="submit" class="btn btn-primary rounded-3 px-4 fw-semibold">Simpan</button>
            </div>
        </form>
    </dialog>

    {{-- ================= DIALOG EDIT ================= --}}
    <dialog id="dialogEdit" class="acc-dialog">
        <form method="POST" id="formEdit" action="{{ route('admin.accounts.update', ['user' => old('_user_id', 0)]) }}">
            @csrf
            @method('PUT')
            <input type="hidden" name="_form" value="edit">
            <input type="hidden" name="_user_id" id="editUserId" value="{{ old('_user_id') }}">
            <div class="akun-dialog-head d-flex align-items-start justify-content-between">
                <div>
                    <h5 class="fw-bold mb-0">Edit Akun</h5>
                    <p class="text-secondary small mb-0">Ubah data akun atau reset password.</p>
                </div>
                <button type="button" class="btn-close" data-dialog-close="dialogEdit" aria-label="Tutup"></button>
            </div>
            <div class="akun-dialog-body">
                <div class="mb-3">
                    <label class="form-label small fw-semibold" for="editName">Nama <span class="text-danger">*</span></label>
                    <input type="text" name="name" id="editName" class="form-control rounded-3" maxlength="100" required>
                    @error('name')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold" for="editEmail">Email <span class="text-danger">*</span></label>
                    <input type="email" name="email" id="editEmail" class="form-control rounded-3" required autocomplete="off">
                    @error('email')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Role</label>
                    <div class="akun-role-static">
                        <span class="akun-role-static-value" id="editRoleLabel">-</span>
                        <span class="akun-role-static-note">Role tidak dapat diubah</span>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold" for="editPassword">Password</label>
                    <div class="akun-password-wrap">
                        <input type="password" name="password" id="editPassword" class="form-control rounded-3" minlength="8" autocomplete="new-password">
                        <button type="button" class="akun-password-toggle" data-toggle-pass="editPassword" aria-label="Tampilkan password">
                            <svg class="akun-password-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                        </button>
                    </div>
                    <div class="text-secondary small mt-1" id="editPasswordHint">Ubah kolom ini untuk mengganti password.</div>
                    @error('password')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                </div>
            </div>
            <div class="akun-dialog-footer">
                <button type="button" class="btn btn-light rounded-3 px-3 fw-semibold" data-dialog-close="dialogEdit">Batal</button>
                <button type="submit" class="btn btn-primary rounded-3 px-4 fw-semibold">Simpan</button>
            </div>
        </form>
    </dialog>

    {{-- ================= DIALOG HAPUS ================= --}}
    <dialog id="dialogHapus" class="acc-dialog">
        <form method="POST" id="formHapus" action="">
            @csrf
            @method('DELETE')
            <div class="akun-dialog-body">
                <div class="d-flex align-items-start gap-3 mb-3">
                    <i class='bx bx-error-circle hapus-icon'></i>
                    <div>
                        <h5 class="fw-bold mb-1">Hapus Akun Ini?</h5>
                        <p class="mb-0 small text-secondary">
                            Akun <strong id="hapusNama">-</strong> (<span id="hapusEmail">-</span>) akan dihapus permanen dan tidak dapat dipulihkan.
                        </p>
                    </div>
                </div>
                <label class="hapus-check"><input type="checkbox" class="form-check-input" data-hapus-check> <span>Saya memahami akun ini dihapus permanen dan tidak dapat dipulihkan.</span></label>
                <label class="hapus-check"><input type="checkbox" class="form-check-input" data-hapus-check> <span>Saya yakin ingin menghapus akun ini.</span></label>
            </div>
            <div class="akun-dialog-footer">
                <button type="button" class="btn btn-light rounded-3 px-3 fw-semibold" data-dialog-close="dialogHapus">Tidak</button>
                <button type="submit" class="btn btn-danger rounded-3 px-4 fw-semibold" id="hapusSubmit" disabled>Hapus Akun</button>
            </div>
        </form>
    </dialog>
@endsection

@push('scripts')
<script>
(function () {
    'use strict';
    var $ = function (id) { return document.getElementById(id); };
    var open = function (d) { if (d && typeof d.showModal === 'function') { d.showModal(); } };
    var dTambah = $('dialogTambah'), dEdit = $('dialogEdit'), dHapus = $('dialogHapus');
    var roleCount = {{ count($roleOptions) }};
    var MSG = 'Password lama belum tersimpan. Isi untuk mengganti, atau kosongkan jika tidak ingin mengganti.';

    var fillEdit = function (b) {
        var set = function (id, v) { var el = $(id); if (el) { el.value = v; } };
        set('editName', b.getAttribute('data-name') || '');
        set('editEmail', b.getAttribute('data-email') || '');
        set('editUserId', b.getAttribute('data-id') || '');
        var lbl = $('editRoleLabel');
        if (lbl) { lbl.textContent = b.getAttribute('data-role-label') || ''; lbl.className = 'akun-role-static-value is-' + b.getAttribute('data-role'); }
        var fe = $('formEdit');
        if (fe) { fe.setAttribute('action', b.getAttribute('data-update-url')); }
    };

    // Tombol TAMBAH
    var btnTambah = $('btnTambahAkun');
    if (btnTambah) {
        btnTambah.addEventListener('click', function () {
            if (roleCount < 1) { var i = $('infoSemuaRole'); if (i) { i.style.display = 'block'; } return; }
            open(dTambah);
        });
    }

    // Delegasi klik: tutup dialog, buka Edit, buka Hapus, ikon mata
    document.addEventListener('click', function (e) {
        var c = e.target.closest('[data-dialog-close]');
        if (c) { var d = $(c.getAttribute('data-dialog-close')); if (d) { d.close(); } return; }

        var eb = e.target.closest('.btn-edit-akun');
        if (eb) {
            fillEdit(eb);
            var pw = $('editPassword'), hint = $('editPasswordHint');
            if (pw) { pw.value = ''; pw.type = 'password'; }
            if (hint) { hint.textContent = 'Ubah kolom ini untuk mengganti password.'; }
            fetch(eb.getAttribute('data-password-url'), { headers: { 'Accept': 'application/json' } })
                .then(function (r) { return r.json(); })
                .then(function (d) { if (d && d.stored && d.decrypted) { if (pw) { pw.value = d.decrypted; } } else if (hint) { hint.textContent = MSG; } })
                .catch(function () { if (hint) { hint.textContent = MSG; } });
            open(dEdit); return;
        }

        var db = e.target.closest('.btn-hapus-akun');
        if (db && !db.disabled) {
            var n = $('hapusNama'), m = $('hapusEmail');
            if (n) { n.textContent = db.getAttribute('data-name') || ''; }
            if (m) { m.textContent = db.getAttribute('data-email') || ''; }
            var fh = $('formHapus');
            if (fh) { fh.setAttribute('action', db.getAttribute('data-delete-url')); }
            var cs = document.querySelectorAll('[data-hapus-check]');
            Array.prototype.forEach.call(cs, function (x) { x.checked = false; });
            var hs = $('hapusSubmit');
            if (hs) { hs.disabled = true; }
            open(dHapus); return;
        }

        var t = e.target.closest('[data-toggle-pass]');
        if (t) {
            var inp = $(t.getAttribute('data-toggle-pass'));
            if (!inp) { return; }
            var show = inp.type === 'password';
            inp.type = show ? 'text' : 'password';
            t.setAttribute('aria-label', show ? 'Sembunyikan password' : 'Tampilkan password');
        }
    });

    // Checkbox hapus -> aktifkan tombol Hapus
    var allChecks = document.querySelectorAll('[data-hapus-check]');
    Array.prototype.forEach.call(allChecks, function (ch) {
        ch.addEventListener('change', function () {
            var ok = true;
            Array.prototype.forEach.call(allChecks, function (x) { if (!x.checked) { ok = false; } });
            var hs = $('hapusSubmit');
            if (hs) { hs.disabled = !ok; }
        });
    });

    // Buka ulang dialog bila validasi gagal
    var reopen = @json(old('_form'));
    if (reopen === 'create') { open(dTambah); }
    if (reopen === 'edit') {
        var uid = @json(old('_user_id'));
        var btn = uid ? document.querySelector('.btn-edit-akun[data-id="' + uid + '"]') : null;
        if (btn) { fillEdit(btn); }
        var fe = $('formEdit');
        if (fe && uid) { fe.setAttribute('action', @json(url('/admin/kelola-akun')) + '/' + uid + '/update'); }
        var nn = $('editName'), ee = $('editEmail');
        if (nn) { nn.value = @json(old('name')); }
        if (ee) { ee.value = @json(old('email')); }
        open(dEdit);
    }
})();
</script>
@endpush