<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 'ignored' — the record is deleted from the admin list but kept
        // in the journal so the guid keeps blocking re-import
        // SQLite doesn't enforce column types, so no DDL change is needed there
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE rss_items MODIFY status ENUM('imported', 'rejected', 'failed', 'ignored') NOT NULL");
        }
    }

    public function down(): void
    {
        DB::table('rss_items')->where('status', 'ignored')->update(['status' => 'rejected']);

        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE rss_items MODIFY status ENUM('imported', 'rejected', 'failed') NOT NULL");
        }
    }
};
