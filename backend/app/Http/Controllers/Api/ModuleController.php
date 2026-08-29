<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Module;
use App\Models\Course;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ModuleController extends Controller
{
    /**
     * List modules for a course.
     *
     * GET /api/courses/{courseId}/modules
     */
    public function index(int $courseId): JsonResponse
    {
        $course = Course::findOrFail($courseId);

        $modules = $course->modules()
            ->with(['videos' => function ($q) {
                $q->where('is_active', true)->orderBy('order');
            }])
            ->where('is_active', true)
            ->orderBy('order')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $modules,
        ]);
    }

    /**
     * [Admin] Create a module.
     *
     * POST /api/admin/modules
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'course_id' => 'required|exists:courses,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'order' => 'integer|min:0',
        ]);

        // Auto-set order if not provided
        if (!isset($validated['order'])) {
            $maxOrder = Module::where('course_id', $validated['course_id'])->max('order');
            $validated['order'] = ($maxOrder ?? -1) + 1;
        }

        $module = Module::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Module created successfully',
            'data' => $module,
        ], 201);
    }

    /**
     * [Admin] Update a module.
     *
     * PUT /api/admin/modules/{id}
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $module = Module::findOrFail($id);

        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'order' => 'integer|min:0',
            'is_active' => 'boolean',
        ]);

        $module->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Module updated successfully',
            'data' => $module,
        ]);
    }

    /**
     * [Admin] Delete a module.
     *
     * DELETE /api/admin/modules/{id}
     */
    public function destroy(int $id): JsonResponse
    {
        $module = Module::findOrFail($id);
        $module->delete();

        // Recalculate course totals
        $module->course->recalculateTotals();

        return response()->json([
            'success' => true,
            'message' => 'Module deleted successfully',
        ]);
    }

    /**
     * [Admin] Reorder modules within a course.
     *
     * POST /api/admin/modules/reorder
     */
    public function reorder(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'modules' => 'required|array',
            'modules.*.id' => 'required|exists:modules,id',
            'modules.*.order' => 'required|integer|min:0',
        ]);

        foreach ($validated['modules'] as $item) {
            Module::where('id', $item['id'])->update(['order' => $item['order']]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Modules reordered successfully',
        ]);
    }
}
