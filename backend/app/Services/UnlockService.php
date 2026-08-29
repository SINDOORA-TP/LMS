<?php

namespace App\Services;

use App\Models\Video;
use App\Models\VideoProgress;

class UnlockService
{
    /**
     * Check if a video is unlocked for a given user.
     *
     * Rules:
     * 1. Preview videos are always unlocked
     * 2. The first video in a module is always unlocked (if enrolled)
     * 3. All other videos require the previous video to be ≥90% completed
     *
     * Note: This does NOT check enrollment — that must be checked separately.
     *
     * @param int $userId
     * @param Video $video
     * @return bool
     */
    public function isUnlocked(int $userId, Video $video): bool
    {
        // Preview videos are always accessible
        if ($video->is_preview) {
            return true;
        }

        // Check if there is a previous video in the course sequence
        $previousVideo = $video->getPreviousVideoInCourse();

        if (!$previousVideo) {
            // No previous video in the course means this is the first video of the course, so it is unlocked
            return true;
        }

        $progress = VideoProgress::where('user_id', $userId)
            ->where('video_id', $previousVideo->id)
            ->first();

        return $progress && $progress->completed;
    }

    /**
     * Get the lock status for all videos in a course for a user.
     *
     * Returns an array indexed by video_id with lock status info.
     *
     * @param int $userId
     * @param int $courseId
     * @return array
     */
    public function getCourseVideoStatuses(int $userId, int $courseId): array
    {
        // Get all videos in the course, ordered by module order then video order
        $videos = Video::whereHas('module', fn($q) => $q->where('course_id', $courseId))
            ->with('module')
            ->get()
            ->sortBy([
                fn($a, $b) => $a->module->order <=> $b->module->order,
                fn($a, $b) => $a->order <=> $b->order,
            ]);

        // Get all progress for this user in this course
        $progressMap = VideoProgress::where('user_id', $userId)
            ->whereIn('video_id', $videos->pluck('id'))
            ->get()
            ->keyBy('video_id');

        $statuses = [];

        foreach ($videos as $video) {
            $progress = $progressMap->get($video->id);
            $isUnlocked = $this->isUnlocked($userId, $video);

            $statuses[$video->id] = [
                'is_locked' => !$isUnlocked,
                'is_completed' => $progress ? $progress->completed : false,
                'completion_percentage' => $progress ? $progress->completion_percentage : 0,
                'watched_seconds' => $progress ? $progress->watched_seconds : 0,
            ];
        }

        return $statuses;
    }
}
