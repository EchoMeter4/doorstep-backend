<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class VehicleSeeder extends Seeder
{
    public function run(): void
    {
        $vehicles = [
            ['id' => 1, 'plate_number' => 'ABC-123', 'make' => 'Toyota',    'model' => 'Corolla',  'year' => 2020, 'color' => 'Blanco',  'type' => 'sedan',  'user_id' => 1],
            ['id' => 2, 'plate_number' => 'DEF-456', 'make' => 'Honda',     'model' => 'Civic',    'year' => 2019, 'color' => 'Gris',    'type' => 'sedan',  'user_id' => 1],
            ['id' => 3, 'plate_number' => 'GHI-789', 'make' => 'Nissan',    'model' => 'Versa',    'year' => 2021, 'color' => 'Negro',   'type' => 'sedan',  'user_id' => 2],
            ['id' => 4, 'plate_number' => 'JKL-012', 'make' => 'Ford',      'model' => 'Explorer', 'year' => 2018, 'color' => 'Azul',    'type' => 'suv',    'user_id' => 4],
            ['id' => 5, 'plate_number' => 'MNO-345', 'make' => 'Chevrolet', 'model' => 'Spark',    'year' => 2022, 'color' => 'Rojo',    'type' => 'hatch',  'user_id' => 5],
            ['id' => 6, 'plate_number' => 'PQR-678', 'make' => 'Kia',       'model' => 'Sportage', 'year' => 2023, 'color' => 'Plateado','type' => 'suv',    'user_id' => 5],
            ['id' => 7, 'plate_number' => 'STU-901', 'make' => 'Volkswagen','model' => 'Jetta',    'year' => 2020, 'color' => 'Blanco',  'type' => 'sedan',  'user_id' => 7],
        ];

        foreach ($vehicles as $vehicle) {
            $userId = $vehicle['user_id'];
            unset($vehicle['user_id']);

            DB::table('vehicles')->updateOrInsert(
                ['id' => $vehicle['id']],
                array_merge($vehicle, ['created_at' => now(), 'updated_at' => now()])
            );

            DB::table('user_vehicle')->updateOrInsert(
                ['vehicle_id' => $vehicle['id'], 'user_id' => $userId],
                ['vehicle_id' => $vehicle['id'], 'user_id' => $userId, 'created_at' => now(), 'updated_at' => now()]
            );
        }
    }
}
