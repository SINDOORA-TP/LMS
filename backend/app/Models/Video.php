<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Video extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'module_id',
        'title',
        'description',
        'youtube_video_id',
        'duration',
        'order',
        'is_preview',
        'is_active',
    ];

    protected $casts = [
        'duration' => 'integer',
        'order' => 'integer',
        'is_preview' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * The attributes that should be hidden for serialization.
     * YouTube video ID is hidden by default — only returned after access check.
     */
    protected $hidden = [
        'youtube_video_id',
    ];

    /**
     * Get the module that owns the video.
     */
    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class);
    }

    /**
     * Get progress records for this video.
     */
    public function progress(): HasMany
    {
        return $this->hasMany(VideoProgress::class);
    }

    /**
     * Get the previous video in the same module (by order).
     */
    public function getPreviousVideo(): ?Video
    {
        return Video::where('module_id', $this->module_id)
            ->where('order', '<', $this->order)
            ->orderBy('order', 'desc')
            ->first();
    }

    /**
     * Get the previous video in the entire course sequence (across all modules).
     */
    public function getPreviousVideoInCourse(): ?Video
    {
        $courseId = $this->module->course_id;

        // Get all videos in this course ordered by module order and video order
        $videos = Video::whereHas('module', fn($q) => $q->where('course_id', $courseId))
            ->with('module')
            ->get()
            ->sortBy([
                fn($a, $b) => $a->module->order <=> $b->module->order,
                fn($a, $b) => $a->order <=> $b->order,
            ])
            ->values();

        $currentIndex = $videos->search(fn($v) => $v->id === $this->id);

        if ($currentIndex === false || $currentIndex === 0) {
            return null;
        }

        return $videos[$currentIndex - 1];
    }

    /**
     * Check if this is the first video in its module.
     */
    public function isFirstInModule(): bool
    {
        return !Video::where('module_id', $this->module_id)
            ->where('order', '<', $this->order)
            ->exists();
    }

    /**
     * Get the formatted duration (e.g., "12:30").
     */
    public function getFormattedDurationAttribute(): string
    {
        $minutes = floor($this->duration / 60);
        $seconds = $this->duration % 60;
        return sprintf('%d:%02d', $minutes, $seconds);
    }
}
