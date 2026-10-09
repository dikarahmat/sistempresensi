@extends('layouts.app')

@section('title', 'Tambah Siswa')
@section('page_title', 'Tambah Siswa Baru')
@section('page_subtitle', 'Formulir pendaftaran dan registrasi siswa baru')

@section('page_header_right')
<a href="{{ panel_route('students.index') }}" class="btn btn-light border rounded-3 px-3 py-1.5 d-inline-flex align-items-center gap-1.5 shadow-2xs text-secondary fw-semibold" style="font-size: 0.85rem;">
    <i class='bx bx-chevron-left'></i> Kembali ke Data Siswa
</a>
@endsection

@push('styles')
<style>
    .card-modern { background: #fff; border-radius: 16px; border: 1px solid #e2e8f0; max-width: 680px; margin: 0 auto; box-shadow: 0 4px 14px rgba(0,0,0,0.03); }
</style>
@endpush

@section('content')
<div class="card card-modern p-4">

    @if($errors->any())
    <div class="alert alert-danger py-2 px-3 small mb-4 border-0 rounded-3" role="alert">
        <i class='bx bx-error-circle me-1'></i> Data belum bisa disimpan. Periksa isian yang bertanda merah.
    </div>
    @endif

    <form action="{{ panel_route('students.store') }}" method="POST">
        @csrf
        <div class="mb-3">
            <label class="form-label small fw-semibold">Nama Lengkap Siswa</label>
            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" required value="{{ old('name') }}" placeholder="Contoh: Muhammad Bintang">
            @error('name')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
        </div>

        <div class="row g-3 mb-3">
            <div class="col-6">
                <label class="form-label small fw-semibold">NISN</label>
                <input type="text" name="nisn" class="form-control @error('nisn') is-invalid @enderror" required value="{{ old('nisn') }}" placeholder="Contoh: 0081234567" inputmode="numeric" pattern="[0-9]*" maxlength="10" autocomplete="off" oninput="this.value=this.value.replace(/[^0-9]/g,'')">
                @error('nisn')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
            </div>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-6">
                <label class="form-label small fw-semibold">Kelas</label>
                <select name="school_class_id" class="form-select @error('school_class_id') is-invalid @enderror" required>
                    <option value="">Pilih Kelas</option>
                    @foreach($classes as $c)
                    <option value="{{ $c->id }}" {{ old('school_class_id') == $c->id ? 'selected' : '' }}>Kelas {{ $c->name }}</option>
                    @endforeach
                </select>
                @error('school_class_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
            </div>
            <div class="col-6">
                <label class="form-label small fw-semibold">Jenis Kelamin</label>
                <select name="gender" class="form-select @error('gender') is-invalid @enderror" required>
                    <option value="Laki-laki" {{ old('gender') == 'Laki-laki' || old('gender') == 'L' ? 'selected' : '' }}>Laki-Laki</option>
                    <option value="Perempuan" {{ old('gender') == 'Perempuan' || old('gender') == 'P' ? 'selected' : '' }}>Perempuan</option>
                </select>
                @error('gender')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
            </div>
        </div>

        <div class="d-flex justify-content-end gap-2">
            <a href="{{ panel_route('students.index') }}" class="btn btn-light border px-4 fw-semibold">Batal</a>
            <button type="submit" class="btn btn-primary px-4 fw-semibold">Simpan Siswa</button>
        </div>
    </form>
</div>
@endsection