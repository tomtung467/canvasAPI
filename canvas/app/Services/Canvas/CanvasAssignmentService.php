<?php
namespace App\Services\Canvas;
use App\Models\AssignmentCache;
use App\Services\Canvas\CanvasBaseService;
use Illuminate\Support\Facades\Http;

class CanvasAssignmentService extends CanvasBaseService
{
    /**
     * Lấy assignments từ cache hoặc Canvas
     */
    public function getAssignments(int $courseId, bool $forceRefresh = false)
    {
        // Kiểm tra cache nếu không force refresh
        if (
            !$forceRefresh &&
            AssignmentCache::where('canvas_course_id', $courseId)->exists()
        ) {
            $this->logAction('FETCH_ASSIGNMENTS_CACHED', 'course', $courseId);
            return AssignmentCache::where('canvas_course_id', $courseId)->get();
        }

        // Gọi API Canvas
        $assignments = $this->request(
            'get',
            "/api/v1/courses/{$courseId}/assignments",
            ['per_page' => 100]
        );

        // Lưu vào cache
        foreach ($assignments as $assignment) {
            AssignmentCache::updateOrCreate(
                ['canvas_assignment_id' => $assignment['id']],
                [
                    'canvas_course_id' => $courseId,
                    'name' => $assignment['name'],
                    'due_at' => $assignment['due_at'],
                    'last_synced_at' => now(),
                ]
            );
        }

        $this->logAction(
            'FETCH_ASSIGNMENTS',
            'course',
            $courseId,
            ['count' => count($assignments)],
            'success'
        );

        return AssignmentCache::where('canvas_course_id', $courseId)->get();
    }

    /**
     * Lấy điểm của một assignment (scores)
     */
    public function getScores(int $courseId, int $assignmentId)
    {
        $scores = $this->request(
            'get',
            "/api/v1/courses/{$courseId}/assignments/{$assignmentId}"
        );
        $data = collect($scores)->only([
            'id',
            'name',
            'points_possible',
            'due_at',
            'submission_types',
            'allowed_extensions',
            'turnitin_enabled',
        ])->toArray();
        $this->logAction(
            'FETCH_SCORES',
            'assignment',
            $assignmentId,
            ['course_id' => $courseId],
            'success'
        );
        return $data;
    }

    /**
     * Submit assignment với files
     */
    public function submitAssignment(int $courseId, int $assignmentId, array $files)
    {
        $fileIds = [];

        // Upload từng file
        foreach ($files as $file) {
            // Step 1: Request upload URL
            $uploadRequest = Http::withToken($this->token)->post(
                "{$this->baseUrl}/api/v1/courses/{$courseId}/assignments/{$assignmentId}/submissions/self/files",
                [
                    'name' => $file->getClientOriginalName(),
                    'size' => $file->getSize(),
                ]
            );

            if (!$uploadRequest->successful()) {
                $this->logAction(
                    'SUBMIT_ASSIGNMENT',
                    'assignment',
                    $assignmentId,
                    [
                        'course_id' => $courseId,
                        'error' => 'Cannot request upload URL'
                    ],
                    'failed'
                );

                return [
                    'success' => false,
                    'error' => 'Cannot request upload URL',
                    'detail' => $uploadRequest->json()
                ];
            }

            $uploadData = $uploadRequest->json();

            // Step 2: Upload file
            $uploadResponse = Http::attach(
                'file',
                file_get_contents($file->getRealPath()),
                $file->getClientOriginalName()
            )->post($uploadData['upload_url'], $uploadData['upload_params']);

            if (!$uploadResponse->successful()) {
                $this->logAction(
                    'SUBMIT_ASSIGNMENT',
                    'assignment',
                    $assignmentId,
                    [
                        'course_id' => $courseId,
                        'error' => 'Upload file failed'
                    ],
                    'failed'
                );

                return [
                    'success' => false,
                    'error' => 'Upload file failed',
                    'detail' => $uploadResponse->json()
                ];
            }

            $fileIds[] = $uploadResponse->json()['id'];
        }

        // Step 3: Submit assignment
        $submit = Http::withToken($this->token)->post(
            "{$this->baseUrl}/api/v1/courses/{$courseId}/assignments/{$assignmentId}/submissions",
            [
                'submission' => [
                    'submission_type' => 'online_upload',
                    'file_ids' => $fileIds,
                ]
            ]
        );

        if (!$submit->successful()) {
            $this->logAction(
                'SUBMIT_ASSIGNMENT',
                'assignment',
                $assignmentId,
                [
                    'course_id' => $courseId,
                    'file_ids' => $fileIds,
                    'error' => 'Submit failed'
                ],
                'failed'
            );

            return [
                'success' => false,
                'error' => 'Submit assignment failed',
                'detail' => $submit->json()
            ];
        }

        $this->logAction(
            'SUBMIT_ASSIGNMENT',
            'assignment',
            $assignmentId,
            [
                'course_id' => $courseId,
                'file_count' => count($fileIds),
                'file_ids' => $fileIds
            ],
            'success'
        );

        return [
            'success' => true,
            'message' => 'Submit files successfully',
            'files' => count($fileIds),
            'file_ids' => $fileIds,
        ];
    }
}
