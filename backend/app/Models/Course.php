<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Course extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'title',
        'description',
        'thumbnail',
        'price',
        'is_free',
        'status',
        'level',
        'language',
        'total_duration',
        'total_videos',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'is_free' => 'boolean',
        'total_duration' => 'integer',
        'total_videos' => 'integer',
    ];

    /**
     * Get the modules for the course (ordered).
     */
    public function modules(): HasMany
    {
        return $this->hasMany(Module::class)->orderBy('order');
    }

    /**
     * Get the enrollments for the course.
     */
    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    /**
     * Scope a query to only include published courses.
     */
    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    /**
     * Get total number of enrolled students.
     */
    public function getStudentCountAttribute(): int
    {
        return $this->enrollments()->where('status', 'active')->count();
    }

    /**
     * Recalculate total duration and video count from modules/videos.
     */
    public function recalculateTotals(): void
    {
        $videos = Video::whereIn('module_id', $this->modules()->pluck('id'))->get();
        $this->total_duration = $videos->sum('duration');
        $this->total_videos = $videos->count();
        $this->save();
    }
}
