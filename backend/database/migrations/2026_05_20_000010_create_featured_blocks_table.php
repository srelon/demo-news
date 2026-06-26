<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('featured_blocks', function (Blueprint $table) {
            $table->id();
            $table->string('context')->unique(); // 'home' | 'category:sport'
            $table->foreignId('featured_id')->nullable()->constrained('articles')->nullOnDelete();
            $table->foreignId('top_right_id')->nullable()->constrained('articles')->nullOnDelete();
            $table->foreignId('bottom_left_id')->nullable()->constrained('articles')->nullOnDelete();
            $table->foreignId('bottom_right_id')->nullable()->constrained('articles')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('featured_blocks');
    }
};
