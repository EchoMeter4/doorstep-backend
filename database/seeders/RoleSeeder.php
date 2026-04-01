<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            ['id' => 1, 'organization_id' => 1, 'name' => 'Administrador', 'description' => 'Acceso total al sistema',                     'enabled' => true],
            ['id' => 2, 'organization_id' => 1, 'name' => 'Empleado',      'description' => 'Acceso a áreas comunes de trabajo',           'enabled' => true],
            ['id' => 3, 'organization_id' => 1, 'name' => 'Visitante',     'description' => 'Acceso temporal a zonas públicas',            'enabled' => true],
            ['id' => 4, 'organization_id' => 1, 'name' => 'Directivo',     'description' => 'Acceso a áreas ejecutivas y administrativas', 'enabled' => false],
            ['id' => 5, 'organization_id' => 1, 'name' => 'Auxiliar',      'description' => 'Acceso limitado a áreas de soporte',          'enabled' => true],
            ['id' => 6, 'organization_id' => 1, 'name' => 'Seguridad',     'description' => 'Acceso a todas las áreas del recinto',        'enabled' => true],
            ['id' => 7, 'organization_id' => 1, 'name' => 'Contratista',   'description' => 'Acceso temporal para personal externo',       'enabled' => true],
        ];

        foreach ($roles as $role) {
            DB::table('roles')->updateOrInsert(
                ['id' => $role['id']],
                array_merge($role, ['created_at' => now(), 'updated_at' => now()])
            );
        }
    }
}
