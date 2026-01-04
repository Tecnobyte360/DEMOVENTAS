<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\CuentasContables\PlanCuentas;

class PlanCuentasCostosSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {

            // (codigo, nombre, nivel, padre_codigo, naturaleza, titulo)
            $cuentas = [

                // =========================
                // NIVEL 1
                // =========================
                ['6','COSTOS',1,null,'COSTOS',true],

                // =========================
                // NIVEL 2 (hijos de 6)
                // =========================
                ['61','COSTO DE VENTAS Y DE PRESTACION DE SERVICIOS',2,'6','COSTOS',true],
                ['62','COMPRAS',2,'6','COSTOS',true],
                ['63','COSTOS DE PRODUCCION O DE OPERACION',2,'6','COSTOS',true],

                // =========================
                // NIVEL 3 (hijos de 61)
                // =========================
                ['6105','COSTO DE VENTAS',3,'61','COSTOS',false],
                ['6110','COSTO DE PRESTACION DE SERVICIOS',3,'61','COSTOS',false],

                // =========================
                // NIVEL 3 (hijos de 62)
                // =========================
                ['6205','COMPRAS',3,'62','COSTOS',false],
                ['6210','DEVOLUCIONES, REBAJAS Y DESCUENTOS EN COMPRAS',3,'62','COSTOS',false],

                // =========================
                // NIVEL 3 (hijos de 63)
                // =========================
                ['6305','COSTOS DE PRODUCCION',3,'63','COSTOS',false],
                ['6310','COSTOS DE OPERACION',3,'63','COSTOS',false],
            ];

            /**
             * ✅ Generación automática:
             * 4 dígitos -> 6 dígitos (XXXX01) Nivel 4
             * 6 dígitos -> 8 dígitos (XXXX0101) Nivel 5
             */
            $auto = [];
            foreach ($cuentas as $c) {
                [$codigo, $nombre, $nivel, $padreCodigo, $naturaleza, $titulo] = $c;

                if (strlen($codigo) === 4) {
                    $codigo6 = $codigo . '01';  // ej: 6105 -> 610501
                    $codigo8 = $codigo6 . '01'; // ej: 610501 -> 61050101

                    $auto[] = [$codigo6, $nombre . ' (DETALLE)', 4, $codigo, $naturaleza, false];
                    $auto[] = [$codigo8, $nombre . ' (AUX 01)', 5, $codigo6, $naturaleza, false];
                }
            }

            $cuentas = array_merge($cuentas, $auto);

            // ✅ Inserta padres primero
            usort($cuentas, fn($a, $b) => strlen($a[0]) <=> strlen($b[0]) ?: strcmp($a[0], $b[0]));

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
