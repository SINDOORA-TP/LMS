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
        Schema::create('zoom_meetings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained('courses')->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();

            // Zoom-specific fields
            $table->string('zoom_meeting_id', 50);
            $table->string('zoom_host_id', 50)->nullable();
            $table->text('join_url');
            $table->text('start_url');
            $table->string('password', 50)->nullable();

            // Scheduling
            $table->timestamp('scheduled_at');
            $table->unsignedInteger('duration')->default(60); // minutes
            $table->string('timezone', 50)->default('Asia/Kolkata');

            // Status tracking
            $table->enum('status', ['scheduled', 'started', 'ended', 'cancelled'])->default('scheduled');
            $table->text('recording_url')->nullable();

            $table->timestamps();

            // Indexes
            $table->index(['course_id', 'status']);
            $table->index('scheduled_at');
            $table->index('zoom_meeting_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('zoom_meetings');
    }
};
