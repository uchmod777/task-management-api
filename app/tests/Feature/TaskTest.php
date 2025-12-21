<?php

namespace Tests\Feature;

use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_create_task()
    {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/tasks', [
                'title' => 'New Task',
                'description' => 'Task description',
                'status' => 'pending',
                'due_date' => '2025-12-31',
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'title' => 'New Task',
                'status' => 'pending',
            ]);

        $this->assertDatabaseHas('tasks', [
            'title' => 'New Task',
            'user_id' => $user->id,
        ]);
    }

    public function test_user_can_list_their_tasks()
    {
        $user = User::factory()->create();
        Task::factory()->count(3)->create(['user_id' => $user->id]);

        $token = $user->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/tasks');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    public function test_user_can_view_single_task()
    {
        $user = User::factory()->create();
        $task = Task::factory()->create(['user_id' => $user->id]);

        $token = $user->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson("/api/tasks/{$task->id}");

        $response->assertStatus(200)
            ->assertJson([
                'id' => $task->id,
                'title' => $task->title,
            ]);
    }

    public function test_user_can_update_their_task()
    {
        $user = User::factory()->create();
        $task = Task::factory()->create(['user_id' => $user->id, 'status' => 'pending']);

        $token = $user->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->putJson("/api/tasks/{$task->id}", [
                'status' => 'completed',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'completed',
            ]);

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'status' => 'completed',
        ]);
    }

    public function test_user_can_delete_their_task()
    {
        $user = User::factory()->create();
        $task = Task::factory()->create(['user_id' => $user->id]);

        $token = $user->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->deleteJson("/api/tasks/{$task->id}");

        $response->assertStatus(204);  // Changed from 200

        $this->assertDatabaseMissing('tasks', [
            'id' => $task->id,
        ]);
    }

    public function test_user_cannot_view_other_users_task()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $task = Task::factory()->create(['user_id' => $user2->id]);

        $token = $user1->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson("/api/tasks/{$task->id}");

        $response->assertStatus(403);
    }

    public function test_assignee_can_view_assigned_task()
    {
        $owner = User::factory()->create();
        $assignee = User::factory()->create();
        $task = Task::factory()->create([
            'user_id' => $owner->id,
            'assigned_to' => $assignee->id,
        ]);

        $token = $assignee->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson("/api/tasks/{$task->id}");

        $response->assertStatus(200);
    }

    public function test_assignee_can_update_assigned_task()
    {
        $owner = User::factory()->create();
        $assignee = User::factory()->create();
        $task = Task::factory()->create([
            'user_id' => $owner->id,
            'assigned_to' => $assignee->id,
            'status' => 'pending',
        ]);

        $token = $assignee->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->putJson("/api/tasks/{$task->id}", [
                'status' => 'in-progress',
            ]);

        $response->assertStatus(200)
            ->assertJson(['status' => 'in-progress']);
    }

    public function test_user_can_filter_tasks_by_status()
    {
        $user = User::factory()->create();
        Task::factory()->create(['user_id' => $user->id, 'status' => 'pending']);
        Task::factory()->create(['user_id' => $user->id, 'status' => 'completed']);
        Task::factory()->create(['user_id' => $user->id, 'status' => 'completed']);

        $token = $user->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/tasks?status=completed');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    public function test_user_can_filter_tasks_by_date_range()
    {
        $user = User::factory()->create();
        Task::factory()->create(['user_id' => $user->id, 'due_date' => '2025-01-15']);
        Task::factory()->create(['user_id' => $user->id, 'due_date' => '2025-06-15']);
        Task::factory()->create(['user_id' => $user->id, 'due_date' => '2025-12-15']);

        $token = $user->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/tasks?due_after=2025-05-01&due_before=2025-12-31');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    public function test_user_can_search_tasks()
    {
        $user = User::factory()->create();
        Task::factory()->create(['user_id' => $user->id, 'title' => 'Fix login bug']);
        Task::factory()->create(['user_id' => $user->id, 'title' => 'Update documentation']);
        Task::factory()->create(['user_id' => $user->id, 'title' => 'Fix payment bug']);

        $token = $user->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/tasks?search=bug');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    public function test_user_can_sort_tasks()
    {
        $user = User::factory()->create();
        Task::factory()->create(['user_id' => $user->id, 'title' => 'Zebra task', 'created_at' => now()->subDays(1)]);
        Task::factory()->create(['user_id' => $user->id, 'title' => 'Apple task', 'created_at' => now()]);

        $token = $user->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/tasks?sort=title&order=asc');

        $response->assertStatus(200);

        $tasks = $response->json('data');
        $this->assertEquals('Apple task', $tasks[0]['title']);
        $this->assertEquals('Zebra task', $tasks[1]['title']);
    }

    public function test_invalid_status_returns_validation_error()
    {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/tasks?status=invalid');

        $response->assertStatus(422);
    }
}
