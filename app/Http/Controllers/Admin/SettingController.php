<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class SettingController extends Controller
{
    public function index(): View
    {
        $rawPhone = Setting::get('school_phone', '');
        $rawNip = Setting::get('headmaster_nip', '');

        // Normalisasi: simpan hanya digit untuk validasi & penyimpanan.
        // Tampilkan nilai normalisasi supaya user bisa mengoreksi format lama.
        $normalizedPhone = preg_replace('/[^0-9]/', '', (string) $rawPhone);
        $normalizedNip = preg_replace('/[^0-9]/', '', (string) $rawNip);

        $settings = [
            'school_name' => Setting::get('school_name', 'SMP Presensi PGRI'),
            'app_title' => Setting::get('app_title', 'Sistem Presensi Sekolah'),
            'school_logo' => Setting::get('school_logo'),
            'school_address' => Setting::get('school_address', 'Jl. Pendidikan No. 45, Kota Pelajar'),
            // Tampilkan versi bersih (hanya digit) supaya nilai lama seperti
            // '(021) 555-1234' jadi '0215551234' dan bisa diedit/disimpan.
            'school_phone' => $normalizedPhone,
            'headmaster_name' => Setting::get('headmaster_name', 'Drs. H. Ahmad Sudrajat, M.Pd'),
            'headmaster_nip' => $normalizedNip,
            'check_in_time' => Setting::get('check_in_time', '06:45'),
            'late_limit_time' => Setting::get('late_limit_time', '07:15'),
        ];

        return view('admin.settings.index', compact('settings'));
    }

    public function update(Request $request): RedirectResponse
    {
        // Kolom opsional yang dikosongkan dinormalkan ke null SEBELUM validasi:
        // string kosong '' selalu gagal regex digit, padahal field-nya opsional.
        foreach (['school_phone', 'headmaster_nip'] as $optionalDigit) {
            if (trim((string) $request->input($optionalDigit, '')) === '') {
                $request->merge([$optionalDigit => null]);
            }
        }

        $request->validate([
            'school_name' => ['required', 'string', 'max:255'],
            'app_title' => ['nullable', 'string', 'max:255'],
            'school_logo' => ['nullable', 'file', 'mimes:webp,png,jpg,jpeg', 'max:2048'],
            'school_address' => ['nullable', 'string', 'max:255'],
            'school_phone' => ['nullable', 'string', 'regex:/^[0-9]{10,15}$/'],
            'headmaster_name' => ['nullable', 'string', 'max:100'],
            'headmaster_nip' => ['nullable', 'string', 'regex:/^[0-9]{18}$/'],
            'check_in_time' => ['required', 'string', 'date_format:H:i'],
            'late_limit_time' => ['required', 'string', 'date_format:H:i', 'after:check_in_time'],
            'school_npsn' => ['nullable', 'string', 'regex:/^[0-9]{0,8}$/'],
            'school_name.required' => 'Nama sekolah wajib diisi.',
            'school_name.max' => 'Nama sekolah maksimal 255 karakter.',
            'app_title.max' => 'Judul aplikasi maksimal 255 karakter.',
            'school_logo.file' => 'Logo harus berupa gambar PNG, JPG, atau WEBP, maksimal 2 MB.',
            'school_logo.mimes' => 'Logo harus berupa gambar PNG, JPG, atau WEBP, maksimal 2 MB.',
            'school_logo.max' => 'Logo harus berupa gambar PNG, JPG, atau WEBP, maksimal 2 MB.',
            'school_address.max' => 'Alamat sekolah maksimal 255 karakter.',
            'school_phone.regex' => 'Nomor telepon harus 10-15 digit angka.',
            'headmaster_name.max' => 'Nama kepala sekolah maksimal 100 karakter.',
            'headmaster_nip.regex' => 'NIP harus 18 digit angka.',
            'school_npsn.regex' => 'NPSN harus 8 digit angka.',
            'school_npsn.max' => 'NPSN harus maksimal 8 digit angka.',
            'check_in_time.required' => 'Jam buka masuk wajib diisi.',
            'check_in_time.date_format' => 'Format jam buka masuk tidak valid.',
            'late_limit_time.required' => 'Batas toleransi keterlambatan wajib diisi.',
            'late_limit_time.date_format' => 'Format batas toleransi tidak valid.',
            'late_limit_time.after' => 'Batas toleransi harus lebih besar dari jam buka masuk.',
        ]);

        // Simpan di dalam try/catch: bila ada kegagalan sistem (mis. storage
        // tidak writable), isian user TIDAK hilang dan pesannya jelas.
        try {
            Setting::set('school_name', strip_tags(trim($request->school_name)));
            Setting::set('app_title', strip_tags(trim($request->app_title ?? '')));
            Setting::set('school_address', strip_tags(trim($request->school_address ?? '')));
            Setting::set('school_phone', trim($request->school_phone ?? ''));
            Setting::set('headmaster_name', strip_tags(trim($request->headmaster_name ?? '')));
            Setting::set('headmaster_nip', trim($request->headmaster_nip ?? ''));
            Setting::set('school_npsn', trim($request->school_npsn ?? ''));
            Setting::set('check_in_time', $request->check_in_time);
            Setting::set('late_limit_time', $request->late_limit_time);

            if ($request->hasFile('school_logo')) {
                $oldLogo = Setting::get('school_logo');
                if ($oldLogo && Storage::disk('public')->exists($oldLogo)) {
                    Storage::disk('public')->delete($oldLogo);
                }

                $path = $request->file('school_logo')->store('settings', 'public');
                Setting::set('school_logo', $path);
            }

            Setting::clearCache();
        } catch (\Throwable $e) {
            report($e);

            return redirect()->route('admin.settings.index')
                ->withInput()
                ->with('error', __('Gagal menyimpan pengaturan. Coba lagi.'));
        }

        return redirect()->route('admin.settings.index')->with('success', __('Pengaturan berhasil disimpan.'));
    }
}
