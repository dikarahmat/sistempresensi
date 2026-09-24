<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->string('nis', 20)->unique();
            $table->string('nisn', 20)->unique();
            $table->string('name', 100);
            $table->enum('gender', ['Laki-laki', 'Perempuan']);
            $table->string('birth_place', 50)->nullable();
            $table->date('birth_date')->nullable();
            $table->text('address')->nullable();
            $table->string('parent_name', 100)->nullable();
            $table->string('parent_phone', 20);
            $table->foreignId('school_class_id')->constrained('school_classes')->cascadeOnDelete();
            $table->string('photo')->nullable();
            $table->enum('status', ['Aktif', 'Tidak Aktif', 'Lulus', 'Pindah'])->default('Aktif');
            $table->string('qr_token', 64)->unique();
            $table->timestamps();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};