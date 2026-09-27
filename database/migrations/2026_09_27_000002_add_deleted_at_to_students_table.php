<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Menambahkan kolom deleted_at nullable ke tabel students.
     * Idempotent: hanya tambahkan jika kolom belum ada.
     */
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            if (!Schema::hasColumn('students', 'deleted_at')) {
                $table->softDeletes();
            }
        });
    }

    /**
     * Hapus kolom deleted_at dari tabel students.
     * Idempotent: hanya hapus jika kolom ada.
     */
    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            if (Schema::hasColumn('students', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
        });
    }
};
