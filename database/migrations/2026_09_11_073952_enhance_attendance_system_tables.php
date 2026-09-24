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
        Schema::table('school_classes', function (Blueprint $table) {
            if (!Schema::hasColumn('school_classes', 'grade')) {
                $table->string('grade', 10)->nullable()->after('name');
            }
            $table->unsignedBigInteger('academic_year_id')->nullable()->change();
        });

        Schema::table('holidays', function (Blueprint $table) {
            if (!Schema::hasColumn('holidays', 'start_date')) {
                $table->date('start_date')->nullable()->after('date');
            }
            if (!Schema::hasColumn('holidays', 'end_date')) {
                $table->date('end_date')->nullable()->after('start_date');
            }
            // Drop unique index on date if exists
            $table->dropUnique(['date']);
        });

        Schema::table('attendances', function (Blueprint $table) {
            if (!Schema::hasColumn('attendances', 'proof_document')) {
                $table->string('proof_document', 255)->nullable()->after('notes');
            }
        });
    }

    public function down(): void
    {
        Schema::table('school_classes', function (Blueprint $table) {
            if (Schema::hasColumn('school_classes', 'grade')) {
                $table->dropColumn('grade');
            }
        });

        Schema::table('holidays', function (Blueprint $table) {
            if (Schema::hasColumn('holidays', 'start_date')) {
                $table->dropColumn(['start_date', 'end_date']);
            }
            $table->unique(['date']);
        });

        Schema::table('attendances', function (Blueprint $table) {
            if (Schema::hasColumn('attendances', 'proof_document')) {
                $table->dropColumn('proof_document');
            }
        });
    }
};
