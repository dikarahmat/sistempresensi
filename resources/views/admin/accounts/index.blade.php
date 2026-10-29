@extends('layouts.app')

@section('title', __('Kelola Akun'))
@section('page_title', __('Kelola Akun'))
@section('page_subtitle', __('Kelola akun yang bisa login ke sistem: tambah akun, ubah data, dan reset password.'))

@section('page_header_right')
    <button type="button" class="btn btn-sm btn-primary akun-btn-tambah" id="btnTambahAkun">
        {{ __('Tambah') }}
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

    /* Tabel Akun memakai seluruh lebar kartu putih. min-width 620px (mobile)
       tetap dipakai => tabel tetap bisa di-scroll di layar kecil tanpa
       overflow. */

    .table-zebra-custom tbody td {
        /* Baris tabel Akun hanya berisi satu baris teks (username), jadi
           padding vertikalnya ditambah supaya barisnya tetap rapi. */
        padding: 0.85rem 0.75rem !important;
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

    /* ===== Ikon aksi (pensil & tong sampah) – PURE ICON, tanpa background box ===== */
    .btn-row-action {
        display: inline-flex; align-items: center; justify-content: center;
        border: none; border-radius: 0 !important;
        width: auto; height: auto; padding: 0.2rem;
        background: transparent !important;
        color: inherit; transition: color .15s ease, opacity .15s ease;
    }
    .btn-row-action svg { display: block; width: 20px; height: 20px; }
    .btn-row-action.action-edit { color: #f59e0b; } /* pensil - kuning/oranye */
    .btn-row-action.action-edit:hover { color: #d97706; }
    .btn-row-action.action-delete { color: #dc2626; } /* tong sampah – merah */
    .btn-row-action.action-delete:hover { color: #b91c1c; }
    .btn-row-action.action-delete.is-disabled,
    .btn-row-action.action-edit.is-disabled { opacity: .4; cursor: not-allowed; filter: none; }

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
    /* Username ditampilkan PERSIS seperti tersimpan di database (huruf kecil),
       jadi uppercase bawaan sel tabel zebra dibatalkan khusus untuk teks ini.
       Label role tetap uppercase (bawaan layout). */
    .akun-name { font-weight: 400; color: #0f172a; text-transform: none !important; }
    .akun-role { font-size: .85rem; font-weight: 400; }
    .akun-role.is-admin, .akun-role-static-value.is-admin { color: #1d4ed8; }
    .akun-role.is-guru, .akun-role-static-value.is-guru { color: #15803d; }
    .akun-role.is-piket, .akun-role-static-value.is-piket { color: #b45309; }
    .akun-status { display: inline-flex; align-items: center; gap: .4rem; font-size: .85rem; font-weight: 400; color: #0f172a; }
    .akun-status-dot { width: 8px; height: 8px; border-radius: 50%; display: inline-block; }
    .akun-status.is-aktif .akun-status-dot { background-color: #16a34a; }
    .akun-status.is-nonaktif .akun-status-dot { background-color: #dc2626; }

    /* ===== Kolom AKSI =====
       Header & isi sama-sama diberi jarak dari tepi kanan tabel (40px) dan
       isinya rata tengah. Karena header dan isi memakai kotak konten yang
       sama, teks "AKSI" tepat sejajar di tengah dengan pasangan ikon
       edit + hapus di bawahnya. */
    #daftar-akun th.akun-col-aksi,
    #daftar-akun td.akun-col-aksi {
        padding-right: 40px !important;
    }

    /* ===== Dialog native ===== */
    dialog.acc-dialog {
        position: fixed;
        inset: 0;
        margin: auto;
        width: calc(100% - 32px);
        max-width: 420px;
        height: fit-content;
        max-height: calc(100dvh - 32px);
        overflow-y: auto;
        border: 0;
        border-radius: 18px;
        padding: 16px;
        box-shadow: 0 20px 50px rgba(0,0,0,.25);
        color: #0f172a;
    }
    dialog.acc-dialog::backdrop { background: rgba(15, 23, 42, .55); backdrop-filter: blur(2px); }
    /* dialog.acc-dialog sudah punya padding 24px, jadi isi hanya atur jarak vertikal. */
    .akun-dialog-body { padding: 12px 0 0; }
    .akun-dialog-head { padding: 0; }
    .akun-dialog-footer { padding: 12px 0 0; display: flex; gap: 10px; justify-content: center; }
    .akun-dialog-footer .btn { flex: 1 1 50%; width: 50%; height: 42px; min-height: 42px; }
    .akun-role-static { padding: 12px 14px; background-color: #f8fafc; border-radius: 10px; }
    .akun-role-static-value { font-size: .95rem; font-weight: 600; }
    .akun-role-static-note { display: block; font-size: .72rem; color: #64748b; margin-top: 2px; }
    .akun-password-wrap { position: relative; }
    .akun-password-wrap .form-control { padding-right: 48px; }

    /* ===== Modal Edit: teks diperbesar supaya nyaman dibaca =====
       Semua aturan di sini memakai #dialogEdit sebagai scope, jadi Modal
       Tambah (dialogTambah) tidak ikut berubah sama sekali. */
    #dialogEdit .form-label { font-size: 14px !important; }
    #dialogEdit .form-control {
        font-size: 16px !important;
        /* padding vertikal saja: padding kanan input password (48px) tetap
           dipakai untuk memberi ruang ikon mata. */
        padding-top: .55rem !important;
        padding-bottom: .55rem !important;
    }
    #dialogEdit .text-secondary.small { font-size: 13px !important; }
    #dialogEdit .akun-role-static-value { font-size: 15px !important; }
    #dialogEdit .akun-role-static-note { font-size: 13px !important; }
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
    .hapus-check { display: flex; gap: .5rem; align-items: flex-start; margin-bottom: .35rem; font-size: .8rem; }
    .hapus-icon { color: #dc2626; font-size: 1.5rem; line-height: 1; margin-bottom: 0.5rem; display: block; }

    /* ===== Mobile: tabel tetap mendatar dengan scroll horizontal ===== */
    @media (max-width: 575.98px) {
        #daftar-akun .table-responsive {
            overflow-x: auto !important;
            -webkit-overflow-scrolling: touch;
        }
        /* Pastikan tabel lebar cukup agar ada yang bisa di-geser di layar sempit */
        #daftar-akun .table-responsive table {
            min-width: 620px;
        }
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
        <button type="button" class="btn-close btn-close-sm" data-bs-dismiss="alert" aria-label="{{ __('Tutup') }}"></button>
    </div>
    @endif

    <div id="infoSemuaRole" class="alert alert-info border-0 rounded-3 small mb-3" style="display:none;" role="alert">
        <i class='bx bx-info-circle me-1'></i> {{ __('Semua role sudah memiliki akun. Hapus salah satu akun terlebih dahulu.') }}
    </div>

    <div class="card border-0 shadow-sm rounded-4 overflow-hidden" id="daftar-akun">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 table-zebra-custom">
                <thead>
                    <tr>
                        <th class="text-start" style="width: 30%;">{{ __('Username') }}</th>
                        <th class="text-start" style="width: 25%;">{{ __('Role') }}</th>
                        <th class="text-start" style="width: 25%;">{{ __('Status') }}</th>
                        <th class="text-center akun-col-aksi" style="width: 20%;">{{ __('Aksi') }}</th>
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
                            <td data-label="{{ __('Username') }}">
                                <div class="akun-name">{{ $akun->username }}</div>
                            </td>
                            <td data-label="{{ __('Role') }}" class="text-start"><span class="akun-role {{ $akunRoleKelas }}">{{ $akun->roleLabel() }}</span></td>
                            <td data-label="{{ __('Status') }}" class="text-start">
                                <span class="akun-status {{ $akunAktif ? 'is-aktif' : 'is-nonaktif' }}">
                                    <span class="akun-status-dot" aria-hidden="true"></span>
                                    <span>{{ $akunAktif ? __('Aktif') : __('Nonaktif') }}</span>
                                </span>
                            </td>
                            <td class="text-center col-aksi akun-col-aksi" data-label="{{ __('Aksi') }}">
                                <div class="d-flex align-items-center justify-content-center gap-1">
                                    <button type="button" class="btn-row-action action-edit btn-edit-akun"
                                            aria-label="{{ __('Edit akun') }}" title="{{ __('Edit akun') }}"
                                            data-id="{{ $akun->id }}"
                                            data-username="{{ $akun->username }}"
                                            data-role="{{ $akun->role }}"
                                            data-role-label="{{ $akun->roleLabel() }}"
                                            data-update-url="{{ route('admin.accounts.update', $akun) }}"
                                            data-password-url="{{ route('admin.accounts.password', $akun) }}">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/><path d="m15 5 4 4"/></svg>
                                    </button>
                                    <button type="button" class="btn-row-action action-delete btn-hapus-akun {{ $akunAdmin ? 'is-disabled' : '' }}"
                                            aria-label="{{ __('Hapus akun') }}"
                                            title="{{ $akunAdmin ? __('Akun Admin tidak dapat dihapus') : __('Hapus akun') }}"
                                            data-id="{{ $akun->id }}"
                                            data-username="{{ $akun->username }}"
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
        <div class="mb-3">
        <span class="badge bg-secondary" style="font-size: 0.75rem; padding: 0.5rem 0.75rem;">Info</span>
        <span class="text-muted small">Setiap role hanya memiliki satu akun.</span>
    </div>
    <form method="POST" action="{{ route('admin.accounts.store') }}">
            @csrf
            <input type="hidden" name="_form" value="create">
            <div class="akun-dialog-head d-flex align-items-start justify-content-between">
                <div>
                    <h5 class="fw-bold mb-0">{{ __('Tambah Akun') }}</h5>
                    <p class="text-secondary small mb-0">{{ __('Buat akun login baru.') }}</p>
                </div>
                <button type="button" class="btn-close" data-dialog-close="dialogTambah" aria-label="{{ __('Tutup') }}"></button>
            </div>
            <div class="akun-dialog-body">
                <div class="mb-3">
                    <label class="form-label small fw-semibold" for="tambahName">{{ __('Nama') }} <span class="text-danger">*</span></label>
                    <input type="text" name="name" id="tambahName" class="form-control rounded-3" maxlength="100" value="{{ old('_form') === 'create' ? old('name') : '' }}" required>
                    @error('name')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold" for="tambahUsername">{{ __('Username') }} <span class="text-danger">*</span></label>
                    <input type="text" name="username" id="tambahUsername" class="form-control rounded-3" maxlength="255" value="{{ old('_form') === 'create' ? old('username') : '' }}" required autocomplete="off" autocapitalize="none" spellcheck="false">
                    @error('username')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold" for="tambahRole">{{ __('Role') }} <span class="text-danger">*</span></label>
                    <select name="role" id="tambahRole" class="form-select rounded-3" required>
                        @foreach($roleOptions as $nilai => $label)
                        <option value="{{ $nilai }}" {{ old('role') === $nilai ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('role')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold" for="tambahPassword">{{ __('Password') }} <span class="text-danger">*</span></label>
                    <div class="akun-password-wrap">
                        <input type="password" name="password" id="tambahPassword" class="form-control rounded-3" minlength="8" autocomplete="new-password">
                        <button type="button" class="akun-password-toggle" data-toggle-pass="tambahPassword" aria-label="{{ __('Tampilkan password') }}">
                            <svg class="akun-password-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                        </button>
                    </div>
                    <div class="text-secondary small mt-1">{{ __('(minimal 8 karakter)') }}</div>
                    @error('password')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                </div>
            </div>
            <div class="akun-dialog-footer">
                <button type="button" class="btn btn-light rounded-3 px-3 fw-semibold" data-dialog-close="dialogTambah">{{ __('Batal') }}</button>
                @if(count($roleOptions)>0)
                    <button type="submit" class="btn btn-primary rounded-3 px-4 fw-semibold">{{ __('Simpan') }}</button>
                @else
                    <button type="submit" class="btn btn-primary rounded-3 px-4 fw-semibold" disabled>
                        <i class="bi bi-check-circle"></i>
                        Semua role sudah terisi
                    </button>
                @endif
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
                    <h5 class="fw-bold mb-0">{{ __('Edit Akun') }}</h5>
                    <p class="text-secondary small mb-0">{{ __('Ubah data akun atau reset password.') }}</p>
                </div>
                <button type="button" class="btn-close" data-dialog-close="dialogEdit" aria-label="{{ __('Tutup') }}"></button>
            </div>
            <div class="akun-dialog-body">
                <div class="mb-3">
                    <label class="form-label small fw-semibold" for="editUsername">{{ __('Username') }} <span class="text-danger">*</span></label>
                    <input type="text" name="username" id="editUsername" class="form-control rounded-3" maxlength="255" required autocomplete="off" autocapitalize="none" spellcheck="false">
                    @error('username')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold" for="editPassword">{{ __('Password') }}</label>
                    <div class="akun-password-wrap">
                        <input type="password" name="password" id="editPassword" class="form-control rounded-3" minlength="8" autocomplete="new-password">
                        <button type="button" class="akun-password-toggle" data-toggle-pass="editPassword" aria-label="{{ __('Tampilkan password') }}">
                            <svg class="akun-password-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                        </button>
                    </div>
                    <div class="text-secondary small mt-1" id="editPasswordHint">{{ __('Ubah kolom ini untuk mengganti password.') }}</div>
                    @error('password')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">{{ __('Role') }}</label>
                    <div class="akun-role-static">
                        <span class="akun-role-static-value" id="editRoleLabel">-</span>
                        <span class="akun-role-static-note">{{ __('Role tidak dapat diubah') }}</span>
                    </div>
                </div>
            </div>
            <div class="akun-dialog-footer">
                <button type="button" class="btn btn-light rounded-3 px-3 fw-semibold" data-dialog-close="dialogEdit">{{ __('Batal') }}</button>
                <button type="submit" class="btn btn-primary rounded-3 px-4 fw-semibold">{{ __('Simpan') }}</button>
            </div>
        </form>
    </dialog>

    {{-- ================= DIALOG HAPUS ================= --}}
    <dialog id="dialogHapus" class="acc-dialog">
        <form method="POST" id="formHapus" action="">
            @csrf
            @method('DELETE')
            <div class="akun-dialog-body">
                <!-- Header: ikon di atas tengah, judul dan deskripsi rata tengah -->
                <div class="text-center mb-2">
                    <i class='bx bx-error-circle hapus-icon'></i>
                    <h5 class="fw-bold mb-1">{{ __('Hapus Akun Ini?') }}</h5>
                    <p class="mb-0 small text-secondary">
                        {{ __('Akun') }} <strong id="hapusUsername">-</strong> {{ __('akan dihapus permanen dan tidak dapat dipulihkan.') }}
                    </p>
                </div>
                <label class="hapus-check"><input type="checkbox" class="form-check-input" data-hapus-check> <span>{{ __('Saya memahami akun ini dihapus permanen dan tidak dapat dipulihkan.') }}</span></label>
                <label class="hapus-check"><input type="checkbox" class="form-check-input" data-hapus-check> <span>{{ __('Saya yakin ingin menghapus akun ini.') }}</span></label>
            </div>
            <div class="akun-dialog-footer">
                <button type="button" class="btn btn-light rounded-3 px-3 fw-semibold" data-dialog-close="dialogHapus">{{ __('Tidak') }}</button>
                <button type="submit" class="btn btn-danger rounded-3 px-4 fw-semibold" id="hapusSubmit" disabled>{{ __('Hapus Akun') }}</button>
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
    var MSG = {!! json_encode(__('Password lama belum tersimpan. Isi untuk mengganti, atau kosongkan jika tidak ingin mengganti.')) !!};

    var fillEdit = function (b) {
        var set = function (id, v) { var el = $(id); if (el) { el.value = v; } };
        set('editUsername', b.getAttribute('data-username') || '');
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
            if (hint) { hint.textContent = {!! json_encode(__('Ubah kolom ini untuk mengganti password.')) !!}; }
            fetch(eb.getAttribute('data-password-url'), { headers: { 'Accept': 'application/json' } })
                .then(function (r) { return r.json(); })
                .then(function (d) { if (d && d.stored && d.decrypted) { if (pw) { pw.value = d.decrypted; } } else if (hint) { hint.textContent = MSG; } })
                .catch(function () { if (hint) { hint.textContent = MSG; } });
            open(dEdit); return;
        }

        var db = e.target.closest('.btn-hapus-akun');
        if (db && !db.disabled) {
            var m = $('hapusUsername');
            if (m) { m.textContent = db.getAttribute('data-username') || ''; }
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
            t.setAttribute('aria-label', show ? {!! json_encode(__('Sembunyikan password')) !!} : {!! json_encode(__('Tampilkan password')) !!});
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
        var ee = $('editUsername');
        if (ee) { ee.value = @json(old('username')); }
        open(dEdit);
    }
})();
</script>
@endpush