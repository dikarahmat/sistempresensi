<?php

namespace App\Http\Controllers\Admin;

use App\Exports\HolidayTemplateExport;
use App\Http\Controllers\Controller;
use App\Imports\HolidaysImport;
use App\Models\Holiday;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Validator;
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
    public function import(Request $request): RedirectResponse|JsonResponse
    {
        $isAjax = $request->expectsJson() || $request->ajax() || $request->wantsJson() || $request->header('X-Requested-With') === 'XMLHttpRequest';

        $validator = Validator::make($request->all(), [
            'file_excel' => 'required|file|mimes:xlsx,xls,csv|max:5120',
        ], [
            'file_excel.required' => 'Pilih file Excel hari libur yang akan diunggah.',
            'file_excel.mimes' => 'Format file harus berekstensi .xlsx, .xls, atau .csv.',
        ]);

        if ($validator->fails()) {
            $message = $validator->errors()->first();
            if ($isAjax) {
                session()->flash('error', $message);
                return response()->json([
                    'success' => false,
                    'status' => 'error',
                    'imported' => 0,
                    'skipped' => 0,
                    'message' => $message,
                ], 200);
            }
            return redirect()->route('admin.holidays.index')->with('error', $message);
        }

        try {
            $import = new HolidaysImport();
            Excel::import($import, $request->file('file_excel'));

            $imported = $import->getImportedCount();
            $skipped = $import->getSkippedCount();
            $errors = $import->getErrors();

            // 1) Semua baris berhasil -> notifikasi hijau (sukses)
            if ($imported > 0 && $skipped === 0) {
                $message = __('Import berhasil: :imported data hari libur ditambahkan.', ['imported' => $imported]);
                if ($isAjax) {
                    session()->flash('success', $message);
                    return response()->json([
                        'success' => true,
                        'status' => 'success',
                        'imported' => $imported,
                        'skipped' => $skipped,
                        'message' => $message,
                    ]);
                }
                return redirect()->route('admin.holidays.index')
                    ->with('success', $message);
            }

            // 2) Sebagian berhasil -> notifikasi kuning (peringatan)
            if ($imported > 0) {
                $message = __('Import sebagian berhasil: :imported ditambahkan, :skipped dilewati karena sudah ada.', ['imported' => $imported, 'skipped' => $skipped]);
                if ($isAjax) {
                    session()->flash('warning', $message);
                    if (!empty($errors)) {
                        session()->flash('import_errors', $errors);
                    }
                    return response()->json([
                        'success' => true,
                        'status' => 'warning',
                        'imported' => $imported,
                        'skipped' => $skipped,
                        'message' => $message,
                        'errors' => $errors,
                    ]);
                }
                return redirect()->route('admin.holidays.index')
                    ->with('warning', $message)
                    ->with('import_errors', $errors);
            }

            // 3) Tidak ada baris yang masuk -> notifikasi merah (gagal)
            if ($skipped > 0) {
                $message = __('Import gagal: tidak ada data baru, semua data sudah ada.');
            } else {
                $message = __('Import gagal: tidak ada data yang diimpor. File tidak memiliki baris data atau format kolom salah. Periksa format file Excel.');
            }

            if ($isAjax) {
                session()->flash('error', $message);
                if (!empty($errors)) {
                    session()->flash('import_errors', $errors);
                }
                return response()->json([
                    'success' => false,
                    'status' => 'error',
                    'imported' => $imported,
                    'skipped' => $skipped,
                    'message' => $message,
                    'errors' => $errors,
                ]);
            }

            return redirect()->route('admin.holidays.index')
                ->with('error', $message)
                ->with('import_errors', $errors);
        } catch (\Throwable $e) {
            report($e);
            $message = __('Gagal memproses file Excel. Silakan coba lagi atau hubungi admin.');
            if ($isAjax) {
                session()->flash('error', $message);
                return response()->json([
                    'success' => false,
                    'status' => 'error',
                    'imported' => 0,
                    'skipped' => 0,
                    'message' => $message,
                ], 200);
            }
            return redirect()->route('admin.holidays.index')
                ->with('error', $message);
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
            ], [
                'start_date.required' => 'Tanggal mulai wajib diisi.',
                'start_date.date' => 'Tanggal mulai tidak valid.',
                'end_date.required' => 'Tanggal selesai wajib diisi.',
                'end_date.date' => 'Tanggal selesai tidak valid.',
                'end_date.after_or_equal' => 'Tanggal selesai tidak boleh sebelum tanggal mulai.',
                'description.required' => 'Keterangan hari libur wajib diisi.',
                'description.max' => 'Keterangan maksimal 255 karakter.',
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
            ], [
                'date.required' => 'Tanggal hari libur wajib diisi.',
                'date.date' => 'Tanggal hari libur tidak valid.',
                'description.required' => 'Keterangan hari libur wajib diisi.',
                'description.max' => 'Keterangan maksimal 255 karakter.',
            ]);

            Holiday::create([
                'date' => $request->date,
                'start_date' => $request->date,
                'end_date' => $request->date,
                'description' => trim($request->description),
            ]);
        }

        return redirect()->route('admin.holidays.index')->with('success', __('Hari libur berhasil ditambahkan!'));
    }

    /**
     * PERBAIKAN: parameter route resource 'hari-libur' bernama {hari_libur},
     * jadi harus dicari manual (sama seperti method destroy).
     */
    public function update(Request $request, $hari_libur): RedirectResponse
    {
        $holiday = Holiday::findOrFail($hari_libur);
        $type = $request->input('type', 'single');

        if ($type === 'range') {
            $request->validate([
                'start_date' => 'required|date',
                'end_date' => 'required|date|after_or_equal:start_date',
                'description' => 'required|string|max:255',
            ], [
                'start_date.required' => 'Tanggal mulai wajib diisi.',
                'start_date.date' => 'Tanggal mulai tidak valid.',
                'end_date.required' => 'Tanggal selesai wajib diisi.',
                'end_date.date' => 'Tanggal selesai tidak valid.',
                'end_date.after_or_equal' => 'Tanggal selesai tidak boleh sebelum tanggal mulai.',
                'description.required' => 'Keterangan hari libur wajib diisi.',
                'description.max' => 'Keterangan maksimal 255 karakter.',
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
            ], [
                'date.required' => 'Tanggal hari libur wajib diisi.',
                'date.date' => 'Tanggal hari libur tidak valid.',
                'description.required' => 'Keterangan hari libur wajib diisi.',
                'description.max' => 'Keterangan maksimal 255 karakter.',
            ]);

            $holiday->update([
                'date' => $request->date,
                'start_date' => $request->date,
                'end_date' => $request->date,
                'description' => trim($request->description),
            ]);
        }

        return redirect()->route('admin.holidays.index')->with('success', __('Hari libur berhasil diperbarui!'));
    }

    public function destroy($hari_libur): RedirectResponse
    {
        $holiday = Holiday::find($hari_libur) ?? Holiday::findOrFail($hari_libur);
        $holiday->delete();
        // Notifikasi hasil HAPUS memakai flash 'deleted' supaya tampil MERAH.
        return redirect()->route('admin.holidays.index')->with('deleted', __('Hari libur berhasil dihapus!'));
    }

    public function destroyAll(): RedirectResponse
    {
        $count = Holiday::count();

        if ($count === 0) {
            return redirect()->route('admin.holidays.index')->with('error', __('Tidak ada data hari libur yang bisa dihapus.'));
        }

        Holiday::query()->delete();

        return redirect()->route('admin.holidays.index')->with('deleted', __(':count data hari libur berhasil dihapus.', ['count' => $count]));
    }
}