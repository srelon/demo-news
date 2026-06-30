<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->boolean('demo_edited')->default(false)->after('active');
            $table->boolean('demo_created')->default(false)->after('demo_edited');
            $table->json('demo_snapshot')->nullable()->after('demo_created');
        });
    }

    public function down(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->dropColumn(['demo_edited', 'demo_created', 'demo_snapshot']);
        });
    }
};
