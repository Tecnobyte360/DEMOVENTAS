<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\CuentasContables\PlanCuentas;

class PlanCuentasActivosSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {

            // (codigo, nombre, nivel, padre_codigo, naturaleza, titulo)
            $cuentas = [
                // =========================
                // NIVEL 1
                // =========================
                ['1','ACTIVO',1,null,'ACTIVOS',true],

                // =========================
                // NIVEL 2 (hijos de 1)
                // =========================
                ['11','DISPONIBLE',2,'1','ACTIVOS',true],
                ['12','INVERSIONES',2,'1','ACTIVOS',true],
                ['13','DEUDORES',2,'1','ACTIVOS',true],
                ['14','INVENTARIOS',2,'1','ACTIVOS',true],
                ['15','PROPIEDADES PLANTA Y EQUIPO',2,'1','ACTIVOS',true],
                ['16','INTANGIBLES',2,'1','ACTIVOS',true],
                ['17','DIFERIDOS',2,'1','ACTIVOS',true],
                ['18','OTROS ACTIVOS',2,'1','ACTIVOS',true],
                ['19','VALORIZACIONES',2,'1','ACTIVOS',true],

                // =========================
                // NIVEL 3 (hijos de 11)
                // =========================
                ['1105','CAJA',3,'11','ACTIVOS',false],
                ['1110','BANCOS',3,'11','ACTIVOS',false],
                ['1115','REMESAS EN TRANSITO',3,'11','ACTIVOS',false],
                ['1120','CUENTAS DE AHORRO',3,'11','ACTIVOS',false],
                ['1125','FONDOS',3,'11','ACTIVOS',false],

                // =========================
                // NIVEL 3 (hijos de 12)
                // =========================
                ['1210','CUOTAS O PARTES DE INTERES',3,'12','ACTIVOS',false],
                ['1215','BONOS',3,'12','ACTIVOS',false],
                ['1220','CEDULAS',3,'12','ACTIVOS',false],
                ['1225','CERTIFICADOS',3,'12','ACTIVOS',false],
                ['1230','PAPELES COMERCIALES',3,'12','ACTIVOS',false],
                ['1235','TITULOS',3,'12','ACTIVOS',false],
                ['1240','ACEPTACIONES BANCARIAS O',3,'12','ACTIVOS',false],
                ['1245','DERECHOS FIDUCIARIOS',3,'12','ACTIVOS',false],
                ['1250','DERECHOS DE RECOMPRA DE',3,'12','ACTIVOS',false],
                ['1255','OBLIGATORIAS',3,'12','ACTIVOS',false],
                ['1260','CUENTAS EN PARTICIPACION',3,'12','ACTIVOS',false],
                ['1295','OTRAS INVERSIONES',3,'12','ACTIVOS',false],
                ['1299','PROVISIONES',3,'12','ACTIVOS',false],

                // =========================
                // NIVEL 3 (hijos de 13)
                // =========================
                ['1305','CLIENTES',3,'13','ACTIVOS',false],
                ['1310','CUENTAS CORRIENTES',3,'13','ACTIVOS',false],
                ['1315','CUENTAS POR COBRAR A CASA',3,'13','ACTIVOS',false],
                ['1320','CUENTAS POR COBRAR A',3,'13','ACTIVOS',false],
                ['1325','CUENTAS POR COBRAR A',3,'13','ACTIVOS',false],
                ['1328','APORTES POR COBRAR',3,'13','ACTIVOS',false],
                ['1330','ANTICIPOS Y AVANCES',3,'13','ACTIVOS',false],
                ['1332','CUENTAS DE OPERACION',3,'13','ACTIVOS',false],
                ['1335','DEPOSITOS',3,'13','ACTIVOS',false],
                ['1340','PROMESAS DE COMPRAVENTA',3,'13','ACTIVOS',false],
                ['1345','INGRESOS POR COBRAR',3,'13','ACTIVOS',false],
                ['1350','RETENCION SOBRE',3,'13','ACTIVOS',false],
                ['1355','ANTICIPO DE IMPUESTOS Y',3,'13','ACTIVOS',false],
                ['1360','RECLAMACIONES',3,'13','ACTIVOS',false],
                ['1365','CUENTAS POR COBRAR A',3,'13','ACTIVOS',false],
                ['1370','PRESTAMOS A PARTICULARES',3,'13','ACTIVOS',false],
                ['1380','DEUDORES VARIOS',3,'13','ACTIVOS',false],
                ['1385','DERECHOS DE RECOMPRA DE',3,'13','ACTIVOS',false],
                ['1390','DEUDAS DE DIFICIL COBRO',3,'13','ACTIVOS',false],
                ['1399','PROVISIONES',3,'13','ACTIVOS',false],

                // =========================
                // NIVEL 3 (hijos de 14)
                // =========================
                ['1405','MATERIAS PRIMAS',3,'14','ACTIVOS',false],
                ['1410','PRODUCTO(S) EN PROCESO',3,'14','ACTIVOS',false],
                ['1415','OBRAS DE CONSTRUCCION EN',3,'14','ACTIVOS',false],
                ['1417','OBRAS DE URBANISMO',3,'14','ACTIVOS',false],
                ['1420','CONTRATOS EN EJECUCION',3,'14','ACTIVOS',false],
                ['1425','CULTIVOS EN DESARROLLO',3,'14','ACTIVOS',false],
                ['1430','PRODUCTOS TERMINADOS',3,'14','ACTIVOS',false],
                ['1435','MERCANCIAS NO FABRICADAS',3,'14','ACTIVOS',false],
                ['1440','BIENES RAICES PARA LA VENTA',3,'14','ACTIVOS',false],
                ['1445','SEMOVIENTES',3,'14','ACTIVOS',false],
                ['1450','TERRENOS',3,'14','ACTIVOS',false],
                ['1455','MATERIALES, REPUESTOS Y',3,'14','ACTIVOS',false],
                ['1460','ENVASES Y EMPAQUES',3,'14','ACTIVOS',false],
                ['1465','INVENTARIOS EN TRANSITO',3,'14','ACTIVOS',false],
                ['1499','PROVISIONES',3,'14','ACTIVOS',false],

                // =========================
                // NIVEL 3 (hijos de 15)
                // =========================
                ['1504','TERRENOS',3,'15','ACTIVOS',false],
                ['1506','MATERIALES PROYECTOS',3,'15','ACTIVOS',false],
                ['1508','CONSTRUCCIONES EN CURSO',3,'15','ACTIVOS',false],
                ['1512','MAQUINARIA Y EQUIPO(S) EN',3,'15','ACTIVOS',false],
                ['1516','CONSTRUCCIONES Y',3,'15','ACTIVOS',false],
                ['1520','MAQUINARIA Y EQUIPO',3,'15','ACTIVOS',false],
                ['1524','EQUIPO DE OFICINA',3,'15','ACTIVOS',false],
                ['1528','EQUIPO DE COMPUTACION Y',3,'15','ACTIVOS',false],
                ['1532','EQUIPO MEDICO CIENTIFICO',3,'15','ACTIVOS',false],
                ['1536','EQUIPO DE HOTELES Y',3,'15','ACTIVOS',false],
                ['1540','FLOTA Y EQUIPO DE',3,'15','ACTIVOS',false],
                ['1544','FLOTA Y EQUIPO FLUVIAL Y/O',3,'15','ACTIVOS',false],
                ['1548','FLOTA Y EQUIPO AEREO',3,'15','ACTIVOS',false],
                ['1552','FLOTA Y EQUIPO FERREO',3,'15','ACTIVOS',false],
                ['1556','ACUEDUCTOS PLANTAS Y',3,'15','ACTIVOS',false],
                ['1560','ARMAMENTO DE VIGILANCIA',3,'15','ACTIVOS',false],
                ['1562','ENVASES Y EMPAQUES',3,'15','ACTIVOS',false],
                ['1564','PLANTACIONES AGRICOLAS Y',3,'15','ACTIVOS',false],
                ['1568','VIAS DE COMUNICACION Y',3,'15','ACTIVOS',false],
                ['1572','MINAS Y CANTERAS',3,'15','ACTIVOS',false],
                ['1576','POZOS ARTESIANOS',3,'15','ACTIVOS',false],
                ['1580','YACIMIENTOS',3,'15','ACTIVOS',false],
                ['1584','SEMOVIENTES',3,'15','ACTIVOS',false],
                ['1588','PROPIEDADES PLANTA Y',3,'15','ACTIVOS',false],
                ['1592','DEPRECIACION ACUMULADA',3,'15','ACTIVOS',false],
                ['1596','DEPRECIACION DIFERIDA',3,'15','ACTIVOS',false],
                ['1597','AMORTIZACION ACUMULADA',3,'15','ACTIVOS',false],
                ['1598','AGOTAMIENTO ACUMULADO',3,'15','ACTIVOS',false],
                ['1599','PROVISIONES',3,'15','ACTIVOS',false],

                // =========================
                // NIVEL 3 (hijos de 16)
                // =========================
                ['1605','CREDITO MERCANTIL',3,'16','ACTIVOS',false],
                ['1610','MARCAS',3,'16','ACTIVOS',false],
                ['1615','PATENTES',3,'16','ACTIVOS',false],
                ['1625','DERECHOS',3,'16','ACTIVOS',false],
                ['1630','KNOW HOW',3,'16','ACTIVOS',false],
                ['1635','LICENCIAS',3,'16','ACTIVOS',false],
                ['1698','DEPRECIACION Y/O',3,'16','ACTIVOS',false],
                ['1699','PROVISIONES',3,'16','ACTIVOS',false],

                // =========================
                // NIVEL 3 (hijos de 17)
                // =========================
                ['1705','GASTOS PAGADOS POR',3,'17','ACTIVOS',false],
                ['1710','CARGOS DIFERIDOS',3,'17','ACTIVOS',false],
                ['1715','COSTOS DE EXPLORACION POR',3,'17','ACTIVOS',false],
                ['1720','COSTOS DE EXPLOTACION Y',3,'17','ACTIVOS',false],
                ['1730','CARGOS POR CORRECCION',3,'17','ACTIVOS',false],
                ['1798','AMORTIZACION ACUMULADA',3,'17','ACTIVOS',false],

                // =========================
                // NIVEL 3 (hijos de 18)
                // =========================
                ['1805','BIENES DE ARTE Y CULTURA',3,'18','ACTIVOS',false],
                ['1895','DIVERSOS',3,'18','ACTIVOS',false],
                ['1899','PROVISIONES',3,'18','ACTIVOS',false],

                // =========================
                // NIVEL 3 (hijos de 19)
                // =========================
                ['1905','DE INVERSIONES',3,'19','ACTIVOS',false],
                ['1910','DE PROPIEDADES PLANTA Y',3,'19','ACTIVOS',false],
                ['1995','OTROS ACTIVOS',3,'19','ACTIVOS',false],




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

                // ✅ Evita el error "Data too long for column moneda"
                // Si después amplías la columna, puedes cambiarlo a "Pesos Colombianos"
                'moneda' => 'COP',

                'requiere_tercero' => 0,
                'saldo' => 0,
            ]
        );
    }
}
