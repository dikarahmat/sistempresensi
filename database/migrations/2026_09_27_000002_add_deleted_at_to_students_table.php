<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Menambahkan kolom deleted_at nullable ke tabel students.
     * Digunakan untuk soft delete manual (tanpa trait SoftDeletes).
     */
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->timestamp('deleted_at')->nullable()->after('status');
        });
    }

    /**
     * Hapus kolom deleted_at dari tabel students.
     */
    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn('deleted_at');
        });
    }
};
