<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class CreateUser extends Command
{
    protected $signature = 'user:create';
    protected $description = 'Create a new user and generate and API token';

    public function handle()
    {
        $this->info('Creating user...');

        $name = $this->ask('Name');
        $email = $this->ask('Email');
        $password = $this->secret('Password');

        $validator = Validator::make(['name' => $name, 'email' => $email, 'password' => $password], [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:8'
        ]);

        if ($validator->fails()) {
            $this->error('Validation failed');
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }
            return 1;
        }

        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
        ]);

        $token = $user->createToken('cli-token')->plainTextToken;

        $this->newLine();
        $this->info('User created successfully!');
        $this->line('ID: ' . $user->id);
        $this->line('Name: ' . $user->name);
        $this->line('Email: ' . $user->email);
        $this->newLine();
        $this->info('API Token:');
        $this->line($token);

        return 0;
    }
}
