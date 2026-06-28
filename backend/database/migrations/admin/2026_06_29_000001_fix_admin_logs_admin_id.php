<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('admin_logs', function (Blueprint $table) {
            $table->unsignedBigInteger('admin_id')->change();
        });

        Schema::table('admin_logs', function (Blueprint $table) {
            $table->foreign('admin_id')->references('id')->on('admin_users');
        });
    }

    public function down(): void
    {
        Schema::table('admin_logs', function (Blueprint $table) {
            $table->dropForeign(['admin_id']);
            $table->string('admin_id')->change();
        });
    }
};
