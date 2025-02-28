<?php

namespace App\Http\Controllers;

use App\Models\TaskUsers;
use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Laravel\Sanctum\PersonalAccessToken;

class AuthController extends Controller
{
    // Register
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => [
                        'required',
                        'email',
                        Rule::unique('task_users')->where(function ($query) {
                            return $query->where('deleted_flag', 'N'); // Consider only active users
                        }),
                    ],
            'password' => 'required|string|min:6|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $user = TaskUsers::create([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'role_id' => '2',
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'deleted_flag' => 'N',
        ]);
        return response()->json([
            'message' => 'User registered successfully',
            'user' => $user,
        ], 201);
    }

    // Login
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $user = TaskUsers::where('email', $request->email)->where('deleted_flag', 'N')->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Invalid email or password.'], 401);
        }
        return response()->json([
            'message' => 'Login successful',
            'user' => $user,
        ], 200);
    }

    // Get user list
    public function index()
    {
        $users = TaskUsers::where('role_id', '!=', '1') // Exclude admin users if needed
                    ->where('deleted_flag', 'N')
                    ->select('id', 'first_name', 'last_name', 'email', 'role_id', 'created_at')
                    ->orderBy('first_name', 'asc')
                    ->get();

        return response()->json([
            'message' => $users->isEmpty() ? 'No users found' : 'Users retrieved successfully',
            'data' => $users
        ], $users->isEmpty() ? 404 : 200);
    }

        // Delete a user
    public function deleteUser($id)
    {
        $user = TaskUsers::find($id);

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        // Check if the user has any assigned tasks
        $assignedTasks = Task::where('user_id', $id)
                            ->where('deleted_flag', 'N') // Only check active tasks
                            ->exists();

        if ($assignedTasks) {
            return response()->json(['message' => 'User cannot be deleted as tasks are assigned to them'], 400);
        }

        $user->deleted_flag = 'Y';
        $user->save();

        return response()->json(['message' => 'User deleted successfully'], 200);
    }


}
