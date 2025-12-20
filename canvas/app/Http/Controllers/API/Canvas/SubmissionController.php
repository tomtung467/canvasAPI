<?php

namespace App\Http\Controllers\API\Canvas;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\Canvas\CanvasSubmissionService;
use App\Traits\ApiResponseTrait;

class SubmissionController extends Controller
{
    use ApiResponseTrait;
    protected CanvasSubmissionService $submissionService;
    use ApiResponseTrait;
    public function __construct(CanvasSubmissionService $submissionService)
    {
        $this->submissionService = $submissionService;
        $this->middleware('auth:api');
    }

     use ApiResponseTrait;
     public function getSubmissions(Request $request)
    {
        $request->validate([
            'course_id'     => 'required|integer',
            'assignment_id' => 'required|integer',
        ]);
        try {
            $submissions = $this->submissionService->getSubmissions(
                $request->course_id,
                $request->assignment_id
            );
            return response()->json($submissions);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to fetch submissions',
                'message' => $e->getMessage()
            ], 500);
        }
    }
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

            response()->download($zipPath)->deleteFileAfterSend(true);
            return $this->successResponse(null, 'Submissions downloaded successfully.');
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to download submissions',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
