<?php

namespace App\Http\Controllers\API\Canvas;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\Canvas\CanvasUserService;
use App\Traits\ApiResponseTrait;

class UserController extends Controller
{
    //
    use ApiResponseTrait;
    protected CanvasUserService $userService;
    public function __construct(CanvasUserService $userService)
    {
        $this->userService = $userService;
        $this->middleware('auth:api');
    }
    public function getUser($userId)
    {
        try {
            $user = $this->userService->getUser($userId);
            return response()->json($user);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to fetch user',
                'message' => $e->getMessage()
            ], 500);
        }
    }
    public function GetUserEnrollments(Request $request)
    {
        $request->validate([
            'user_id' => 'required|integer',
        ]);
        try {
            $enrollments = $this->userService->getUserEnrollments(
                $request->user_id
            );
            return response()->json($enrollments);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to fetch user enrollments',
                'message' => $e->getMessage()
            ], 500);
        }
    }
    public function getCurrentUser()
    {
        try {
            $user = $this->userService->getCurrentUser();
            return response()->json($user);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to fetch current user',
                'message' => $e->getMessage()
            ], 500);
        }
    }
    public function GetUserProfile(request $request)
    {
        try {
            $user = $this->userService->GetUserProfile($request->user_id);
            return response()->json($user);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to fetch user profile',
                'message' => $e->getMessage()
            ], 500);
        }
    }
    public function searchUsers(Request $request)
    {
        try {
            $searchTerm = $request->input('name');

            if (empty($searchTerm)) {
                return response()->json([
                    'error' => 'Search term is required'
                ], 400);
            }

            $users = $this->userService->searchUsers($searchTerm);
            return response()->json($users);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to search users',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
