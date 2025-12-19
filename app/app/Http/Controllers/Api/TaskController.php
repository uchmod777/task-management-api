<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Task;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $tasks = $request->user()->tasks()->latest()->paginate(10);

        return response()->json($tasks);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'nullable|in:pending, in-progress, completed',
            'due_date' => 'nullable|date',
        ]);

        $task = $request->user()->tasks()->create($request->all());

        return response()->json($task, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, Task $task)
    {
        if ($task->user_id !== $request->user()->id) {
            return response()->json(['error' => 'Unauthorized.'], 403);
        }

        return response()->json($task);
    }

    /**
     * Update the specified resource.
     */
    public function update(Request $request, Task $task) {
        // Check user validation on task
        if ($request->user()->id !== $task->user_id) {
            return response()->json(['error' => 'Unauthorized.'], 403);
        }

        // Validate the request
        $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'nullable|in:pending, in-progress, completed',
            'due_date' => 'nullable|date',
        ]);

        // Update the task
        $task->update($request->all());

        // Return the response
        return response()->json($task);
    }

    /**
     * Delete the specified resource.
     */
    public function destroy(Request $request, Task $task)
    {
        // Validate user
        if ($task->user_id !== $request->user()->id) {
            return response()->json(['error' => 'Unauthorized.'], 403);
        }

        // Delete task
        $task->delete();

        // Respond
        return response()->json(['message' => 'Task deleted successfully'], 204);
    }
}
