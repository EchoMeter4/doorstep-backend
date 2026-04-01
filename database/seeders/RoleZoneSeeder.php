<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RoleZoneSeeder extends Seeder
{
    public function run(): void
    {
        // role_zone defines RESTRICTED zones per role
        $restrictions = [
            ['role_id' => 2, 'zone_id' => 6],
            ['role_id' => 2, 'zone_id' => 1],
            ['role_id' => 2, 'zone_id' => 5],

            ['role_id' => 3, 'zone_id' => 6],
            ['role_id' => 3, 'zone_id' => 1],
            ['role_id' => 3, 'zone_id' => 2],
            ['role_id' => 3, 'zone_id' => 3],
            ['role_id' => 3, 'zone_id' => 4],

            ['role_id' => 5, 'zone_id' => 6],
            ['role_id' => 5, 'zone_id' => 3],
            ['role_id' => 5, 'zone_id' => 4],

            ['role_id' => 7, 'zone_id' => 1],
            ['role_id' => 7, 'zone_id' => 2],
            ['role_id' => 7, 'zone_id' => 3],
            ['role_id' => 7, 'zone_id' => 4],
            ['role_id' => 7, 'zone_id' => 5],
            ['role_id' => 7, 'zone_id' => 6],
        ];

        foreach ($restrictions as $row) {
            DB::table('role_zone')->updateOrInsert(
                ['role_id' => $row['role_id'], 'zone_id' => $row['zone_id']],
                array_merge($row, ['created_at' => now(), 'updated_at' => now()])
            );
        }
    }
}
