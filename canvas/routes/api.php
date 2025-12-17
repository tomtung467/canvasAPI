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
Route::prefix('v1')->group(function () {
Route::prefix('courses')->group(function () {
    Route::post('/submit-assignment', [CanvasController::class, 'submitAssignment']);
    Route::get('/{courseId}', [CanvasController::class, 'getCourses']);
    route::get('/{courseId}/assignments/{assignmentId}', [CanvasController::class, 'getScores']);
});
Route::prefix('users')->group(function () {
    route::post('/search', [CanvasController::class, 'searchUsers']);
    Route::get('/{userId}', [CanvasController::class, 'getUser']);
});
});
