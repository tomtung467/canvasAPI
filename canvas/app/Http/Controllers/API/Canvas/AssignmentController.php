<?php

namespace App\Http\Controllers\API\Canvas;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\Canvas\CanvasAssignmentService;
use App\Traits\ApiResponseTrait;

class AssignmentController extends Controller
{
    //
    use ApiResponseTrait;
    protected CanvasAssignmentService $assignmentService;
    public function __construct(CanvasAssignmentService $assignmentService)
    {
        $this->assignmentService = $assignmentService;
        $this->middleware('auth:api');
    }
     public function submitAssignment(Request $request)
    {
        $request->validate([
            'course_id'     => 'required|integer',
            'assignment_id' => 'required|integer',
            'file'          => 'required|array',
            'file.*'        => 'file'
        ]);

        try {
            $courseId = $request->input('course_id');
            $assignmentId = $request->input('assignment_id');
            $files = $request->file('file');

            $result = $this->assignmentService->submitAssignment(
                $courseId,
                $assignmentId,
                $files
            );

            if (!$result['success']) {
                return response()->json([
                    'error' => $result['error'],
                    'detail' => $result['detail'] ?? null
                ], 400);
            }

            return response()->json([
                'message' => $result['message'],
                'files' => $result['files'],
                'file_ids' => $result['file_ids']
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to submit assignment',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Lấy danh sách assignments của course
     */
    public function getAssignments(Request $request)
    {
        $request->validate([
            'course_id' => 'required|integer',
        ]);

        try {
            $courseId = $request->input('course_id');

            $assignments = $this->assignmentService->getAssignments($courseId);

            return response()->json($assignments);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to fetch assignments',
                'message' => $e->getMessage()
            ], 500);
        }
    }
        public function getScores(Request $request, $assignmentId)
    {
        $request->validate([
            'course_id'     => 'required|integer',
        ]);

        try {
            $courseId = $request->input('course_id');

            $scores = $this->assignmentService->getScores($courseId, $assignmentId);

            return response()->json($scores);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to fetch scores',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
