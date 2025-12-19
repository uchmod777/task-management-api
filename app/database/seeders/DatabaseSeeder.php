<?php

namespace Database\Seeders;

use App\Models\Task;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::factory(10)->create()->each(function ($user) {
            Task::factory()->count(rand(15, 25))->create([
                'user_id' => $user->id
            ]);
        });

        $testUser = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        Task::factory(20)->create([
            'user_id' => $testUser->id
        ]);

        $this->command->info('Created 10 test users with 15-25 tasks per user');
        $this->command->info('Test login: test@example.com / password');
    }
}
