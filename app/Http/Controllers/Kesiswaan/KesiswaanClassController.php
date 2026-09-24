<?php

namespace App\Http\Controllers\Kesiswaan;

use App\Http\Controllers\Controller;
use App\Models\SchoolClass;
use App\Models\Teacher;
use Illuminate\Http\Request;
use Illuminate\View\View;

class KesiswaanClassController extends Controller
{
    /**
     * Tampilkan data kelas / rombel (Strict Read-Only).
     */
    public function index(Request $request): View
    {
        $query = SchoolClass::with(['teacher', 'academicYear'])->withCount('students');

        if ($request->filled('grade')) {
            $query->where('grade', $request->grade);
        }

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        $classes = $query->orderBy('name')->paginate(20)->withQueryString();
        $teachers = Teacher::orderBy('name')->get();

        return view('kesiswaan.classes.index', compact('classes', 'teachers'));
    }
}
