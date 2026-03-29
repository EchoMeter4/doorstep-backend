<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            ['id' => 1, 'name' => 'Juan',   'first_last_name' => 'García',   'enabled' => true,  'user_type_id' => 1],
            ['id' => 2, 'name' => 'María',  'first_last_name' => 'López',    'enabled' => true,  'user_type_id' => 1],
            ['id' => 3, 'name' => 'Carlos', 'first_last_name' => 'Pérez',    'enabled' => true,  'user_type_id' => 2],
            ['id' => 4, 'name' => 'Ana',    'first_last_name' => 'Torres',   'enabled' => true,  'user_type_id' => 2],
            ['id' => 5, 'name' => 'Luis',   'first_last_name' => 'Ramírez',  'enabled' => true,  'user_type_id' => 2],
            ['id' => 6, 'name' => 'Pedro',  'first_last_name' => 'Sánchez',  'enabled' => false, 'user_type_id' => 2],
            ['id' => 7, 'name' => 'Sofia',  'first_last_name' => 'Mendoza',  'enabled' => true,  'user_type_id' => 3],
            ['id' => 8, 'name' => 'Elena',  'first_last_name' => 'Vásquez',  'enabled' => true,  'user_type_id' => 3],
        ];

        foreach ($users as $user) {
            $slug = strtolower(
                iconv('UTF-8', 'ASCII//TRANSLIT', $user['name']) . '.' .
                iconv('UTF-8', 'ASCII//TRANSLIT', $user['first_last_name'])
            );

            DB::table('users')->updateOrInsert(
                ['id' => $user['id']],
                [
                    'user_type_id'    => $user['user_type_id'],
                    'name'            => $user['name'],
                    'middle_name'     => '',
                    'first_last_name' => $user['first_last_name'],
                    'second_last_name'=> '',
                    'email'           => $slug . '@doorstep.test',
                    'password'        => Hash::make('test'),
                    'enabled'         => $user['enabled'],
                    'created_at'      => now(),
                    'updated_at'      => now(),
                ]
            );
        }
    }
}
