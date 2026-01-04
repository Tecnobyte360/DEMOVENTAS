<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\CuentasContables\PlanCuentas;

class PlanCuentasPasivosSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {

            // (codigo, nombre, nivel, padre_codigo, naturaleza, titulo)
            $cuentas = [

                // =========================
                // NIVEL 1
                // =========================
                ['2','PASIVO',1,null,'PASIVOS',true],

                // =========================
                // NIVEL 2 (hijos de 2)
                // =========================
                ['21','OBLIGACIONES FINANCIERAS',2,'2','PASIVOS',true],
                ['22','PROVEEDORES',2,'2','PASIVOS',true],
                ['23','CUENTAS POR PAGAR',2,'2','PASIVOS',true],
                ['24','IMPUESTOS, GRAVAMENES Y TASAS',2,'2','PASIVOS',true],
                ['25','OBLIGACIONES LABORALES',2,'2','PASIVOS',true],
                ['26','PASIVOS ESTIMADOS Y PROVISIONES',2,'2','PASIVOS',true],
                ['27','DIFERIDOS',2,'2','PASIVOS',true],
                ['28','OTROS PASIVOS',2,'2','PASIVOS',true],
                ['29','BONOS Y PAPELES COMERCIALES',2,'2','PASIVOS',true],

                // =========================
                // NIVEL 3 (hijos de 21)
                // =========================
                ['2105','BANCOS NACIONALES',3,'21','PASIVOS',false],
                ['2110','BANCOS DEL EXTERIOR',3,'21','PASIVOS',false],
                ['2115','CORPORACIONES FINANCIERAS',3,'21','PASIVOS',false],
                ['2120','COMPAÑIAS DE FINANCIAMIENTO COMERCIAL',3,'21','PASIVOS',false],
                ['2125','CORPORACIONES DE AHORRO Y VIVIENDA',3,'21','PASIVOS',false],
                ['2130','ENTIDADES COOPERATIVAS',3,'21','PASIVOS',false],
                ['2135','OTRAS ENTIDADES FINANCIERAS',3,'21','PASIVOS',false],

                // =========================
                // NIVEL 3 (hijos de 22)
                // =========================
                ['2205','NACIONALES',3,'22','PASIVOS',false],
                ['2210','DEL EXTERIOR',3,'22','PASIVOS',false],

                // =========================
                // NIVEL 3 (hijos de 23)
                // =========================
                ['2305','CUENTAS CORRIENTES COMERCIALES',3,'23','PASIVOS',false],
                ['2310','A CASA MATRIZ',3,'23','PASIVOS',false],
                ['2315','A COMPAÑIAS VINCULADAS',3,'23','PASIVOS',false],
                ['2320','A CONTRATISTAS',3,'23','PASIVOS',false],
                ['2330','ORDENES DE COMPRA POR UTILIZAR',3,'23','PASIVOS',false],
                ['2335','COSTOS Y GASTOS POR PAGAR',3,'23','PASIVOS',false],
                ['2340','INSTALAMENTOS POR PAGAR',3,'23','PASIVOS',false],
                ['2355','DEUDAS CON ACCIONISTAS O SOCIOS',3,'23','PASIVOS',false],
                ['2360','DIVIDENDOS O PARTICIPACIONES POR PAGAR',3,'23','PASIVOS',false],
                ['2365','RETENCION EN LA FUENTE',3,'23','PASIVOS',false],
                ['2367','IMPUESTO A LAS VENTAS RETENIDO',3,'23','PASIVOS',false],
                ['2368','IMPUESTO DE INDUSTRIA Y COMERCIO RETENIDO',3,'23','PASIVOS',false],
                ['2370','RETENCIONES Y APORTES DE NOMINA',3,'23','PASIVOS',false],
                ['2375','CUOTAS POR DEVOLVER',3,'23','PASIVOS',false],
                ['2380','ACREEDORES VARIOS',3,'23','PASIVOS',false],
                ['2395','OTRAS CUENTAS POR PAGAR',3,'23','PASIVOS',false],

                // =========================
                // NIVEL 3 (hijos de 24)
                // =========================
                ['2404','IMPUESTO DE RENTA Y COMPLEMENTARIOS',3,'24','PASIVOS',false],
                ['2408','IMPUESTO SOBRE LAS VENTAS POR PAGAR',3,'24','PASIVOS',false],
                ['2412','IMPUESTO DE INDUSTRIA Y COMERCIO',3,'24','PASIVOS',false],
                ['2416','IMPUESTO DE TIMBRE',3,'24','PASIVOS',false],
                ['2420','GRAVAMENES Y TASAS',3,'24','PASIVOS',false],
                ['2424','IMPUESTO DE VALORIZACION',3,'24','PASIVOS',false],
                ['2428','IMPUESTOS ADUANEROS',3,'24','PASIVOS',false],
                ['2432','IMPUESTO AL CONSUMO',3,'24','PASIVOS',false],
                ['2436','OTROS IMPUESTOS',3,'24','PASIVOS',false],

                // =========================
                // NIVEL 3 (hijos de 25)
                // =========================
                ['2505','SALARIOS POR PAGAR',3,'25','PASIVOS',false],
                ['2510','CESANTIAS CONSOLIDADAS',3,'25','PASIVOS',false],
                ['2515','INTERESES SOBRE CESANTIAS',3,'25','PASIVOS',false],
                ['2520','PRIMA DE SERVICIOS',3,'25','PASIVOS',false],
                ['2525','VACACIONES CONSOLIDADAS',3,'25','PASIVOS',false],
                ['2530','PRESTACIONES EXTRALEGALES',3,'25','PASIVOS',false],
                ['2535','APORTES A SEGURIDAD SOCIAL',3,'25','PASIVOS',false],
                ['2540','PENSIONES POR PAGAR',3,'25','PASIVOS',false],
                ['2545','OTRAS OBLIGACIONES LABORALES',3,'25','PASIVOS',false],

                // =========================
                // NIVEL 3 (hijos de 26)
                // =========================
                ['2605','PARA COSTOS Y GASTOS',3,'26','PASIVOS',false],
                ['2610','PARA OBLIGACIONES LABORALES',3,'26','PASIVOS',false],
                ['2615','PARA OBLIGACIONES FISCALES',3,'26','PASIVOS',false],
                ['2620','PENSIONES DE JUBILACION',3,'26','PASIVOS',false],
                ['2625','PARA OBRAS DE URBANISMO',3,'26','PASIVOS',false],
                ['2630','PARA MANTENIMIENTO Y REPARACIONES',3,'26','PASIVOS',false],
                ['2635','PARA CONTINGENCIAS',3,'26','PASIVOS',false],
                ['2640','PARA OTRAS PROVISIONES',3,'26','PASIVOS',false],

                // =========================
                // NIVEL 3 (hijos de 27)
                // =========================
                ['2705','INGRESOS RECIBIDOS POR ANTICIPADO',3,'27','PASIVOS',false],
                ['2710','ABONOS DIFERIDOS',3,'27','PASIVOS',false],
                ['2715','UTILIDAD DIFERIDA EN VENTAS A PLAZOS',3,'27','PASIVOS',false],
                ['2720','CREDITO MERCANTIL',3,'27','PASIVOS',false],
                ['2725','IMPUESTOS DIFERIDOS',3,'27','PASIVOS',false],

                // =========================
                // NIVEL 3 (hijos de 28)
                // =========================
                ['2805','ANTICIPOS Y AVANCES RECIBIDOS',3,'28','PASIVOS',false],
                ['2810','DEPOSITOS RECIBIDOS',3,'28','PASIVOS',false],
                ['2815','INGRESOS RECIBIDOS PARA TERCEROS',3,'28','PASIVOS',false],
                ['2820','CUENTAS DE OPERACION CONJUNTA',3,'28','PASIVOS',false],
                ['2825','RETENCIONES A TERCEROS SOBRE CONTRATOS',3,'28','PASIVOS',false],
                ['2830','EMBARGOS JUDICIALES',3,'28','PASIVOS',false],
                ['2835','ACREEDORES DEL SISTEMA',3,'28','PASIVOS',false],
                ['2895','DIVERSOS',3,'28','PASIVOS',false],

                // =========================
                // NIVEL 3 (hijos de 29)
                // =========================
                ['2905','BONOS EN CIRCULACION',3,'29','PASIVOS',false],
                ['2910','PAPELES COMERCIALES',3,'29','PASIVOS',false],
                ['2915','OBLIGACIONES CONVERTIBLES EN ACCIONES',3,'29','PASIVOS',false],
                ['2920','BONOS OBLIGATORIAMENTE CONVERTIBLES EN ACCIONES',3,'29','PASIVOS',false],
                ['2995','OTROS BONOS Y PAPELES',3,'29','PASIVOS',false],
            ];

            // ✅ Generación automática 6 y 8 dígitos para cada cuenta de 4 dígitos
            $auto = [];
            foreach ($cuentas as $c) {
                [$codigo, $nombre, $nivel, $padreCodigo, $naturaleza, $titulo] = $c;

                if (strlen($codigo) === 4) {
                    $codigo6 = $codigo . '01';  // ej: 2105 -> 210501
                    $codigo8 = $codigo6 . '01'; // ej: 210501 -> 21050101

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
