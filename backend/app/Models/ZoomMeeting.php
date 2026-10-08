<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ZoomMeeting extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'course_id',
        'title',
        'description',
        'zoom_meeting_id',
        'zoom_host_id',
        'join_url',
        'start_url',
        'password',
        'scheduled_at',
        'duration',
        'timezone',
        'status',
        'recording_url',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'duration'     => 'integer',
    ];

    /**
     * Get the course this meeting belongs to.
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * Scope: only upcoming meetings (scheduled in the future, not cancelled).
     */
    public function scopeUpcoming($query)
    {
        return $query->where('scheduled_at', '>', now())
                     ->where('status', 'scheduled')
                     ->orderBy('scheduled_at', 'asc');
    }

    /**
     * Scope: meetings for a specific course.
     */
    public function scopeForCourse($query, int $courseId)
    {
        return $query->where('course_id', $courseId);
    }

    /**
     * Scope: recent meetings (ended/started within the last 24 hours).
     */
    public function scopeRecent($query)
    {
        return $query->where('scheduled_at', '>=', now()->subDay())
                     ->whereIn('status', ['started', 'ended'])
                     ->orderBy('scheduled_at', 'desc');
    }

    /**
     * Check if the meeting is currently live.
     */
    public function getIsLiveAttribute(): bool
    {
        if ($this->status === 'started') {
            return true;
        }

        // Auto-detect: meeting is "live" if within scheduled window
        $start = $this->scheduled_at;
        $end = $this->scheduled_at->addMinutes($this->duration);

        return now()->between($start, $end) && $this->status === 'scheduled';
    }

    /**
     * Check if the meeting is upcoming.
     */
    public function getIsUpcomingAttribute(): bool
    {
        return $this->scheduled_at->isFuture() && $this->status === 'scheduled';
    }
}
