<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use App\Models\User;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Catálogo de permisos por módulo
        |--------------------------------------------------------------------------
        | Estructura: <modulo>.<accion>
        | Se usan desde @can('xxx') en vistas y middleware 'can:xxx' en rutas.
        */
        $permissionsPorModulo = [

            // ---------------- DASHBOARD ----------------
            'Dashboard' => [
                'dashboard.ver',
            ],

            // ---------------- TERCEROS ----------------
            'Terceros' => [
                'terceros.ver',
                'terceros.gestionar',
            ],

            // ---------------- VENTAS ----------------
            'Ventas' => [
                'ventas.ver',                // visibilidad del menú Ventas
                'facturas.ver',
                'facturas.crear',
                'facturas.editar',
                'facturas.anular',
                'facturas.modificar_precio',
                'cotizaciones.ver',
                'cotizaciones.crear',
                'cotizaciones.editar',
                'cotizaciones.eliminar',
                'notas_credito.ver',
                'notas_credito.crear',
                'caja.ver',
                'caja.abrir',
                'caja.cerrar',
            ],

            // ---------------- COMPRAS ----------------
            'Compras' => [
                'compras.ver',
                'compras.crear',
                'compras.editar',
                'compras.anular',
                'notas_credito_compra.ver',
                'notas_credito_compra.crear',
            ],

            // ---------------- INVENTARIO ----------------
            'Inventario' => [
                'inventario.ver',
                'inventario.entradas',
                'bodegas.ver',
                'bodegas.ver_todas',
                'bodegas.gestionar',
                'productos.ver',
                'productos.gestionar',
                'categorias.ver',
                'categorias.gestionar',
                'kardex.ver',
                'transferencias.ver',
                'transferencias.gestionar',
            ],

            // ---------------- FINANZAS ----------------
            'Finanzas' => [
                'finanzas.ver',
                'pagos.ver',
                'pagos.gestionar',
                'gastos.ver',
                'gastos.gestionar',
            ],

            // ---------------- INFORMES ----------------
            'Informes' => [
                'informes.ver',
                'informes.ventas',
                'informes.asientos',
                'informes.ver_costos', // 👈 controla si ve costo/utilidad/margen
            ],

            // ---------------- CONFIGURACIÓN ----------------
            'Configuracion' => [
                'configuracion.ver',
                'usuarios.gestionar',
                'roles.gestionar',
                'empresas.gestionar',
                'series.gestionar',
                'normas_reparto.gestionar',
                'cuentas_contables.gestionar',
                'impuestos.gestionar',
                'condiciones_pago.gestionar',
                'medios_pago.gestionar',
                'tipo_documentos.gestionar',
                'conceptos_documentos.gestionar',
            ],
        ];

        // Aplanar todos los permisos
        $todosLosPermisos = collect($permissionsPorModulo)->flatten()->unique()->values();

        // Crear permisos si no existen
        foreach ($todosLosPermisos as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        }

        /*
        |--------------------------------------------------------------------------
        | ROL: administrador  → TODO
        |--------------------------------------------------------------------------
        */
        $adminRole = Role::firstOrCreate(['name' => 'administrador', 'guard_name' => 'web']);
        $adminRole->syncPermissions($todosLosPermisos->all());

        /*
        |--------------------------------------------------------------------------
        | ROL: ventas  → acciones comerciales típicas
        |--------------------------------------------------------------------------
        | Puede: emitir facturas, cotizaciones, notas crédito, caja, ver terceros
        |        y productos, ver informe de ventas (SIN costos ni utilidad).
        | No puede: modificar precios, anular documentos, ver compras, inventario
        |           (movimientos), finanzas, configuración.
        */
        $ventasPermisos = [
            'dashboard.ver',

            'terceros.ver',

            'ventas.ver',
            'facturas.ver',
            'facturas.crear',
            'facturas.editar',
            'cotizaciones.ver',
            'cotizaciones.crear',
            'cotizaciones.editar',
            'notas_credito.ver',
            'notas_credito.crear',
            'caja.ver',
            'caja.abrir',
            'caja.cerrar',

            'productos.ver',
            'bodegas.ver',
            'kardex.ver',

            'informes.ver',
            'informes.ventas',
            // ❌ informes.ver_costos (oculta rentabilidad)
        ];

        $ventasRole = Role::firstOrCreate(['name' => 'ventas', 'guard_name' => 'web']);
        $ventasRole->syncPermissions($ventasPermisos);

        /*
        |--------------------------------------------------------------------------
        | Asignar rol administrador al usuario ID 1 (si existe)
        |--------------------------------------------------------------------------
        */
        $admin = User::find(1);
        if ($admin && !$admin->hasRole('administrador')) {
            $admin->assignRole($adminRole);
        }

        // Refrescar caché de Spatie
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
