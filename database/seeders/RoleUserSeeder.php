<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RoleUserSeeder extends Seeder
{
    public function run(): void
    {
        $assignments = [
            ['user_id' => 1, 'role_id' => 1],
            ['user_id' => 1, 'role_id' => 4],
            ['user_id' => 2, 'role_id' => 1],
            ['user_id' => 2, 'role_id' => 4],
            ['user_id' => 3, 'role_id' => 2],
            ['user_id' => 4, 'role_id' => 2],
            ['user_id' => 5, 'role_id' => 2],
            ['user_id' => 5, 'role_id' => 5],
            ['user_id' => 6, 'role_id' => 3],
            ['user_id' => 7, 'role_id' => 1],
            ['user_id' => 8, 'role_id' => 2],
            ['user_id' => 8, 'role_id' => 5],
            ['user_id' => 8, 'role_id' => 6],
        ];

        foreach ($assignments as $row) {
            DB::table('role_user')->updateOrInsert(
                ['user_id' => $row['user_id'], 'role_id' => $row['role_id']],
                array_merge($row, ['created_at' => now(), 'updated_at' => now()])
            );
        }
    }
}
