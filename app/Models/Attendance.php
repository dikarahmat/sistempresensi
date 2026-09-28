<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attendance extends Model
{
    protected $fillable = [
        'student_id', 'academic_year_id', 'date', 'check_in',
        'check_out', 'status', 'time_remark', 'is_late', 'late_minutes', 'notes', 'proof_document'
    ];

    protected $casts = [
        'date' => 'date',
        'is_late' => 'boolean',
        'late_minutes' => 'integer',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }
}