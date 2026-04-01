<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ZoneSeeder extends Seeder
{
    public function run(): void
    {
        $zones = [
            ['id' => 1,  'name' => 'Edificio A',            'description' => 'Área administrativa principal',           'type' => 'pedestrian', 'enabled' => true],
            ['id' => 2,  'name' => 'Edificio B',            'description' => 'Departamento de operaciones',             'type' => 'pedestrian', 'enabled' => true],
            ['id' => 3,  'name' => 'Edificio C',            'description' => 'Centro de desarrollo tecnológico',        'type' => 'pedestrian', 'enabled' => true],
            ['id' => 4,  'name' => 'Edificio D',            'description' => 'Área de recursos humanos',                'type' => 'pedestrian', 'enabled' => false],
            ['id' => 5,  'name' => 'Estacionamiento Norte', 'description' => 'Zona de estacionamiento vehicular norte', 'type' => 'vehicular',  'enabled' => true],
            ['id' => 6,  'name' => 'Estacionamiento Sur',   'description' => 'Zona de estacionamiento vehicular sur',  'type' => 'vehicular',  'enabled' => true],
            // Zones referenced in access logs
            ['id' => 7,  'name' => 'Entrada Principal',     'description' => null, 'type' => 'pedestrian', 'enabled' => true],
            ['id' => 8,  'name' => 'Sala de Servidores',    'description' => null, 'type' => 'pedestrian', 'enabled' => true],
            ['id' => 9,  'name' => 'Estacionamiento',       'description' => null, 'type' => 'vehicular', 'enabled' => true],
            ['id' => 10, 'name' => 'Almacén',               'description' => null, 'type' => 'pedestrian', 'enabled' => true],
        ];

        foreach ($zones as $zone) {
            DB::table('zones')->updateOrInsert(
                ['id' => $zone['id']],
                array_merge($zone, [
                    'organization_id' => 1,
                    'created_at'      => now(),
                    'updated_at'      => now(),
                ])
            );
        }
    }
}
