<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Index untuk query attendance per student dan date
        Schema::table('attendances', function (Blueprint $table) {
            if (!Schema::hasIndex('attendances', 'attendances_student_id_date_index')) {
                $table->index(['student_id', 'date'], 'attendances_student_id_date_index');
            }
            if (!Schema::hasIndex('attendances', 'attendances_date_index')) {
                $table->index('date', 'attendances_date_index');
            }
        });

        // Index untuk query students per class
        Schema::table('students', function (Blueprint $table) {
            if (!Schema::hasIndex('students', 'students_school_class_id_index')) {
                $table->index('school_class_id', 'students_school_class_id_index');
            }
        });

        // Index untuk query teacher per user
        Schema::table('teachers', function (Blueprint $table) {
            if (!Schema::hasIndex('teachers', 'teachers_user_id_index')) {
                $table->index('user_id', 'teachers_user_id_index');
            }
        });

        // Index untuk query classes per teacher
        Schema::table('school_classes', function (Blueprint $table) {
            if (!Schema::hasIndex('school_classes', 'school_classes_teacher_id_index')) {
                $table->index('teacher_id', 'school_classes_teacher_id_index');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropIndex('attendances_student_id_date_index');
            $table->dropIndex('attendances_date_index');
        });

        Schema::table('students', function (Blueprint $table) {
            $table->dropIndex('students_school_class_id_index');
        });

        Schema::table('teachers', function (Blueprint $table) {
            $table->dropIndex('teachers_user_id_index');
        });

        Schema::table('school_classes', function (Blueprint $table) {
            $table->dropIndex('school_classes_teacher_id_index');
        });
    }
};
