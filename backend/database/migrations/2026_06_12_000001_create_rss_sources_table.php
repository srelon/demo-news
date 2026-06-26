<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rss_sources', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('url');
            $table->boolean('active')->default(true);
            $table->foreignId('subcategory_id')->constrained('subcategories')->cascadeOnDelete();
            $table->timestamp('last_fetched_at')->nullable();
            $table->enum('last_status', ['ok', 'error'])->nullable();
            $table->string('last_error')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rss_sources');
    }
};
