<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('teachers', function (Blueprint $table) {
            if (!Schema::hasColumn('teachers', 'nip')) {
                $table->string('nip', 30)->nullable()->after('name');
            }
            if (!Schema::hasColumn('teachers', 'gender')) {
                $table->string('gender', 20)->default('Laki-laki')->after('nip');
            }
            if (!Schema::hasColumn('teachers', 'birth_place')) {
                $table->string('birth_place', 100)->nullable()->after('gender');
            }
            if (!Schema::hasColumn('teachers', 'birth_date')) {
                $table->date('birth_date')->nullable()->after('birth_place');
            }
        });
    }

    public function down(): void
    {
        Schema::table('teachers', function (Blueprint $table) {
            $table->dropColumn(['nip', 'gender', 'birth_place', 'birth_date']);
        });
    }
};