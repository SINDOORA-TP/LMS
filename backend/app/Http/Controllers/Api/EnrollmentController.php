<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Enrollment;
use App\Services\PhonePeService;
use App\Services\ProgressService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EnrollmentController extends Controller
{
    /**
     * Enroll in a course (free courses) or initiate payment (paid courses).
     *
     * POST /api/courses/{id}/enroll
     */
    public function enroll(Request $request, int $id): JsonResponse
    {
        $user = $request->attributes->get('user');
        $course = Course::published()->findOrFail($id);

        // Check if already enrolled
        if ($user->isEnrolledIn($course->id)) {
            return response()->json([
                'success' => false,
                'message' => 'Already enrolled in this course',
            ], 409);
        }

        // Free course — enroll directly
        if ($course->is_free || $course->price <= 0) {
            $enrollment = Enrollment::create([
                'user_id' => $user->id,
                'course_id' => $course->id,
                'status' => 'active',
                'amount_paid' => 0,
                'enrolled_at' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Successfully enrolled in course',
                'data' => [
                    'enrollment' => $enrollment,
                    'requires_payment' => false,
                ],
            ], 201);
        }

        // Paid course — initiate PhonePe payment
        $phonePe = app(PhonePeService::class);
        $result = $phonePe->initiatePayment($user, $course);

        if ($result['success']) {
            return response()->json([
                'success' => true,
                'message' => 'Payment initiated',
                'data' => [
                    'requires_payment' => true,
                    'redirect_url' => $result['redirect_url'],
                    'merchant_transaction_id' => $result['merchant_transaction_id'],
                ],
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Failed to initiate payment',
        ], 500);
    }

    /**
     * Get user's enrolled courses with progress.
     *
     * GET /api/my-courses
     */
    public function myCourses(Request $request): JsonResponse
    {
        $user = $request->attributes->get('user');
        $progressService = app(ProgressService::class);

        $enrollments = Enrollment::with('course')
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->orderBy('enrolled_at', 'desc')
            ->get();

        $coursesWithProgress = $enrollments->map(function ($enrollment) use ($user, $progressService) {
            $course = $enrollment->course;
            return [
                'enrollment_id' => $enrollment->id,
                'enrolled_at' => $enrollment->enrolled_at,
                'course' => [
                    'id' => $course->id,
                    'title' => $course->title,
                    'description' => $course->description,
                    'thumbnail' => $course->thumbnail,
                    'total_videos' => $course->total_videos,
                    'total_duration' => $course->total_duration,
                ],
                'progress' => $progressService->getCourseProgress($user->id, $course->id),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $coursesWithProgress,
        ]);
    }
}
