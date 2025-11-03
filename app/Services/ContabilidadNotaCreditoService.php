<?php

namespace App\Services;

use App\Models\Asiento\Asiento;
use App\Models\Movimiento\Movimiento;
use App\Models\NotaCredito;
use App\Models\Impuestos\Impuesto;
use App\Models\Productos\Producto;
use App\Models\SocioNegocio\SocioNegocio;
use App\Models\CuentasContables\PlanCuentas;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Genera el asiento de una Nota Crédito (total o parcial).
 * Toma las cuentas con la MISMA lógica configurable que usas en Factura:
 * - producto.mov_contable_segun: ARTICULO / SUBCATEGORIA
 * - tipos: DEVOLUCION, IVA, INVENTARIO, COSTO (fallbacks incluidos)
 */
class ContabilidadNotaCreditoService
{
    public static function asientoDesdeNotaCredito(NotaCredito $nc): void
    {
        $nc->loadMissing(
            'detalles.producto.cuentas.tipo',
            'detalles.producto.impuesto',
            'cliente',
            'serie'
        );

        if ($nc->detalles->isEmpty())   throw new RuntimeException('La nota crédito no tiene líneas.');
        if (!$nc->fecha)                throw new RuntimeException('La nota crédito no tiene fecha.');
        if (!$nc->cuenta_cobro_id)      throw new RuntimeException('Seleccione una cuenta contable de cobro para la nota crédito.');

        // Asegura totales
        $nc->recalcularTotales();

        $fecha     = $nc->fecha;
        $numFmt    = $nc->numero ? str_pad((string)$nc->numero, optional($nc->serie)->longitud ?? 6, '0', STR_PAD_LEFT) : (string)$nc->id;
        $glosaNum  = $nc->prefijo ? ($nc->prefijo.'-'.$numFmt) : $numFmt;
        $glosaBase = 'NC Venta '.$glosaNum.' — Cliente: '.($nc->cliente->razon_social ?? ('ID '.$nc->socio_negocio_id));

        $movs = [];

        /* =========================================================
         * 1) DÉBITOS: Reversar Ingreso por DEVOLUCIÓN + IVA generado
         * ========================================================= */
        foreach ($nc->detalles as $d) {
            $cant   = max(0, (float)$d->cantidad);
            if ($cant <= 0) continue;

            $precio = max(0, (float)$d->precio_unitario);   // neto sin IVA
            $desc   = min(100, max(0, (float)$d->descuento_pct));
            $ivaPct = min(100, max(0, (float)$d->impuesto_pct));

            $base = round($cant * $precio * (1 - $desc/100), 2);
            $iva  = round($base * $ivaPct / 100, 2);

            // Debe: DEVOLUCIÓN EN VENTAS (ingreso con tipo DEVOLUCION / fallback)
            $ctaDev = self::cuentaDevolucion($d->producto);
            if ($base > 0 && !$ctaDev) {
                throw new RuntimeException("Producto {$d->producto_id}: falta cuenta de 'Ingreso por devolución'.");
            }
            if ($base > 0) {
                $movs[] = [
                    'cuenta_id' => $ctaDev,
                    'debito'    => $base,
                    'credito'   => 0.0,
                    'glosa'     => self::recortar('Devolución en ventas · '.($d->descripcion ?? '')),
                ];
            }

            // Debe: Reversar IVA generado (cuenta IVA ventas)
            if ($iva > 0 && $d->impuesto_id) {
                $imp = Impuesto::find($d->impuesto_id);
                $ctaIva = self::cuentaIvaVentas($d->producto, $imp);
                if (!$ctaIva) {
                    $n = $imp->nombre ?? 'IVA';
                    throw new RuntimeException("No se encontró cuenta contable para {$n} (ventas).");
                }
                $movs[] = [
                    'cuenta_id' => $ctaIva,
                    'debito'    => $iva,
                    'credito'   => 0.0,
                    'glosa'     => self::recortar('Reversa IVA generado'),
                ];
            }
        }

        /* =====================================================
         * 2) HABER: Contrapartida CxC/Caja/Bancos por total NC
         * ===================================================== */
        $ctaCobro = self::cuentaCobro($nc->cuenta_cobro_id, $nc->cliente);
        $totalNc  = round((float)$nc->total, 2);

        if ($totalNc > 0 && $ctaCobro) {
            $movs[] = [
                'cuenta_id' => $ctaCobro,
                'debito'    => 0.0,
                'credito'   => $totalNc,
                'glosa'     => self::recortar('Contrapartida (CxC/Caja/Bancos)'),
            ];
        }

        /* =============================================================================
         * 3) (OPCIONAL) Reversar COSTO y Reingresar INVENTARIO si así lo definiste
         * ============================================================================= */
        if (!empty($nc->reponer_inventario)) {
            foreach ($nc->detalles as $d) {
                $cant = max(0, (float)$d->cantidad);
                if ($cant <= 0 || !$d->producto_id) continue;

                // mismo CPU que factura (centralizado en tu ContabilidadService)
                $cpu = ContabilidadService::costoPromedioParaLinea($d->producto, $d->bodega_id ?? null);
                $costo = round($cpu * $cant, 2);
                if ($costo <= 0) continue;

                $ctaInv   = self::cuentaInventario($d->producto);
                $ctaCosto = self::cuentaCosto($d->producto);
                if (!$ctaInv)   throw new RuntimeException("Producto {$d->producto_id}: falta cuenta de INVENTARIO.");
                if (!$ctaCosto) throw new RuntimeException("Producto {$d->producto_id}: falta cuenta de COSTO.");

                // Debe: Inventario (reingreso físico)
                $movs[] = [
                    'cuenta_id' => $ctaInv,
                    'debito'    => $costo,
                    'credito'   => 0.0,
                    'glosa'     => self::recortar('Reingreso inventario · '.($d->producto->nombre ?? '')),
                ];
                // Haber: Costo de venta (reversión)
                $movs[] = [
                    'cuenta_id' => $ctaCosto,
                    'debito'    => 0.0,
                    'credito'   => $costo,
                    'glosa'     => self::recortar('Reversión costo de venta · '.($d->producto->nombre ?? '')),
                ];
            }
        }

        /* ==========================
         * 4) Consolidar y validar
         * ========================== */
        $movs = self::consolidar($movs);
        $deb = round(array_sum(array_column($movs, 'debito')), 2);
        $cre = round(array_sum(array_column($movs, 'credito')), 2);
        if (abs($deb - $cre) >= 0.01) {
            Log::error('Descuadre en asiento de Nota Crédito', [
                'nota_credito_id' => $nc->id, 'deb' => $deb, 'cre' => $cre, 'diff' => round($deb-$cre,2), 'movs' => $movs
            ]);
            throw new RuntimeException('El asiento de la NC no cuadra.');
        }

        /* ==========================
         * 5) Insertar asiento
         * ========================== */
        self::insertAsiento([
            'fecha'        => $fecha,
            'glosa'        => $glosaBase,
            'documento'    => 'nota_credito',
            'documento_id' => $nc->id,
            'referencia'   => $glosaNum,
            'movimientos'  => $movs,
        ]);

        // Si manejas compensación automática contra la factura
        if ($nc->factura_id && class_exists(\App\Services\PagosService::class)
            && method_exists(\App\Services\PagosService::class, 'aplicarNotaCreditoSobreFactura')) {
            try { \App\Services\PagosService::aplicarNotaCreditoSobreFactura($nc); }
            catch (\Throwable $e) { Log::warning('NC no aplicada a factura: '.$e->getMessage(), ['nc_id'=>$nc->id]); }
        }
    }

    /** ---------- Inserción segura del asiento (cuadrado) ---------- */
    private static function insertAsiento(array $payload): Asiento
    {
        $movs = $payload['movimientos'] ?? [];
        if (empty($movs)) throw new RuntimeException('No hay movimientos para registrar.');

        $deb = array_sum(array_column($movs, 'debito'));
        $cre = array_sum(array_column($movs, 'credito'));
        if (round($deb - $cre, 2) !== 0.0) {
            throw new RuntimeException('El asiento no cuadra.');
        }

        return DB::transaction(function () use ($payload, $movs) {
            $asiento = Asiento::create([
                'fecha'        => $payload['fecha'] ?? now()->toDateString(),
                'glosa'        => $payload['glosa'] ?? null,
                'documento'    => $payload['documento'] ?? null,
                'documento_id' => $payload['documento_id'] ?? null,
                'referencia'   => $payload['referencia'] ?? null,
            ]);

            $now = now();
            $rows = collect($movs)->map(fn($m) => [
                'asiento_id' => $asiento->id,
                'cuenta_id'  => (int)$m['cuenta_id'],
                'debito'     => (float)$m['debito'],
                'credito'    => (float)$m['credito'],
                'glosa'      => (string)($m['glosa'] ?? null),
                'created_at' => $now,
                'updated_at' => $now,
            ])->toArray();

            Movimiento::insert($rows);

            $delta = round($asiento->movimientos()->sum('debito') - $asiento->movimientos()->sum('credito'), 2);
            if ($delta !== 0.0) {
                Log::error('Asiento no cuadra luego de insertar movimientos', ['asiento_id'=>$asiento->id,'Δ'=>$delta]);
                throw new RuntimeException('El asiento no cuadra tras insertar movimientos.');
            }
            return $asiento;
        });
    }

    /** ---------- Consolidación por cuenta ---------- */
    private static function consolidar(array $movs): array
    {
        $by = [];
        foreach ($movs as $m) {
            $id = (int)$m['cuenta_id'];
            if (!isset($by[$id])) $by[$id] = ['cuenta_id'=>$id,'debito'=>0.0,'credito'=>0.0,'glosa'=>[]];
            $by[$id]['debito']  += (float)$m['debito'];
            $by[$id]['credito'] += (float)$m['credito'];
            if (!empty($m['glosa'])) $by[$id]['glosa'][] = self::recortar($m['glosa']);
        }
        foreach ($by as $id => &$r) {
            $d = round($r['debito'],2); $c = round($r['credito'],2);
            if (abs($d-$c) < 0.01) { unset($by[$id]); continue; }
            $r['debito']  = max(0, $d-$c);
            $r['credito'] = max(0, $c-$d);
            $r['glosa']   = self::recortar(implode(' · ', array_filter($r['glosa'])));
        }
        return array_values($by);
    }

    private static function recortar(string $s, int $max = 120): string
    {
        $s = trim($s);
        return mb_strlen($s) > $max ? mb_substr($s, 0, $max - 1).'…' : $s;
    }

    /* ===================== Resolución de cuentas ===================== */
    private static function cuentaDevolucion(?Producto $p): ?int
    {
        if (!$p) return null;
        // Usa la misma lógica que factura: por SUBCATEGORIA o ARTICULO.
        return ContabilidadService::cuentaSegunConfiguracion($p, 'DEVOLUCION')
            ?? ContabilidadService::cuentaSegunConfiguracion($p, 'INGRESO_DEV')
            ?? ContabilidadService::cuentaSegunConfiguracion($p, 'INGRESO');
    }

    private static function cuentaIvaVentas(?Producto $p, ?Impuesto $imp): ?int
    {
        // Primero por configuración (SUBCATEGORIA/ARTICULO tipo IVA)
        $cta = ContabilidadService::cuentaSegunConfiguracion($p, 'IVA');
        if ($cta) return $cta;

        // Luego cuenta directa del impuesto (si tu modelo la trae)
        foreach (['plan_cuenta_id','plan_cuenta_pasivo_id','cuenta_id'] as $f) {
            if ($imp && isset($imp->{$f}) && $imp->{$f} && PlanCuentas::whereKey($imp->{$f})->exists()) {
                return (int)$imp->{$f};
            }
        }
        return null;
    }

    private static function cuentaInventario(?Producto $p): ?int
    {
        return $p ? (ContabilidadService::cuentaSegunConfiguracion($p, 'INVENTARIO')) : null;
    }

    private static function cuentaCosto(?Producto $p): ?int
    {
        return $p ? (ContabilidadService::cuentaSegunConfiguracion($p, 'COSTO')) : null;
    }

    private static function cuentaCobro(?int $seleccionada, ?SocioNegocio $cliente): ?int
    {
        if ($seleccionada && PlanCuentas::whereKey($seleccionada)->exists()) return (int)$seleccionada;

        if ($cliente) {
            foreach (['plan_cuenta_id','cuenta_cxc_id'] as $f) {
                if (isset($cliente->{$f}) && $cliente->{$f} && PlanCuentas::whereKey($cliente->{$f})->exists()) {
                    return (int)$cliente->{$f};
                }
            }
        }

        // Hints: CAJA/BANCOS
        foreach ([['clase_cuenta','CAJA_GENERAL'], ['clase_cuenta','BANCOS']] as [$col,$val]) {
            $id = PlanCuentas::where('cuenta_activa',1)
                ->where(fn($q)=>$q->where('titulo',0)->orWhereNull('titulo'))
                ->where($col,$val)->value('id');
            if ($id) return (int)$id;
        }
        return null;
    }
}
