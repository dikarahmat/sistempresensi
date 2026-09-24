<?php

namespace App\Http\Controllers\Kesiswaan;

use App\Http\Controllers\Controller;
use App\Models\SchoolClass;
use App\Models\Teacher;
use Illuminate\Http\Request;
use Illuminate\View\View;

class KesiswaanTeacherController extends Controller
{
    /**
     * Tampilkan data seluruh guru dan wali kelas (Strict Read-Only).
     */
    public function index(Request $request): View
    {
        $query = Teacher::with(['schoolClass', 'schoolClasses', 'user']);

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('nip', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('phone_number', 'like', "%{$search}%");
            });
        }

        $teachers = $query->orderBy('name', 'asc')->paginate(15)->withQueryString();
        $classes = SchoolClass::orderBy('name')->get();
        $totalTeachers = Teacher::count();
        $totalWaliKelas = SchoolClass::whereNotNull('teacher_id')->distinct('teacher_id')->count('teacher_id');

        return view('kesiswaan.teachers.index', compact(
            'teachers',
            'classes',
            'totalTeachers',
            'totalWaliKelas'
        ));
    }
}
