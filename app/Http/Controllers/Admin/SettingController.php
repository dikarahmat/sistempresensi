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
            'whatsapp_gateway_status' => Setting::get('whatsapp_gateway_status', 'inactive'),
            'whatsapp_api_token' => Setting::get('whatsapp_api_token', ''),
            'whatsapp_sender' => Setting::get('whatsapp_sender', ''),
        ];

        return view('admin.settings.index', compact('settings'));
    }

    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'school_name' => 'required|string|max:255',
            'app_title' => 'nullable|string|max:255',
            'school_logo' => 'nullable|file|mimes:webp,png,jpg,jpeg|max:2048',
            'school_address' => 'nullable|string|max:255',
            'school_phone' => 'nullable|string|max:50',
            'headmaster_name' => 'nullable|string|max:100',
            'headmaster_nip' => 'nullable|string|max:100',
            'check_in_time' => 'required|string',
            'late_limit_time' => 'required|string',
            'check_out_time' => 'nullable|string',
            'whatsapp_gateway_status' => 'nullable|in:active,inactive',
            'whatsapp_api_token' => 'nullable|string|max:255',
            'whatsapp_sender' => 'nullable|string|max:50',
        ]);

        Setting::set('school_name', trim($request->school_name));
        Setting::set('app_title', trim($request->app_title ?? 'Sistem Presensi Sekolah'));
        Setting::set('school_address', trim($request->school_address ?? ''));
        Setting::set('school_phone', trim($request->school_phone ?? ''));
        Setting::set('headmaster_name', trim($request->headmaster_name ?? ''));
        Setting::set('headmaster_nip', trim($request->headmaster_nip ?? ''));
        Setting::set('check_in_time', $request->check_in_time);
        Setting::set('late_limit_time', $request->late_limit_time);
        if ($request->filled('check_out_time')) {
            Setting::set('check_out_time', $request->check_out_time);
        }
        Setting::set('whatsapp_gateway_status', $request->whatsapp_gateway_status ?? 'inactive');
        Setting::set('whatsapp_api_token', trim($request->whatsapp_api_token ?? ''));
        Setting::set('whatsapp_sender', trim($request->whatsapp_sender ?? ''));

        if ($request->hasFile('school_logo')) {
            $oldLogo = Setting::get('school_logo');
            if ($oldLogo && Storage::disk('public')->exists($oldLogo)) {
                Storage::disk('public')->delete($oldLogo);
            }

            $path = $request->file('school_logo')->store('settings', 'public');
            Setting::set('school_logo', $path);
        }

        Setting::clearCache();

        return redirect()->route('admin.settings.index')->with('success', 'Pengaturan sistem berhasil disimpan!');
    }
}
