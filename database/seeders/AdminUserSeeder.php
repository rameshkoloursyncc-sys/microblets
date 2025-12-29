<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Check if admin user already exists
        $existingUser = User::where('name', 'koloursyncc11')->first();
        
        if (!$existingUser) {
            User::create([
                'name' => 'koloursyncc11',
                'email' => null, // Email is nullable
                'password' => Hash::make('kolorsync1010'),
                'role' => 'admin',
            ]);
            
            $this->command->info('Admin user created successfully!');
        } else {
            $this->command->info('Admin user already exists.');
        }
    }
}
