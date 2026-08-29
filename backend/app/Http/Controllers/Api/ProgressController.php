<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ProgressService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProgressController extends Controller
{
    protected ProgressService $progressService;

    public function __construct(ProgressService $progressService)
    {
        $this->progressService = $progressService;
    }

    /**
     * Record video playback progress (heartbeat).
     *
     * Called every 10 seconds from the Flutter video player.
     * Sends current position and watched ranges to prevent cheating.
     *
     * POST /api/videos/{id}/progress
     */
    public function recordProgress(Request $request, int $id): JsonResponse
    {
        $user = $request->attributes->get('user');

        $validated = $request->validate([
            'current_position' => 'required|integer|min:0',
            'ranges' => 'sometimes|array',
            'ranges.*' => 'array|size:2',
            'ranges.*.0' => 'numeric|min:0',
            'ranges.*.1' => 'numeric|min:0',
        ]);

        $progress = $this->progressService->updateProgress(
            $user->id,
            $id,
            $validated['current_position'],
            $validated['ranges'] ?? []
        );

        return response()->json([
            'success' => true,
            'data' => [
                'watched_seconds' => $progress->watched_seconds,
                'completion_percentage' => $progress->completion_percentage,
                'completed' => $progress->completed,
            ],
        ]);
    }

    /**
     * Get progress for a specific course.
     *
     * GET /api/courses/{courseId}/progress
     */
    public function courseProgress(Request $request, int $courseId): JsonResponse
    {
        $user = $request->attributes->get('user');

        $progress = $this->progressService->getCourseProgress($user->id, $courseId);

        return response()->json([
            'success' => true,
            'data' => [
                'course_id' => $courseId,
                'completion_percentage' => $progress,
            ],
        ]);
    }

    /**
     * Get overall progress summary across all enrolled courses.
     *
     * GET /api/my-progress
     */
    public function myProgress(Request $request): JsonResponse
    {
        $user = $request->attributes->get('user');

        $enrollments = $user->enrollments()
            ->where('status', 'active')
            ->with('course')
            ->get();

        $progressData = $enrollments->map(function ($enrollment) use ($user) {
            return [
                'course_id' => $enrollment->course_id,
                'course_title' => $enrollment->course->title,
                'completion_percentage' => $this->progressService->getCourseProgress(
                    $user->id,
                    $enrollment->course_id
                ),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $progressData,
        ]);
    }
}
