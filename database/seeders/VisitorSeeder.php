<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class VisitorSeeder extends Seeder
{
    public function run(): void
    {
        $visitors = [
            [
                'id'              => 1,
                'organization_id' => 1,
                'name'            => 'Roberto',
                'first_last_name' => 'Fuentes',
                'email'           => 'roberto.fuentes@tecnosoluciones.mx',
                'phone'           => '+52 55 1234 5678',
                'company'         => 'TecnoSoluciones MX',
                'enabled'         => true,
            ],
            [
                'id'              => 2,
                'organization_id' => 1,
                'name'            => 'Claudia',
                'first_last_name' => 'Herrera',
                'email'           => 'c.herrera@grupoinnovate.com',
                'phone'           => '+52 33 9876 5432',
                'company'         => 'Grupo Innovate',
                'enabled'         => true,
            ],
            [
                'id'              => 3,
                'organization_id' => 1,
                'name'            => 'Andrés',
                'first_last_name' => 'Morales',
                'email'           => 'amorales@consultoriam.com',
                'phone'           => '+52 81 5555 0101',
                'company'         => 'Consultoría M',
                'enabled'         => true,
            ],
            [
                'id'              => 4,
                'organization_id' => 1,
                'name'            => 'Patricia',
                'first_last_name' => 'Villanueva',
                'email'           => 'pvillanueva@outsourcingpv.mx',
                'phone'           => '+52 55 2222 9999',
                'company'         => 'Outsourcing PV',
                'enabled'         => false,
            ],
        ];

        foreach ($visitors as $visitor) {
            DB::table('visitors')->updateOrInsert(
                ['id' => $visitor['id']],
                array_merge($visitor, [
                    'middle_name'      => null,
                    'second_last_name' => null,
                    'created_at'       => now(),
                    'updated_at'       => now(),
                ])
            );
        }
    }
}
