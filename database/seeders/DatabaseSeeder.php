<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run(): void
    {
        $this->call([
            DashboardTableSeeder::class,
            ProductoCuentaTipoSeeder::class,
            UnidadMedidaSeeder::class,
            EntradasMercanciaTipoYSerieSeeder::class,
            PlanCuentasActivosSeeder::class,
            PlanCuentasPasivosSeeder::class,
            PlanCuentasPatrimonioSeeder::class,
            PlanCuentasIngresosSeeder::class,
            PlanCuentasGastosSeeder::class
        ]);
    }
}
