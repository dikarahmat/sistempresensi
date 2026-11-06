<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /** Run the migrations. */
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropUnique(['nisn']);
            $table->index('nisn');
        });

        // Pastikan semua siswa lama yang belum punya qr_token mendapatkannya sekarang.
        foreach (DB::table('students')->whereNull('qr_token')->orWhere('qr_token', '')->get() as $row) {
            DB::table('students')->where('id', $row->id)->update(['qr_token' => (string) Str::uuid()]);
        }
    }

    /** Reverse the migrations. */
    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropIndex(['nisn']);
            $table->unique('nisn');
        });
    }
};
