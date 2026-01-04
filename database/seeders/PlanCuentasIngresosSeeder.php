<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\CuentasContables\PlanCuentas;

class PlanCuentasIngresosSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {

            // (codigo, nombre, nivel, padre_codigo, naturaleza, titulo)
            $cuentas = [

                // =========================
                // NIVEL 1
                // =========================
                ['4','INGRESOS',1,null,'INGRESOS',true],

                // =========================
                // NIVEL 2 (hijos de 4)
                // =========================
                ['41','OPERACIONALES',2,'4','INGRESOS',true],
                ['42','NO OPERACIONALES',2,'4','INGRESOS',true],

                // =========================
                // NIVEL 3 (hijos de 41)
                // =========================
                ['4105','AGRICULTURA, GANADERIA, CAZA Y SILVICULTURA',3,'41','INGRESOS',false],
                ['4110','PESCA',3,'41','INGRESOS',false],
                ['4115','EXPLOTACION DE MINAS Y CANTERAS',3,'41','INGRESOS',false],
                ['4120','INDUSTRIAS MANUFACTURERAS',3,'41','INGRESOS',false],
                ['4125','SUMINISTRO DE ELECTRICIDAD, GAS Y AGUA',3,'41','INGRESOS',false],
                ['4130','CONSTRUCCION',3,'41','INGRESOS',false],
                ['4135','COMERCIO AL POR MAYOR Y AL POR MENOR',3,'41','INGRESOS',false],
                ['4140','HOTELES Y RESTAURANTES',3,'41','INGRESOS',false],
                ['4145','TRANSPORTE, ALMACENAMIENTO Y COMUNICACIONES',3,'41','INGRESOS',false],
                ['4150','ACTIVIDAD FINANCIERA',3,'41','INGRESOS',false],
                ['4155','ACTIVIDAD INMOBILIARIA',3,'41','INGRESOS',false],
                ['4160','ENSEÑANZA',3,'41','INGRESOS',false],
                ['4165','SERVICIOS SOCIALES Y DE SALUD',3,'41','INGRESOS',false],
                ['4170','OTRAS ACTIVIDADES DE SERVICIOS COMUNITARIOS, SOCIALES Y PERSONALES',3,'41','INGRESOS',false],
                ['4175','DEVOLUCIONES, REBAJAS Y DESCUENTOS EN VENTAS',3,'41','INGRESOS',false],

                // =========================
                // NIVEL 3 (hijos de 42)
                // =========================
                ['4205','OTRAS VENTAS',3,'42','INGRESOS',false],
                ['4210','FINANCIEROS',3,'42','INGRESOS',false],
                ['4215','DIVIDENDOS Y PARTICIPACIONES',3,'42','INGRESOS',false],
                ['4220','ARRENDAMIENTOS',3,'42','INGRESOS',false],
                ['4225','COMISIONES',3,'42','INGRESOS',false],
                ['4230','HONORARIOS',3,'42','INGRESOS',false],
                ['4235','SERVICIOS',3,'42','INGRESOS',false],
                ['4240','UTILIDAD EN VENTA DE INVERSIONES',3,'42','INGRESOS',false],
                ['4245','UTILIDAD EN VENTA DE PROPIEDADES PLANTA Y EQUIPO',3,'42','INGRESOS',false],
                ['4250','RECUPERACIONES',3,'42','INGRESOS',false],
                ['4255','INDEMNIZACIONES',3,'42','INGRESOS',false],
                ['4260','PARTIDAS EXTRAORDINARIAS',3,'42','INGRESOS',false],
                ['4295','DIVERSOS',3,'42','INGRESOS',false],
            ];

            // ✅ Generación automática 6 y 8 dígitos para cada cuenta de 4 dígitos
            $auto = [];
            foreach ($cuentas as $c) {
                [$codigo, $nombre, $nivel, $padreCodigo, $naturaleza, $titulo] = $c;

                if (strlen($codigo) === 4) {
                    $codigo6 = $codigo . '01';  // ej: 4105 -> 410501
                    $codigo8 = $codigo6 . '01'; // ej: 410501 -> 41050101

                    $auto[] = [$codigo6, $nombre . ' (DETALLE)', 4, $codigo, $naturaleza, false];
                    $auto[] = [$codigo8, $nombre . ' (AUX 01)', 5, $codigo6, $naturaleza, false];
                }
            }

            $cuentas = array_merge($cuentas, $auto);

            // ✅ Inserta padres primero (por longitud + orden)
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
