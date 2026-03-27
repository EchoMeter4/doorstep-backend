<?php

namespace Database\Seeders;

use Hash;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'Test User',
            'middle_name' => 'test',
            'first_last_name' => 'test',
            'second_last_name' => 'test',
            'email' => 'test@test.com',
            'password' => Hash::make('test')
        ]);
    }
}
