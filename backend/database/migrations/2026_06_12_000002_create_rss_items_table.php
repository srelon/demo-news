<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rss_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rss_source_id')->constrained('rss_sources')->cascadeOnDelete();
            $table->string('guid_hash', 32)->unique();
            $table->string('guid');
            $table->string('title')->nullable();
            $table->enum('status', ['imported', 'rejected', 'failed', 'ignored']);
            $table->string('reason')->nullable();
            $table->foreignId('article_id')->nullable()->constrained('articles')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rss_items');
    }
};
