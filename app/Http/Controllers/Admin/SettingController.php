<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

use App\Models\OperatingSchedule;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class SettingController extends Controller
{
    public function index(): View
    {
        $rawPhone = Setting::get('school_phone', '');
        $rawNip = Setting::get('headmaster_nip', '');
        $rawNpsn = Setting::get('school_npsn', '');

        // Normalisasi: simpan hanya digit untuk validasi & penyimpanan.
        // Tampilkan nilai normalisasi supaya user bisa mengoreksi format lama.
        $normalizedPhone = preg_replace('/[^0-9]/', '', (string) $rawPhone);
        $normalizedNip = preg_replace('/[^0-9]/', '', (string) $rawNip);
        $normalizedNpsn = preg_replace('/[^0-9]/', '', (string) $rawNpsn);

        $checkOutFallback = Setting::getCheckOutTime();
        $defaults = [
            'check_in_time' => Setting::getCheckInTime(),
            'late_limit_time' => Setting::getLateLimitTime(),
            'check_out_time' => $checkOutFallback,
            'check_out_limit_time' => date('H:i', strtotime($checkOutFallback . ' +60 minutes')),
        ];

        $existing = OperatingSchedule::whereIn('day_of_week', array_keys(OperatingSchedule::DAY_NAMES))
            ->get()
            ->keyBy('day_of_week');

        $schedules = [];
        foreach (OperatingSchedule::DAY_NAMES as $day => $label) {
            $row = $existing->get($day);
            $schedules[$day] = [
                'label' => $label,
                'check_in_time' => substr((string) ($row->check_in_time ?? $defaults['check_in_time']), 0, 5),
                'late_limit_time' => substr((string) ($row->late_limit_time ?? $defaults['late_limit_time']), 0, 5),
                'check_out_time' => substr((string) ($row->check_out_time ?? $defaults['check_out_time']), 0, 5),
                'check_out_limit_time' => substr((string) ($row->check_out_limit_time ?? $defaults['check_out_limit_time']), 0, 5),
            ];
        }

        $settings = [
            'school_name' => Setting::get('school_name', 'SMP Presensi PGRI'),
            'school_npsn' => $normalizedNpsn,
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
            'check_out_time' => $checkOutFallback,
        ];

        return view('admin.settings.index', compact('settings', 'schedules', 'defaults'));
    }

    public function update(Request $request): RedirectResponse
    {
        // Kolom opsional yang dikosongkan dinormalkan ke null SEBELUM validasi:
        // string kosong '' selalu gagal regex digit, padahal field-nya opsional.
        foreach (['school_phone', 'headmaster_nip', 'school_npsn'] as $optionalDigit) {
            if (trim((string) $request->input($optionalDigit, '')) === '') {
                $request->merge([$optionalDigit => null]);
            }
        }

        // Dukung pengiriman form lama yang belum memakai input per hari:
        // bangun jadwal Senin-Jumat dari setting tunggal yang dikirim/di DB.
        if (!$request->filled('schedules')) {
            $legacyCheckOut = (string) $request->input('check_out_time', Setting::getCheckOutTime());
            $legacySchedule = [
                'check_in_time' => (string) $request->input('check_in_time', Setting::getCheckInTime()),
                'late_limit_time' => (string) $request->input('late_limit_time', Setting::getLateLimitTime()),
                'check_out_time' => $legacyCheckOut,
                'check_out_limit_time' => (string) $request->input(
                    'check_out_limit_time',
                    date('H:i', strtotime($legacyCheckOut . ' +60 minutes'))
                ),
            ];

            $request->merge([
                'schedules' => array_fill_keys(array_keys(OperatingSchedule::DAY_NAMES), $legacySchedule),
            ]);
        }

        $request->validate([
            'school_name' => ['required', 'string', 'max:255'],
            'school_logo' => ['nullable', 'file', 'mimes:webp,png,jpg,jpeg', 'max:2048'],
            'school_address' => ['nullable', 'string', 'max:255'],
            'school_phone' => ['nullable', 'string', 'regex:/^[0-9]{10,15}$/'],
            'headmaster_name' => ['nullable', 'string', 'max:100'],
            'headmaster_nip' => ['nullable', 'string', 'regex:/^[0-9]{18}$/'],
            'school_npsn' => ['nullable', 'string', 'regex:/^[0-9]{8}$/'],
            'schedules' => ['required', 'array'],
            'schedules.*.check_in_time' => ['required', 'date_format:H:i'],
            'schedules.*.late_limit_time' => ['required', 'date_format:H:i'],
            'schedules.*.check_out_time' => ['required', 'date_format:H:i'],
            'schedules.*.check_out_limit_time' => ['required', 'date_format:H:i'],
            'school_name.required' => 'Nama sekolah wajib diisi.',
            'school_name.max' => 'Nama sekolah maksimal 255 karakter.',
            'school_logo.file' => 'Logo harus berupa gambar PNG, JPG, atau WEBP, maksimal 2 MB.',
            'school_logo.mimes' => 'Logo harus berupa gambar PNG, JPG, atau WEBP, maksimal 2 MB.',
            'school_logo.max' => 'Logo harus berupa gambar PNG, JPG, atau WEBP, maksimal 2 MB.',
            'school_address.max' => 'Alamat sekolah maksimal 255 karakter.',
            'school_phone.regex' => 'Nomor telepon harus 10-15 digit angka.',
            'headmaster_name.max' => 'Nama kepala sekolah maksimal 100 karakter.',
            'headmaster_nip.regex' => 'NIP harus 18 digit angka.',
            'school_npsn.regex' => 'NPSN harus 8 digit angka.',
            'school_npsn.max' => 'NPSN harus maksimal 8 digit angka.',
            'schedules.required' => 'Jadwal operasional presensi wajib diisi.',
            'schedules.*.check_in_time.required' => 'Jam masuk wajib diisi.',
            'schedules.*.check_in_time.date_format' => 'Format jam masuk tidak valid.',
            'schedules.*.late_limit_time.required' => 'Batas keterlambatan wajib diisi.',
            'schedules.*.late_limit_time.date_format' => 'Format batas keterlambatan tidak valid.',
            'schedules.*.check_out_time.required' => 'Jam pulang wajib diisi.',
            'schedules.*.check_out_time.date_format' => 'Format jam pulang tidak valid.',
            'schedules.*.check_out_limit_time.required' => 'Batas waktu pulang wajib diisi.',
            'schedules.*.check_out_limit_time.date_format' => 'Format batas waktu pulang tidak valid.',
        ]);

        $schedulesInput = (array) $request->input('schedules');
        $minutes = static function (string $time): int {
            return ((int) substr($time, 0, 2)) * 60 + (int) substr($time, 3, 2);
        };

        foreach (OperatingSchedule::DAY_NAMES as $day => $label) {
            $row = $schedulesInput[$day] ?? null;

            if (!is_array($row)) {
                throw ValidationException::withMessages([
                    "schedules.{$day}.check_in_time" => "Jadwal hari {$label} wajib diisi.",
                ]);
            }

            $checkInMinutes = $minutes($row['check_in_time']);
            $lateLimitMinutes = $minutes($row['late_limit_time']);
            $checkOutMinutes = $minutes($row['check_out_time']);
            $checkOutLimitMinutes = $minutes($row['check_out_limit_time']);

            if (!($checkInMinutes < $lateLimitMinutes
                && $lateLimitMinutes < $checkOutMinutes
                && $checkOutMinutes <= $checkOutLimitMinutes)) {
                throw ValidationException::withMessages([
                    "schedules.{$day}.check_out_time" =>
                        "Jadwal hari {$label}: urutan jam harus Jam Masuk < Batas Keterlambatan < Jam Pulang <= Batas Waktu Pulang.",
                ]);
            }
        }

        // Simpan di dalam try/catch: bila ada kegagalan sistem (mis. storage
        // tidak writable), isian user TIDAK hilang dan pesannya jelas.
        try {
            foreach (OperatingSchedule::DAY_NAMES as $day => $label) {
                $row = $schedulesInput[$day];

                OperatingSchedule::updateOrCreate(
                    ['day_of_week' => $day],
                    [
                        'check_in_time' => $row['check_in_time'],
                        'late_limit_time' => $row['late_limit_time'],
                        'check_out_time' => $row['check_out_time'],
                        'check_out_limit_time' => $row['check_out_limit_time'],
                    ]
                );
            }

            // Sinkronkan setting tunggal lama dengan jadwal Senin supaya
            // komponen yang belum membaca jadwal per hari tetap konsisten.
            $monday = $schedulesInput[1];
            Setting::set('check_in_time', $monday['check_in_time']);
            Setting::set('late_limit_time', $monday['late_limit_time']);
            Setting::set('check_out_time', $monday['check_out_time']);
            Setting::set('check_out_limit_time', $monday['check_out_limit_time']);

            Setting::set('school_name', strip_tags(trim($request->school_name)));
            if ($request->has('app_title')) {
                Setting::set('app_title', strip_tags(trim($request->app_title ?? '')));
            }
            Setting::set('school_address', strip_tags(trim($request->school_address ?? '')));
            Setting::set('school_phone', trim($request->school_phone ?? ''));
            Setting::set('headmaster_name', strip_tags(trim($request->headmaster_name ?? '')));
            Setting::set('headmaster_nip', trim($request->headmaster_nip ?? ''));
            Setting::set('school_npsn', trim($request->school_npsn ?? ''));

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
