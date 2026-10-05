@extends('layouts.app')

@section('title', 'Edit Siswa')
@section('page_title', 'Edit Data Siswa: ' . $student->name)
@section('page_subtitle', 'Perbarui biodata siswa dan data rombongan belajar')

@section('page_header_right')
<div class="d-flex gap-2">
    <a href="{{ panel_route('students.index') }}" class="btn btn-primary rounded-3 px-4 py-1.5 d-inline-flex align-items-center gap-1.5 shadow-2xs text-white fw-semibold" style="font-size: 0.85rem;">
        Kembali
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
        <button type="button" class="btn-close btn-close-sm" data-bs-dismiss="alert" aria-label="Tutup"></button>
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
        <button type="button" class="btn-close btn-close-sm" data-bs-dismiss="alert" aria-label="Tutup"></button>
    </div>
    @endif

    @if($errors->any())
    <div class="alert alert-danger py-2 px-3 small mb-4 border-0 rounded-3" role="alert">
        <i class='bx bx-error-circle me-1'></i> Data belum bisa disimpan. Periksa isian yang bertanda merah.
    </div>
    @endif

    <div class="card-modern">
        <form action="{{ panel_route('students.update', $student->id) }}" method="POST">
            @csrf
            @method('PUT')

            <!-- Kelas -->
            <div class="mb-3">
                <label class="form-label">Kelas</label>
                <select name="school_class_id" class="form-select @error('school_class_id') is-invalid @enderror" required>
                    @foreach($classes as $c)
                    <option value="{{ $c->id }}" {{ old('school_class_id', $student->school_class_id) == $c->id ? 'selected' : '' }}>
                        {{ $c->name }}
                    </option>
                    @endforeach
                </select>
                @error('school_class_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
            </div>

            <!-- Nama & NIS -->
            <div class="row g-3 mb-3">
                <div class="col-12 col-md-6">
                    <label class="form-label">Nama Siswa</label>
                    <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $student->name) }}" required>
                    @error('name')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label">NIS</label>
                    <input type="text" name="nis" class="form-control @error('nis') is-invalid @enderror" value="{{ old('nis', $student->nis) }}" required inputmode="numeric" pattern="[0-9]*" maxlength="30" autocomplete="off" oninput="this.value=this.value.replace(/[^0-9]/g,'')">
                    @error('nis')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>
            </div>

            <!-- NISN & No. HP Siswa -->
            <div class="row g-3 mb-3">
                <div class="col-12 col-md-6">
                    <label class="form-label">NISN (Opsional)</label>
                    <input type="text" name="nisn" class="form-control @error('nisn') is-invalid @enderror" value="{{ old('nisn', $student->nisn) }}" placeholder="10 digit angka" maxlength="10" inputmode="numeric" pattern="[0-9]*" autocomplete="off" oninput="this.value=this.value.replace(/[^0-9]/g,'')">
                    @error('nisn')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label">Nomor WhatsApp Siswa (Opsional)</label>
                    <input type="text" name="phone" class="form-control @error('phone') is-invalid @enderror" value="{{ old('phone', $student->phone) }}" placeholder="10-15 digit angka" maxlength="15" inputmode="numeric" pattern="[0-9]*" autocomplete="off" oninput="this.value=this.value.replace(/[^0-9]/g,'')">
                    @error('phone')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>
            </div>

            <!-- Tempat & Tanggal Lahir -->
            <div class="row g-3 mb-3">
                <div class="col-12 col-md-6">
                    <label class="form-label">Tempat Lahir</label>
                    <input type="text" name="birth_place" class="form-control" value="{{ old('birth_place', $student->birth_place) }}" placeholder="Contoh: Semarang">
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label">Tanggal Lahir</label>
                    <input type="date" name="birth_date" class="form-control" value="{{ old('birth_date', $student->birth_date ? $student->birth_date->format('Y-m-d') : '') }}">
                </div>
            </div>

            <!-- Jenis Kelamin & Alamat -->
            <div class="row g-3 mb-3">
                <div class="col-12 col-md-6">
                    <label class="form-label">Jenis Kelamin</label>
                    <select name="gender" class="form-select @error('gender') is-invalid @enderror" required>
                        <option value="Laki-laki" {{ old('gender', $student->gender) == 'Laki-laki' || old('gender', $student->gender) == 'L' ? 'selected' : '' }}>Laki-laki</option>
                        <option value="Perempuan" {{ old('gender', $student->gender) == 'Perempuan' || old('gender', $student->gender) == 'P' ? 'selected' : '' }}>Perempuan</option>
                    </select>
                    @error('gender')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label">Alamat</label>
                    <input type="text" name="address" class="form-control" value="{{ old('address', $student->address) }}" placeholder="Contoh: Jl. Merdeka No. 83">
                </div>
            </div>

            <!-- Nama Orang Tua & No HP Orang Tua -->
            <div class="row g-3 mb-3">
                <div class="col-12 col-md-6">
                    <label class="form-label">Nama Orang Tua</label>
                    <input type="text" name="parent_name" class="form-control @error('parent_name') is-invalid @enderror" value="{{ old('parent_name', $student->parent_name) }}" placeholder="Contoh: Bambang Susilo">
                    @error('parent_name')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label">Nomor WhatsApp Orang Tua (Opsional)</label>
                    <input type="text" name="parent_phone" class="form-control @error('parent_phone') is-invalid @enderror" value="{{ old('parent_phone', $student->parent_phone) }}" placeholder="10-15 digit angka" maxlength="15" inputmode="numeric" pattern="[0-9]*" autocomplete="off" oninput="this.value=this.value.replace(/[^0-9]/g,'')">
                    @error('parent_phone')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>
            </div>

            <!-- Tombol Simpan & Batal -->
            <div class="d-flex align-items-center gap-2">
                <button type="submit" class="btn-submit-blue">Simpan Perubahan</button>
                <a href="{{ panel_route('students.show', $student->id) }}" class="btn-cancel-gray">Batal</a>
            </div>
        </form>
    </div>
@endsection