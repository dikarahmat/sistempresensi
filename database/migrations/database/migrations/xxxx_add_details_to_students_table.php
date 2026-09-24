<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            if (!Schema::hasColumn('students', 'birth_place')) {
                $table->string('birth_place')->nullable()->after('gender');
            }
            if (!Schema::hasColumn('students', 'birth_date')) {
                $table->date('birth_date')->nullable()->after('birth_place');
            }
            if (!Schema::hasColumn('students', 'address')) {
                $table->text('address')->nullable()->after('birth_date');
            }
            if (!Schema::hasColumn('students', 'parent_name')) {
                $table->string('parent_name')->nullable()->after('address');
            }
            if (!Schema::hasColumn('students', 'parent_phone')) {
                $table->string('parent_phone')->nullable()->after('parent_name');
            }
            if (!Schema::hasColumn('students', 'photo')) {
                $table->string('photo')->nullable()->after('parent_phone');
            }
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn(['birth_place', 'birth_date', 'address', 'parent_name', 'parent_phone', 'photo']);
        });
    }
};