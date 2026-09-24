@extends('layouts.app')

@section('title', 'Edit Siswa')
@section('page_title', 'Edit Data Siswa: ' . $student->name)
@section('page_subtitle', 'Perbarui biodata siswa dan data rombongan belajar')

@section('page_header_right')
<div class="d-flex gap-2">
    <a href="{{ route('admin.students.show', $student->id) }}" class="btn btn-light border rounded-3 px-3 py-1.5 d-inline-flex align-items-center gap-1.5 shadow-2xs text-secondary fw-semibold" style="font-size: 0.85rem;">
        <i class='bx bx-show'></i> Detail
    </a>
    <a href="{{ route('admin.students.index') }}" class="btn btn-light border rounded-3 px-3 py-1.5 d-inline-flex align-items-center gap-1.5 shadow-2xs text-secondary fw-semibold" style="font-size: 0.85rem;">
        <i class='bx bx-chevron-left'></i> Kembali
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
        border-radius: 8px;
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
        border-radius: 8px;
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
        border-radius: 8px;
        padding: 0.6rem 1.5rem;
        text-decoration: none;
    }
    .btn-cancel-gray:hover { background-color: #f1f5f9; color: #0f172a; }
</style>
@endpush

@section('content')
    @if($errors->any())
    <div class="alert alert-danger py-2 small mb-4">
        <ul class="mb-0">
            @foreach($errors->all() as $err)
            <li>{{ $err }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <div class="card-modern">
        <form action="{{ route('admin.students.update', $student->id) }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <!-- Kelas -->
            <div class="mb-3">
                <label class="form-label">Kelas</label>
                <select name="school_class_id" class="form-select" required>
                    @foreach($classes as $c)
                    <option value="{{ $c->id }}" {{ old('school_class_id', $student->school_class_id) == $c->id ? 'selected' : '' }}>
                        {{ $c->name }}
                    </option>
                    @endforeach
                </select>
            </div>

            <!-- Nama & NIS -->
            <div class="row g-3 mb-3">
                <div class="col-12 col-md-6">
                    <label class="form-label">Nama Siswa</label>
                    <input type="text" name="name" class="form-control" value="{{ old('name', $student->name) }}" required>
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label">NIS</label>
                    <input type="text" name="nis" class="form-control" value="{{ old('nis', $student->nis) }}" required>
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
                    <input type="date" name="birth_date" class="form-control" value="{{ old('birth_date', $student->birth_date) }}">
                </div>
            </div>

            <!-- Jenis Kelamin & Alamat -->
            <div class="row g-3 mb-3">
                <div class="col-12 col-md-6">
                    <label class="form-label">Jenis Kelamin</label>
                    <select name="gender" class="form-select" required>
                        <option value="Laki-laki" {{ old('gender', $student->gender) == 'Laki-laki' || old('gender', $student->gender) == 'L' ? 'selected' : '' }}>Laki-laki</option>
                        <option value="Perempuan" {{ old('gender', $student->gender) == 'Perempuan' || old('gender', $student->gender) == 'P' ? 'selected' : '' }}>Perempuan</option>
                    </select>
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
                    <input type="text" name="parent_name" class="form-control" value="{{ old('parent_name', $student->parent_name) }}" placeholder="Contoh: Bambang Susilo">
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label">Nomor HP Orang Tua</label>
                    <input type="text" name="parent_phone" class="form-control" value="{{ old('parent_phone', $student->parent_phone) }}" placeholder="Contoh: 08985444487">
                </div>
            </div>

            <!-- Foto Siswa -->
            <div class="mb-4">
                <label class="form-label">Perbarui Foto Siswa (opsional)</label>
                <input type="file" name="photo" class="form-control" accept="image/*">
            </div>

            <!-- Tombol Simpan & Batal -->
            <div class="d-flex align-items-center gap-2">
                <button type="submit" class="btn-submit-blue">Simpan Perubahan</button>
                <a href="{{ route('admin.students.show', $student->id) }}" class="btn-cancel-gray">Batal</a>
            </div>
        </form>
    </div>
@endsection