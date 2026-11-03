<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Tabel presensi guru (mirror tabel attendances siswa, dengan teacher_id).
     * Dibuat karena belum ada model/tabel presensi guru sebelumnya.
     */
    public function up(): void
    {
        Schema::create('teacher_attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_id')->constrained('teachers')->cascadeOnDelete();
            $table->foreignId('academic_year_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->time('check_in')->nullable();
            $table->time('check_out')->nullable();
            $table->enum('status', ['Hadir', 'Sakit', 'Izin', 'Alfa']);
            $table->enum('time_remark', ['Tepat Waktu', 'Terlambat'])->nullable();
            $table->boolean('is_late')->default(false);
            $table->unsignedInteger('late_minutes')->default(0);
            $table->text('notes')->nullable();
            $table->string('proof_document', 255)->nullable();
            $table->timestamps();

            $table->unique(['teacher_id', 'date', 'academic_year_id']);
            $table->index(['date', 'status']);
            $table->index(['teacher_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teacher_attendances');
    }
};
