<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('comments', function (Blueprint $table) {
            // 0 = null (user/admin), 1 = moderator deleted (shows placeholder)
            $table->tinyInteger('deleted_by')->nullable()->default(null)->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('comments', function (Blueprint $table) {
            $table->dropColumn('deleted_by');
        });
    }
};
