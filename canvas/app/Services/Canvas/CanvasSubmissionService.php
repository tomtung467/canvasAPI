<?php

namespace App\Services\Canvas;

use ZipArchive;
use Illuminate\Support\Facades\Http;
class CanvasSubmissionService extends CanvasBaseService
{
    /**
     * Download submissions và zip
     */
    function formatsize($size)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $power = $size > 0 ? floor(log($size, 1024)) : 0;
        return number_format($size / pow(1024, $power), 2, '.', ',') . ' ' . $units[$power];
    }
    public function getSubmissions(int $courseId, int $assignmentId)
    {
        $submissions = $this->request(
            'get',
            "/api/v1/courses/{$courseId}/assignments/{$assignmentId}/submissions",
            [
                'include[]' => ['attachments'],
                'per_page' => 100,
            ]
        );
        $data = collect($submissions)->map(function ($item) {
            return [
            'id' => $item['id'],
            'user_id' => $item['user_id'],
            'submission_type' => $item['submission_type'],
            'workflow_state' => $item['workflow_state'],
            'grade' => $item['grade'],
            'submitted_at' => $item['submitted_at'],
             'attachments' =>
                isset($item['attachments'])
                ? collect($item['attachments'])->map(function ($a) {
                    return [
                        'id' => $a['id'],
                        'filename' => $a['filename'],
                        'display_name' => $a['display_name'],
                        'url' => $a['url'],
                        'size' => $this->formatsize($a['size']),
                        'content_type' => $a['content-type'],
                        'created_at' => $a['created_at'],
                    ];
                })->toArray()
                : [],
            ];
        })->toArray();

        return $data;
    }
    public function downloadAssignmentSubmissions(
        int $courseId,
        int $assignmentId
    ): string {
        $submissions = $this->request(
            'get',
            "/api/v1/courses/{$courseId}/assignments/{$assignmentId}/submissions",
            [
                'include[]' => ['attachments'],
                'per_page' => 100,
            ]
        );

        // Lưu submissions theo cấu trúc: app/canvas/course_{courseId}/assignment_{assignmentId}/
        $root = storage_path("app/canvas/course_{$courseId}/assignment_{$assignmentId}");
        if (!is_dir($root)) {
            mkdir($root, 0777, true);
        }

        foreach ($submissions as $submission) {
            if (empty($submission['attachments'])) {
                continue;
            }

            $studentDir = "{$root}/user_{$submission['user_id']}";
            if (!is_dir($studentDir)) {
                mkdir($studentDir, 0777, true);
            }

            foreach ($submission['attachments'] as $file) {
                $this->downloadFile(
                    $file['url'],
                    "{$studentDir}/{$file['filename']}"
                );
            }
        }

        // Lưu file zip trong thư mục course tương ứng
        $zipPath = storage_path("app/canvas/course_{$courseId}/assignment_{$assignmentId}_submissions.zip");
        $this->zipFolder($root, $zipPath);

        $this->logAction(
            'DOWNLOAD_SUBMISSION_ZIP',
            'assignment',
            $assignmentId,
            [
                'course_id' => $courseId,
                'submission_count' => count($submissions),
                'zip_path' => $zipPath
            ],
            'success'
        );
        return $zipPath;
    }

    /**
     * Download file từ URL với token
     */
    protected function downloadFile(string $url, string $path): void
    {
        $response = Http::withToken($this->token)->get($url);

        if ($response->successful()) {
            file_put_contents($path, $response->body());
        }
    }

    /**
     * Zip thư mục thành file ZIP
     */
    protected function zipFolder(string $source, string $zipPath): void
    {
        $zip = new ZipArchive();
        $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($source),
            \RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($files as $file) {
            if ($file->isDir()) {
                continue;
            }

            $filePath = $file->getRealPath();
            $relativePath = str_replace($source . DIRECTORY_SEPARATOR, '', $filePath);

            $zip->addFile($filePath, $relativePath);
        }

        $zip->close();
    }
    public function getSelfSubmissions(int $courseId, int $assignmentId)
    {
        $self = $this->request(
            'get',
            "/api/v1/courses/{$courseId}/assignments/{$assignmentId}/submissions/self",
            [
                'include[]' => ['attachments'],
            ]
        );
        $data = collect($self)->only([
            'id',
            'user_id',
            'submission_type',
            'workflow_state',
            'grade',
            'submitted_at',
        ])->toArray();
        $data['attachments'] = isset($self['attachments'])
        ? collect($self['attachments'])->map(function ($a) {
            return [
                'id' => $a['id'],
                'filename' => $a['filename'],
                'display_name' => $a['display_name'],
                'url' => $a['url'],
                'size' => $this->formatsize($a['size']),
                'content_type' => $a['content-type'],
                'created_at' => $a['created_at'],
            ];
            })->toArray()
        : [];

        $this->logAction(
            'FETCH_SELF_SUBMISSIONS',
            'assignment',
            $assignmentId,
            null,
            'success'
        );
        return $data;
    }

}
