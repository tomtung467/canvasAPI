<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CanvasApiService
{
    protected $baseUrl;
    protected $token;

    public function __construct()
    {
        $this->baseUrl = config('services.canvas.url');
        $this->token = config('services.canvas.token');
    }

    public function request($method, $endpoint, $params = [])
    {
        try {
            $response = Http::withToken($this->token)
                ->$method($this->baseUrl . $endpoint, $params);
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error('Canvas connection error', ['endpoint' => $endpoint, 'message' => $e->getMessage()]);
            throw $e;
        }

        return $response->json();
    }

    /**
     * Quick check to validate that the configured base URL and token work
     * Returns array with 'ok' => bool and 'detail' => response or error
     */
    public function validateConnection()
    {
        try {
            $resp = Http::withToken($this->token)
                ->get(rtrim($this->baseUrl, '/') . '/api/v1/users/self/profile');
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error('Canvas validateConnection failed', ['message' => $e->getMessage()]);
            return ['ok' => false, 'error' => 'Connection failed', 'detail' => $e->getMessage()];
        }

        if ($resp->successful()) {
            return ['ok' => true, 'detail' => $resp->json()];
        }

        return ['ok' => false, 'error' => 'Invalid response', 'status' => $resp->status(), 'detail' => $resp->json()];
    }

    /**
     * Check that both the course and assignment exist and are accessible by the token
     */
    public function checkCourseAssignmentExists($courseId, $assignmentId)
    {
        $base = rtrim($this->baseUrl, '/');
        try {
            $courseResp = Http::withToken($this->token)
                ->get("{$base}/api/v1/courses/{$courseId}");
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error('Connection error checking course', ['message' => $e->getMessage(), 'course' => $courseId]);
            return ['ok' => false, 'error' => 'connection', 'detail' => $e->getMessage()];
        }

        if ($courseResp->status() === 404) {
            return ['ok' => false, 'error' => 'course_not_found', 'status' => 404, 'detail' => $courseResp->json()];
        }

        if (!$courseResp->successful()) {
            return ['ok' => false, 'error' => 'course_error', 'status' => $courseResp->status(), 'detail' => $courseResp->json()];
        }

        try {
            $assignmentResp = Http::withToken($this->token)
                ->get("{$base}/api/v1/courses/{$courseId}/assignments/{$assignmentId}");
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error('Connection error checking assignment', ['message' => $e->getMessage(), 'course' => $courseId, 'assignment' => $assignmentId]);
            return ['ok' => false, 'error' => 'connection', 'detail' => $e->getMessage()];
        }

        if ($assignmentResp->status() === 404) {
            return ['ok' => false, 'error' => 'assignment_not_found', 'status' => 404, 'detail' => $assignmentResp->json()];
        }

        if (!$assignmentResp->successful()) {
            return ['ok' => false, 'error' => 'assignment_error', 'status' => $assignmentResp->status(), 'detail' => $assignmentResp->json()];
        }

        return ['ok' => true, 'course' => $courseResp->json(), 'assignment' => $assignmentResp->json()];
    }

    // Lấy danh sách course
    public function getCourses($id)
    {
        return $this->request('get', '/api/v1/courses/' . $id );
    }
    public function getScores($courseId,$assignmentId)
    {
        return $this->request('get', '/api/v1/courses/' . $courseId . '/assignments/' . $assignmentId);
    }
    // Lấy user theo ID
    public function getUser($id)
    {
        return $this->request('get', '/api/v1/users/' . $id);
    }
    public function searchUsers($name)
    {
        // Tham số thứ 3 là mảng chứa dữ liệu tìm kiếm
        return $this->request('get', '/api/v1/accounts/self/users', ['search_term' => $name]);
    }
    public function submitAssignment($courseId, $assignmentId, $files)
    {
        $token = $this->token;
        $domain = $this->baseUrl;
        /* =========================
         * STEP 1: Request upload URL
         * ========================= */
          $fileIds = [];

    foreach ($files as $file) {

        // STEP 1: request upload URL
        $uploadRequest = Http::withToken($token)->post(
            "$domain/api/v1/courses/$courseId/assignments/$assignmentId/submissions/self/files",
            [
                'name' => $file->getClientOriginalName(),
                'size' => $file->getSize(),
            ]
        );

        if (!$uploadRequest->successful()) {
            return response()->json([
                'error' => 'Cannot request upload URL',
                'detail' => $uploadRequest->json()
            ], 400);
        }

        $uploadData = $uploadRequest->json();

        // STEP 2: upload file
        $uploadResponse = Http::attach(
            'file',
            file_get_contents($file->getRealPath()),
            $file->getClientOriginalName()
        )->post($uploadData['upload_url'], $uploadData['upload_params']);

        if (!$uploadResponse->successful()) {
            return response()->json([
                'error' => 'Upload file failed',
                'detail' => $uploadResponse->json()
            ], 400);
        }

        $fileIds[] = $uploadResponse->json()['id'];
    }

    // STEP 3: submit assignment
    $submit = Http::withToken($token)->post(
        "$domain/api/v1/courses/$courseId/assignments/$assignmentId/submissions",
        [
            'submission' => [
                'submission_type' => 'online_upload',
                'file_ids' => $fileIds
            ]
        ]
    );
    return response()->json([
        'message'  => 'Submit files successfully',
        'files'    => count($fileIds),
        'file_ids' => $fileIds,
        //'result'   => $submit->json()
    ]);
    }
    public function getAssignments($courseId)
    {
        return $this->request('get', '/api/v1/courses/' . $courseId . '/assignments' );
    }

    /**
     * Download all student submissions for an assignment as a zip file
     */
    public function downloadAssignmentSubmissions($courseId, $assignmentId)
    {
        $domain = $this->baseUrl;
        $token = $this->token;

        // Get all submissions for the assignment
        try {
            $submissionsResponse = Http::withToken($token)
                ->timeout(60)
                ->accept('application/json')
                ->withHeaders([
                    'Accept-Encoding' => 'gzip, deflate'
                ])
                ->get(
                    "$domain/api/v1/courses/$courseId/assignments/$assignmentId/submissions",
                    ['include' => ['user', 'submission_history']]
                );

            if (!$submissionsResponse->successful()) {
                return response()->json([
                    'error' => 'Cannot get submissions',
                    'status' => $submissionsResponse->status(),
                    'detail' => $submissionsResponse->json()
                ], 400);
            }

            $submissions = $submissionsResponse->json();

            // Check if there are any submissions with attachments
            $hasAttachments = false;
            foreach ($submissions as $submission) {
                if (isset($submission['attachments']) && !empty($submission['attachments'])) {
                    $hasAttachments = true;
                    break;
                }
            }

            if (!$hasAttachments) {
                return response()->json([
                    'message' => 'No submissions with attachments found for this assignment',
                    'total_submissions' => count($submissions)
                ], 404);
            }

            // Create a temporary directory to store downloaded files
            $tempDir = storage_path('app/temp/submissions_' . $assignmentId . '_' . time());
            if (!file_exists($tempDir)) {
                mkdir($tempDir, 0755, true);
            }

            $downloadedFiles = [];
            $failedDownloads = [];

            // Download each submission's attachments
            foreach ($submissions as $submission) {
                if (!isset($submission['attachments']) || empty($submission['attachments'])) {
                    continue;
                }

                $userId = $submission['user_id'] ?? 'unknown';
                $userName = $submission['user']['name'] ?? 'Unknown User';

                // Sanitize username for folder name
                $safeUserName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $userName);
                $userDir = $tempDir . '/' . $userId . '_' . $safeUserName;

                if (!file_exists($userDir)) {
                    mkdir($userDir, 0755, true);
                }

                foreach ($submission['attachments'] as $attachment) {
                    try {
                        $fileUrl = $attachment['url'];
                        $fileName = $attachment['filename'];

                        // Download the file with proper options
                        $fileContent = Http::withToken($token)
                            ->timeout(120)
                            ->withOptions([
                                'decode_content' => true,
                                'verify' => false
                            ])
                            ->get($fileUrl);

                        if ($fileContent->successful()) {
                            $filePath = $userDir . '/' . $fileName;
                            file_put_contents($filePath, $fileContent->body());
                            $downloadedFiles[] = $filePath;
                        } else {
                            $failedDownloads[] = [
                                'user' => $userName,
                                'file' => $fileName,
                                'status' => $fileContent->status()
                            ];
                        }
                    } catch (\Exception $e) {
                        Log::error('Error downloading submission file', [
                            'user_id' => $userId,
                            'file' => $attachment['filename'] ?? 'unknown',
                            'error' => $e->getMessage()
                        ]);
                        $failedDownloads[] = [
                            'user' => $userName,
                            'file' => $attachment['filename'] ?? 'unknown',
                            'error' => $e->getMessage()
                        ];
                    }
                }
            }

            // Check if any files were downloaded
            if (empty($downloadedFiles)) {
                $this->deleteDirectory($tempDir);
                return response()->json([
                    'error' => 'Failed to download any submission files',
                    'failed_downloads' => $failedDownloads
                ], 500);
            }

            // Create zip file
            $zipFileName = "assignment_{$assignmentId}_submissions_" . date('Y-m-d_His') . ".zip";
            $zipPath = storage_path('app/temp/' . $zipFileName);

            $zip = new \ZipArchive();
            if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
                $this->deleteDirectory($tempDir);
                return response()->json(['error' => 'Cannot create zip file'], 500);
            }

            // Add files to zip
            foreach ($downloadedFiles as $file) {
                $relativePath = str_replace($tempDir . '/', '', $file);
                $zip->addFile($file, $relativePath);
            }

            $zip->close();

            // Clean up temporary files
            $this->deleteDirectory($tempDir);

            // Log summary
            Log::info('Assignment submissions downloaded', [
                'assignment_id' => $assignmentId,
                'total_files' => count($downloadedFiles),
                'failed_downloads' => count($failedDownloads)
            ]);

            return response()->download($zipPath, $zipFileName, [
                'Content-Type' => 'application/zip',
                'Content-Disposition' => 'attachment; filename="' . $zipFileName . '"'
            ])->deleteFileAfterSend(true);

        } catch (\Exception $e) {
            Log::error('Error in downloadAssignmentSubmissions', [
                'course_id' => $courseId,
                'assignment_id' => $assignmentId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => 'An error occurred while processing submissions',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Recursively delete a directory
     */
    private function deleteDirectory($dir)
    {
        if (!file_exists($dir)) {
            return;
        }

        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            is_dir($path) ? $this->deleteDirectory($path) : unlink($path);
        }
        rmdir($dir);
    }
}
