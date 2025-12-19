<?php
namespace App\Services\Canvas;
use App\Models\CourseCache;
class CanvasCourseService extends CanvasBaseService
{
    /**
     * Lấy course theo ID từ Canvas và lưu vào cache
     */
    public function getCourse(int $courseId, bool $forceRefresh = false)
    {
        // Kiểm tra cache nếu không force refresh
        if (!$forceRefresh) {
            $cached = CourseCache::where('canvas_course_id', $courseId)->first();
            if ($cached) {
                $this->logAction('FETCH_COURSE_CACHED', 'course', $courseId);
                return $cached;
            }
        }

        // Gọi API Canvas
        $courseData = $this->request('get', "/api/v1/courses/{$courseId}");

        // Lưu vào cache
        $course = CourseCache::updateOrCreate(
            ['canvas_course_id' => $courseId],
            [
                'course_name' => $courseData['name'] ?? '',
                'course_code' => $courseData['course_code'] ?? null,
                'last_synced_at' => now(),
            ]
        );

        $this->logAction(
            'FETCH_COURSE',
            'course',
            $courseId,
            null,
            'success'
        );

        return $course;
    }

    /**
     * Lấy tất cả courses của user từ Canvas
     */
    public function getAllCourses(bool $forceRefresh = false)
    {
        // Nếu không force refresh và đã có cache, trả về cache
        if (!$forceRefresh && CourseCache::count() > 0) {
            $this->logAction('FETCH_ALL_COURSES_CACHED', 'course');
            return CourseCache::all();
        }

        // Gọi API Canvas để lấy tất cả courses
        $coursesData = $this->request('get', '/api/v1/courses', [
            'per_page' => 100
        ]);

        // Lưu vào cache
        foreach ($coursesData as $courseData) {
            CourseCache::updateOrCreate(
                ['canvas_course_id' => $courseData['id']],
                [
                    'course_name' => $courseData['name'] ?? '',
                    'course_code' => $courseData['course_code'] ?? null,
                    'last_synced_at' => now(),
                ]
            );
        }

        $this->logAction(
            'FETCH_ALL_COURSES',
            'course',
            null,
            ['count' => count($coursesData)],
            'success'
        );

        return CourseCache::all();
    }
}
