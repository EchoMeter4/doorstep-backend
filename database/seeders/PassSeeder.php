<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PassSeeder extends Seeder
{
    public function run(): void
    {
        // All datetimes are UTC-6 (America/Mexico_City) → stored as UTC (+6 hours)
        $passes = [
            ['id' => 101, 'visitor_id' => 1, 'valid_from' => '2026-03-10 15:00:00', 'valid_until' => '2026-03-11 00:00:00', 'zones' => [1, 2]],
            ['id' => 102, 'visitor_id' => 1, 'valid_from' => '2026-03-15 14:00:00', 'valid_until' => '2026-03-17 02:00:00', 'zones' => [5]],
            ['id' => 201, 'visitor_id' => 2, 'valid_from' => '2026-03-20 16:00:00', 'valid_until' => '2026-03-20 23:00:00', 'zones' => [3]],
            ['id' => 401, 'visitor_id' => 4, 'valid_from' => '2026-02-01 15:00:00', 'valid_until' => '2026-02-28 00:00:00', 'zones' => [1, 6]],
        ];

        foreach ($passes as $pass) {
            DB::table('passes')->updateOrInsert(
                ['id' => $pass['id']],
                [
                    'visitor_id' => $pass['visitor_id'],
                    'created_by' => null,
                    'valid_from' => $pass['valid_from'],
                    'valid_until'=> $pass['valid_until'],
                    'status'     => 'activo',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );

            foreach ($pass['zones'] as $zoneId) {
                DB::table('pass_zone')->updateOrInsert(
                    ['pass_id' => $pass['id'], 'zone_id' => $zoneId],
                    [
                        'pass_id'    => $pass['id'],
                        'zone_id'    => $zoneId,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
            }
        }
    }
}
