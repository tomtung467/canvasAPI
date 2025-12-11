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

    public function getUser($userId, CanvasApiService $canvas)
    {
        $user = $canvas->getUser($userId);
        return response()->json($user);
    }

}
