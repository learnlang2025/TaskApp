<?php
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\TaskController;

Route::post('register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login']);
Route::get('/users', [AuthController::class, 'index']);
Route::delete('/users/{id}', [AuthController::class, 'deleteUser']);

Route::get('tasks', [TaskController::class, 'index']);
Route::post('tasks/pending', [TaskController::class, 'pendingTasks']);
Route::post('tasks/completed', [TaskController::class, 'completedTasks']);
Route::post('tasks', [TaskController::class, 'store']);
Route::put('tasks/{id}', [TaskController::class, 'update']);
Route::delete('tasks/{id}', [TaskController::class, 'delete']);
Route::get('tasks/{id}', [TaskController::class, 'show']);
Route::put('/tasks/{id}/complete', [TaskController::class, 'completeTask']);
    
Route::middleware('admin')->group(function () {
    
});
