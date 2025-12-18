<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class CreateUser extends Command
{
    protected $signature = 'user:create {name} {password} {role=user} {--email=}';
    protected $description = 'Create a new user';

    public function handle()
    {
        $name = $this->argument('name');
        $password = $this->argument('password');
        $role = $this->argument('role');
        $email = $this->option('email');

        try {
            $user = User::create([
                'name' => $name,
                'email' => $email,
                'password' => Hash::make($password),
                'role' => $role,
            ]);

            $this->info("User '{$name}' created successfully with role '{$role}'");
            $this->info("User ID: {$user->id}");
            
            return 0;
        } catch (\Exception $e) {
            $this->error("Failed to create user: " . $e->getMessage());
            return 1;
        }
    }
}