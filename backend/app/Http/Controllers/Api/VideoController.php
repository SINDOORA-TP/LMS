<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Video;
use App\Models\Module;
use App\Services\UnlockService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VideoController extends Controller
{
    protected UnlockService $unlockService;

    public function __construct(UnlockService $unlockService)
    {
        $this->unlockService = $unlockService;
    }

    /**
     * Get video details and YouTube video ID (if authorized).
     *
     * This is the SECURE video access endpoint.
     * It checks: auth → enrollment → unlock status before returning the YouTube video ID.
     *
     * GET /api/videos/{id}/access
     */
    public function access(Request $request, int $id): JsonResponse
    {
        $user = $request->attributes->get('user');
        $video = Video::with('module.course')->findOrFail($id);
        $course = $video->module->course;

        // Check 1: Is user enrolled? (Preview videos skip this check)
        if (!$video->is_preview && !$user->isEnrolledIn($course->id)) {
            return response()->json([
                'success' => false,
                'message' => 'You are not enrolled in this course',
                'error_code' => 'NOT_ENROLLED',
            ], 403);
        }

        // Check 2: Is video unlocked? (90% completion of previous video)
        if (!$this->unlockService->isUnlocked($user->id, $video)) {
            return response()->json([
                'success' => false,
                'message' => 'Complete the previous video first (90% required)',
                'error_code' => 'VIDEO_LOCKED',
            ], 403);
        }

        // All checks passed — return YouTube video ID
        return response()->json([
            'success' => true,
            'data' => [
                'id' => $video->id,
                'title' => $video->title,
                'description' => $video->description,
                'youtube_video_id' => $video->youtube_video_id,
                'duration' => $video->duration,
                'formatted_duration' => $video->formatted_duration,
            ],
        ]);
    }

    /**
     * [Admin] Create a video entry.
     *
     * POST /api/admin/videos
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'module_id' => 'required|exists:modules,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'youtube_video_id' => 'required|string|max:50',
            'duration' => 'required|integer|min:1',
            'order' => 'integer|min:0',
            'is_preview' => 'boolean',
        ]);

        // Auto-set order if not provided
        if (!isset($validated['order'])) {
            $maxOrder = Video::where('module_id', $validated['module_id'])->max('order');
            $validated['order'] = ($maxOrder ?? -1) + 1;
        }

        $video = Video::create($validated);

        // Recalculate course totals
        $module = Module::find($validated['module_id']);
        $module->course->recalculateTotals();

        return response()->json([
            'success' => true,
            'message' => 'Video created successfully',
            'data' => $video->makeVisible('youtube_video_id'),
        ], 201);
    }

    /**
     * [Admin] Update a video.
     *
     * PUT /api/admin/videos/{id}
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $video = Video::findOrFail($id);

        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'youtube_video_id' => 'sometimes|string|max:50',
            'duration' => 'sometimes|integer|min:1',
            'order' => 'integer|min:0',
            'is_preview' => 'boolean',
            'is_active' => 'boolean',
        ]);

        $video->update($validated);

        // Recalculate course totals
        $video->module->course->recalculateTotals();

        return response()->json([
            'success' => true,
            'message' => 'Video updated successfully',
            'data' => $video->makeVisible('youtube_video_id'),
        ]);
    }

    /**
     * [Admin] Delete a video.
     *
     * DELETE /api/admin/videos/{id}
     */
    public function destroy(int $id): JsonResponse
    {
        $video = Video::with('module.course')->findOrFail($id);
        $course = $video->module->course;

        $video->delete();

        // Recalculate course totals
        $course->recalculateTotals();

        return response()->json([
            'success' => true,
            'message' => 'Video deleted successfully',
        ]);
    }
}
