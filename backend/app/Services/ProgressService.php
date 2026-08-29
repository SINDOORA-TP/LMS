<?php

namespace App\Services;

use App\Models\Video;
use App\Models\VideoProgress;

class ProgressService
{
    /**
     * The completion threshold (90%).
     */
    const COMPLETION_THRESHOLD = 90.0;

    /**
     * Update video progress with watched ranges.
     *
     * This method merges new ranges with existing ones and calculates
     * the actual unique seconds watched. This prevents cheating by
     * skipping to the end — only genuinely watched portions count.
     *
     * @param int $userId
     * @param int $videoId
     * @param int $currentPosition Current playback position in seconds
     * @param array $newRanges Array of [start, end] ranges from this session
     * @return VideoProgress
     */
    public function updateProgress(int $userId, int $videoId, int $currentPosition, array $newRanges = []): VideoProgress
    {
        $video = Video::findOrFail($videoId);

        $progress = VideoProgress::firstOrCreate(
            ['user_id' => $userId, 'video_id' => $videoId],
            ['watched_ranges' => [], 'watched_seconds' => 0]
        );

        // Merge new ranges with existing ones
        $existingRanges = $progress->watched_ranges ?? [];
        $allRanges = array_merge($existingRanges, $newRanges);
        $mergedRanges = $this->mergeRanges($allRanges);

        // Calculate total unique seconds watched
        $uniqueSeconds = $this->calculateUniqueSeconds($mergedRanges);

        // Calculate completion percentage based on unique seconds
        $completionPercentage = $video->duration > 0
            ? min(100, ($uniqueSeconds / $video->duration) * 100)
            : 0;

        // Check if completed (≥90%)
        $completed = $completionPercentage >= self::COMPLETION_THRESHOLD;

        $progress->update([
            'watched_seconds' => $uniqueSeconds,
            'watched_ranges' => $mergedRanges,
            'completion_percentage' => round($completionPercentage, 2),
            'completed' => $completed,
            'last_watched_at' => now(),
        ]);

        return $progress->fresh();
    }

    /**
     * Merge overlapping time ranges.
     *
     * Input:  [[0, 120], [100, 200], [300, 400]]
     * Output: [[0, 200], [300, 400]]
     *
     * @param array $ranges
     * @return array
     */
    public function mergeRanges(array $ranges): array
    {
        if (empty($ranges)) {
            return [];
        }

        // Validate and filter ranges
        $ranges = array_filter($ranges, function ($range) {
            return is_array($range)
                && count($range) === 2
                && is_numeric($range[0])
                && is_numeric($range[1])
                && $range[1] > $range[0];
        });

        if (empty($ranges)) {
            return [];
        }

        // Sort by start time
        usort($ranges, fn($a, $b) => $a[0] <=> $b[0]);

        $merged = [$ranges[0]];

        for ($i = 1; $i < count($ranges); $i++) {
            $last = &$merged[count($merged) - 1];
            $current = $ranges[$i];

            // If current range overlaps with or is adjacent to the last merged range
            if ($current[0] <= $last[1]) {
                $last[1] = max($last[1], $current[1]);
            } else {
                $merged[] = $current;
            }
        }

        return $merged;
    }

    /**
     * Calculate total unique seconds from merged ranges.
     *
     * @param array $ranges Merged (non-overlapping) ranges
     * @return int
     */
    public function calculateUniqueSeconds(array $ranges): int
    {
        $total = 0;
        foreach ($ranges as $range) {
            $total += ($range[1] - $range[0]);
        }
        return $total;
    }

    /**
     * Get course completion percentage for a user.
     *
     * @param int $userId
     * @param int $courseId
     * @return float
     */
    public function getCourseProgress(int $userId, int $courseId): float
    {
        $videoIds = Video::whereHas('module', fn($q) => $q->where('course_id', $courseId))
            ->pluck('id');

        if ($videoIds->isEmpty()) {
            return 0;
        }

        $completedCount = VideoProgress::where('user_id', $userId)
            ->whereIn('video_id', $videoIds)
            ->where('completed', true)
            ->count();

        return round(($completedCount / $videoIds->count()) * 100, 2);
    }
}
