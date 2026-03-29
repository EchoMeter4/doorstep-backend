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
        $this->call([
            OrganizationSeeder::class,
            UserTypeSeeder::class,
            ZoneSeeder::class,
            RoleSeeder::class,
            RoleZoneSeeder::class,
            UserSeeder::class,
            RoleUserSeeder::class,
            CredentialSeeder::class,
            VehicleSeeder::class,
            VisitorSeeder::class,
            PassSeeder::class,
            AccessLogSeeder::class,
        ]);

        // Test user (gets ID 9, after the 8 spec users)
        User::factory()->create([
            'name'             => 'Test User',
            'middle_name'      => 'test',
            'first_last_name'  => 'test',
            'second_last_name' => 'test',
            'email'            => 'test@test.com',
            'password'         => Hash::make('test'),
            'enabled'          => true,
        ]);
    }
}
