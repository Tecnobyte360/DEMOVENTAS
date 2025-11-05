<?php

namespace App\Services;

use App\Models\Asiento\Asiento;
use App\Models\CuentasContables\PlanCuentas;
use App\Models\Factura\Factura;
use App\Models\Factura\FacturaPago;
use App\Models\MediosPago\MedioPagoCuenta;
use App\Models\MediosPago\MedioPagos;
use App\Models\Movimiento\Movimiento;
use App\Models\Productos\Producto;
use App\Models\Productos\ProductoCuentaTipo;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Servicio contable central.
 *
 * - Resuelve cuentas por configuración (ARTICULO / SUBCATEGORIA) y tipos: INGRESO, DEVOLUCION, IVA, INVENTARIO, COSTO.
 * - Calcula costo promedio por línea (por bodega, con fallback).
 * - Postea asientos (ventas, cobros) y puede revertir asientos ligados a una factura.
 */
class ContabilidadService
{
    /* ============================================================
     *  RESOLUCIÓN DINÁMICA DE CUENTAS (Producto/Subcategoría)
     * ============================================================ */

    protected static function tipoId(string $codigo): ?int
    {
        $codigo = strtoupper(trim($codigo));
        return cache()->remember("pcta:tipo:$codigo", 600, function () use ($codigo) {
            $id = ProductoCuentaTipo::where('codigo', $codigo)->value('id');
            return $id ? (int)$id : null;
        });
    }

    protected static function cuentaDeSubcategoriaPorTipo(?int $subcategoriaId, string $tipoCodigo): ?int
    {
        if (!$subcategoriaId) return null;

        $tipoId = self::tipoId($tipoCodigo);
        if (!$tipoId) return null;

        return cache()->remember("subcat:$subcategoriaId:tipo:$tipoId", 600, function () use ($subcategoriaId, $tipoId) {
            return DB::table('subcategoria_cuentas')
                ->where('subcategoria_id', $subcategoriaId)
                ->where('tipo_id', $tipoId)
                ->value('plan_cuentas_id') ?: null;
        }) ? (int) cache("subcat:$subcategoriaId:tipo:$tipoId") : null;
    }

    protected static function cuentaDeProductoPorTipo(?Producto $p, string $tipoCodigo): ?int
    {
        if (!$p) return null;

        $tipoId = self::tipoId($tipoCodigo);
        if (!$tipoId) return null;

        $cuenta = $p->relationLoaded('cuentas')
            ? $p->cuentas->firstWhere('tipo_id', $tipoId)
            : $p->cuentas()->where('tipo_id', $tipoId)->first();

        return $cuenta?->plan_cuentas_id ? (int)$cuenta->plan_cuentas_id : null;
    }

    /** Público: usado por Facturas y Notas Crédito. */
    public static function cuentaSegunConfiguracion(?Producto $p, string $tipoCodigo): ?int
    {
        if (!$p) return null;

        $modo = strtoupper((string)($p->mov_contable_segun ?? 'ARTICULO'));

        if ($modo === 'SUBCATEGORIA') {
            return self::cuentaDeSubcategoriaPorTipo($p->subcategoria_id, $tipoCodigo)
                ?: self::cuentaDeProductoPorTipo($p, $tipoCodigo);
        }

        return self::cuentaDeProductoPorTipo($p, $tipoCodigo);
    }

    /* ===================== IVA (Ventas/Compras) ===================== */

    /** IVA ventas: primero configuración (tipo IVA), luego cuenta del impuesto del producto. */
    protected static function cuentaIvaParaProducto(?Producto $p): ?int
    {
        if (!$p) return null;

        $cta = self::cuentaSegunConfiguracion($p, 'IVA');
        if ($cta) return $cta;

        $imp = $p->relationLoaded('impuesto') ? $p->impuesto : $p->impuesto()->first();
        return $imp && !empty($imp->cuenta_id) ? (int)$imp->cuenta_id : null;
    }

    /** IVA compras (si lo necesitas en compras). */
    protected static function resolveCuentaIvaCompra(?Producto $p, int $proveedorId, int|string|null $indicadorCuentaId = null): ?int
    {
        if (is_string($indicadorCuentaId)) {
            $indicadorCuentaId = trim($indicadorCuentaId) === '' ? null : (int)$indicadorCuentaId;
        }
        if (!is_null($indicadorCuentaId) && (int)$indicadorCuentaId > 0) return (int)$indicadorCuentaId;

        $cta = self::cuentaSegunConfiguracion($p, 'IVA');
        if ($cta) return $cta;

        $imp = $p?->impuesto;
        return ($imp && !empty($imp->cuenta_id)) ? (int)$imp->cuenta_id : null;
    }

    /* ============================================================
     *  UTILIDADES: inventario / costo
     * ============================================================ */

    protected static function cuentaInventarioProducto(?Producto $p): ?int
    {
        return self::cuentaSegunConfiguracion($p, 'INVENTARIO');
    }

    protected static function cuentaCostoProducto(?Producto $p): ?int
    {
        return self::cuentaSegunConfiguracion($p, 'COSTO');
    }

    /* ============================================================
     *  COSTO PROMEDIO POR LÍNEA (por bodega)
     * ============================================================ */
    public static function costoPromedioParaLinea(Producto $p, ?int $bodegaId): float
    {
        try {
            if ($bodegaId) {
                $cpu = \App\Models\Productos\ProductoBodega::query()
                    ->where('producto_id', $p->id)
                    ->where('bodega_id', $bodegaId)
                    ->value('costo_promedio');

                if (is_numeric($cpu) && (float)$cpu > 0) {
                    return round((float)$cpu, 4);
                }
            }

            $cpuGlobal = \App\Models\Productos\ProductoBodega::query()
                ->where('producto_id', $p->id)
                ->avg('costo_promedio');

            if (is_numeric($cpuGlobal) && (float)$cpuGlobal > 0) {
                return round((float)$cpuGlobal, 4);
            }
        } catch (\Throwable $e) {
            // Si no existe tabla/columna, continuar con fallback.
        }

        $fallback = $p->costo_promedio ?? $p->costo ?? 0;
        return round((float)$fallback, 4);
    }

    /* ============================================================
     *  UTILIDADES DE CUENTAS
     * ============================================================ */

    protected static function cuentaPorCodigo(string $codigo): ?int
    {
        $codigo = trim($codigo);
        if ($codigo === '') return null;

        return cache()->remember("puc:codigo:$codigo", 600, function () use ($codigo) {
            return PlanCuentas::where('codigo', $codigo)
                ->where('cuenta_activa', 1)
                ->where(fn($q) => $q->where('titulo', 0)->orWhereNull('titulo'))
                ->value('id');
        }) ?: null;
    }

    /** Cuenta asociada a un medio de pago (mapeo explícito → campo directo → heurística). */
    public static function cuentaDesdeMedioPago(?MedioPagos $medio): ?int
    {
        if (!$medio) return null;

        $map = $medio->relationLoaded('cuentas')
            ? $medio->cuentas->first()
            : MedioPagoCuenta::where('medio_pago_id', $medio->id)->first();

        if ($map?->plan_cuentas_id) return (int)$map->plan_cuentas_id;

        if (!empty($medio->cuenta_contable_id)) return (int)$medio->cuenta_contable_id;

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
     *  REGLAS DE NATURALEZA / POSTEO / SALDOS
     * ============================================================ */

    protected static function esDeudora(PlanCuentas $c): bool
    {
        $nat = strtoupper((string)$c->naturaleza);
        if (in_array($nat, ['D','DEUDORA','ACTIVO','ACTIVOS','GASTO','GASTOS','COSTO','COSTOS','INVENTARIO'])) return true;
        if (in_array($nat, ['C','ACREEDORA','PASIVO','PASIVOS','PATRIMONIO','INGRESOS'])) return false;

        $first = substr((string)$c->codigo, 0, 1);
        return in_array($first, ['1','5','6']); // heurística
    }

    public static function postAumento(Asiento $asiento, int $cuentaId, float $monto, ?string $detalle = null, array $meta = []): Movimiento
    {
        $c = PlanCuentas::lockForUpdate()->findOrFail($cuentaId);
        if (!$c->cuenta_activa || $c->titulo) {
            throw new \RuntimeException("Cuenta no imputable o inactiva (id={$cuentaId}).");
        }

        $debe  = self::esDeudora($c) ? $monto : 0.0;
        $haber = self::esDeudora($c) ? 0.0   : $monto;

        return self::postMovimiento($asiento, $c, $debe, $haber, $detalle, $meta);
    }

    public static function post(Asiento $asiento, int $cuentaId, float $debe, float $haber, ?string $detalle = null, array $meta = []): Movimiento
    {
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

                // columnas legacy (si existen en tu tabla)
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

    /* ============================================================
     *  ASIENTOS DE COBRO (ventas)
     * ============================================================ */

    /** Asiento por un solo pago. */
    public static function asientoDesdePago(Factura $f, FacturaPago $pago): Asiento
    {
        return self::asientoDesdePagos($f, collect([$pago]), 'Cobro Factura');
    }

    /**
     * Asiento por varios pagos (un DEBE por cada medio y un HABER a 1305/CxC).
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

            $asiento = Asiento::create([
                'fecha'       => optional($pagos->first())->fecha ?: $f->fecha,
                'tipo'        => 'COBRO',
                'glosa'       => sprintf('%s %s-%s · %s', $glosa, (string)$f->prefijo, (string)$f->numero, optional($f->cliente)->razon_social),
                'origen'      => 'factura',
                'origen_id'   => $f->id,
                'tercero_id'  => $f->socio_negocio_id,
                'moneda'      => $f->moneda ?? 'COP',
                'total_debe'  => 0,
                'total_haber' => 0,
            ]);

            $metaBase = ['factura_id' => $f->id, 'tercero_id' => $f->socio_negocio_id];
            $totalDebe = $totalHaber = $totalCobro = 0.0;

            $porMedio = $pagos->groupBy('medio_pago_id')->map(fn($rows) => round($rows->sum('monto'), 2));

            foreach ($porMedio as $medioId => $monto) {
                if ($monto <= 0) continue;

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
     *  ASIENTO DESDE FACTURA (VENTAS)
     * ============================================================ */
    public static function asientoDesdeFactura(Factura $f): Asiento
    {
        return DB::transaction(function () use ($f) {
            if (empty($f->cuenta_cobro_id) || !PlanCuentas::whereKey($f->cuenta_cobro_id)->exists()) {
                throw new \RuntimeException('La factura no tiene cuenta de cobro válida (cuenta_cobro_id).');
            }

            $ingresosPorCuenta   = [];
            $ivaCuentaTarifa     = [];
            $inventarioPorCuenta = [];
            $costoPorCuenta      = [];
            $totalFactura        = 0.0;

            foreach ($f->detalles as $d) {
                $base = (float)$d->cantidad * (float)$d->precio_unitario * (1 - (float)$d->descuento_pct/100);
                $tPct = (float)$d->impuesto_pct;
                $iva  = round($base * $tPct / 100, 2);

                // Ingresos
                $ctaIng = (int)$d->cuenta_ingreso_id;
                if ($ctaIng <= 0) {
                    throw new \RuntimeException("El detalle {$d->id} no tiene cuenta de ingreso.");
                }
                $ingresosPorCuenta[$ctaIng] = ($ingresosPorCuenta[$ctaIng] ?? 0) + $base;

                // IVA
                if ($d->producto_id && $iva > 0) {
                    $p = \App\Models\Productos\Producto::with(['cuentas','impuesto'])->find($d->producto_id);
                    $ctaIva = self::cuentaIvaParaProducto($p);
                    if (!$ctaIva) {
                        throw new \RuntimeException("No se pudo resolver la cuenta de IVA para el producto {$d->producto_id}.");
                    }
                    $ivaCuentaTarifa[$ctaIva][$tPct]['base'] = ($ivaCuentaTarifa[$ctaIva][$tPct]['base'] ?? 0) + $base;
                    $ivaCuentaTarifa[$ctaIva][$tPct]['iva']  = ($ivaCuentaTarifa[$ctaIva][$tPct]['iva']  ?? 0) + $iva;
                }

                $totalFactura += ($base + $iva);

                // Costo/Inventario (si corresponde)
                if ($d->producto_id && $d->cantidad > 0) {
                    $p = isset($p) && $p?->id === $d->producto_id
                        ? $p
                        : \App\Models\Productos\Producto::with('cuentas')->find($d->producto_id);

                    if ($p && $p->es_inventariable) {
                        $cpu   = self::costoPromedioParaLinea($p, $d->bodega_id ?? null);
                        $costo = round($cpu * (float)$d->cantidad, 2);

                        if ($costo > 0) {
                            $ctaInv   = self::cuentaInventarioProducto($p); // Haber
                            $ctaCosto = self::cuentaCostoProducto($p);      // Debe
                            if (!$ctaInv)   throw new \RuntimeException("Producto {$p->id} sin cuenta de INVENTARIO.");
                            if (!$ctaCosto) throw new \RuntimeException("Producto {$p->id} sin cuenta de COSTO.");

                            $inventarioPorCuenta[$ctaInv] = ($inventarioPorCuenta[$ctaInv] ?? 0) + $costo;
                            $costoPorCuenta[$ctaCosto]    = ($costoPorCuenta[$ctaCosto]    ?? 0) + $costo;
                        }
                    }
                }
            }

            // Cabecera
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

            $totalDebe = $totalHaber = 0.0;
            $metaBase  = ['factura_id' => $f->id, 'tercero_id' => $f->socio_negocio_id];

            // DEBE: CxC total
            $mov = self::post($asiento, (int)$f->cuenta_cobro_id, round($totalFactura, 2), 0.0, 'Cobro factura', $metaBase);
            $totalDebe  += $mov->debito;
            $totalHaber += $mov->credito;

            // HABER: Ingresos
            foreach ($ingresosPorCuenta as $cuentaId => $montoBase) {
                $mov = self::post(
                    $asiento, (int)$cuentaId, 0.0, round($montoBase, 2),
                    'Ingreso base sin IVA',
                    $metaBase + [
                        'descripcion'    => 'Ingreso base sin IVA',
                        'base_gravable'  => round($montoBase,2),
                        'tarifa_pct'     => null,
                        'valor_impuesto' => 0.0,
                        'impuesto_id'    => null
                    ]
                );
                $totalDebe  += $mov->debito;
                $totalHaber += $mov->credito;
            }

            // HABER: IVA generado
            foreach ($ivaCuentaTarifa as $ctaIva => $porTarifa) {
                foreach ($porTarifa as $pct => $vals) {
                    $base = round($vals['base'] ?? 0, 2);
                    $iva  = round($vals['iva']  ?? 0, 2);
                    if ($iva <= 0) continue;

                    $mov = self::post(
                        $asiento, (int)$ctaIva, 0.0, $iva, 'IVA ventas',
                        $metaBase + [
                            'descripcion'    => 'IVA generado',
                            'impuesto_id'    => $vals['impuesto_id'] ?? null,
                            'base_gravable'  => $base,
                            'tarifa_pct'     => (float)$pct,
                            'valor_impuesto' => $iva
                        ]
                    );
                    $totalDebe  += $mov->debito;
                    $totalHaber += $mov->credito;
                }
            }

            // DEBE: Costo de ventas
            foreach ($costoPorCuenta as $cta => $monto) {
                $mov = self::post($asiento, (int)$cta, round($monto, 2), 0.0, 'Costo de ventas por factura', $metaBase);
                $totalDebe  += $mov->debito;
                $totalHaber += $mov->credito;
            }

            // HABER: Inventario
            foreach ($inventarioPorCuenta as $cta => $monto) {
                $mov = self::post($asiento, (int)$cta, 0.0, round($monto, 2), 'Salida de inventario (costo)', $metaBase);
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

    /* ============================================================
     *  REVERSIÓN DE ASIENTOS POR FACTURA (no toca inventario/pagos)
     * ============================================================ */
    public static function revertirPorFactura(Factura $f): bool
    {
        return DB::transaction(function () use ($f) {
            $asientos = Asiento::where('origen', 'factura')
                ->where('origen_id', $f->id)
                ->lockForUpdate()
                ->get();

            if ($asientos->isEmpty()) return true;

            foreach ($asientos as $asiento) {
                $movs = Movimiento::where('asiento_id', $asiento->id)->get();

                foreach ($movs as $mov) {
                    /** @var PlanCuentas|null $cuenta */
                    $cuenta = PlanCuentas::lockForUpdate()->find($mov->cuenta_id);
                    if (!$cuenta) { $mov->delete(); continue; }

                    $esDeudora = self::esDeudora($cuenta);
                    $deltaOriginal = $esDeudora
                        ? ((float)$mov->debito - (float)$mov->credito)
                        : ((float)$mov->credito - (float)$mov->debito);

                    $reversa = -1 * $deltaOriginal;

                    PlanCuentas::whereKey($cuenta->id)->update([
                        'saldo' => DB::raw('saldo + ' . number_format($reversa, 2, '.', '')),
                    ]);

                    $mov->delete();
                }

                $asiento->delete();
            }

            Log::info('Asientos contables revertidos para factura', ['factura_id' => $f->id]);
            return true;
        });
    }
}
