<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Menambahkan kolom deleted_at ke tabel students untuk mendukung SoftDeletes.
     * Diperlukan karena Model Student menggunakan trait SoftDeletes.
     */
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->softDeletes();
        });
    }

    /**
     * Hapus kolom deleted_at dari tabel students.
     */
    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
