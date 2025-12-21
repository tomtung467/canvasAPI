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
    public function getUserCourses()
    {
        // Gọi API Canvas để lấy courses của user
        $coursesData = $this->request('get', "/api/v1/users/self/courses", [
            'per_page' => 100
        ]);
        $data = collect($coursesData)->map(function ($item) {
            return [
                'id' => $item['id'],
                'name' => $item['name'],
                'course_code' => $item['course_code'],
                'start_at' => $item['start_at'],
                'end_at' => $item['end_at'],
                'enrollments' => $item['enrollments'] ?? null,
            ];
        })->toArray();
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
            'FETCH_USER_COURSES',
            'user',
            null,
            ['count' => count($coursesData)],
            'success'
        );;
        return $data;
    }
    public function getUsersInCourse(int $courseId)
    {
        // Gọi API Canvas để lấy users trong course
        $usersData = $this->request('get', "/api/v1/courses/{$courseId}/users", [
            'per_page' => 100
        ]);

        $this->logAction(
            'FETCH_USERS_IN_COURSE',
            'course',
            $courseId,
            ['count' => count($usersData)],
            'success'
        );

        return $usersData;
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
    public function createCourse(string $courseName, ?string $courseCode = null)
    {
        $courseData = [
            'course' => [
                'name' => $courseName,
            ]
        ];
        if ($courseCode) {
            $courseData['course']['course_code'] = $courseCode;
        }

        $newCourse = $this->request('post', '/api/v1/accounts/self/courses', $courseData);

        $this->logAction(
            'CREATE_COURSE',
            'course',
            $newCourse['id'] ?? null,
            null,
            'success'
        );

        return $newCourse;
    }
}
