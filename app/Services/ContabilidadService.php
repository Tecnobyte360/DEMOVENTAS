<?php

namespace App\Services;

use App\Models\Asiento\Asiento;
use App\Models\CuentasContables\PlanCuentas;
use App\Models\Factura\Factura;
use App\Models\Factura\FacturaPago;
use App\Models\MediosPago\MedioPagoCuenta;
use App\Models\Movimiento\Movimiento;
use App\Models\Productos\Producto;
use App\Models\Productos\ProductoCuentaTipo;
use App\Models\MediosPago\MedioPagos;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ContabilidadService
{
    /* ============================================================
     *  RESOLUCIÓN DINÁMICA DE CUENTAS
     * ============================================================ */

    protected static function tipoId(string $codigo): ?int
    {
        return cache()->remember("pcta:tipo:$codigo", 600, function () use ($codigo) {
            return (int) (ProductoCuentaTipo::where('codigo', $codigo)->value('id') ?? 0) ?: null;
        });
    }

    protected static function cuentaDeProductoPorTipo(?Producto $p, string $tipoCodigo): ?int
    {
        if (!$p) return null;

        $tipoId = self::tipoId($tipoCodigo);
        if (!$tipoId) return null;

        $cuenta = $p->relationLoaded('cuentas')
            ? $p->cuentas->firstWhere('tipo_id', $tipoId)
            : $p->cuentas()->where('tipo_id', $tipoId)->first();

        return $cuenta?->plan_cuentas_id ? (int) $cuenta->plan_cuentas_id : null;
    }

    protected static function cuentaIvaParaProducto(?Producto $p): ?int
    {
        if (!$p) return null;

        $cta = self::cuentaDeProductoPorTipo($p, 'IVA');
        if ($cta) return $cta;

        $imp = $p->relationLoaded('impuesto') ? $p->impuesto : $p->impuesto()->with('cuenta')->first();
        if ($imp && !empty($imp->cuenta_id)) {
            return (int) $imp->cuenta_id;
        }

        return null;
    }

    protected static function cuentaInventarioProducto(?Producto $p): ?int
    {
        return self::cuentaDeProductoPorTipo($p, 'INVENTARIO');
    }

    protected static function cuentaCostoProducto(?Producto $p): ?int
    {
        return self::cuentaDeProductoPorTipo($p, 'COSTO');
    }

    protected static function esDeudora(PlanCuentas $c): bool
    {
        $nat = strtoupper((string) $c->naturaleza);
        if (in_array($nat, ['D','DEUDORA','ACTIVO','ACTIVOS','GASTO','GASTOS','COSTO','COSTOS','INVENTARIO'])) return true;
        if (in_array($nat, ['C','ACREEDORA','PASIVO','PASIVOS','PATRIMONIO','INGRESOS'])) return false;

        $first = substr((string) $c->codigo, 0, 1);
        return in_array($first, ['1','5','6']);
    }

    /* ============================================================
     *  POSTEOS
     * ============================================================ */

    protected static function postAumento(Asiento $asiento, int $cuentaId, float $monto, ?string $detalle = null, array $meta = []): Movimiento
    {
        /** @var PlanCuentas $c */
        $c = PlanCuentas::lockForUpdate()->findOrFail($cuentaId);
        if (!$c->cuenta_activa || $c->titulo) {
            throw new \RuntimeException("Cuenta no imputable o inactiva (id={$cuentaId}).");
        }

        $debe  = self::esDeudora($c) ? $monto : 0.0;
        $haber = self::esDeudora($c) ? 0.0   : $monto;

        return self::postMovimiento($asiento, $c, $debe, $haber, $detalle, $meta);
    }

    protected static function post(Asiento $asiento, int $cuentaId, float $debe, float $haber, ?string $detalle = null, array $meta = []): Movimiento
    {
        /** @var PlanCuentas $c */
        $c = PlanCuentas::lockForUpdate()->findOrFail($cuentaId);
        if (!$c->cuenta_activa || $c->titulo) {
            throw new \RuntimeException("Cuenta no imputable o inactiva (id={$cuentaId}).");
        }
        return self::postMovimiento($asiento, $c, $debe, $haber, $detalle, $meta);
    }

    protected static function postMovimiento(Asiento $asiento, PlanCuentas $cuenta, float $debe, float $haber, ?string $detalle = null, array $meta = []): Movimiento
    {
        try {
            $payload = array_merge([
                'asiento_id' => $asiento->id,
                'cuenta_id'  => $cuenta->id,
                'debito'     => round($debe, 2),
                'credito'    => round($haber, 2),

                // columnas legacy si existen
                'debe'       => 0.0,
                'haber'      => 0.0,
                'detalle'    => $detalle,
            ], $meta);

            $mov = Movimiento::create($payload);
        } catch (QueryException $qe) {
            throw new \RuntimeException("Error al insertar movimiento: ".$qe->getMessage(), 0, $qe);
        }

        $delta = self::esDeudora($cuenta)
            ? ($mov->debito - $mov->credito)
            : ($mov->credito - $mov->debito);

        PlanCuentas::whereKey($cuenta->id)->update([
            'saldo' => DB::raw('saldo + ' . number_format($delta, 2, '.', '')),
        ]);

        return $mov;
    }

    protected static function costoPromedioUnitario(Producto $p): float
    {
        return round((float) ($p->costo_promedio ?? $p->costo ?? 0), 4);
    }

    /* ============================================================
     *  UTILIDADES DE CUENTAS ESPECÍFICAS
     * ============================================================ */

    protected static function cuentaPorCodigo(string $codigo): ?int
    {
        $codigo = trim($codigo);
        if ($codigo === '') return null;

        return cache()->remember("puc:codigo:$codigo", 600, function () use ($codigo) {
            return PlanCuentas::where('codigo', $codigo)
                ->where('cuenta_activa', 1)
                ->where(function ($q) { $q->where('titulo', 0)->orWhereNull('titulo'); })
                ->value('id');
        });
    }

    /** Cuenta asociada a un medio de pago. */
    protected static function cuentaDesdeMedioPago(?MedioPagos $medio): ?int
    {
        if (!$medio) return null;

        // 1) Mapeo explícito en medio_pago_cuentas
        if ($medio->relationLoaded('cuentas')) {
            $map = $medio->cuentas->first();
        } else {
            $map = MedioPagoCuenta::where('medio_pago_id', $medio->id)->first();
        }
        if ($map && $map->plan_cuentas_id) {
            return (int) $map->plan_cuentas_id;
        }

        // 2) Campo directo opcional en medio_pagos
        if (!empty($medio->cuenta_contable_id)) {
            return (int) $medio->cuenta_contable_id;
        }

        // 3) Heurística (último recurso)
        $codigo = strtoupper((string)($medio->codigo ?? ''));
        $nombre = strtoupper((string)($medio->nombre ?? ''));

        if (str_contains($codigo, 'CAJA') || str_contains($nombre, 'CAJA') || str_contains($nombre, 'EFECTIVO')) {
            return PlanCuentas::where('clase_cuenta', 'CAJA_GENERAL')
                ->where('cuenta_activa', 1)->where('titulo', 0)->value('id');
        }
        if (str_contains($codigo, 'BAN') || str_contains($nombre, 'BANCO') || str_contains($nombre, 'TRANSFER')) {
            return PlanCuentas::where('clase_cuenta', 'BANCOS')
                ->where('cuenta_activa', 1)->where('titulo', 0)->value('id');
        }

        return null;
    }

    /* ============================================================
     *  ASIENTOS DE COBRO
     * ============================================================ */

    /** Asiento por **un solo** pago (sigue disponible). */
    public static function asientoDesdePago(Factura $f, FacturaPago $pago): Asiento
    {
        return self::asientoDesdePagos($f, collect([$pago]), 'Cobro Factura');
    }

    /**
     * Asiento por **varios pagos** (un debe por cada medio y un haber a 1305).
     *
     * Debe  : cuentas de medios (Caja/Banco) por cada medio.
     * Haber : 1305 (CxC) o cuenta_cobro_id por el total.
     */
    public static function asientoDesdePagos(Factura $f, array|Collection $pagos, string $glosa = 'Cobro Factura'): Asiento
    {
        return DB::transaction(function () use ($f, $pagos, $glosa) {
            $pagos = $pagos instanceof Collection ? $pagos : collect($pagos);
            if ($pagos->isEmpty()) {
                throw new \RuntimeException('No hay pagos para contabilizar.');
            }

            $ctaCxC1305 = self::cuentaPorCodigo('1305') ?: $f->cuenta_cobro_id;
            if (!$ctaCxC1305 || !PlanCuentas::whereKey($ctaCxC1305)->exists()) {
                throw new \RuntimeException('No se encontró la cuenta 1305 ni una cuenta de cobro válida.');
            }

            // Cabecera del asiento (ligada a la factura)
            $asiento = Asiento::create([
                'fecha'       => optional($pagos->first())->fecha ?: $f->fecha,
                'tipo'        => 'COBRO',
                'glosa'       => sprintf('%s %s-%s · %s',
                                    $glosa, (string)$f->prefijo, (string)$f->numero, optional($f->cliente)->razon_social),
                'origen'      => 'factura',
                'origen_id'   => $f->id,
                'tercero_id'  => $f->socio_negocio_id,
                'moneda'      => $f->moneda ?? 'COP',
                'total_debe'  => 0,
                'total_haber' => 0,
            ]);

            $metaBase = [
                'factura_id' => $f->id,
                'tercero_id' => $f->socio_negocio_id,
            ];

            $totalDebe  = 0.0;
            $totalHaber = 0.0;
            $totalCobro = 0.0;

            // Agrupa por medio y suma
            $porMedio = $pagos->groupBy('medio_pago_id')->map(fn($rows) => round($rows->sum('monto'), 2));

            // Debe: una línea por medio
            foreach ($porMedio as $medioId => $monto) {
                if ($monto <= 0) continue;

                /** @var MedioPagos|null $medio */
                $medio    = MedioPagos::find($medioId);
                $ctaMedio = self::cuentaDesdeMedioPago($medio);
                if (!$ctaMedio || !PlanCuentas::whereKey($ctaMedio)->exists()) {
                    throw new \RuntimeException('No se pudo resolver la cuenta contable del medio de pago.');
                }

                $mov = self::postAumento(
                    $asiento,
                    (int)$ctaMedio,
                    $monto,
                    'Ingreso por cobro de factura',
                    $metaBase + ['descripcion' => $medio?->nombre ?: 'Medio']
                );
                $totalDebe  += $mov->debito;
                $totalHaber += $mov->credito;
                $totalCobro += $monto;
            }

            if ($totalCobro <= 0) {
                throw new \RuntimeException('El total de cobro es cero.');
            }

            // Haber: CxC por el total
            $movHaber = self::post($asiento, (int)$ctaCxC1305, 0.0, $totalCobro, 'Aplicación a CxC 1305', $metaBase);
            $totalDebe  += $movHaber->debito;
            $totalHaber += $movHaber->credito;

            $asiento->update([
                'total_debe'  => round($totalDebe, 2),
                'total_haber' => round($totalHaber, 2),
            ]);

            return $asiento;
        });
    }

    /* ============================================================
     *  ASIENTO DESDE FACTURA (VENTA)
     * ============================================================ */
    public static function asientoDesdeFactura(Factura $f): Asiento
    {
        return DB::transaction(function () use ($f) {
            if (empty($f->cuenta_cobro_id) || !PlanCuentas::whereKey($f->cuenta_cobro_id)->exists()) {
                throw new \RuntimeException('La factura no tiene cuenta de cobro válida (cuenta_cobro_id).');
            }

            $ingresosPorCuenta   = [];
            $ivaCuentaTarifa     = [];
            $totalFactura        = 0.0;
            $inventarioPorCuenta = [];
            $costoPorCuenta      = [];

            foreach ($f->detalles as $d) {
                $base = (float)$d->cantidad * (float)$d->precio_unitario * (1 - (float)$d->descuento_pct/100);
                $tPct = (float)$d->impuesto_pct;
                $iva  = round($base * $tPct / 100, 2);

                $ctaIng = (int)$d->cuenta_ingreso_id;
                if ($ctaIng <= 0) throw new \RuntimeException("El detalle {$d->id} no tiene cuenta de ingreso.");
                $ingresosPorCuenta[$ctaIng] = ($ingresosPorCuenta[$ctaIng] ?? 0.0) + $base;

                if ($d->producto_id && $iva > 0) {
                    /** @var Producto|null $p */
                    $p = Producto::with(['cuentas','impuesto'])->find($d->producto_id);
                    $ctaIva = self::cuentaIvaParaProducto($p);
                    if (!$ctaIva) throw new \RuntimeException("No se pudo resolver la cuenta de IVA para el producto {$d->producto_id}.");
                    $impId = $p?->impuesto?->id;

                    $ivaCuentaTarifa[$ctaIva][$tPct]['base']        = ($ivaCuentaTarifa[$ctaIva][$tPct]['base'] ?? 0) + $base;
                    $ivaCuentaTarifa[$ctaIva][$tPct]['iva']         = ($ivaCuentaTarifa[$ctaIva][$tPct]['iva']  ?? 0) + $iva;
                    $ivaCuentaTarifa[$ctaIva][$tPct]['impuesto_id'] = $impId;
                }

                $totalFactura += ($base + $iva);

                if ($d->producto_id && $d->cantidad > 0) {
                    $p = $p ?? Producto::with(['cuentas','impuesto'])->find($d->producto_id);
                    if ($p) {
                        $cpu   = self::costoPromedioUnitario($p);
                        $costo = round($cpu * (float)$d->cantidad, 2);
                        if ($costo > 0) {
                            $ctaInv   = self::cuentaInventarioProducto($p);
                            $ctaCosto = self::cuentaCostoProducto($p);
                            if (!$ctaInv)   throw new \RuntimeException("Producto {$p->id} sin cuenta de INVENTARIO.");
                            if (!$ctaCosto) throw new \RuntimeException("Producto {$p->id} sin cuenta de COSTO.");

                            $inventarioPorCuenta[$ctaInv] = ($inventarioPorCuenta[$ctaInv] ?? 0.0) + $costo;
                            $costoPorCuenta[$ctaCosto]    = ($costoPorCuenta[$ctaCosto] ?? 0.0) + $costo;
                        }
                    }
                }
            }

            $asiento = Asiento::create([
                'fecha'       => $f->fecha,
                'tipo'        => 'VENTA',
                'glosa'       => sprintf('Factura %s-%s · %s', (string)$f->prefijo, (string)$f->numero, optional($f->cliente)->razon_social),
                'origen'      => 'factura',
                'origen_id'   => $f->id,
                'tercero_id'  => $f->socio_negocio_id,
                'moneda'      => $f->moneda ?? 'COP',
                'total_debe'  => 0,
                'total_haber' => 0,
            ]);

            $totalDebe  = 0.0;
            $totalHaber = 0.0;

            $metaBase = [
                'factura_id' => $f->id,
                'tercero_id' => $f->socio_negocio_id,
            ];

            $mov = self::postAumento(
                $asiento,
                (int)$f->cuenta_cobro_id,
                round($totalFactura, 2),
                'Cobro factura',
                $metaBase + ['descripcion'=>'Cobro factura','base_gravable'=>null,'tarifa_pct'=>null,'valor_impuesto'=>null]
            );
            $totalDebe  += $mov->debito;
            $totalHaber += $mov->credito;

            foreach ($ingresosPorCuenta as $cuentaId => $montoBase) {
                $mov = self::postAumento(
                    $asiento,
                    (int)$cuentaId,
                    round($montoBase, 2),
                    'Ingreso base sin IVA',
                    $metaBase + ['descripcion'=>'Ingreso base sin IVA','base_gravable'=>round($montoBase,2),'tarifa_pct'=>null,'valor_impuesto'=>0.0,'impuesto_id'=>null]
                );
                $totalDebe  += $mov->debito;
                $totalHaber += $mov->credito;
            }

            foreach ($ivaCuentaTarifa as $ctaIva => $porTarifa) {
                foreach ($porTarifa as $pct => $vals) {
                    $base = round($vals['base'] ?? 0, 2);
                    $iva  = round($vals['iva']  ?? 0, 2);
                    if ($iva <= 0) continue;

                    $mov = self::postAumento(
                        $asiento,
                        (int)$ctaIva,
                        $iva,
                        'IVA ventas',
                        $metaBase + ['descripcion'=>'IVA generado','impuesto_id'=>$vals['impuesto_id'] ?? null,'base_gravable'=>$base,'tarifa_pct'=>(float)$pct,'valor_impuesto'=>$iva]
                    );
                    $totalDebe  += $mov->debito;
                    $totalHaber += $mov->credito;
                }
            }

            foreach ($costoPorCuenta as $cta => $monto) {
                $mov = self::post($asiento, (int)$cta, round($monto, 2), 0.0, 'Costo de ventas', $metaBase + ['descripcion'=>'Costo de ventas']);
                $totalDebe  += $mov->debito;
                $totalHaber += $mov->credito;
            }
            foreach ($inventarioPorCuenta as $cta => $monto) {
                $mov = self::post($asiento, (int)$cta, 0.0, round($monto, 2), 'Salida de inventario (costo)', $metaBase + ['descripcion'=>'Salida de inventario (costo)']);
                $totalDebe  += $mov->debito;
                $totalHaber += $mov->credito;
            }

            $asiento->update([
                'total_debe'  => round($totalDebe, 2),
                'total_haber' => round($totalHaber, 2),
            ]);

            return $asiento;
        });
    }
}
