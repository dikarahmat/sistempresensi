<?php

namespace App\Http\Controllers\Admin;

use App\Exports\HolidayTemplateExport;
use App\Http\Controllers\Controller;
use App\Imports\HolidaysImport;
use App\Models\Holiday;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;

class HolidayController extends Controller
{
    public function index(): View
    {
        $holidays = Holiday::orderByRaw('COALESCE(start_date, date) desc')->paginate(15);
        return view('admin.holidays.index', compact('holidays'));
    }

    /**
     * Unduh template Excel kosong untuk import hari libur.
     */
    public function template()
    {
        return \App\Services\DownloadCacheService::downloadTemplate(
            'holidays',
            new HolidayTemplateExport(),
            'Template_Hari_Libur.xlsx'
        );
    }

    /**
     * Import hari libur dari file Excel/CSV.
     */
    public function import(Request $request): RedirectResponse
    {
        $request->validate([
            'file_excel' => 'required|file|mimes:xlsx,xls,csv|max:5120',
        ], [
            'file_excel.required' => 'Pilih file Excel hari libur yang akan diunggah.',
            'file_excel.mimes' => 'Format file harus berekstensi .xlsx, .xls, atau .csv.',
        ]);

        try {
            $import = new HolidaysImport();
            Excel::import($import, $request->file('file_excel'));

            $imported = $import->getImportedCount();
            $skipped = $import->getSkippedCount();
            $errors = $import->getErrors();

            // 1) Semua baris berhasil -> notifikasi hijau (sukses)
            if ($imported > 0 && $skipped === 0) {
                return redirect()->route('admin.holidays.index')
                    ->with('success', "Import berhasil: {$imported} hari libur diimpor.");
            }

            // 2) Sebagian berhasil -> notifikasi kuning (peringatan)
            if ($imported > 0) {
                return redirect()->route('admin.holidays.index')
                    ->with('warning', "Import sebagian berhasil: {$imported} diimpor, {$skipped} dilewati.")
                    ->with('import_errors', $errors);
            }

            // 3) Tidak ada baris yang masuk -> notifikasi merah (gagal)
            $reason = $skipped > 0
                ? "{$skipped} baris dilewati karena format tidak sesuai."
                : 'File tidak memiliki baris data.';

            return redirect()->route('admin.holidays.index')
                ->with('error', "Import gagal: tidak ada data yang diimpor. {$reason} Periksa format file Excel.")
                ->with('import_errors', $errors);
        } catch (\Throwable $e) {
            report($e);
            return redirect()->route('admin.holidays.index')
                ->with('error', 'Gagal memproses file Excel. Silakan coba lagi atau hubungi admin.');
        }
    }

    public function store(Request $request): RedirectResponse
    {
        $type = $request->input('type', 'single');

        if ($type === 'range') {
            $request->validate([
                'start_date' => 'required|date',
                'end_date' => 'required|date|after_or_equal:start_date',
                'description' => 'required|string|max:255',
            ]);

            Holiday::create([
                'date' => $request->start_date,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'description' => trim($request->description),
            ]);
        } else {
            $request->validate([
                'date' => 'required|date',
                'description' => 'required|string|max:255',
            ]);

            Holiday::create([
                'date' => $request->date,
                'start_date' => $request->date,
                'end_date' => $request->date,
                'description' => trim($request->description),
            ]);
        }

        return redirect()->route('admin.holidays.index')->with('success', 'Hari libur berhasil ditambahkan!');
    }

    public function update(Request $request, Holiday $holiday): RedirectResponse
    {
        $type = $request->input('type', 'single');

        if ($type === 'range') {
            $request->validate([
                'start_date' => 'required|date',
                'end_date' => 'required|date|after_or_equal:start_date',
                'description' => 'required|string|max:255',
            ]);

            $holiday->update([
                'date' => $request->start_date,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'description' => trim($request->description),
            ]);
        } else {
            $request->validate([
                'date' => 'required|date',
                'description' => 'required|string|max:255',
            ]);

            $holiday->update([
                'date' => $request->date,
                'start_date' => $request->date,
                'end_date' => $request->date,
                'description' => trim($request->description),
            ]);
        }

        return redirect()->route('admin.holidays.index')->with('success', 'Hari libur berhasil diperbarui!');
    }

    public function destroy(Holiday $holiday): RedirectResponse
    {
        $holiday->delete();
        return redirect()->route('admin.holidays.index')->with('success', 'Hari libur berhasil dihapus!');
    }
}
