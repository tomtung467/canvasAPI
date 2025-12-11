<?php

namespace App\Http\Controllers;

use App\Services\CanvasApiService;

class CanvasController extends Controller
{
    public function getCourses(CanvasApiService $canvas)
    {
        $courses = $canvas->getCourses();
        return response()->json($courses);
    }
    public function getStudents($courseId, CanvasApiService $canvas)
{
    return $canvas->request('get', "/courses/$courseId/users", [
        'enrollment_type' => 'student'
    ]);
}

}
