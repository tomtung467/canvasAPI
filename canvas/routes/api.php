<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CanvasController;
/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });

Route::group([
    'middleware' => 'api',
    'prefix' => 'v1'
], function ($router) {

    // ==================== Authentication Routes ====================
    Route::post('login', [App\Http\Controllers\AuthController::class, 'login']);
    Route::post('logout', [App\Http\Controllers\AuthController::class, 'logout']);
    Route::post('refresh', [App\Http\Controllers\AuthController::class, 'refresh']);
    Route::get('profile', [App\Http\Controllers\AuthController::class, 'profile']);

    // ==================== Canvas Users Routes ====================
    Route::prefix('users')->group(function () {
        // Đặt route cụ thể trước để tránh conflict với /{userId}
        Route::post('/search', [CanvasController::class, 'searchUsers']); // cần quyền admin
        Route::get('/{userId}', [CanvasController::class, 'getUser']);
    });

    // ==================== Canvas Courses Routes ====================
    Route::prefix('courses')->group(function () {
        // Course routes
        Route::get('/{courseId}', [CanvasController::class, 'getCourses']);

        // Assignment routes - đặt routes cụ thể trước
        Route::post('/assignments/submit', [CanvasController::class, 'submitAssignment']);
        Route::post('/assignments/download-zip', [CanvasController::class, 'downloadAssignmentZip']);

        // Course assignments routes
        Route::get('/{courseId}/assignments', [CanvasController::class, 'getAssignments']);
        Route::get('/{courseId}/assignments/{assignmentId}', [CanvasController::class, 'getScores']);
    });
});
