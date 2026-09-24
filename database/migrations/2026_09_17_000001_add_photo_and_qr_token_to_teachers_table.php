<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('teachers', function (Blueprint $table) {
            if (!Schema::hasColumn('teachers', 'photo')) {
                $table->string('photo', 255)->nullable()->after('phone');
            }
            if (!Schema::hasColumn('teachers', 'qr_token')) {
                $table->string('qr_token', 255)->nullable()->after('photo');
            }
        });
    }

    public function down(): void
    {
        Schema::table('teachers', function (Blueprint $table) {
            if (Schema::hasColumn('teachers', 'photo')) {
                $table->dropColumn('photo');
            }
            if (Schema::hasColumn('teachers', 'qr_token')) {
                $table->dropColumn('qr_token');
            }
        });
    }
};
