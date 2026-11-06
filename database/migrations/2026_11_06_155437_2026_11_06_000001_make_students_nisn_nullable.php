<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /** Run the migrations. */
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            // Ubah kolom nisn jadi nullable dan ubah string kosong menjadi NULL
            $table->string('nisn', 20)->nullable()->change();
        });
    }

    /** Reverse the migrations. */
    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            // Kembalikan nisn jadi not null
            $table->string('nisn', 20)->nullable(false)->change();
        });
    }
};