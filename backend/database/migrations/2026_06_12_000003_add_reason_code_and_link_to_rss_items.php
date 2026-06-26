<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rss_items', function (Blueprint $table) {
            $table->string('reason_code', 32)->nullable()->after('reason');
            $table->string('link')->nullable()->after('guid');
        });
    }

    public function down(): void
    {
        Schema::table('rss_items', function (Blueprint $table) {
            $table->dropColumn(['reason_code', 'link']);
        });
    }
};
