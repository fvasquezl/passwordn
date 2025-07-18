<?php

namespace Database\Seeders;

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
        User::factory()->create([
            'name' => 'Faustino Vasquez',
            'email' => 'fvasquez@local.com',
        ]);

        User::factory()->create([
            'name' => 'Sebastian Vasquez',
            'email' => 'svasquez@local.com',
        ]);

        User::factory(10)->create();
    }
}
