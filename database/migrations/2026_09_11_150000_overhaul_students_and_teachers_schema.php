<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // 1. Modifikasi tabel teachers
        Schema::table('teachers', function (Blueprint $table) {
            if (!Schema::hasColumn('teachers', 'phone_number')) {
                $table->string('phone_number', 20)->nullable()->after('birth_date');
            }
            if (Schema::hasColumn('teachers', 'phone')) {
                $table->string('phone', 20)->nullable()->change();
            }
            $table->unsignedBigInteger('user_id')->nullable()->change();
            $table->string('name', 255)->change();
            $table->string('nip', 50)->nullable()->change();
            $table->string('birth_place', 100)->nullable()->change();
            $table->date('birth_date')->nullable()->change();
        });

        // 2. Modifikasi tabel students
        Schema::table('students', function (Blueprint $table) {
            $table->string('name', 255)->change();
            $table->string('nis', 30)->change();
            $table->string('nisn', 30)->nullable()->change();
            $table->string('birth_place', 100)->nullable()->change();
            $table->date('birth_date')->nullable()->change();
            $table->string('parent_name', 255)->nullable()->change();
            $table->string('parent_phone', 20)->nullable()->change();
            $table->string('photo', 255)->nullable()->change();
            $table->string('qr_token', 255)->nullable()->change();
        });

        // 3. Reset total data students dan teachers (auto-increment ke 1)
        if (DB::getDriverName() === 'mysql') {
            DB::statement('SET FOREIGN_KEY_CHECKS = 0;');
        }
        
        // Bersihkan data presensi yang bergantung pada siswa
        DB::table('attendances')->truncate();
        
        // Reset kelas agar relasi teacher_id menjadi null
        DB::table('school_classes')->update(['teacher_id' => null]);
        
        // Kosongkan tabel siswa dan wali kelas
        DB::table('students')->truncate();
        DB::table('teachers')->truncate();
        
        // Reset auto increment
        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE students AUTO_INCREMENT = 1;');
            DB::statement('ALTER TABLE teachers AUTO_INCREMENT = 1;');
            DB::statement('SET FOREIGN_KEY_CHECKS = 1;');
        }
    }

    public function down(): void
    {
        // Reversible structure if needed
    }
};
