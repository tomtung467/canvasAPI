<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Services\CanvasApiService;
use Illuminate\Support\Facades\Http;

class CanvasController extends Controller
{
    public function getCourses($courseId, CanvasApiService $canvas)
    {
        $courses = $canvas->getCourses($courseId);
        return response()->json($courses);
    }

    public function getUser($userId, CanvasApiService $canvas)
    {
        $user = $canvas->getUser($userId);
        return response()->json($user);
    }
    public function getScores($courseId, $assignmentId, CanvasApiService $canvas)
    {
        $scores = $canvas->getScores($courseId, $assignmentId);
        return response()->json($scores);
    }
    public function searchUsers(Request $request, CanvasApiService $canvas)
    {
        // Lấy giá trị từ tham số ?name=... trên URL
        $searchTerm = $request->input('name');
        // Gọi service
        $users = $canvas->searchUsers($searchTerm);
        return response()->json($users);
    }
    public function submitAssignment(Request $request, CanvasApiService $canvas)
    {
        $courseId = $request->input('course_id');
        $assignmentId = $request->input('assignment_id');
        $files = $request->file('file');
        $request->validate([
            'course_id'     => 'required',
            'assignment_id' => 'required',
            'file'          => 'required|array',
            'file.*' =>'file'
        ]);

        // The service already returns a JSON response, so return it directly
        return $canvas->submitAssignment($courseId, $assignmentId, $files);
    }
    public

}
