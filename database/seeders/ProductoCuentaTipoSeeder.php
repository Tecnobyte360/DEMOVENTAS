<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Productos\ProductoCuentaTipo;

class ProductoCuentaTipoSeeder extends Seeder
{
    public function run(): void
    {
        $rows = [
            ['codigo'=>'GASTO',       'nombre'=>'Cuenta de gastos',       'orden'=>1],
            ['codigo'=>'INGRESO',     'nombre'=>'Cuenta de ingreso',      'orden'=>2],
            ['codigo'=>'COSTO',       'nombre'=>'Cuenta de costo',        'orden'=>3],
            ['codigo'=>'INVENTARIO',  'nombre'=>'Cuenta de inventario',   'orden'=>4],
            ['codigo'=>'GASTO_DEV',   'nombre'=>'Gasto por devolución',   'orden'=>5],
            ['codigo'=>'INGRESO_DEV', 'nombre'=>'Ingreso por devolución', 'orden'=>6],
        ];

        foreach ($rows as $r) {
            ProductoCuentaTipo::updateOrCreate(
                ['codigo' => $r['codigo']],
                $r + ['obligatorio' => true, 'activo' => true]
            );
        }
    }
}
