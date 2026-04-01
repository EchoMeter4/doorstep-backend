<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UserTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            ['id' => 1, 'name' => 'Administrador'],
            ['id' => 2, 'name' => 'Empleado'],
            ['id' => 3, 'name' => 'Seguridad'],
        ];

        foreach ($types as $type) {
            DB::table('user_types')->updateOrInsert(
                ['id' => $type['id']],
                array_merge($type, ['created_at' => now(), 'updated_at' => now()])
            );
        }
    }
}
