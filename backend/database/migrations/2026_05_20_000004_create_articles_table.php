<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('articles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subcategory_id')->constrained()->cascadeOnDelete();
            $table->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('excerpt')->nullable();
            $table->longText('body');
            $table->string('image')->nullable();
            $table->unsignedInteger('views')->default(0);
            $table->enum('source_type', ['manual', 'rss', 'ai'])->default('manual');
            $table->string('source_url')->nullable();
            $table->string('source_name')->nullable();
            $table->enum('status', ['draft', 'published', 'archived'])->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->softDeletes();
            $table->index(['status', 'published_at']);
            $table->index('subcategory_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('articles');
    }
};
