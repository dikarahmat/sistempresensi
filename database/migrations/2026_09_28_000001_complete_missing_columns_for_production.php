<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Melengkapi seluruh kolom yang dideklarasikan pada Model (fillable) tetapi
 * belum pernah ditambahkan lewat migrasi sebelumnya, serta membuat kolom
 * academic_year_id pada tabel attendances nullable agar proses insert presensi
 * tidak pernah gagal dengan integrity constraint error saat tidak ada
 * tahun ajaran aktif.
 *
 * Semua operasi dibuat IDEMPOTEN (dilindungi Schema::hasColumn /
 * pengecekan nullability) sehingga aman dijalankan otomatis di production
 * (Railway) tanpa intervensi SQL manual, termasuk pada database yang kolomnya
 * sudah pernah ditambahkan secara manual via SQL editor.
 */
return new class extends Migration {
    public function up(): void
    {
        // =========================================================================
        // 1. STUDENTS — kolom pelengkap sesuai deklarasi Model\Student::$fillable
        // =========================================================================
        Schema::table('students', function (Blueprint $table) {
            if (!Schema::hasColumn('students', 'religion')) {
                $table->string('religion', 30)->nullable()->after('gender');
            }
            if (!Schema::hasColumn('students', 'phone')) {
                $table->string('phone', 20)->nullable()->after('birth_date');
            }
            if (!Schema::hasColumn('students', 'email')) {
                $table->string('email')->nullable()->after('phone');
            }
            if (!Schema::hasColumn('students', 'parent_occupation')) {
                $table->string('parent_occupation', 100)->nullable()->after('parent_phone');
            }
            if (!Schema::hasColumn('students', 'notes')) {
                $table->text('notes')->nullable()->after('parent_occupation');
            }
        });

        // =========================================================================
        // 2. TEACHERS — kolom pelengkap sesuai deklarasi Model\Teacher::$fillable
        // =========================================================================
        Schema::table('teachers', function (Blueprint $table) {
            if (!Schema::hasColumn('teachers', 'nuptk')) {
                $table->string('nuptk', 30)->nullable()->after('nip');
            }
            if (!Schema::hasColumn('teachers', 'religion')) {
                $table->string('religion', 30)->nullable()->after('gender');
            }
            if (!Schema::hasColumn('teachers', 'address')) {
                $table->text('address')->nullable()->after('birth_place');
            }
            if (!Schema::hasColumn('teachers', 'email')) {
                $table->string('email')->nullable()->after('phone');
            }
            if (!Schema::hasColumn('teachers', 'status')) {
                $table->string('status', 20)->nullable()->default('Aktif')->after('email');
            }
            if (!Schema::hasColumn('teachers', 'subject')) {
                $table->string('subject', 100)->nullable()->after('status');
            }
            if (!Schema::hasColumn('teachers', 'notes')) {
                $table->text('notes')->nullable()->after('subject');
            }
        });

        // =========================================================================
        // 3. ATTENDANCES — kolom tambahan logika kamera/scanner + academic_year_id
        //    nullable agar insert presensi selalu aman (default constraint).
        // =========================================================================
        Schema::table('attendances', function (Blueprint $table) {
            if (!Schema::hasColumn('attendances', 'is_late')) {
                $table->boolean('is_late')->default(false)->after('time_remark');
            }
            if (!Schema::hasColumn('attendances', 'late_minutes')) {
                $table->unsignedSmallInteger('late_minutes')->nullable()->after('is_late');
            }
        });

        // Buat academic_year_id nullable (dipertahankan FK-nya; perubahan tipe
        // nullable pada MySQL/MariaDB tidak menghapus foreign key constraint).
        $academicYearColumn = collect(Schema::getColumns('attendances'))
            ->firstWhere('name', 'academic_year_id');

        if ($academicYearColumn !== null && ($academicYearColumn['nullable'] ?? true) === false) {
            Schema::table('attendances', function (Blueprint $table) {
                $table->unsignedBigInteger('academic_year_id')->nullable()->change();
            });
        }
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            foreach (['religion', 'phone', 'email', 'parent_occupation', 'notes'] as $column) {
                if (Schema::hasColumn('students', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('teachers', function (Blueprint $table) {
            foreach (['nuptk', 'religion', 'address', 'email', 'status', 'subject', 'notes'] as $column) {
                if (Schema::hasColumn('teachers', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('attendances', function (Blueprint $table) {
            foreach (['is_late', 'late_minutes'] as $column) {
                if (Schema::hasColumn('attendances', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
