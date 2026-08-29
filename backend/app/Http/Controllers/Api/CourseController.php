<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Services\ProgressService;
use App\Services\UnlockService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CourseController extends Controller
{
    /**
     * List all published courses.
     *
     * GET /api/courses
     */
    public function index(Request $request): JsonResponse
    {
        $query = Course::published();

        // Search
        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Filter by level
        if ($request->has('level')) {
            $query->where('level', $request->input('level'));
        }

        // Filter by price (free/paid)
        if ($request->has('is_free')) {
            $query->where('is_free', $request->boolean('is_free'));
        }

        $courses = $query->withCount(['enrollments as student_count' => function ($q) {
            $q->where('status', 'active');
        }])->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $courses,
        ]);
    }

    /**
     * Get a single course with modules and videos.
     *
     * GET /api/courses/{id}
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $course = Course::with(['modules.videos' => function ($q) {
            $q->where('is_active', true)->orderBy('order');
        }])
        ->withCount(['enrollments as student_count' => function ($q) {
            $q->where('status', 'active');
        }])
        ->findOrFail($id);

        $user = $request->attributes->get('user');
        $responseData = $course->toArray();

        // If user is authenticated, include enrollment and progress info
        if ($user) {
            $isEnrolled = $user->isEnrolledIn($course->id);
            $responseData['is_enrolled'] = $isEnrolled;

            if ($isEnrolled) {
                $progressService = app(ProgressService::class);
                $unlockService = app(UnlockService::class);

                $responseData['course_progress'] = $progressService->getCourseProgress($user->id, $course->id);
                $videoStatuses = $unlockService->getCourseVideoStatuses($user->id, $course->id);

                // Attach lock/progress status to each video
                foreach ($responseData['modules'] as &$module) {
                    foreach ($module['videos'] as &$video) {
                        $status = $videoStatuses[$video['id']] ?? [
                            'is_locked' => true,
                            'is_completed' => false,
                            'completion_percentage' => 0,
                            'watched_seconds' => 0,
                        ];
                        $video = array_merge($video, $status);
                    }
                }
            }
        }

        return response()->json([
            'success' => true,
            'data' => $responseData,
        ]);
    }

    /**
     * [Admin] Create a new course.
     *
     * POST /api/admin/courses
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'thumbnail' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'is_free' => 'boolean',
            'status' => 'in:draft,published',
            'level' => 'in:beginner,intermediate,advanced',
            'language' => 'string|max:50',
        ]);

        $course = Course::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Course created successfully',
            'data' => $course,
        ], 201);
    }

    /**
     * [Admin] Update a course.
     *
     * PUT /api/admin/courses/{id}
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $course = Course::findOrFail($id);

        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'thumbnail' => 'nullable|string',
            'price' => 'sometimes|numeric|min:0',
            'is_free' => 'boolean',
            'status' => 'in:draft,published,archived',
            'level' => 'in:beginner,intermediate,advanced',
            'language' => 'string|max:50',
        ]);

        $course->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Course updated successfully',
            'data' => $course,
        ]);
    }

    /**
     * [Admin] Delete a course.
     *
     * DELETE /api/admin/courses/{id}
     */
    public function destroy(int $id): JsonResponse
    {
        $course = Course::findOrFail($id);
        $course->delete();

        return response()->json([
            'success' => true,
            'message' => 'Course deleted successfully',
        ]);
    }
}
