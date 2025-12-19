<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\Canvas\CanvasCourseService;
use App\Services\Canvas\CanvasAssignmentService;
use App\Services\Canvas\CanvasSubmissionService;
use App\Services\Canvas\CanvasUserService;
use App\Traits\ApiResponseTrait;

class CanvasController extends Controller
{
    use ApiResponseTrait;

    protected CanvasCourseService $courseService;
    protected CanvasAssignmentService $assignmentService;
    protected CanvasSubmissionService $submissionService;
    protected CanvasUserService $userService;

    public function __construct(
        CanvasCourseService $courseService,
        CanvasAssignmentService $assignmentService,
        CanvasSubmissionService $submissionService,
        CanvasUserService $userService
    ) {
        $this->courseService = $courseService;
        $this->assignmentService = $assignmentService;
        $this->submissionService = $submissionService;
        $this->userService = $userService;
        $this->middleware('auth:api');
    }

    /**
     * Lấy thông tin course theo ID
     */
    public function getCourses($courseId)
    {
        try {
            $course = $this->courseService->getCourse($courseId);
            return response()->json($course);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to fetch course',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Lấy thông tin user theo ID
     */
    public function getUser($userId)
    {
        try {
            $user = $this->userService->getUser($userId);
            return response()->json($user);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to fetch user',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Lấy điểm của assignment
     */
    public function getScores($courseId, $assignmentId)
    {
        try {
            $scores = $this->assignmentService->getScores($courseId, $assignmentId);
            return response()->json($scores);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to fetch scores',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Tìm kiếm users theo tên
     */
    public function searchUsers(Request $request)
    {
        try {
            $searchTerm = $request->input('name');

            if (empty($searchTerm)) {
                return response()->json([
                    'error' => 'Search term is required'
                ], 400);
            }

            $users = $this->userService->searchUsers($searchTerm);
            return response()->json($users);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to search users',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Submit assignment với files
     */
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
    public function getAssignments($courseId)
    {
        try {
            $assignments = $this->assignmentService->getAssignments($courseId);
            return response()->json($assignments);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to fetch assignments',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Download tất cả submissions của assignment dưới dạng ZIP
     */
    public function downloadAssignmentZip(Request $request)
    {
        $request->validate([
            'course_id'     => 'required|integer',
            'assignment_id' => 'required|integer',
        ]);

        try {
            $zipPath = $this->submissionService->downloadAssignmentSubmissions(
                $request->course_id,
                $request->assignment_id
            );

            return response()->download($zipPath)->deleteFileAfterSend(true);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to download submissions',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}

