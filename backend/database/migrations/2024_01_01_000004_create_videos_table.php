<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('videos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('module_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('youtube_video_id')->comment('YouTube unlisted video ID');
            $table->unsignedInteger('duration')->default(0)->comment('Duration in seconds');
            $table->unsignedInteger('order')->default(0);
            $table->boolean('is_preview')->default(false)->comment('Free preview video');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['module_id', 'order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('videos');
    }
};
