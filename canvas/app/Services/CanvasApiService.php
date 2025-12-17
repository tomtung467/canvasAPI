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
    public function 
}
