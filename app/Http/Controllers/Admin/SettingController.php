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
        $settings = [
            'school_name' => Setting::get('school_name', 'SMP Presensi PGRI'),
            'app_title' => Setting::get('app_title', 'Sistem Presensi Sekolah'),
            'school_logo' => Setting::get('school_logo'),
            'school_address' => Setting::get('school_address', 'Jl. Pendidikan No. 45, Kota Pelajar'),
            'school_phone' => Setting::get('school_phone', '(021) 555-1234'),
            'headmaster_name' => Setting::get('headmaster_name', 'Drs. H. Ahmad Sudrajat, M.Pd'),
            'headmaster_nip' => Setting::get('headmaster_nip', '-'),
            'check_in_time' => Setting::get('check_in_time', '06:45'),
            'late_limit_time' => Setting::get('late_limit_time', '07:15'),
            'check_out_time' => Setting::get('check_out_time', '14:30'),
        ];

        return view('admin.settings.index', compact('settings'));
    }

    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'school_name' => ['required', 'string', 'max:255'],
            'app_title' => ['nullable', 'string', 'max:255'],
            'school_logo' => ['nullable', 'file', 'mimes:webp,png,jpg,jpeg', 'max:2048'],
            'school_address' => ['nullable', 'string', 'max:255'],
            // Telepon: hanya angka, 10-15 digit (kode negara tanpa tanda +).
            'school_phone' => ['nullable', 'string', 'regex:/^[0-9]{10,15}$/'],
            'headmaster_name' => ['nullable', 'string', 'max:100'],
            // NIP: hanya angka, tepat 18 digit.
            'headmaster_nip' => ['nullable', 'string', 'regex:/^[0-9]{18}$/'],
            'check_in_time' => ['required', 'string'],
            'late_limit_time' => ['required', 'string'],
            'check_out_time' => ['nullable', 'string'],
        ], [
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
            'check_in_time.required' => 'Jam buka masuk wajib diisi.',
            'late_limit_time.required' => 'Batas toleransi keterlambatan wajib diisi.',
        ]);

        // Simpan di dalam try/catch: bila ada kegagalan sistem (mis. storage
        // tidak writable), isian user TIDAK hilang dan pesannya jelas.
        try {
            Setting::set('school_name', trim($request->school_name));
            Setting::set('app_title', trim($request->app_title ?? ''));
            Setting::set('school_address', trim($request->school_address ?? ''));
            Setting::set('school_phone', trim($request->school_phone ?? ''));
            Setting::set('headmaster_name', trim($request->headmaster_name ?? ''));
            Setting::set('headmaster_nip', trim($request->headmaster_nip ?? ''));
            Setting::set('check_in_time', $request->check_in_time);
            Setting::set('late_limit_time', $request->late_limit_time);
            if ($request->filled('check_out_time')) {
                Setting::set('check_out_time', $request->check_out_time);
            }

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
                ->with('error', 'Gagal menyimpan pengaturan. Coba lagi.');
        }

        return redirect()->route('admin.settings.index')->with('success', 'Pengaturan berhasil disimpan.');
    }
}
