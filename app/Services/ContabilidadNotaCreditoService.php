<?php
declare(strict_types=1);

namespace App\Services;

use App\Models\Asiento\Asiento;
use App\Models\Movimiento\Movimiento;
use App\Models\NotaCredito;
use App\Models\Impuestos\Impuesto;
use App\Models\Productos\Producto;
use App\Models\SocioNegocio\SocioNegocio;
use App\Models\CuentasContables\PlanCuentas;
use App\Models\Movimiento\ProductoCostoMovimiento;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use App\Services\ContabilidadService; // necesario para resolver cuentas/costos

class ContabilidadNotaCreditoService
{
    /* ============================================================
     * Utilidades
     * ============================================================ */

    /** Normaliza strings numéricos ("1,428.00") a float seguro. */
    private static function f(mixed $v): float
    {
        if (is_string($v)) {
            $v = preg_replace('/[^\d\.\-]/', '', str_replace(',', '', $v));
        }
        return (float) $v;
    }

    /** Acorta glosas con ellipsis. */
    private static function recortar(string $s, int $max = 120): string
    {
        $s = trim($s);
        return mb_strlen($s) > $max ? mb_substr($s, 0, $max - 1) . '…' : $s;
    }

    /* ============================================================
     * API principal
     * ============================================================ */

    /**
     * Genera el asiento contable de una Nota Crédito.
     * - FAST-PATH: invierte proporcionalmente el asiento de la factura origen.
     * - FALLBACK : DEV+IVA vs CxC/Caja/Bancos y opcional Inventario↔Costo.
     */
    public static function asientoDesdeNotaCredito(NotaCredito $nc): void
    {
        $nc->loadMissing(
            'detalles.producto.cuentas.tipo',
            'detalles.producto.impuesto',
            'cliente',
            'serie'
        );

        if ($nc->detalles->isEmpty())  throw new RuntimeException('La nota crédito no tiene líneas.');
        if (!$nc->fecha)               throw new RuntimeException('La nota crédito no tiene fecha.');
        if (!$nc->cuenta_cobro_id)     throw new RuntimeException('Seleccione una cuenta contable de cobro para la nota crédito.');

        if (method_exists($nc, 'recalcularTotales')) {
            $nc->recalcularTotales();
        }

        $fecha     = $nc->fecha;
        $numFmt    = $nc->numero
            ? str_pad((string) $nc->numero, optional($nc->serie)->longitud ?? 6, '0', STR_PAD_LEFT)
            : (string) $nc->id;
        $glosaNum  = $nc->prefijo ? ($nc->prefijo . '-' . $numFmt) : $numFmt;
        $glosaBase = 'NC Venta ' . $glosaNum . ' — Cliente: ' . ($nc->cliente->razon_social ?? ('ID ' . $nc->socio_negocio_id));

        /* ========================= FAST-PATH ========================= */
        if ($nc->factura_id) {
            $asientoFactura = Asiento::with('movimientos')
                ->where('origen_id', $nc->factura_id)
                ->where(function ($q) {
                    $q->where('origen', 'factura')
                      ->orWhere('origen', 'Factura')
                      ->orWhere('origen_type', 'like', '%Factura%');
                })
                ->latest('id')
                ->first();

            if ($asientoFactura && $asientoFactura->movimientos->isNotEmpty()) {
                $totalAsientoFactura = self::f($asientoFactura->movimientos->sum('debito'));
                $totalNC             = round(self::f($nc->total), 2);

                if ($totalAsientoFactura > 0.0 && $totalNC > 0.0) {
                    $ratio   = min(1.0, round($totalNC / $totalAsientoFactura, 6));
                    $movsInv = self::invertirMovsConRatio($asientoFactura->movimientos, $ratio);
                    $movsInv = self::consolidarArray($movsInv);

                    // Verifica cuadratura
                    $deb = round(array_sum(array_map('floatval', array_column($movsInv, 'debito'))), 2);
                    $cre = round(array_sum(array_map('floatval', array_column($movsInv, 'credito'))), 2);
                    if (abs($deb - $cre) >= 0.01) {
                        $delta = round($deb - $cre, 2);
                        if ($delta !== 0.0 && !empty($movsInv)) {
                            usort($movsInv, fn($a, $b) =>
                                (max(self::f($b['debito']), self::f($b['credito'])) <=> max(self::f($a['debito']), self::f($a['credito'])))
                            );
                            if ($delta > 0)  $movsInv[0]['credito'] = round(self::f($movsInv[0]['credito']) + $delta, 2);
                            else             $movsInv[0]['debito']  = round(self::f($movsInv[0]['debito'])  + abs($delta), 2);
                        }
                        $deb = round(array_sum(array_map('floatval', array_column($movsInv, 'debito'))), 2);
                        $cre = round(array_sum(array_map('floatval', array_column($movsInv, 'credito'))), 2);
                        if (round($deb - $cre, 2) !== 0.0) {
                            throw new RuntimeException('El asiento proporcional (fast-path) no cuadra.');
                        }
                    }

                    self::insertAsiento([
                        'fecha'       => $fecha,
                        'glosa'       => $glosaBase,
                        'origen'      => 'nota_credito',
                        'origen_id'   => $nc->id,
                        'referencia'  => $glosaNum,
                        'movimientos' => $movsInv,
                    ]);

                    if ($nc->factura_id && class_exists(\App\Services\PagosService::class)
                        && method_exists(\App\Services\PagosService::class, 'aplicarNotaCreditoSobreFactura')) {
                        try {
                            \App\Services\PagosService::aplicarNotaCreditoSobreFactura($nc);
                        } catch (\Throwable $e) {
                            Log::warning('NC no aplicada a factura: ' . $e->getMessage(), ['nc_id' => $nc->id]);
                        }
                    }
                    return;
                }
            }
        }

        /* ========================= FALLBACK ========================= */
        $movs = [];

        // 1) DÉBITOS: DEVOLUCIÓN + IVA
        foreach ($nc->detalles as $d) {
            $cant   = max(0.0, self::f($d->cantidad));
            if ($cant <= 0) continue;

            $precio = max(0.0, self::f($d->precio_unitario)); // neto sin IVA
            $desc   = min(100.0, max(0.0, self::f($d->descuento_pct)));
            $ivaPct = min(100.0, max(0.0, self::f($d->impuesto_pct)));

            $base = round($cant * $precio * (1 - $desc / 100), 2);
            $iva  = round($base * $ivaPct / 100, 2);

            // Debe: DEVOLUCIÓN EN VENTAS
            $ctaDev = self::cuentaDevolucion($d->producto);
            if ($base > 0 && !$ctaDev) {
                throw new RuntimeException("Producto {$d->producto_id}: falta cuenta de 'Ingreso por devolución'.");
            }
            if ($base > 0) {
                $movs[] = [
                    'cuenta_id' => $ctaDev,
                    'debito'    => $base,
                    'credito'   => 0.0,
                    'glosa'     => self::recortar('Devolución en ventas · ' . ($d->descripcion ?? '')),
                ];
            }

            // Debe: Reversar IVA generado (IVA ventas)
            if ($iva > 0 && $d->impuesto_id) {
                $imp    = Impuesto::find($d->impuesto_id);
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

        // 2) HABER: CxC/Caja/Bancos por TOTAL NC
        $ctaCobro = self::cuentaCobro($nc->cuenta_cobro_id, $nc->cliente);
        $totalNc  = round(self::f($nc->total), 2);
        if ($totalNc > 0 && !$ctaCobro) {
            throw new RuntimeException('No se pudo resolver la cuenta de contrapartida (CxC/Caja/Bancos) para la Nota Crédito.');
        }
        if ($totalNc > 0) {
            $movs[] = [
                'cuenta_id' => $ctaCobro,
                'debito'    => 0.0,
                'credito'   => $totalNc,
                'glosa'     => self::recortar('Contrapartida (CxC/Caja/Bancos)'),
            ];
        }

        // 3) COSTO / INVENTARIO (opcional)
          if (!empty($nc->reponer_inventario)) {
            foreach ($nc->detalles as $d) {
                $cant = max(0.0, self::f($d->cantidad));
                if ($cant <= 0 || !$d->producto_id) {
                    continue;
                }

                // 🔹 NUEVO: intentar usar el MISMO costo que usó la factura
                $cpu = self::resolverCostoHistoricoParaNC($nc, $d);

                $costo = round($cpu * $cant, 2);
                if ($costo <= 0) {
                    continue;
                }

                $ctaInv   = self::cuentaInventario($d->producto);
                $ctaCosto = self::cuentaCosto($d->producto);

                if (!$ctaInv) {
                    throw new RuntimeException("Producto {$d->producto_id}: falta cuenta de INVENTARIO.");
                }
                if (!$ctaCosto) {
                    throw new RuntimeException("Producto {$d->producto_id}: falta cuenta de COSTO.");
                }

                // Debe: INVENTARIO (reingreso)
                $movs[] = [
                    'cuenta_id' => $ctaInv,
                    'debito'    => $costo,
                    'credito'   => 0.0,
                    'glosa'     => self::recortar('Reingreso inventario · ' . ($d->producto->nombre ?? '')),
                ];

                // Haber: COSTO (reversión costo de venta)
                $movs[] = [
                    'cuenta_id' => $ctaCosto,
                    'debito'    => 0.0,
                    'credito'   => $costo,
                    'glosa'     => self::recortar('Reversión costo de venta · ' . ($d->producto->nombre ?? '')),
                ];
            }
        }
        // 4) Consolidar y validar
        $movs = self::consolidar($movs);
        $deb  = round(array_sum(array_map('floatval', array_column($movs, 'debito'))), 2);
        $cre  = round(array_sum(array_map('floatval', array_column($movs, 'credito'))), 2);
        if (abs($deb - $cre) >= 0.01) {
            Log::error('Descuadre en asiento de Nota Crédito', [
                'nota_credito_id' => $nc->id,
                'deb' => $deb,
                'cre' => $cre,
                'diff' => round($deb - $cre, 2),
                'movs' => $movs,
            ]);
            throw new RuntimeException('El asiento de la NC no cuadra.');
        }

        // 5) Insertar asiento
        self::insertAsiento([
            'fecha'       => $fecha,
            'glosa'       => $glosaBase,
            'origen'      => 'nota_credito',
            'origen_id'   => $nc->id,
            'referencia'  => $glosaNum,
            'movimientos' => $movs,
        ]);

        if ($nc->factura_id && class_exists(\App\Services\PagosService::class)
            && method_exists(\App\Services\PagosService::class, 'aplicarNotaCreditoSobreFactura')) {
            try {
                \App\Services\PagosService::aplicarNotaCreditoSobreFactura($nc);
            } catch (\Throwable $e) {
                Log::warning('NC no aplicada a factura: ' . $e->getMessage(), ['nc_id' => $nc->id]);
            }
        }
    }

    private static function resolverCostoHistoricoParaNC(NotaCredito $nc, $detalle): float
    {
        try {
            if (
                $nc->factura_id &&
                $detalle->producto_id &&
                !empty($detalle->bodega_id)
            ) {
                $row = ProductoCostoMovimiento::where('producto_id', $detalle->producto_id)
                    ->where('bodega_id', $detalle->bodega_id)
                    ->where('doc_id', $nc->factura_id)
                    ->orderByDesc('id')
                    ->first();

                if ($row && $row->costo_unit_mov !== null) {
                    return self::f($row->costo_unit_mov);
                }
            }
        } catch (\Throwable $e) {
            Log::warning('NC: no se pudo resolver costo histórico, usando promedio.', [
                'nc_id'        => $nc->id ?? null,
                'detalle_id'   => $detalle->id ?? null,
                'producto_id'  => $detalle->producto_id ?? null,
                'bodega_id'    => $detalle->bodega_id ?? null,
                'msg'          => $e->getMessage(),
            ]);
        }

        // Fallback: comportamiento original (costo promedio por bodega)
        return self::f(
            ContabilidadService::costoPromedioParaLinea(
                $detalle->producto,
                $detalle->bodega_id ?? null
            )
        );
    }
    /* ============================================================
     * Persistencia de asientos
     * ============================================================ */
    private static function insertAsiento(array $payload): Asiento
    {
        $movs = $payload['movimientos'] ?? [];
        if (empty($movs)) {
            throw new RuntimeException('No hay movimientos para registrar.');
        }

        $deb = round(array_sum(array_map('floatval', array_column($movs, 'debito'))), 2);
        $cre = round(array_sum(array_map('floatval', array_column($movs, 'credito'))), 2);
        if ($deb !== $cre) {
            throw new RuntimeException('El asiento no cuadra.');
        }

        return DB::transaction(function () use ($payload, $movs, $deb, $cre) {
            $asiento = Asiento::create([
                'fecha'       => $payload['fecha'] ?? now()->toDateString(),
                'glosa'       => $payload['glosa'] ?? null,
                'origen'      => $payload['origen'] ?? null,
                'origen_id'   => $payload['origen_id'] ?? null,
                'referencia'  => $payload['referencia'] ?? null,
                'moneda'      => $payload['moneda'] ?? 'COP',
                'total_debe'  => $deb,
                'total_haber' => $cre,
            ]);

            $now  = now();
            $rows = collect($movs)->map(fn(array $m) => [
                'asiento_id' => $asiento->id,
                'cuenta_id'  => (int) $m['cuenta_id'],
                'debito'     => (float) $m['debito'],
                'credito'    => (float) $m['credito'],
                'glosa'      => (string) ($m['glosa'] ?? null),
                'created_at' => $now,
                'updated_at' => $now,
            ])->toArray();

            Movimiento::insert($rows);

            $deb2 = round(self::f($asiento->movimientos()->sum('debito')), 2);
            $cre2 = round(self::f($asiento->movimientos()->sum('credito')), 2);
            if ($deb2 !== $cre2 || $deb2 !== $deb) {
                Log::error('Asiento no cuadra tras insertar movimientos', [
                    'asiento_id' => $asiento->id, 'deb' => $deb2, 'cre' => $cre2,
                ]);
                throw new RuntimeException('El asiento no cuadra tras insertar movimientos.');
            }

            return $asiento;
        });
    }

    /* ============================================================
     * Consolidaciones
     * ============================================================ */

    /** Consolida por cuenta y netea débitos vs créditos. */
    private static function consolidar(array $movs): array
    {
        $by = [];
        foreach ($movs as $m) {
            $id = (int) $m['cuenta_id'];
            if (!isset($by[$id])) {
                $by[$id] = ['cuenta_id' => $id, 'debito' => 0.0, 'credito' => 0.0, 'glosa' => []];
            }
            $by[$id]['debito']  += self::f($m['debito']);
            $by[$id]['credito'] += self::f($m['credito']);
            if (!empty($m['glosa'])) $by[$id]['glosa'][] = self::recortar($m['glosa']);
        }

        foreach ($by as $id => &$r) {
            $d = round(self::f($r['debito']), 2);
            $c = round(self::f($r['credito']), 2);
            if (abs($d - $c) < 0.01) { unset($by[$id]); continue; }
            $r['debito']  = max(0.0, $d - $c);
            $r['credito'] = max(0.0, $c - $d);
            $r['glosa']   = self::recortar(implode(' · ', array_filter($r['glosa'])));
        }

        return array_values($by);
    }

    /** Consolida por cuenta (sin netear), redondea y concatena glosas. */
    private static function consolidarArray(array $movs): array
    {
        $by = [];
        foreach ($movs as $m) {
            $id = (int) $m['cuenta_id'];
            if (!isset($by[$id])) {
                $by[$id] = ['cuenta_id' => $id, 'debito' => 0.0, 'credito' => 0.0, 'glosa' => []];
            }
            $by[$id]['debito']  += self::f($m['debito']);
            $by[$id]['credito'] += self::f($m['credito']);
            if (!empty($m['glosa'])) $by[$id]['glosa'][] = self::recortar($m['glosa']);
        }
        foreach ($by as $id => &$r) {
            $r['debito']  = round(self::f($r['debito']), 2);
            $r['credito'] = round(self::f($r['credito']), 2);
            $r['glosa']   = self::recortar(implode(' · ', array_filter($r['glosa'])));
        }
        return array_values($by);
    }

    /** Invierte movimientos existentes proporcionalmente (ratio). */
    private static function invertirMovsConRatio($movs, float $ratio): array
    {
        $ratio = max(0.0, $ratio);
        $rows  = [];
        foreach ($movs as $m) {
            $deb = round(self::f($m->debito)  * $ratio, 2);
            $cre = round(self::f($m->credito) * $ratio, 2);
            $rows[] = [
                'cuenta_id' => (int) $m->cuenta_id,
                'debito'    => $cre, // invertido
                'credito'   => $deb, // invertido
                'glosa'     => self::recortar('Reverso (NC) · ' . (string) ($m->glosa ?? '')),
            ];
        }
        return $rows;
    }

    /* ============================================================
     * Resolución de cuentas
     * ============================================================ */

    private static function cuentaDevolucion(?Producto $p): ?int
    {
        if (!$p) return null;
        return ContabilidadService::cuentaSegunConfiguracion($p, 'DEVOLUCION')
            ?? ContabilidadService::cuentaSegunConfiguracion($p, 'INGRESO_DEV')
            ?? ContabilidadService::cuentaSegunConfiguracion($p, 'INGRESO');
    }

    private static function cuentaIvaVentas(?Producto $p, ?Impuesto $imp): ?int
    {
        $cta = ContabilidadService::cuentaSegunConfiguracion($p, 'IVA');
        if ($cta) return $cta;

        foreach (['plan_cuenta_id', 'plan_cuenta_pasivo_id', 'cuenta_id'] as $f) {
            if ($imp && isset($imp->{$f}) && $imp->{$f} && PlanCuentas::whereKey($imp->{$f})->exists()) {
                return (int) $imp->{$f};
            }
        }
        return null;
    }

    private static function cuentaInventario(?Producto $p): ?int
    {
        return $p ? ContabilidadService::cuentaSegunConfiguracion($p, 'INVENTARIO') : null;
    }

    private static function cuentaCosto(?Producto $p): ?int
    {
        return $p ? ContabilidadService::cuentaSegunConfiguracion($p, 'COSTO') : null;
    }

    private static function cuentaCobro(?int $seleccionada, ?SocioNegocio $cliente): ?int
    {
        if ($seleccionada && PlanCuentas::whereKey($seleccionada)->exists()) {
            return (int) $seleccionada;
        }

        if ($cliente) {
            foreach (['plan_cuenta_id', 'cuenta_cxc_id'] as $f) {
                if (isset($cliente->{$f}) && $cliente->{$f} && PlanCuentas::whereKey($cliente->{$f})->exists()) {
                    return (int) $cliente->{$f};
                }
            }
        }

        // Fallback: Caja / Bancos
        foreach ([['clase_cuenta', 'CAJA_GENERAL'], ['clase_cuenta', 'BANCOS']] as [$col, $val]) {
            $id = PlanCuentas::where('cuenta_activa', 1)
                ->where(fn($q) => $q->where('titulo', 0)->orWhereNull('titulo'))
                ->where($col, $val)->value('id');
            if ($id) return (int) $id;
        }
        return null;
    }

    /* ============================================================
     * Reverso de asientos
     * ============================================================ */

    /** Genera el reverso del asiento de la Nota Crédito. */
    public static function revertirAsientoNotaCredito(NotaCredito $nc): Asiento
    {
        return DB::transaction(function () use ($nc) {
            $orig = Asiento::with('movimientos')
                ->where('origen', 'nota_credito')
                ->where('origen_id', $nc->id)
                ->latest('id')
                ->first();

            if (!$orig)            throw new RuntimeException('No se encontró el asiento original de la Nota Crédito.');
            if ($orig->movimientos->isEmpty()) throw new RuntimeException('El asiento original no tiene movimientos.');

            $yaRev = Asiento::where('origen', 'nota_credito_reverso')
                ->where('origen_id', $nc->id)
                ->exists();
            if ($yaRev)            throw new RuntimeException('El asiento de la Nota Crédito ya fue revertido.');

            $reverso = Asiento::create([
                'fecha'       => now()->toDateString(),
                'glosa'       => 'Reverso de asiento NC ID ' . $nc->id,
                'origen'      => 'nota_credito_reverso',
                'origen_id'   => $nc->id,
                'moneda'      => 'COP',
                'total_debe'  => 0,
                'total_haber' => 0,
            ]);

            $now  = now();
            $rows = $orig->movimientos->map(function ($m) use ($reverso, $now) {
                return [
                    'asiento_id' => $reverso->id,
                    'cuenta_id'  => (int) $m->cuenta_id,
                    'debito'     => (float) $m->credito,
                    'credito'    => (float) $m->debito,
                    'glosa'      => 'Reverso: ' . (string) ($m->glosa ?? ''),
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            })->toArray();

            Movimiento::insert($rows);

            $deb = round(self::f($reverso->movimientos()->sum('debito')), 2);
            $cre = round(self::f($reverso->movimientos()->sum('credito')), 2);
            if ($deb !== $cre) {
                Log::error('Reverso NC descuadrado', ['reverso_id' => $reverso->id, 'deb' => $deb, 'cre' => $cre]);
                throw new RuntimeException('El reverso contable no cuadra.');
            }

            $reverso->update(['total_debe' => $deb, 'total_haber' => $cre]);
            return $reverso;
        }, 3);
    }
}
