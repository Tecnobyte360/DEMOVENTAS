<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\CuentasContables\PlanCuentas;

class PlanCuentasGastosSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {

            // (codigo, nombre, nivel, padre_codigo, naturaleza, titulo)
            $cuentas = [

                // =========================
                // NIVEL 1
                // =========================
                ['5','GASTOS',1,null,'GASTOS',true],

                // =========================
                // NIVEL 2 (hijos de 5)
                // =========================
                ['51','OPERACIONALES DE ADMINISTRACION',2,'5','GASTOS',true],
                ['52','OPERACIONALES DE VENTAS',2,'5','GASTOS',true],
                ['53','NO OPERACIONALES',2,'5','GASTOS',true],

                // =========================
                // NIVEL 3 (hijos de 51)
                // =========================
                ['5105','GASTOS DE PERSONAL',3,'51','GASTOS',false],
                ['5110','HONORARIOS',3,'51','GASTOS',false],
                ['5115','IMPUESTOS',3,'51','GASTOS',false],
                ['5120','ARRENDAMIENTOS',3,'51','GASTOS',false],
                ['5125','CONTRIBUCIONES Y AFILIACIONES',3,'51','GASTOS',false],
                ['5130','SEGUROS',3,'51','GASTOS',false],
                ['5135','SERVICIOS',3,'51','GASTOS',false],
                ['5140','GASTOS LEGALES',3,'51','GASTOS',false],
                ['5145','MANTENIMIENTO Y REPARACIONES',3,'51','GASTOS',false],
                ['5150','ADECUACION E INSTALACION',3,'51','GASTOS',false],
                ['5155','GASTOS DE VIAJE',3,'51','GASTOS',false],
                ['5160','DEPRECIACIONES',3,'51','GASTOS',false],
                ['5165','AMORTIZACIONES',3,'51','GASTOS',false],
                ['5195','DIVERSOS',3,'51','GASTOS',false],

                // =========================
                // NIVEL 3 (hijos de 52)
                // =========================
                ['5205','GASTOS DE PERSONAL',3,'52','GASTOS',false],
                ['5210','HONORARIOS',3,'52','GASTOS',false],
                ['5215','IMPUESTOS',3,'52','GASTOS',false],
                ['5220','ARRENDAMIENTOS',3,'52','GASTOS',false],
                ['5225','CONTRIBUCIONES Y AFILIACIONES',3,'52','GASTOS',false],
                ['5230','SEGUROS',3,'52','GASTOS',false],
                ['5235','SERVICIOS',3,'52','GASTOS',false],
                ['5240','GASTOS LEGALES',3,'52','GASTOS',false],
                ['5245','MANTENIMIENTO Y REPARACIONES',3,'52','GASTOS',false],
                ['5250','ADECUACION E INSTALACION',3,'52','GASTOS',false],
                ['5255','GASTOS DE VIAJE',3,'52','GASTOS',false],
                ['5260','DEPRECIACIONES',3,'52','GASTOS',false],
                ['5265','AMORTIZACIONES',3,'52','GASTOS',false],
                ['5270','DIFERIDOS',3,'52','GASTOS',false],
                ['5295','DIVERSOS',3,'52','GASTOS',false],

                // =========================
                // NIVEL 3 (hijos de 53)
                // =========================
                ['5305','FINANCIEROS',3,'53','GASTOS',false],
                ['5310','PERDIDA EN VENTA Y RETIRO DE BIENES',3,'53','GASTOS',false],
                ['5315','GASTOS EXTRAORDINARIOS',3,'53','GASTOS',false],
                ['5395','DIVERSOS',3,'53','GASTOS',false],
            ];

            foreach ($cuentas as $c) {
                $this->upsertCuenta(...$c);
            }
        });
    }

    private function upsertCuenta(
        string $codigo,
        string $nombre,
        int $nivel,
        ?string $padreCodigo,
        string $naturaleza,
        bool $titulo
    ): void {
        $padreId = null;

        if (!blank($padreCodigo)) {
            $padreId = PlanCuentas::where('codigo', $padreCodigo)->value('id');
        }

        PlanCuentas::updateOrCreate(
            ['codigo' => $codigo],
            [
                'nombre' => $nombre,
                'nivel' => $nivel,
                'padre_id' => $padreId,
                'naturaleza' => strtoupper($naturaleza),
                'cuenta_activa' => 1,
                'titulo' => $titulo ? 1 : 0,
                'moneda' => 'COP',
                'requiere_tercero' => 0,
                'saldo' => 0,
            ]
        );
    }
}
