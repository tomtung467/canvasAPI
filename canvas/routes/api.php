<?php

use App\Http\Controllers\API\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\canvas\UserController;
use App\Http\Controllers\API\canvas\CourseController;
use App\Http\Controllers\API\canvas\AssignmentController;
USE App\Http\Controllers\API\canvas\SubmissionController;
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

    // ==================== Authentication Routes ======================
    Route::post('login', [AuthController::class, 'login']);
    Route::post('logout', [AuthController::class, 'logout']);
    Route::post('refresh', [AuthController::class, 'refresh']);
    Route::get('profile', [AuthController::class, 'profile']);

    // ==================== Canvas Users Routes =========================
    Route::prefix('users')->group(function () {
        // Đặt route cụ thể trước để tránh conflict với /{userId}
        Route::get('/current', [UserController::class, 'getCurrentUser']);
        Route::post('/search', [UserController::class, 'searchUsers']); // cần quyền admin
        Route::post('/enrollments', [UserController::class, 'GetUserEnrollments']);
        Route::get('/{userId}', [UserController::class, 'getUser']);
    });

    // ==================== Canvas Courses Routes ========================
    Route::prefix('courses')->group(function () {
        // Course routes
        Route::get('/self', [CourseController::class, 'GetUsersCourses']);
        Route::get('/{courseId}', [CourseController::class, 'getCourses']);
        route::post('/', [CourseController::class, 'CreateCourse']); // cần quyền admin
        Route::post('/users', [CourseController::class, 'GetUsersInCourse']);
    });
    // ==================== Canvas Assignments Routes ====================
    Route::prefix('assignments')->group(function () {
        Route::post('/submit', [AssignmentController::class, 'submitAssignment']);
        Route::post('/', [AssignmentController::class, 'getAssignments']);
        Route::post('/download-zip', [SubmissionController::class, 'downloadAssignmentZip']);
        Route::post('/{assignmentId}', [AssignmentController::class, 'getScores']);
    });
    // ==================== Canvas Submissions Routes ====================
    Route::prefix('submissions')->group(function () {

        Route::post('/', [SubmissionController::class, 'getSubmissions']);
    });

});
