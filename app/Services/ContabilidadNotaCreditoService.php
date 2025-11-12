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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Genera el asiento contable de una Nota Crédito (total o parcial).
 *
 * Estrategia:
 * 1) FAST-PATH: si la NC tiene factura origen con asiento, invierte las
 *    mismas cuentas de ese asiento, proporcionalmente al total de la NC.
 * 2) FALLBACK: si no hay asiento origen, usa la lógica configurable
 *    (DEVOLUCIÓN/IVA/INVENTARIO/COSTO) igual a la de Factura.
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

        if ($nc->detalles->isEmpty()) {
            throw new RuntimeException('La nota crédito no tiene líneas.');
        }
        if (!$nc->fecha) {
            throw new RuntimeException('La nota crédito no tiene fecha.');
        }
        if (!$nc->cuenta_cobro_id) {
            throw new RuntimeException('Seleccione una cuenta contable de cobro para la nota crédito.');
        }

        // Asegura totales actualizados
        $nc->recalcularTotales();

        $fecha   = $nc->fecha;
        $numFmt  = $nc->numero
            ? str_pad((string) $nc->numero, optional($nc->serie)->longitud ?? 6, '0', STR_PAD_LEFT)
            : (string) $nc->id;
        $glosaNum  = $nc->prefijo ? ($nc->prefijo . '-' . $numFmt) : $numFmt;
        $glosaBase = 'NC Venta ' . $glosaNum . ' — Cliente: ' . ($nc->cliente->razon_social ?? ('ID ' . $nc->socio_negocio_id));

        /* ===========================================================
         * FAST-PATH: Reversar mismas cuentas del asiento de la FACTURA
         * =========================================================== */
        if ($nc->factura_id) {
            $asientoFactura = Asiento::with('movimientos')
                ->where('origen', 'factura')
                ->where('origen_id', $nc->factura_id)
                ->orderByDesc('id')
                ->first();

            if ($asientoFactura && $asientoFactura->movimientos->isNotEmpty()) {
                // Suma total del asiento base (referencia de proporcionalidad)
                $totalAsientoFactura = (float) $asientoFactura->movimientos->sum('debito');
                $totalNC             = round((float) $nc->total, 2);

                if ($totalAsientoFactura > 0.0 && $totalNC > 0.0) {
                    // Ratio global si la NC es parcial
                    $ratio   = min(1.0, round($totalNC / $totalAsientoFactura, 6));
                    $movsInv = self::invertirMovsConRatio($asientoFactura->movimientos, $ratio);
                    $movsInv = self::consolidarArray($movsInv);

                    // Verificación y microajuste por redondeos
                    $deb = round(array_sum(array_column($movsInv, 'debito')), 2);
                    $cre = round(array_sum(array_column($movsInv, 'credito')), 2);

                    if (abs($deb - $cre) >= 0.01 && !empty($movsInv)) {
                        $delta = round($deb - $cre, 2);

                        // Corrige el mayor movimiento
                        usort(
                            $movsInv,
                            fn ($a, $b) => (max($b['debito'], $b['credito']) <=> max($a['debito'], $a['credito']))
                        );

                        if ($delta > 0) {
                            $movsInv[0]['credito'] = round($movsInv[0]['credito'] + $delta, 2);
                        } else {
                            $movsInv[0]['debito'] = round($movsInv[0]['debito'] + abs($delta), 2);
                        }

                        // Revalidar
                        $deb = round(array_sum(array_column($movsInv, 'debito')), 2);
                        $cre = round(array_sum(array_column($movsInv, 'credito')), 2);

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
                        'moneda'      => $nc->moneda ?? 'COP',
                    ]);

                    // Aplicación automática a la factura (si existe servicio)
                    if (
                        $nc->factura_id
                        && class_exists(\App\Services\PagosService::class)
                        && method_exists(\App\Services\PagosService::class, 'aplicarNotaCreditoSobreFactura')
                    ) {
                        try {
                            \App\Services\PagosService::aplicarNotaCreditoSobreFactura($nc);
                        } catch (\Throwable $e) {
                            Log::warning(
                                'NC no aplicada a factura: ' . $e->getMessage(),
                                ['nc_id' => $nc->id]
                            );
                        }
                    }

                    // ✅ Terminado por fast-path
                    return;
                }
            }
        }

        /* =========================================================
         * FALLBACK: lógica por tipos (DEVOLUCIÓN/IVA/INVENTARIO/COSTO)
         * ========================================================= */
        $movs = [];

        // 1) DÉBITOS: DEVOLUCIÓN + IVA
        foreach ($nc->detalles as $d) {
            $cant   = max(0, (float) $d->cantidad);
            if ($cant <= 0) {
                continue;
            }

            $precio = max(0, (float) $d->precio_unitario); // neto sin IVA
            $desc   = min(100, max(0, (float) $d->descuento_pct));
            $ivaPct = min(100, max(0, (float) $d->impuesto_pct));

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

            // Debe: Reversar IVA generado (cuenta IVA ventas)
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
        $totalNc  = round((float) $nc->total, 2);

        if ($totalNc > 0 && $ctaCobro) {
            $movs[] = [
                'cuenta_id' => $ctaCobro,
                'debito'    => 0.0,
                'credito'   => $totalNc,
                'glosa'     => self::recortar('Contrapartida (CxC/Caja/Bancos)'),
            ];
        }

        // 3) (OPCIONAL) Reversar COSTO y reingresar INVENTARIO
        if (!empty($nc->reponer_inventario)) {
            foreach ($nc->detalles as $d) {
                $cant = max(0, (float) $d->cantidad);
                if ($cant <= 0 || !$d->producto_id) {
                    continue;
                }

                // CPU alineado con factura original
                $cpu   = ContabilidadService::costoPromedioParaLinea($d->producto, $d->bodega_id ?? null);
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

                // Debe: Inventario
                $movs[] = [
                    'cuenta_id' => $ctaInv,
                    'debito'    => $costo,
                    'credito'   => 0.0,
                    'glosa'     => self::recortar('Reingreso inventario · ' . ($d->producto->nombre ?? '')),
                ];

                // Haber: Costo de venta
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

        $deb = round(array_sum(array_column($movs, 'debito')), 2);
        $cre = round(array_sum(array_column($movs, 'credito')), 2);

        if (abs($deb - $cre) >= 0.01) {
            Log::error('Descuadre en asiento de Nota Crédito', [
                'nota_credito_id' => $nc->id,
                'deb'             => $deb,
                'cre'             => $cre,
                'diff'            => round($deb - $cre, 2),
                'movs'            => $movs,
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
            'moneda'      => $nc->moneda ?? 'COP',
        ]);

        // Aplicación automática (si procede)
        if (
            $nc->factura_id
            && class_exists(\App\Services\PagosService::class)
            && method_exists(\App\Services\PagosService::class, 'aplicarNotaCreditoSobreFactura')
        ) {
            try {
                \App\Services\PagosService::aplicarNotaCreditoSobreFactura($nc);
            } catch (\Throwable $e) {
                Log::warning('NC no aplicada a factura: ' . $e->getMessage(), ['nc_id' => $nc->id]);
            }
        }
    }

    // ---------- Inserción segura del asiento (cuadrado) ----------
    private static function insertAsiento(array $payload): Asiento
    {
        $movs = $payload['movimientos'] ?? [];
        if (empty($movs)) {
            throw new RuntimeException('No hay movimientos para registrar.');
        }

        $deb = round(array_sum(array_column($movs, 'debito')), 2);
        $cre = round(array_sum(array_column($movs, 'credito')), 2);

        if ($deb !== $cre) {
            throw new RuntimeException('El asiento no cuadra.');
        }

        return DB::transaction(function () use ($payload, $movs, $deb) {
            /** @var \App\Models\Asiento\Asiento $asiento */
            $asiento = Asiento::create([
                'fecha'       => $payload['fecha'] ?? now()->toDateString(),
                'glosa'       => $payload['glosa'] ?? null,
                'origen'      => $payload['origen'] ?? null,
                'origen_id'   => $payload['origen_id'] ?? null,
                'referencia'  => $payload['referencia'] ?? null,
                'moneda'      => $payload['moneda'] ?? 'COP',
                'total_debe'  => $deb,
                'total_haber' => $deb, // igual al debe
                // 'tercero_id' => ... si asocias cliente
            ]);

            $now  = now();
            $rows = collect($movs)->map(static fn ($m) => [
                'asiento_id' => $asiento->id,
                'cuenta_id'  => (int) $m['cuenta_id'],
                'debito'     => (float) $m['debito'],
                'credito'    => (float) $m['credito'],
                'glosa'      => (string) ($m['glosa'] ?? null),
                'created_at' => $now,
                'updated_at' => $now,
            ])->toArray();

            Movimiento::insert($rows);

            // Verificación post–insert
            $deb2 = round($asiento->movimientos()->sum('debito'), 2);
            $cre2 = round($asiento->movimientos()->sum('credito'), 2);

            if ($deb2 !== $cre2 || $deb2 !== $deb) {
                Log::error('Asiento no cuadra tras insertar movimientos', [
                    'asiento_id' => $asiento->id,
                    'deb'        => $deb2,
                    'cre'        => $cre2,
                ]);

                throw new RuntimeException('El asiento no cuadra tras insertar movimientos.');
            }

            return $asiento;
        });
    }

    // ---------- Consolidación por cuenta (fallback) ----------
    private static function consolidar(array $movs): array
    {
        $by = [];

        foreach ($movs as $m) {
            $id = (int) $m['cuenta_id'];

            if (!isset($by[$id])) {
                $by[$id] = ['cuenta_id' => $id, 'debito' => 0.0, 'credito' => 0.0, 'glosa' => []];
            }

            $by[$id]['debito']  += (float) $m['debito'];
            $by[$id]['credito'] += (float) $m['credito'];

            if (!empty($m['glosa'])) {
                $by[$id]['glosa'][] = self::recortar($m['glosa']);
            }
        }

        foreach ($by as $id => &$r) {
            $d = round($r['debito'], 2);
            $c = round($r['credito'], 2);

            if (abs($d - $c) < 0.01) {
                unset($by[$id]);
                continue;
            }

            $r['debito']  = max(0, $d - $c);
            $r['credito'] = max(0, $c - $d);
            $r['glosa']   = self::recortar(implode(' · ', array_filter($r['glosa'])));
        }

        return array_values($by);
    }

    // ---------- Consolidación para el fast-path (array simple) ----------
    private static function consolidarArray(array $movs): array
    {
        $by = [];

        foreach ($movs as $m) {
            $id = (int) $m['cuenta_id'];

            if (!isset($by[$id])) {
                $by[$id] = ['cuenta_id' => $id, 'debito' => 0.0, 'credito' => 0.0, 'glosa' => []];
            }

            $by[$id]['debito']  += (float) $m['debito'];
            $by[$id]['credito'] += (float) $m['credito'];

            if (!empty($m['glosa'])) {
                $by[$id]['glosa'][] = self::recortar($m['glosa']);
            }
        }

        foreach ($by as $id => &$r) {
            $r['debito']  = round($r['debito'], 2);
            $r['credito'] = round($r['credito'], 2);
            $r['glosa']   = self::recortar(implode(' · ', array_filter($r['glosa'])));
        }

        return array_values($by);
    }

    // ---------- Invierte movimientos de un asiento con un ratio ----------
    private static function invertirMovsConRatio($movs, float $ratio): array
    {
        $ratio = max(0.0, $ratio);
        $rows  = [];

        foreach ($movs as $m) {
            $deb = round((float) $m->debito * $ratio, 2);
            $cre = round((float) $m->credito * $ratio, 2);

            // Invierte: lo que fue débito va a crédito y viceversa
            $rows[] = [
                'cuenta_id' => (int) $m->cuenta_id,
                'debito'    => $cre,
                'credito'   => $deb,
                'glosa'     => self::recortar('Reverso (NC) · ' . (string) ($m->glosa ?? '')),
            ];
        }

        return $rows;
    }

    private static function recortar(string $s, int $max = 120): string
    {
        $s = trim($s);

        return mb_strlen($s) > $max
            ? mb_substr($s, 0, $max - 1) . '…'
            : $s;
    }

    /* ===================== Resolución de cuentas (fallback) ===================== */

    private static function cuentaDevolucion(?Producto $p): ?int
    {
        if (!$p) {
            return null;
        }

        return ContabilidadService::cuentaSegunConfiguracion($p, 'DEVOLUCION')
            ?? ContabilidadService::cuentaSegunConfiguracion($p, 'INGRESO_DEV')
            ?? ContabilidadService::cuentaSegunConfiguracion($p, 'INGRESO');
    }

    private static function cuentaIvaVentas(?Producto $p, ?Impuesto $imp): ?int
    {
        $cta = ContabilidadService::cuentaSegunConfiguracion($p, 'IVA');
        if ($cta) {
            return $cta;
        }

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

        // Hints: CAJA/BANCOS
        foreach ([['clase_cuenta', 'CAJA_GENERAL'], ['clase_cuenta', 'BANCOS']] as [$col, $val]) {
            $id = PlanCuentas::where('cuenta_activa', 1)
                ->where(fn ($q) => $q->where('titulo', 0)->orWhereNull('titulo'))
                ->where($col, $val)
                ->value('id');

            if ($id) {
                return (int) $id;
            }
        }

        return null;
    }

    /* ============ Reversión del asiento de la NC (anulación) ============ */

    public static function revertirAsientoNotaCredito(NotaCredito $nc): Asiento
    {
        return DB::transaction(function () use ($nc) {
            // 1) Encontrar el asiento original de la NC por tu esquema
            /** @var \App\Models\Asiento\Asiento|null $orig */
            $orig = Asiento::with('movimientos')
                ->where('origen', 'nota_credito')
                ->where('origen_id', $nc->id)
                ->orderByDesc('id')
                ->first();

            if (!$orig) {
                throw new RuntimeException('No se encontró el asiento original de la Nota Crédito.');
            }
            if ($orig->movimientos->isEmpty()) {
                throw new RuntimeException('El asiento original no tiene movimientos.');
            }

            // 2) Evitar doble reversión
            $yaRev = Asiento::where('origen', 'nota_credito_reverso')
                ->where('origen_id', $nc->id)
                ->exists();

            if ($yaRev) {
                throw new RuntimeException('El asiento de la Nota Crédito ya fue revertido.');
            }

            // 3) Crear cabecera del reverso
            $reverso = Asiento::create([
                'fecha'       => now()->toDateString(), // o $nc->fecha si prefieres
                'glosa'       => 'Reverso de asiento NC ID ' . $nc->id,
                'origen'      => 'nota_credito_reverso',
                'origen_id'   => $nc->id,
                'moneda'      => 'COP',
                'total_debe'  => 0,
                'total_haber' => 0,
            ]);

            // 4) Insertar movimientos invertidos
            $now  = now();
            $rows = $orig->movimientos->map(static function ($m) use ($reverso, $now) {
                return [
                    'asiento_id' => $reverso->id,
                    'cuenta_id'  => (int) $m->cuenta_id,
                    'debito'     => (float) $m->credito, // invierte
                    'credito'    => (float) $m->debito,  // invierte
                    'glosa'      => 'Reverso: ' . (string) ($m->glosa ?? ''),
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            })->toArray();

            Movimiento::insert($rows);

            // 5) Calcular y guardar totales del reverso
            $deb = round($reverso->movimientos()->sum('debito'), 2);
            $cre = round($reverso->movimientos()->sum('credito'), 2);

            if ($deb !== $cre) {
                Log::error('Reverso NC descuadrado', [
                    'reverso_id' => $reverso->id,
                    'deb'        => $deb,
                    'cre'        => $cre,
                ]);

                throw new RuntimeException('El reverso contable no cuadra.');
            }

            $reverso->update([
                'total_debe'  => $deb,
                'total_haber' => $cre,
            ]);

            return $reverso;
        }, 3);
    }
}
