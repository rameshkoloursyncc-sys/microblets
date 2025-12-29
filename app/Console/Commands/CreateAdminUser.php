<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class CreateAdminUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'admin:create';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create admin user for production';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Check if admin user already exists
        $existingUser = User::where('name', 'koloursyncc11')->first();
        
        if ($existingUser) {
            $this->info('Admin user already exists!');
            $this->info('Name: ' . $existingUser->name);
            $this->info('Role: ' . $existingUser->role);
            return;
        }

        // Create admin user
        $user = User::create([
            'name' => 'koloursyncc11',
            'email' => null, // Email is nullable
            'password' => Hash::make('kolorsync1010'),
            'role' => 'admin',
        ]);

        $this->info('✅ Admin user created successfully!');
        $this->info('Username: koloursyncc');
        $this->info('Password: kolorsync1010');
        $this->info('Database Name: koloursyncc11');
        $this->info('Role: admin');
        
        return 0;
    }
}
