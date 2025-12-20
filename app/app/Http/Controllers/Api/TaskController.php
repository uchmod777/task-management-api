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
        \Log::info('All request params: ', $request->all());
        \Log::info('Has search params: ' . ($request->has('search') ? 'true' : 'false'));
        \Log::info('Search value: ' . $request->search);

        $query = $request->user()->tasks();

        /*\Log::info('Query:' . $query->toSql());*/

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by date
        if ($request->has('due_after')) {
            $query->whereDate("due_date", ">=", $request->due_after);
        }

        if ($request->has('due_before')) {
            $query->whereDate("due_date", "<=", $request->due_after);
        }

        // Filter by title & description
        if ($request->has('search')) {
            $search = $request->search;
            \Log::info('Searching for: ' . $search);
            $query->where(function($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
            \Log::info('Full SQL: ' . $query->toSql());
            \Log::info('Bindings: ', $query->getBindings());
        }

        $tasks = $query->latest()->paginate(10);

        return response()->json($tasks, 200);
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
