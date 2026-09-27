<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->index('school_class_id');
        });

        Schema::table('school_classes', function (Blueprint $table) {
            $table->index('academic_year_id');
            $table->index('teacher_id');
        });

        Schema::table('teachers', function (Blueprint $table) {
            $table->index('user_id');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->index('role');
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropIndex(['school_class_id']);
        });

        Schema::table('school_classes', function (Blueprint $table) {
            $table->dropIndex(['academic_year_id']);
            $table->dropIndex(['teacher_id']);
        });

        Schema::table('teachers', function (Blueprint $table) {
            $table->dropIndex(['user_id']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['role']);
        });
    }
};
