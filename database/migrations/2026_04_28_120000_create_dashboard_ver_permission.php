<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1) Crear el permiso si no existe
        $permiso = Permission::firstOrCreate(
            ['name' => 'dashboard.ver', 'guard_name' => 'web']
        );

        // 2) Asignarlo automáticamente a roles tipicos de admin (no a Ventas)
        $rolesAdmin = ['Admin', 'admin', 'Super Admin', 'super-admin', 'Gerencia', 'gerencia'];

        foreach ($rolesAdmin as $nombre) {
            $rol = Role::where('name', $nombre)->first();
            if ($rol && !$rol->hasPermissionTo('dashboard.ver')) {
                $rol->givePermissionTo('dashboard.ver');
            }
        }

        // 3) Limpiar cache de permisos
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }

    public function down(): void
    {
        $permiso = Permission::where('name', 'dashboard.ver')->first();
        if ($permiso) {
            $permiso->delete();
        }
    }
};
