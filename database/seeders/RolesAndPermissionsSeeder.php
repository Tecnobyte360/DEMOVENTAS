<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Permisos que quieres manejar
        $permissions = [
            'configuracion',
            'finanzas',
            'rutasdisponibles',
            'maestrorutas',
            'socionegocio',
            'inventario',
        ];

        // Crear permisos si no existen
        foreach ($permissions as $perm) {
            Permission::firstOrCreate(['name' => $perm]);
        }

        // Crear rol administrador si no existe
        $adminRole = Role::firstOrCreate(['name' => 'administrador']);

        // Asignar permisos al rol administrador
        $adminRole->syncPermissions($permissions);

        // Asignar rol administrador al usuario con ID 1
        $user = User::find(1);
        if ($user) {
            $user->assignRole($adminRole);
        }
    }
}
