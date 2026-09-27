<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Remove WhatsApp-related settings from the settings table.
     */
    public function up(): void
    {
        DB::table('settings')->where('key', 'like', 'whatsapp%')->delete();
        DB::table('settings')->where('key', 'like', 'wa_%')->delete();
    }

    /**
     * Reverse the migration (no-op since we don't restore deleted settings).
     */
    public function down(): void
    {
        // WhatsApp settings are permanently removed; no rollback needed.
    }
};
