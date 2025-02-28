<?php
namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\Task;
use App\Models\TaskUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TaskController extends Controller
{
    // Show all tasks
    public function index(Request $request)
    {
        $tasks = Task::where('deleted_flag', 'N')
                    ->where('user_id', $request->user()->id)
                    ->get();

        return response()->json([
            'message' => $tasks->isEmpty() ? 'No tasks found' : 'Tasks retrieved successfully',
            'data' => $tasks
        ], $tasks->isEmpty() ? 404 : 200);
    }
    
    // Show pending tasks
    public function pendingTasks(Request $request)
    {
        $query = Task::where('completed_flag', 'N')
            ->where('deleted_flag', 'N');

        if ($request->has('user_id')) {
            $user = TaskUsers::find($request->user_id);
            if (!$user) {
                return response()->json(['message' => 'Invalid user ID'], 404);
            }

            // Check if the user is an admin
            if ($user->role_id != '1') { // Assuming 'role' field determines if user is admin
                $query->where('user_id', $request->user_id);
            }
        }

        $tasks = $query->orderBy('task_date', 'desc')->get();
        // Manually append full_name
        $tasks = $tasks->map(function ($task) {
            $user = TaskUsers::find($task->user_id); // Fetch user manually
            return [
                'id' => $task->id,
                'name' => $task->name,
                'user_id' => $task->user_id,
                'user_name' => $user ? "{$user->first_name} {$user->last_name}" : null,
                'task_date' => Carbon::parse($task->task_date)->format('d-m-Y H:i:s'),
                'created_at' => Carbon::parse($task->created_at)->format('d-m-Y H:i:s'),
                'completed_flag' => $task->completed_flag,
                'completed_at' => Carbon::parse($task->completed_date)->format('d-m-Y H:i:s'),
                'deleted_flag' => $task->deleted_flag
            ];
        });


        return response()->json([
            'message' => $tasks->isEmpty() ? 'No pending tasks found' : 'Pending tasks retrieved successfully',
            'data' => $tasks
        ], $tasks->isEmpty() ? 200 : 200);
    }


    // Show completed tasks
    public function completedTasks(Request $request)
    {
        $query = Task::where('completed_flag', 'Y')
            ->where('deleted_flag', 'N');

        if ($request->has('user_id')) {
            $user = TaskUsers::find($request->user_id);
            if (!$user) {
                return response()->json(['message' => 'Invalid user ID'], 404);
            }
            // Check if the user is an admin
            if ($user->role_id != '1') { // Assuming 'role' field determines admin status
                $query->where('user_id', $request->user_id);
            }
        }

        $tasks = $query->orderBy('completed_date', 'desc')->get();

        // Manually append full_name
        $tasks = $tasks->map(function ($task) {
            $user = TaskUsers::find($task->user_id); // Fetch user manually
            return [
                'id' => $task->id,
                'name' => $task->name,
                'user_id' => $task->user_id,
                'user_name' => $user ? "{$user->first_name} {$user->last_name}" : null,
                'task_date' => Carbon::parse($task->task_date)->format('d-m-Y H:i:s'),
                'created_at' => Carbon::parse($task->created_at)->format('d-m-Y H:i:s'),
                'completed_flag' => $task->completed_flag,
                'completed_at' => Carbon::parse($task->completed_date)->format('d-m-Y H:i:s'),
                'deleted_flag' => $task->deleted_flag
            ];
        });

        return response()->json([
            'message' => $tasks->isEmpty() ? 'No completed tasks found' : 'Completed tasks retrieved successfully',
            'data' => $tasks
        ], $tasks->isEmpty() ? 200 : 200);
    }


    // Store a new task
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'user_id' => 'required|exists:task_users,id',
            'date' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $task = Task::create([
            'name' => $request->name,
            'user_id' => $request->user_id ?? $request->user()->id,
            'task_date' => $request->date,
            'completed_flag' => 'N'
        ]);

        return response()->json([
            'message' => 'Task created successfully',
            'data' => $task,
            'statusCode' => '200'
        ], 200);
    }

    // Update an existing task
    public function update(Request $request, $id)
    {

        $validator = Validator::make($request->all(), [
            'name' => 'nullable|string|max:255',
            'completed_flag' => 'nullable|in:Y,N',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }


        $task = Task::find($id);
        
        if (!$task) {
            return response()->json(['message' => 'Task not found'], 404);
        }

        $task->update([
            'name' => $request->name,
            'completed_flag' => $request->completed_flag,
            'completed_date' => Carbon::now()->format('Y-m-d H:i:s'),
        ]);
        

        return response()->json([
            'message' => 'Task updated successfully',
            'data' => $task
        ], 200);
    }

    // Soft delete a task (mark as deleted)
    public function delete($id)
    {
        $task = Task::where('id', $id)->where('deleted_flag', 'N')->first();

        if (!$task) {
            return response()->json(['message' => 'Task not found or already deleted'], 404);
        }

        $task->update(['deleted_flag' => 'Y']);

        return response()->json(['message' => 'Task deleted successfully'], 200);
    }

    // Show a single task
    public function show($id)
    {
        $task = Task::find($id);

        if (!$task) {
            return response()->json(['message' => 'Task not found'], 404);
        }

        return response()->json([
            'message' => 'Task retrieved successfully',
            'data' => $task
        ], 200);
    }

    // Mark a task as completed
    public function completeTask($id)
    {
        $task = Task::where('id', $id)->where('deleted_flag', 'N')->first();

        if (!$task) {
            return response()->json(['message' => 'Task not found or already deleted'], 404);
        }

        if ($task->completed_flag == 'Y') {
            return response()->json(['message' => 'Task is already completed'], 200);
        }

        $task->update(['completed_flag' => 'Y']);

        return response()->json([
            'message' => 'Task marked as completed successfully',
            'data' => $task
        ], 200);
    }

}
