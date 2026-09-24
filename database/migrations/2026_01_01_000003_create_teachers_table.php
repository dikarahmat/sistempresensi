<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('teachers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('nip', 30)->unique()->nullable();
            $table->string('name', 100);
            $table->enum('gender', ['Laki-laki', 'Perempuan'])->default('Laki-laki');
            $table->string('birth_place', 50)->nullable();
            $table->date('birth_date')->nullable();
            $table->string('phone', 20)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teachers');
    }
};