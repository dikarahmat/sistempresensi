<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('school_classes', function (Blueprint $table) {
            $table->id();
            $table->string('level', 10); // VII, VIII, IX
            $table->string('name', 50);  // 7A, 7B, 8A, dsb
            $table->foreignId('academic_year_id')->constrained()->cascadeOnDelete();
            $table->foreignId('teacher_id')->nullable()->constrained('teachers')->nullOnDelete();
            $table->timestamps();

            $table->unique(['name', 'academic_year_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('school_classes');
    }
};