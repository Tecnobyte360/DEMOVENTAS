<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\CuentasContables\PlanCuentas;

class PlanCuentasPatrimonioSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {

            // (codigo, nombre, nivel, padre_codigo, naturaleza, titulo)
            $cuentas = [

                // =========================
                // NIVEL 1
                // =========================
                ['3','PATRIMONIO',1,null,'PATRIMONIO',true],

                // =========================
                // NIVEL 2 (hijos de 3)
                // =========================
                ['31','CAPITAL SOCIAL',2,'3','PATRIMONIO',true],
                ['32','SUPERAVIT DE CAPITAL',2,'3','PATRIMONIO',true],
                ['33','RESERVAS',2,'3','PATRIMONIO',true],
                ['34','REVALORIZACION DEL PATRIMONIO',2,'3','PATRIMONIO',true],
                ['35','DIVIDENDOS O PARTICIPACIONES DECRETADOS EN ACCIONES',2,'3','PATRIMONIO',true],
                ['36','RESULTADOS DEL EJERCICIO',2,'3','PATRIMONIO',true],
                ['37','RESULTADOS DE EJERCICIOS ANTERIORES',2,'3','PATRIMONIO',true],
                ['38','SUPERAVIT POR VALORIZACIONES',2,'3','PATRIMONIO',true],
                ['39','DEFICIT',2,'3','PATRIMONIO',true],

                // =========================
                // NIVEL 3 (hijos de 31)
                // =========================
                ['3105','CAPITAL SUSCRITO Y PAGADO',3,'31','PATRIMONIO',false],
                ['3115','APORTES SOCIALES',3,'31','PATRIMONIO',false],
                ['3120','CAPITAL ASIGNADO',3,'31','PATRIMONIO',false],
                ['3125','INVERSION SUPLEMENTARIA AL CAPITAL ASIGNADO',3,'31','PATRIMONIO',false],
                ['3130','CAPITAL DE PERSONAS NATURALES',3,'31','PATRIMONIO',false],
                ['3135','APORTES DE SOCIOS',3,'31','PATRIMONIO',false],
                ['3140','CAPITAL SOCIAL POR SUSCRIBIR',3,'31','PATRIMONIO',false],
                ['3145','CAPITAL SUSCRITO POR COBRAR',3,'31','PATRIMONIO',false],

                // =========================
                // NIVEL 3 (hijos de 32)
                // =========================
                ['3205','PRIMA EN COLOCACION DE ACCIONES, CUOTAS O PARTES DE INTERES SOCIAL',3,'32','PATRIMONIO',false],
                ['3210','DONACIONES',3,'32','PATRIMONIO',false],
                ['3215','CREDITO MERCANTIL',3,'32','PATRIMONIO',false],
                ['3220','OTROS SUPERAVIT DE CAPITAL',3,'32','PATRIMONIO',false],

                // =========================
                // NIVEL 3 (hijos de 33)
                // =========================
                ['3305','RESERVA LEGAL',3,'33','PATRIMONIO',false],
                ['3310','RESERVAS ESTATUTARIAS',3,'33','PATRIMONIO',false],
                ['3315','RESERVAS OCASIONALES',3,'33','PATRIMONIO',false],
                ['3320','RESERVA PARA REPOSICION DE ACTIVOS',3,'33','PATRIMONIO',false],
                ['3325','RESERVA PARA FUTURAS CAPITALIZACIONES',3,'33','PATRIMONIO',false],
                ['3330','RESERVA PARA READQUISICION DE ACCIONES',3,'33','PATRIMONIO',false],
                ['3395','OTRAS RESERVAS',3,'33','PATRIMONIO',false],

                // =========================
                // NIVEL 3 (hijos de 34)
                // =========================
                ['3405','AJUSTES POR INFLACION (REVALORIZACION DEL PATRIMONIO)',3,'34','PATRIMONIO',false],

                // =========================
                // NIVEL 3 (hijos de 35)
                // =========================
                ['3505','DIVIDENDOS DECRETADOS EN ACCIONES',3,'35','PATRIMONIO',false],
                ['3510','PARTICIPACIONES DECRETADAS EN CUOTAS O PARTES',3,'35','PATRIMONIO',false],

                // =========================
                // NIVEL 3 (hijos de 36)
                // =========================
                ['3605','UTILIDAD DEL EJERCICIO',3,'36','PATRIMONIO',false],
                ['3610','PERDIDA DEL EJERCICIO',3,'36','PATRIMONIO',false],

                // =========================
                // NIVEL 3 (hijos de 37)
                // =========================
                ['3705','UTILIDADES ACUMULADAS',3,'37','PATRIMONIO',false],
                ['3710','PERDIDAS ACUMULADAS',3,'37','PATRIMONIO',false],

                // =========================
                // NIVEL 3 (hijos de 38)
                // =========================
                ['3805','DE INVERSIONES',3,'38','PATRIMONIO',false],
                ['3810','DE PROPIEDADES PLANTA Y EQUIPO',3,'38','PATRIMONIO',false],
                ['3895','DE OTROS ACTIVOS',3,'38','PATRIMONIO',false],

                // =========================
                // NIVEL 3 (hijos de 39)
                // =========================
                ['3905','DEFICIT DEL EJERCICIO',3,'39','PATRIMONIO',false],
                ['3910','DEFICIT ACUMULADO',3,'39','PATRIMONIO',false],
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
