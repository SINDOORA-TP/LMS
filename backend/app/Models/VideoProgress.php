<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VideoProgress extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'video_progress';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'user_id',
        'video_id',
        'watched_seconds',
        'watched_ranges',
        'completion_percentage',
        'completed',
        'last_watched_at',
    ];

    protected $casts = [
        'watched_seconds' => 'integer',
        'watched_ranges' => 'array',
        'completion_percentage' => 'decimal:2',
        'completed' => 'boolean',
        'last_watched_at' => 'datetime',
    ];

    /**
     * Get the user that owns the progress.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the video for this progress record.
     */
    public function video(): BelongsTo
    {
        return $this->belongsTo(Video::class);
    }
}
