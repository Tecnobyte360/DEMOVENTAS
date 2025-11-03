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

class ContabilidadNotaCreditoService
{
    /**
     * Genera y registra el asiento contable de una Nota Crédito emitida.
     * Soporta devolución total o parcial. Si reponer_inventario = true,
     * revierte costo de venta y reingresa al inventario.
     */
    public static function asientoDesdeNotaCredito(NotaCredito $nc): void
    {
        $nc->loadMissing(
            'detalles.producto.cuentaIngreso',    // por compatibilidad
            'detalles.producto.cuentas.tipo',     // puente (INGRESO / DEV)
            'cliente', 'serie'
        );

        if ($nc->detalles->isEmpty())   throw new RuntimeException('La nota crédito no tiene líneas.');
        if (!$nc->fecha)                throw new RuntimeException('La nota crédito no tiene fecha.');
        if (!$nc->cuenta_cobro_id)      throw new RuntimeException('Seleccione una cuenta contable de cobro para la nota crédito.');

        // Recalcular totales por si acaso
        $nc->recalcularTotales();

        $fecha      = $nc->fecha;
        $glosaPref  = config('contabilidad.glosa_nc_prefix', 'NC Venta');
        $numFmt     = $nc->numero ? str_pad((string)$nc->numero, optional($nc->serie)->longitud ?? 6, '0', STR_PAD_LEFT) : (string)$nc->id;
        $glosaNum   = $nc->prefijo ? ($nc->prefijo.'-'.$numFmt) : $numFmt;
        $glosa      = "{$glosaPref} {$glosaNum} — Cliente: ".($nc->cliente->razon_social ?? ('ID '.$nc->socio_negocio_id));

        $movs = [];

        /* ===============================
         * 1) REVERSA INGRESO + IVA (DÉBITOS)
         * ===============================*/
        foreach ($nc->detalles as $d) {
            $cantidad = max(0, (float) $d->cantidad);
            if ($cantidad <= 0) continue;

            $precio   = max(0, (float) $d->precio_unitario);     // neto sin IVA
            $descPct  = min(100, max(0, (float) $d->descuento_pct));
            $ivaPct   = min(100, max(0, (float) $d->impuesto_pct));

            $base = round($cantidad * $precio * (1 - $descPct / 100), 2);
            $iva  = round($base * $ivaPct / 100, 2);

            // Debe: Ingreso por DEVOLUCIÓN (no el ingreso normal)
            $cuentaIngresoDevId = self::resolveCuentaIngresoNotaCredito($d->producto);
            if ($base > 0 && !$cuentaIngresoDevId) {
                throw new RuntimeException("Producto {$d->producto_id}: falta cuenta de 'Ingreso por devolución'.");
            }
            if ($base > 0) {
                $movs[] = [
                    'cuenta_id' => $cuentaIngresoDevId,
                    'debito'    => $base,
                    'credito'   => 0.0,
                    'glosa'     => self::recortar('Devolución en ventas · '.($d->descripcion ?? '')),
                ];
            }

            // Debe: Reversa de IVA generado (cuenta del impuesto de ventas)
            if ($iva > 0 && $d->impuesto_id) {
                $imp         = Impuesto::find($d->impuesto_id);
                $cuentaIvaId = self::resolveCuentaImpuesto($imp);
                if (!$cuentaIvaId) {
                    $n = $imp->nombre ?? 'IVA';
                    throw new RuntimeException("No se encontró cuenta contable asociada para el {$n}.");
                }
                $movs[] = [
                    'cuenta_id' => $cuentaIvaId,
                    'debito'    => $iva,
                    'credito'   => 0.0,
                    'glosa'     => self::recortar('Reversa IVA generado'),
                ];
            }
        }

        /* ==========================================
         * 2) CONTRAPARTIDA (HABER): CAJA/BANCOS/CxC
         * ==========================================*/
        $cuentaCobroId = self::resolveCuentaCobro($nc->cuenta_cobro_id, $nc->cliente);
        $totalNc       = round((float) $nc->total, 2);

        if ($totalNc > 0 && $cuentaCobroId) {
            $movs[] = [
                'cuenta_id' => $cuentaCobroId,
                'debito'    => 0.0,
                'credito'   => $totalNc,
                'glosa'     => self::recortar('Contrapartida (Caja/Bancos/CxC)'),
            ];
        }

        /* ======================================================
         * 3) (OPCIONAL) REVERSIÓN DE COSTO + REINGRESO INVENTARIO
         * ======================================================*/
        if (!empty($nc->reponer_inventario)) {
            $cpuResolver = function ($detalle) {
                // 1) costo almacenado en la línea de la NC (si lo guardas)
                if (isset($detalle->costo_unitario) && is_numeric($detalle->costo_unitario)) {
                    return (float) $detalle->costo_unitario;
                }
                // 2) costo promedio/costo de la línea (si lo guardas)
                if (isset($detalle->costo_promedio) && is_numeric($detalle->costo_promedio)) {
                    return (float) $detalle->costo_promedio;
                }
                // 3) fallback: costo promedio actual del producto
                return (float) ($detalle->producto->costo_promedio ?? 0);
            };

            $cuentaInvId  = fn($p) => (int)($p->cuenta_inventario_id ?? 0);
            $cuentaCostoId= fn($p) => (int)($p->cuenta_costo_id ?? 0);

            foreach ($nc->detalles as $d) {
                $cant = max(0, (float) $d->cantidad);
                if ($cant <= 0) continue;

                $cpu        = $cpuResolver($d);
                $totalCosto = round($cpu * $cant, 2);
                if ($totalCosto <= 0) continue;

                // Debe: Inventario (reingreso)
                $inv = $cuentaInvId($d->producto);
                if (!$inv) throw new RuntimeException("Producto {$d->producto_id}: falta cuenta de Inventario.");
                $movs[] = [
                    'cuenta_id' => $inv,
                    'debito'    => $totalCosto,
                    'credito'   => 0.0,
                    'glosa'     => self::recortar('Reingreso inventario · '.($d->producto->nombre ?? '')),
                ];

                // Haber: Costo de venta (reversa)
                $costo = $cuentaCostoId($d->producto);
                if (!$costo) throw new RuntimeException("Producto {$d->producto_id}: falta cuenta de Costo de venta.");
                $movs[] = [
                    'cuenta_id' => $costo,
                    'debito'    => 0.0,
                    'credito'   => $totalCosto,
                    'glosa'     => self::recortar('Reversión costo de venta · '.($d->producto->nombre ?? '')),
                ];
            }
        }

        /* ===========================
         * 4) Consolidar y validar
         * ===========================*/
        $movs = self::consolidarMovimientos($movs);
        $debitos  = round(array_sum(array_column($movs, 'debito')), 2);
        $creditos = round(array_sum(array_column($movs, 'credito')), 2);
        $diff     = round($debitos - $creditos, 2);

        if (abs($diff) >= 0.01) {
            Log::error('Descuadre en asiento de Nota Crédito', [
                'nota_credito_id' => $nc->id,
                'debitos' => $debitos,
                'creditos' => $creditos,
                'diff' => $diff,
                'movs' => $movs,
            ]);
            throw new RuntimeException("El asiento de la NC no cuadra (Δ = {$diff}).");
        }

        /* ===========================
         * 5) Registrar asiento
         * ===========================*/
        self::registrarAsiento([
            'fecha'        => $fecha,
            'glosa'        => $glosa,
            'documento'    => 'nota_credito',
            'documento_id' => $nc->id,
            'referencia'   => $glosaNum,
            'movimientos'  => array_values($movs),
        ]);

        // Si tienes PagosService que compensa factura con NC, lo invocamos
        if ($nc->factura_id && class_exists(\App\Services\PagosService::class)
            && method_exists(\App\Services\PagosService::class, 'aplicarNotaCreditoSobreFactura')) {
            try {
                \App\Services\PagosService::aplicarNotaCreditoSobreFactura($nc);
            } catch (\Throwable $e) {
                Log::warning('No se pudo aplicar NC sobre la factura: '.$e->getMessage(), ['nc_id' => $nc->id]);
            }
        }
    }

    /** Inserta el asiento y sus movimientos ya validados (cuadrados). */
    public static function registrarAsiento(array $payload): Asiento
    {
        $movs = $payload['movimientos'] ?? [];
        if (empty($movs)) throw new RuntimeException('No hay movimientos para registrar.');

        $deb = array_sum(array_column($movs, 'debito'));
        $cre = array_sum(array_column($movs, 'credito'));
        if (round($deb - $cre, 2) !== 0.0) {
            throw new RuntimeException("El asiento no cuadra (Δ = ".round($deb - $cre, 2).").");
        }

        return DB::transaction(function () use ($payload, $movs) {
            $asiento = new Asiento([
                'fecha'        => $payload['fecha']        ?? now()->toDateString(),
                'glosa'        => $payload['glosa']        ?? null,
                'documento'    => $payload['documento']    ?? null,
                'documento_id' => $payload['documento_id'] ?? null,
                'referencia'   => $payload['referencia']   ?? null,
            ]);
            $asiento->save();

            $now = now();
            $rows = collect($movs)->map(fn($m) => [
                'asiento_id' => $asiento->id,
                'cuenta_id'  => (int)($m['cuenta_id'] ?? 0),
                'debito'     => (float)($m['debito']  ?? 0),
                'credito'    => (float)($m['credito'] ?? 0),
                'glosa'      => (string)($m['glosa']  ?? null),
                'created_at' => $now,
                'updated_at' => $now,
            ])->toArray();

            Movimiento::insert($rows);

            $delta = round($asiento->movimientos()->sum('debito') - $asiento->movimientos()->sum('credito'), 2);
            if ($delta !== 0.0) {
                Log::error('Asiento no cuadra luego de insertar movimientos', [
                    'asiento_id' => $asiento->id, 'Δ' => $delta
                ]);
                throw new RuntimeException('El asiento no cuadra tras insertar movimientos.');
            }

            return $asiento;
        });
    }

    /** Crea un asiento inverso (para anular). */
    public static function revertirAsientoNotaCredito(NotaCredito $nc): void
    {
        self::revertirAsientoPorDocumento('nota_credito', (int) $nc->id);
    }

    public static function revertirAsientoPorDocumento(string $documento, int $documentoId): void
    {
        DB::transaction(function () use ($documento, $documentoId) {
            $asiento = Asiento::with('movimientos')
                ->where('documento', $documento)
                ->where('documento_id', $documentoId)
                ->latest('id')->first();

            if (!$asiento) {
                throw new RuntimeException("No se encontró asiento para {$documento} #{$documentoId}.");
            }

            $rev = new Asiento([
                'fecha'        => now()->toDateString(),
                'glosa'        => 'Reversión de: '.($asiento->glosa ?? "{$documento} {$documentoId}"),
                'documento'    => "{$documento}_reversion",
                'documento_id' => $documentoId,
                'referencia'   => ($asiento->referencia ?? null).' (REV)',
            ]);
            $rev->save();

            $now  = now();
            $rows = $asiento->movimientos->map(fn($m) => [
                'asiento_id' => $rev->id,
                'cuenta_id'  => (int) $m->cuenta_id,
                'debito'     => (float) $m->credito,
                'credito'    => (float) $m->debito,
                'glosa'      => 'Reversión · '.($m->glosa ?? ''),
                'created_at' => $now,
                'updated_at' => $now,
            ])->toArray();

            Movimiento::insert($rows);

            $delta = round($rev->movimientos()->sum('debito') - $rev->movimientos()->sum('credito'), 2);
            if ($delta !== 0.0) {
                Log::error('Asiento de reversión no cuadra', ['asiento_id' => $rev->id, 'Δ' => $delta]);
                throw new RuntimeException('El asiento de reversión no cuadra.');
            }
        });
    }

    /** Agrupa movimientos por cuenta y compensa débitos/créditos. */
    private static function consolidarMovimientos(array $movs): array
    {
        $by = [];
        foreach ($movs as $m) {
            $id = (int) $m['cuenta_id'];
            if (!isset($by[$id])) $by[$id] = ['cuenta_id' => $id, 'debito' => 0.0, 'credito' => 0.0, 'glosa' => []];
            $by[$id]['debito']  += (float)($m['debito']  ?? 0);
            $by[$id]['credito'] += (float)($m['credito'] ?? 0);
            if (!empty($m['glosa'])) $by[$id]['glosa'][] = self::recortar($m['glosa']);
        }

        foreach ($by as $id => &$r) {
            $d = round($r['debito'], 2);
            $c = round($r['credito'], 2);
            if (abs($d - $c) < 0.01) { unset($by[$id]); continue; } // se compensan

            $r['debito']  = max(0, $d - $c);
            $r['credito'] = max(0, $c - $d);
            $r['glosa']   = self::recortar(implode(' · ', array_filter($r['glosa'])));
        }
        return array_values($by);
    }

    private static function recortar(string $s, int $max = 120): string
    {
        $s = trim($s);
        return mb_strlen($s) > $max ? mb_substr($s, 0, $max - 1).'…' : $s;
    }

    /* ================== Helpers de resolución ================== */

    /**
     * Cuenta de ingreso específica para Nota Crédito (devoluciones).
     * 1) Campo directo en producto (cuenta_ingreso_devolucion_id)
     * 2) Tabla puente por tipo ('INGRESO_DEV' o 'DEVOLUCION')
     * 3) Fallback a ingreso normal del producto
     */
    private static function resolveCuentaIngresoNotaCredito(?Producto $producto): ?int
    {
        if (!$producto) return null;

        foreach (['cuenta_ingreso_devolucion_id', 'cuenta_devolucion_ingreso_id'] as $f) {
            if (isset($producto->{$f}) && $producto->{$f}) return (int) $producto->{$f};
        }

        if (method_exists($producto, 'cuentas')) {
            $id = $producto->cuentas()
                ->whereHas('tipo', fn($q) => $q->whereIn('codigo', ['INGRESO_DEV','DEVOLUCION']))
                ->value('plan_cuentas_id');
            if ($id) return (int) $id;
        }

        return self::resolveCuentaIngreso($producto);
    }

    /** Ingreso normal (compatibilidad) */
    private static function resolveCuentaIngreso(?Producto $producto): ?int
    {
        if (!$producto) return null;

        if ($producto->relationLoaded('cuentaIngreso') && $producto->cuentaIngreso?->id) {
            return (int) $producto->cuentaIngreso->id;
        }
        if (method_exists($producto, 'cuentaIngreso') && ($cu = $producto->cuentaIngreso()->value('id'))) {
            return (int) $cu;
        }

        if ($producto->relationLoaded('cuentas')) {
            $cu = optional($producto->cuentas->firstWhere(fn($c) => optional($c->tipo)->codigo === 'INGRESO'))->plan_cuentas_id;
            if ($cu) return (int) $cu;
        } elseif (method_exists($producto, 'cuentas')) {
            $cu = $producto->cuentas()
                ->whereHas('tipo', fn($q) => $q->where('codigo', 'INGRESO'))
                ->value('plan_cuentas_id');
            if ($cu) return (int) $cu;
        }
        return null;
    }

    /** Cuenta contable asociada a un impuesto (IVA ventas) */
    private static function resolveCuentaImpuesto(?Impuesto $imp): ?int
    {
        if (!$imp) return null;

        foreach (['plan_cuenta_id', 'plan_cuenta_pasivo_id', 'cuenta_id'] as $f) {
            if (isset($imp->{$f}) && $imp->{$f} && PlanCuentas::whereKey($imp->{$f})->exists()) {
                return (int) $imp->{$f};
            }
        }

        if (method_exists($imp, 'cuentaContable')) {
            if ($id = $imp->cuentaContable()->value('id')) return (int) $id;
        }

        // Fallbacks por nombre/código si tu PUC los guarda (opcional)
        return null;
    }

    /** Resuelve la cuenta de contrapartida (Caja/Bancos/CxC) */
    private static function resolveCuentaCobro(?int $cuentaIdSeleccionada, ?SocioNegocio $cliente): ?int
    {
        if ($cuentaIdSeleccionada && PlanCuentas::whereKey($cuentaIdSeleccionada)->exists()) {
            return (int) $cuentaIdSeleccionada;
        }

        if ($cliente) {
            foreach (['plan_cuenta_id','cuenta_cxc_id'] as $f) {
                if (isset($cliente->{$f}) && $cliente->{$f} && PlanCuentas::whereKey($cliente->{$f})->exists()) {
                    return (int) $cliente->{$f};
                }
            }
            if (method_exists($cliente, 'cuentas')) {
                if ($id = $cliente->cuentas()->value('cuenta_cxc_id')) {
                    if (PlanCuentas::whereKey($id)->exists()) return (int) $id;
                }
            }
        }

        // (último recurso) buscar Caja/Bancos por hints en tu PUC
        foreach ([
            ['clase_cuenta','CAJA_GENERAL'],
            ['clase_cuenta','BANCOS'],
        ] as [$col,$val]) {
            $id = PlanCuentas::where('cuenta_activa',1)
                ->where(fn($q)=>$q->where('titulo',0)->orWhereNull('titulo'))
                ->where($col,$val)->value('id');
            if ($id) return (int) $id;
        }

        return null;
    }
}
