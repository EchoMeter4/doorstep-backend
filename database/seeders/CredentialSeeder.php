<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CredentialSeeder extends Seeder
{
    public function run(): void
    {
        $credentials = [
            ['id' => 1, 'credential_code' => '12111403', 'user_id' => 1, 'is_active' => true,  'issued_at' => '2025-01-10'],
            ['id' => 2, 'credential_code' => '100125002', 'user_id' => 2, 'is_active' => true,  'issued_at' => '2025-01-15'],
            ['id' => 3, 'credential_code' => '200201003', 'user_id' => 3, 'is_active' => true,  'issued_at' => '2025-02-01'],
            ['id' => 4, 'credential_code' => '100131004', 'user_id' => 4, 'is_active' => true,  'issued_at' => '2025-01-20'],
            ['id' => 5, 'credential_code' => '200205006', 'user_id' => 6, 'is_active' => false, 'issued_at' => '2025-03-05'],
            ['id' => 6, 'credential_code' => '300301007', 'user_id' => 7, 'is_active' => true,  'issued_at' => '2025-04-01'],
            ['id' => 7, 'credential_code' => '100142008', 'user_id' => 8, 'is_active' => true,  'issued_at' => '2025-01-18'],
        ];

        foreach ($credentials as $credential) {
            DB::table('credentials')->updateOrInsert(
                ['id' => $credential['id']],
                array_merge($credential, [
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );
        }
    }
}
