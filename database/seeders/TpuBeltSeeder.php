<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\TpuBelt;
use Illuminate\Support\Facades\File;

class TpuBeltSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('TPU belt seeder ready - use JSON import functionality in the frontend to add data');
        $this->command->info('No sample data seeded by default');
    }
}