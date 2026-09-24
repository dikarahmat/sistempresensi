@extends('layouts.app')

@section('title', 'Tambah Siswa')
@section('page_title', 'Tambah Siswa Baru')
@section('page_subtitle', 'Formulir pendaftaran dan registrasi siswa baru')

@section('page_header_right')
<a href="{{ route('admin.students.index') }}" class="btn btn-light border rounded-3 px-3 py-1.5 d-inline-flex align-items-center gap-1.5 shadow-2xs text-secondary fw-semibold" style="font-size: 0.85rem;">
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
    <div class="alert alert-danger py-2 small">
        <ul class="mb-0">
            @foreach($errors->all() as $err)
            <li>{{ $err }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <form action="{{ route('admin.students.store') }}" method="POST">
        @csrf
        <div class="mb-3">
            <label class="form-label small fw-semibold">Nama Lengkap Siswa</label>
            <input type="text" name="name" class="form-control" required value="{{ old('name') }}" placeholder="Contoh: Muhammad Bintang">
        </div>

        <div class="row g-3 mb-3">
            <div class="col-6">
                <label class="form-label small fw-semibold">NIS</label>
                <input type="text" name="nis" class="form-control" required value="{{ old('nis') }}" placeholder="Contoh: 260001">
            </div>
            <div class="col-6">
                <label class="form-label small fw-semibold">NISN (Opsional)</label>
                <input type="text" name="nisn" class="form-control" value="{{ old('nisn') }}" placeholder="Contoh: 0081234567">
            </div>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-6">
                <label class="form-label small fw-semibold">Kelas</label>
                <select name="school_class_id" class="form-select" required>
                    <option value="">Pilih Kelas</option>
                    @foreach($classes as $c)
                    <option value="{{ $c->id }}" {{ old('school_class_id') == $c->id ? 'selected' : '' }}>Kelas {{ $c->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6">
                <label class="form-label small fw-semibold">Jenis Kelamin</label>
                <select name="gender" class="form-select" required>
                    <option value="L" {{ old('gender') == 'L' ? 'selected' : '' }}>Laki-Laki (L)</option>
                    <option value="P" {{ old('gender') == 'P' ? 'selected' : '' }}>Perempuan (P)</option>
                </select>
            </div>
        </div>

        <div class="mb-4">
            <label class="form-label small fw-semibold">Nomor WhatsApp Orang Tua / Siswa (Opsional)</label>
            <input type="text" name="phone_number" class="form-control" value="{{ old('phone_number') }}" placeholder="08xxxxxxxxxx">
        </div>

        <div class="d-flex justify-content-end gap-2">
            <a href="{{ route('admin.students.index') }}" class="btn btn-light border px-4 fw-semibold">Batal</a>
            <button type="submit" class="btn btn-primary px-4 fw-semibold">Simpan Siswa</button>
        </div>
    </form>
</div>
@endsection