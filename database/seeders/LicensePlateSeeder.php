<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LicensePlateSeeder extends Seeder
{
    public function run(): void
    {
        // id => [plate_number, user_id]
        $plates = [
            ['id' => 1, 'plate_number' => 'ABC-123', 'user_id' => 1],
            ['id' => 2, 'plate_number' => 'DEF-456', 'user_id' => 1],
            ['id' => 3, 'plate_number' => 'GHI-789', 'user_id' => 2],
            ['id' => 4, 'plate_number' => 'JKL-012', 'user_id' => 4],
            ['id' => 5, 'plate_number' => 'MNO-345', 'user_id' => 5],
            ['id' => 6, 'plate_number' => 'PQR-678', 'user_id' => 5],
            ['id' => 7, 'plate_number' => 'STU-901', 'user_id' => 7],
        ];

        foreach ($plates as $plate) {
            DB::table('license_plates')->updateOrInsert(
                ['id' => $plate['id']],
                [
                    'plate_number' => $plate['plate_number'],
                    'created_at'   => now(),
                    'updated_at'   => now(),
                ]
            );

            DB::table('license_plate_user')->updateOrInsert(
                ['license_plate_id' => $plate['id'], 'user_id' => $plate['user_id']],
                [
                    'license_plate_id' => $plate['id'],
                    'user_id'          => $plate['user_id'],
                    'created_at'       => now(),
                    'updated_at'       => now(),
                ]
            );
        }
    }
}
