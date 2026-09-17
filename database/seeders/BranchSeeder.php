<?php

namespace Database\Seeders;

use App\Models\Branch;
use Illuminate\Database\Seeder;

class BranchSeeder extends Seeder
{
    public function run(): void
    {
        Branch::firstOrCreate(
            ['code' => 'BALI'],
            ['name' => 'Bali', 'timezone' => 'Asia/Makassar', 'is_active' => true]
        );

        Branch::firstOrCreate(
            ['code' => 'JKT'],
            ['name' => 'Jakarta', 'timezone' => 'Asia/Jakarta', 'is_active' => true]
        );
    }
}
