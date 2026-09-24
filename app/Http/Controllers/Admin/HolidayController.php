<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

use App\Models\Holiday;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HolidayController extends Controller
{
    public function index(): View
    {
        $holidays = Holiday::orderByRaw('COALESCE(start_date, date) desc')->paginate(15);
        return view('admin.holidays.index', compact('holidays'));
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
