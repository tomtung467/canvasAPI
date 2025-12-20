<?php

namespace App\Http\Controllers\API\Canvas;

use App\Http\Controllers\Controller;
use App\Services\Canvas\CanvasCourseService;
use Illuminate\Http\Request;
use App\Traits\ApiResponseTrait;

class CourseController extends Controller
{
    //'
    use ApiResponseTrait;
    protected CanvasCourseService $courseService;
    public function __construct(CanvasCourseService $courseService)
    {
        $this->courseService = $courseService;
        $this->middleware('auth:api');
    }
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
    public function GetUsersCourses()
    {
        try {
            $courses = $this->courseService->getUserCourses();
            return response()->json($courses);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to fetch user courses',
                'message' => $e->getMessage()
            ], 500);
        }
    }
    public function GetUsersInCourse(Request $request)
        {
            $request->validate([
                'course_id' => 'required|integer',
            ]);
        $courseId = $request->input('course_id');
            try {
            $users = $this->courseService->getUsersInCourse($courseId);
            return response()->json($users);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to fetch users in course',
                'message' => $e->getMessage()
            ], 500);
        }
    }
    public function CreateCourse(Request $request)
    {
        $request->validate([
            'course_name' => 'required|string',
            'course_code' => 'nullable|string',
        ]);

        $courseName = $request->input('course_name');
        $courseCode = $request->input('course_code');

        try {
            $newCourse = $this->courseService->createCourse($courseName, $courseCode);
            return response()->json($newCourse);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to create course',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
