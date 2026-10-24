<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'admin@crm.test'],
            ['name' => 'Admin', 'password' => 'password', 'role' => 'admin', 'active' => true],
        );

        // sample sales agent; the demo data is assigned to Emma
        User::firstOrCreate(
            ['email' => 'emma@crm.test'],
            ['name' => 'Emma', 'password' => 'password', 'role' => 'agent', 'active' => true],
        );
    }
}
