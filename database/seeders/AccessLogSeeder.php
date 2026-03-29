<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AccessLogSeeder extends Seeder
{
    public function run(): void
    {
        // Zone name → ID mapping for log entries
        $zoneMap = [
            'Entrada Principal'  => 7,
            'Sala de Servidores' => 8,
            'Estacionamiento'    => 9,
            'Almacén'            => 10,
        ];

        // All timestamps UTC-6 (America/Mexico_City) → stored as UTC (+6h)
        $logs = [
            [
                'log_code'        => 'LOG-20260308-1001',
                'credential_type' => 'credential',
                'credential_value'=> 'A-00124',
                'zone'            => 'Entrada Principal',
                'is_authorized'   => true,
                'created_at'      => '2026-03-08 14:15:00',
                'users'           => [1],
            ],
            [
                'log_code'        => 'LOG-20260308-1002',
                'credential_type' => 'credential',
                'credential_value'=> 'Q-00201',
                'zone'            => 'Sala de Servidores',
                'is_authorized'   => false,
                'created_at'      => '2026-03-08 15:02:00',
                'users'           => [3],
            ],
            [
                'log_code'        => 'LOG-20260308-1003',
                'credential_type' => 'lpn',
                'credential_value'=> 'ABC-123',
                'zone'            => 'Estacionamiento',
                'is_authorized'   => true,
                'created_at'      => '2026-03-08 15:30:00',
                'users'           => [1, 2],
            ],
            [
                'log_code'        => 'LOG-20260308-1004',
                'credential_type' => 'credential',
                'credential_value'=> 'A-00125',
                'zone'            => 'Entrada Principal',
                'is_authorized'   => true,
                'created_at'      => '2026-03-08 16:05:00',
                'users'           => [2],
            ],
            [
                'log_code'        => 'LOG-20260308-1005',
                'credential_type' => 'lpn',
                'credential_value'=> 'DEF-456',
                'zone'            => 'Estacionamiento',
                'is_authorized'   => true,
                'created_at'      => '2026-03-08 17:20:00',
                'users'           => [2],
            ],
            [
                'log_code'        => 'LOG-20260308-1006',
                'credential_type' => 'credential',
                'credential_value'=> 'Q-00205',
                'zone'            => 'Almacén',
                'is_authorized'   => false,
                'created_at'      => '2026-03-08 18:45:00',
                'users'           => [6],
            ],
            [
                'log_code'        => 'LOG-20260307-1007',
                'credential_type' => 'credential',
                'credential_value'=> 'A-00131',
                'zone'            => 'Entrada Principal',
                'is_authorized'   => true,
                'created_at'      => '2026-03-07 14:00:00',
                'users'           => [4],
            ],
            [
                'log_code'        => 'LOG-20260307-1008',
                'credential_type' => 'lpn',
                'credential_value'=> 'MNO-345',
                'zone'            => 'Estacionamiento',
                'is_authorized'   => true,
                'created_at'      => '2026-03-07 15:15:00',
                'users'           => [5, 8],
            ],
            [
                'log_code'        => 'LOG-20260307-1009',
                'credential_type' => 'credential',
                'credential_value'=> 'A-00142',
                'zone'            => 'Sala de Servidores',
                'is_authorized'   => true,
                'created_at'      => '2026-03-07 19:50:00',
                'users'           => [8],
            ],
            [
                'log_code'        => 'LOG-20260307-1010',
                'credential_type' => 'lpn',
                'credential_value'=> 'XYZ-999',
                'zone'            => 'Estacionamiento',
                'is_authorized'   => false,
                'created_at'      => '2026-03-07 22:30:00',
                'users'           => [],
            ],
            [
                'log_code'        => 'LOG-20260306-1011',
                'credential_type' => 'credential',
                'credential_value'=> 'Q-00201',
                'zone'            => 'Almacén',
                'is_authorized'   => true,
                'created_at'      => '2026-03-06 14:55:00',
                'users'           => [3],
            ],
            [
                'log_code'        => 'LOG-20260306-1012',
                'credential_type' => 'credential',
                'credential_value'=> 'A-00124',
                'zone'            => 'Sala de Servidores',
                'is_authorized'   => true,
                'created_at'      => '2026-03-06 16:10:00',
                'users'           => [1],
            ],
            [
                'log_code'        => 'LOG-20260306-1013',
                'credential_type' => 'lpn',
                'credential_value'=> 'PQR-678',
                'zone'            => 'Estacionamiento',
                'is_authorized'   => false,
                'created_at'      => '2026-03-06 20:22:00',
                'users'           => [5],
            ],
            [
                'log_code'        => 'LOG-20260305-1014',
                'credential_type' => 'credential',
                'credential_value'=> 'B-00301',
                'zone'            => 'Entrada Principal',
                'is_authorized'   => true,
                'created_at'      => '2026-03-05 13:45:00',
                'users'           => [7],
            ],
            [
                'log_code'        => 'LOG-20260305-1015',
                'credential_type' => 'credential',
                'credential_value'=> 'Q-00205',
                'zone'            => 'Entrada Principal',
                'is_authorized'   => false,
                'created_at'      => '2026-03-05 17:00:00',
                'users'           => [6],
            ],
        ];

        foreach ($logs as $log) {
            $logId = DB::table('access_logs')->updateOrInsert(
                ['log_code' => $log['log_code']],
                [
                    'log_code'        => $log['log_code'],
                    'credential_type' => $log['credential_type'],
                    'credential_value'=> $log['credential_value'],
                    'zone_id'         => $zoneMap[$log['zone']],
                    'is_authorized'   => $log['is_authorized'],
                    'action_type'     => 'acceso',
                    'created_at'      => $log['created_at'],
                    'updated_at'      => $log['created_at'],
                ]
            );

            // Retrieve the inserted/updated row id
            $accessLogId = DB::table('access_logs')
                ->where('log_code', $log['log_code'])
                ->value('id');

            foreach ($log['users'] as $userId) {
                DB::table('log_user')->updateOrInsert(
                    ['access_log_id' => $accessLogId, 'user_id' => $userId],
                    [
                        'access_log_id' => $accessLogId,
                        'user_id'       => $userId,
                        'created_at'    => now(),
                        'updated_at'    => now(),
                    ]
                );
            }
        }
    }
}
