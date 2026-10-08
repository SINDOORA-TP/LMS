<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\ZoomMeeting;
use App\Services\ZoomService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ZoomController extends Controller
{
    /**
     * List live classes for a specific course.
     * Enrolled users see join URLs; others see only titles and times.
     *
     * GET /api/courses/{courseId}/live-classes
     */
    public function index(Request $request, int $courseId): JsonResponse
    {
        $course = Course::findOrFail($courseId);
        $user = $request->attributes->get('user');

        $isEnrolled = $user ? $user->isEnrolledIn($courseId) : false;

        // Upcoming + recent meetings for this course
        $meetings = ZoomMeeting::forCourse($courseId)
            ->where(function ($q) {
                $q->upcoming()
                  ->orWhere(function ($q2) {
                      $q2->where('scheduled_at', '>=', now()->subDay())
                         ->whereIn('status', ['started', 'ended']);
                  });
            })
            ->orderBy('scheduled_at', 'asc')
            ->get();

        $data = $meetings->map(function ($meeting) use ($isEnrolled) {
            return $this->formatMeeting($meeting, $isEnrolled);
        });

        return response()->json([
            'success' => true,
            'data'    => $data,
        ]);
    }

    /**
     * List all upcoming live classes across the user's enrolled courses.
     *
     * GET /api/live-classes/upcoming
     */
    public function upcoming(Request $request): JsonResponse
    {
        $user = $request->attributes->get('user');

        // Get all course IDs the user is enrolled in
        $enrolledCourseIds = $user->enrollments()
            ->where('status', 'active')
            ->pluck('course_id');

        $meetings = ZoomMeeting::whereIn('course_id', $enrolledCourseIds)
            ->where(function ($q) {
                $q->upcoming()
                  ->orWhere(function ($q2) {
                      // Include meetings that started within the last 2 hours (could still be live)
                      $q2->where('scheduled_at', '>=', now()->subHours(2))
                         ->where('status', 'started');
                  });
            })
            ->with('course:id,title,thumbnail')
            ->orderBy('scheduled_at', 'asc')
            ->get();

        $data = $meetings->map(function ($meeting) {
            $formatted = $this->formatMeeting($meeting, true);
            $formatted['course'] = [
                'id'        => $meeting->course->id,
                'title'     => $meeting->course->title,
                'thumbnail' => $meeting->course->thumbnail,
            ];
            return $formatted;
        });

        return response()->json([
            'success' => true,
            'data'    => $data,
        ]);
    }

    /**
     * Get a single live class detail.
     *
     * GET /api/live-classes/{id}
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $user = $request->attributes->get('user');
        $meeting = ZoomMeeting::with('course:id,title,thumbnail')->findOrFail($id);

        $isEnrolled = $user->isEnrolledIn($meeting->course_id);

        return response()->json([
            'success' => true,
            'data'    => $this->formatMeeting($meeting, $isEnrolled),
        ]);
    }

    /**
     * [Admin] Create a new live class (Zoom meeting).
     *
     * POST /api/admin/live-classes
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'course_id'    => 'required|exists:courses,id',
            'title'        => 'required|string|max:255',
            'description'  => 'nullable|string',
            'scheduled_at' => 'required|date|after:now',
            'duration'     => 'nullable|integer|min:15|max:480',
            'timezone'     => 'nullable|string|max:50',
        ]);

        $zoomService = app(ZoomService::class);

        try {
            // Create meeting on Zoom
            $zoomData = $zoomService->createMeeting([
                'title'        => $validated['title'],
                'description'  => $validated['description'] ?? '',
                'scheduled_at' => $validated['scheduled_at'],
                'duration'     => $validated['duration'] ?? 60,
                'timezone'     => $validated['timezone'] ?? 'Asia/Kolkata',
            ]);

            // Save to database
            $meeting = ZoomMeeting::create([
                'course_id'       => $validated['course_id'],
                'title'           => $validated['title'],
                'description'     => $validated['description'] ?? null,
                'zoom_meeting_id' => $zoomData['zoom_meeting_id'],
                'zoom_host_id'    => $zoomData['zoom_host_id'],
                'join_url'        => $zoomData['join_url'],
                'start_url'       => $zoomData['start_url'],
                'password'        => $zoomData['password'],
                'scheduled_at'    => $validated['scheduled_at'],
                'duration'        => $validated['duration'] ?? 60,
                'timezone'        => $validated['timezone'] ?? 'Asia/Kolkata',
                'status'          => 'scheduled',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Live class created successfully',
                'data'    => $meeting,
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create Zoom meeting: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * [Admin] Update a live class.
     *
     * PUT /api/admin/live-classes/{id}
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $meeting = ZoomMeeting::findOrFail($id);

        $validated = $request->validate([
            'title'        => 'sometimes|string|max:255',
            'description'  => 'nullable|string',
            'scheduled_at' => 'sometimes|date|after:now',
            'duration'     => 'sometimes|integer|min:15|max:480',
            'timezone'     => 'sometimes|string|max:50',
            'status'       => 'sometimes|in:scheduled,started,ended,cancelled',
        ]);

        $zoomService = app(ZoomService::class);

        try {
            // Update on Zoom (only if relevant fields changed)
            $zoomFields = array_intersect_key($validated, array_flip([
                'title', 'description', 'scheduled_at', 'duration', 'timezone',
            ]));

            if (!empty($zoomFields)) {
                $zoomData = $zoomService->updateMeeting($meeting->zoom_meeting_id, $zoomFields);

                // Update URLs if Zoom returned new ones
                if (isset($zoomData['join_url']))  $validated['join_url']  = $zoomData['join_url'];
                if (isset($zoomData['start_url'])) $validated['start_url'] = $zoomData['start_url'];
            }

            $meeting->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Live class updated successfully',
                'data'    => $meeting->fresh(),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update Zoom meeting: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * [Admin] Delete a live class.
     *
     * DELETE /api/admin/live-classes/{id}
     */
    public function destroy(int $id): JsonResponse
    {
        $meeting = ZoomMeeting::findOrFail($id);
        $zoomService = app(ZoomService::class);

        try {
            // Delete on Zoom
            $zoomService->deleteMeeting($meeting->zoom_meeting_id);
        } catch (\Exception $e) {
            // Log but don't fail — meeting may already be deleted on Zoom
            \Log::warning('Failed to delete Zoom meeting remotely', [
                'meeting_id'      => $meeting->id,
                'zoom_meeting_id' => $meeting->zoom_meeting_id,
                'error'           => $e->getMessage(),
            ]);
        }

        $meeting->delete();

        return response()->json([
            'success' => true,
            'message' => 'Live class deleted successfully',
        ]);
    }

    /**
     * Format a meeting for API response.
     * Hides join_url from unenrolled users.
     */
    private function formatMeeting(ZoomMeeting $meeting, bool $isEnrolled): array
    {
        $data = [
            'id'           => $meeting->id,
            'course_id'    => $meeting->course_id,
            'title'        => $meeting->title,
            'description'  => $meeting->description,
            'scheduled_at' => $meeting->scheduled_at->toIso8601String(),
            'duration'     => $meeting->duration,
            'timezone'     => $meeting->timezone,
            'status'       => $meeting->status,
            'is_live'      => $meeting->is_live,
            'is_upcoming'  => $meeting->is_upcoming,
        ];

        // Only enrolled students get the join URL
        if ($isEnrolled) {
            $data['join_url'] = $meeting->join_url;
            $data['password'] = $meeting->password;
        }

        return $data;
    }
}
