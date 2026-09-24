<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('academic_year_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->time('check_in')->nullable();
            $table->time('check_out')->nullable();
            $table->enum('status', ['Hadir', 'Sakit', 'Izin', 'Alfa']);
            $table->enum('time_remark', ['Tepat Waktu', 'Terlambat'])->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['student_id', 'date', 'academic_year_id']);
            $table->index(['date', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};