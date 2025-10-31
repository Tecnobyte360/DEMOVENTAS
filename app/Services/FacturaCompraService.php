<?php

namespace App\Services;

use App\Models\Asiento\Asiento;
use App\Models\CuentasContables\PlanCuentas;
use App\Models\Factura\Factura;
use App\Models\Movimiento\Movimiento;
use App\Models\Productos\Producto;
use App\Models\Productos\ProductoBodega;
use App\Models\Productos\ProductoCuentaTipo;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FacturaCompraService
{
    /* ============================
     * Helpers tipos / cuentas
     * ============================ */



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

    protected static function cuentaPorCodigo(?string $codigo): ?int
    {
        $codigo = trim((string)$codigo);
        if ($codigo === '') return null;

        return cache()->remember("puc:codigo:$codigo", 600, function () use ($codigo) {
            return PlanCuentas::where('codigo', $codigo)
                ->where('cuenta_activa', 1)
                ->where(function ($q) {
                    $q->where('titulo', 0)->orWhereNull('titulo');
                })
                ->value('id');
        });
    }

    protected static function esDeudora(PlanCuentas $c): bool
    {
        $nat = strtoupper((string)$c->naturaleza);
        if (in_array($nat, ['D', 'DEUDORA', 'ACTIVO', 'ACTIVOS', 'GASTO', 'GASTOS', 'COSTO', 'COSTOS', 'INVENTARIO'])) return true;
        if (in_array($nat, ['C', 'ACREEDORA', 'PASIVO', 'PASIVOS', 'PATRIMONIO', 'INGRESOS'])) return false;

        $first = substr((string)$c->codigo, 0, 1);
        return in_array($first, ['1', '5', '6']);
    }

    /* ============================
     * Cuentas por proveedor
     * ============================ */

    protected static function cuentasProveedor(?int $proveedorId): array
    {
        $vacio = ['cxp' => null, 'gasto' => null, 'inventario' => null, 'iva' => null];
        if (!$proveedorId) return $vacio;

        $socio = \App\Models\SocioNegocio\SocioNegocio::with('cuentas')->find($proveedorId);
        if (!$socio) return $vacio;

        $c = $socio->cuentas;

        $cxp        = $c?->cuenta_cxp_id ?? $c?->cuenta_cxc_id ?? null;
        $gasto      = $c?->cuenta_gasto_compras_id ?? $c?->cuenta_gastos_id ?? null;
        $inventario = $c?->cuenta_inventario_id ?? null;
        $iva        = $c?->cuenta_iva_compras_id ?? $c?->cuenta_iva_id ?? null;

        return [
            'cxp'        => $cxp ? (int)$cxp : null,
            'gasto'      => $gasto ? (int)$gasto : null,
            'inventario' => $inventario ? (int)$inventario : null,
            'iva'        => $iva ? (int)$iva : null,
        ];
    }

    /* ============================
     * Resoluciones compras
     * ============================ */

    protected static function resolveCuentaBaseCompra(?int $cuentaLinea, ?Producto $p, int $proveedorId): ?int
    {
        if ($cuentaLinea && PlanCuentas::whereKey($cuentaLinea)->exists()) {
            return (int)$cuentaLinea;
        }

        $ctaProd = self::cuentaDeProductoPorTipo($p, 'INVENTARIO')
            ?? self::cuentaDeProductoPorTipo($p, 'GASTO_COMPRAS')
            ?? self::cuentaDeProductoPorTipo($p, 'COSTO');
        if ($ctaProd && PlanCuentas::whereKey($ctaProd)->exists()) {
            return (int)$ctaProd;
        }

        $prov = self::cuentasProveedor($proveedorId);
        foreach (['inventario', 'gasto'] as $k) {
            if (!empty($prov[$k]) && PlanCuentas::whereKey($prov[$k])->exists()) {
                return (int)$prov[$k];
            }
        }

        $fallbackCodigo = (string) config('conta.cta_gasto_compras_default', '');
        $fallback = self::cuentaPorCodigo($fallbackCodigo);
        return $fallback ?: null;
    }

    protected static function resolveCuentaIvaCompra(?Producto $p, int $proveedorId, ?int $indicadorCuentaId = null): ?int
    {
        $cta = self::cuentaDeProductoPorTipo($p, 'IVA_COMPRAS')
            ?? self::cuentaDeProductoPorTipo($p, 'IVA');
        if ($cta && PlanCuentas::whereKey($cta)->exists()) {
            return (int)$cta;
        }

        $prov = self::cuentasProveedor($proveedorId);
        if (!empty($prov['iva']) && PlanCuentas::whereKey($prov['iva'])->exists()) {
            return (int)$prov['iva'];
        }

        if ($indicadorCuentaId && PlanCuentas::whereKey($indicadorCuentaId)->exists()) {
            return (int)$indicadorCuentaId;
        }
        if ($p) {
            $imp = $p->relationLoaded('impuesto') ? $p->impuesto : $p->impuesto()->with('cuenta')->first();
            if ($imp && !empty($imp->cuenta_id) && PlanCuentas::whereKey($imp->cuenta_id)->exists()) {
                return (int)$imp->cuenta_id;
            }
        }

        $fallbackCodigo = (string) config('conta.cta_iva_compras_default', '');
        $fallback = self::cuentaPorCodigo($fallbackCodigo);
        return $fallback ?: null;
    }

    protected static function resolveCuentaCxP(Factura $f): ?int
    {
        if ($f->cuenta_cobro_id && PlanCuentas::whereKey($f->cuenta_cobro_id)->exists()) {
            return (int)$f->cuenta_cobro_id;
        }

        $prov = self::cuentasProveedor((int)$f->socio_negocio_id);
        if (!empty($prov['cxp']) && PlanCuentas::whereKey($prov['cxp'])->exists()) {
            return (int)$prov['cxp'];
        }

        $codigo = (string) config('conta.cta_cxp_default', '');
        $fallback = self::cuentaPorCodigo($codigo);
        return $fallback ?: null;
    }

    /* ============================
     * Posteos contables
     * ============================ */

    protected static function post(Asiento $asiento, int $cuentaId, float $debe, float $haber, ?string $detalle = null, array $meta = []): Movimiento
    {
        /** @var PlanCuentas $c */
        $c = PlanCuentas::lockForUpdate()->findOrFail($cuentaId);
        if (!$c->cuenta_activa || $c->titulo) {
            throw new \RuntimeException("Cuenta no imputable o inactiva (id={$cuentaId}).");
        }

        try {
            $payload = array_merge([
                'asiento_id' => $asiento->id,
                'cuenta_id'  => $c->id,
                'debito'     => round($debe, 2),
                'credito'    => round($haber, 2),
                'debe'       => 0.0,
                'haber'      => 0.0,
                'detalle'    => $detalle,
            ], $meta);

            $mov = Movimiento::create($payload);
        } catch (QueryException $qe) {
            Log::error('COMPRA::post movimiento - DB error', [
                'error'     => $qe->getMessage(),
                'asiento'   => $asiento->id,
                'cuenta_id' => $cuentaId
            ]);
            throw new \RuntimeException("Error al insertar movimiento: " . $qe->getMessage(), 0, $qe);
        }

        $delta = self::esDeudora($c) ? ($mov->debito - $mov->credito)
            : ($mov->credito - $mov->debito);

        PlanCuentas::whereKey($c->id)->update([
            'saldo' => DB::raw('saldo + ' . number_format($delta, 2, '.', '')),
        ]);

        return $mov;
    }

    protected static function postAumento(Asiento $asiento, int $cuentaId, float $monto, ?string $detalle = null, array $meta = []): Movimiento
    {
        $c = PlanCuentas::lockForUpdate()->findOrFail($cuentaId);
        $debe  = self::esDeudora($c) ? $monto : 0.0;
        $haber = self::esDeudora($c) ? 0.0   : $monto;
        return self::post($asiento, $cuentaId, $debe, $haber, $detalle, $meta);
    }

    /* ============================
     * Inventario (entrada por compra)
     * ============================ */

protected static function entrarInventarioPorFactura(Factura $f): void
{
    $f->loadMissing('detalles');

    foreach ($f->detalles as $d) {
        if (!$d->producto_id || !$d->bodega_id) {
            throw new \RuntimeException("Cada línea debe tener producto y bodega.");
        }

        // costo unitario de la compra en esta línea
        $cantidad = (float)$d->cantidad;
        $pu       = (float)$d->precio_unitario; // si manejas costo distinto al precio_unitario, úsalo aquí
        if ($cantidad <= 0) continue;

        /** @var \App\Models\Productos\ProductoBodega $pb */
        $pb = \App\Models\Productos\ProductoBodega::query()
            ->where('producto_id', $d->producto_id)
            ->where('bodega_id',   $d->bodega_id)
            ->lockForUpdate()
            ->first();

        if (!$pb) {
            $pb = new \App\Models\Productos\ProductoBodega([
                'producto_id'     => $d->producto_id,
                'bodega_id'       => $d->bodega_id,
                'stock'           => 0,
                'costo_promedio'  => 0,
                'ultimo_costo'    => null,
            ]);
        }

        $stockAnt   = (float)$pb->stock;
        $cpuAnt     = (float)$pb->costo_promedio;   // 0 si nunca ha tenido
        $stockNuevo = $stockAnt + $cantidad;

        // Promedio ponderado: ((stockAnt * cpuAnt) + (cantidad * pu)) / (stockNuevo)
        $cpuNuevo = $stockNuevo > 0
            ? round((($stockAnt * $cpuAnt) + ($cantidad * $pu)) / $stockNuevo, 4)
            : round($pu, 4);

        $pb->stock          = $stockNuevo;
        $pb->costo_promedio = $cpuNuevo;
        $pb->ultimo_costo   = round($pu, 4);
        $pb->save();

        // (Opcional) también refresca un CPU “global” en productos, si lo usas como fallback:
        // \App\Models\Productos\Producto::whereKey($d->producto_id)
        //     ->update(['costo_promedio' => $cpuNuevo]);
    }
}



    protected static function revertirEntradaInventarioPorFactura(Factura $f): void
    {
        $f->loadMissing('detalles');

        foreach ($f->detalles as $d) {
            if (!$d->producto_id || !$d->bodega_id) continue;

            $pb = ProductoBodega::query()
                ->where('producto_id', $d->producto_id)
                ->where('bodega_id', $d->bodega_id)
                ->lockForUpdate()
                ->first();

            if (!$pb) continue;

            $nuevo = (float)$pb->stock - (float)$d->cantidad;
            $pb->stock = max(0.0, $nuevo);
            $pb->save();
        }
    }

    /* ============================
     * Pre-flight
     * ============================ */

public static function validarFacturaCompraParaAsiento(Factura $f): array
{
    $errores = [];

    $cxp = self::resolveCuentaCxP($f);
    if (!$cxp) {
        $errores[] = 'No se pudo resolver la cuenta de CxP (factura/proveedor).';
    }

    $f->loadMissing('detalles');
    if ($f->detalles->isEmpty()) {
        $errores[] = 'La factura no tiene detalles.';
        return $errores;
    }

    foreach ($f->detalles as $idx => $d) {
        $row = $idx + 1;

        if (!$d->producto_id || !$d->bodega_id) {
            Log::warning('COMPRA::inventario línea inválida', [
                'factura_id'  => $f->id,
                'detalle_id'  => $d->id ?? null,
                'producto_id' => $d->producto_id,
                'bodega_id'   => $d->bodega_id,
            ]);
            $errores[] = "Fila #{$row}: debe tener producto y bodega.";
            // seguimos validando el resto para reportar todo
        }

        // Reglas duras para COMPRAS (evita asientos con base 0)
        $cantidad = (float)$d->cantidad;
        $costo    = (float)$d->precio_unitario;
        $descPct  = (float)$d->descuento_pct;
        $ivaPct   = (float)$d->impuesto_pct;

        if ($cantidad <= 0) {
            $errores[] = "Fila #{$row}: la cantidad debe ser mayor que 0.";
        }
        if ($costo <= 0) {
            $errores[] = "Fila #{$row}: el costo unitario debe ser mayor que 0 en compras.";
        }
        if ($descPct < 0 || $descPct > 100) {
            $errores[] = "Fila #{$row}: el descuento (%) debe estar entre 0 y 100.";
        }
        if ($ivaPct < 0 || $ivaPct > 100) {
            $errores[] = "Fila #{$row}: el impuesto (%) debe estar entre 0 y 100.";
        }

        /** @var Producto|null $p */
        $p = $d->producto_id ? Producto::with(['cuentas', 'impuesto'])->find($d->producto_id) : null;

        $ctaBase = self::resolveCuentaBaseCompra(
            $d->cuenta_ingreso_id ?: null,
            $p,
            (int)$f->socio_negocio_id
        );
        if (!$ctaBase) {
            $errores[] = "Fila #{$row}: sin cuenta base (producto/proveedor).";
        }

        // Base gravable
        $base = $cantidad * $costo * (1 - $descPct / 100);
        if ($base <= 0) {
            $errores[] = "Fila #{$row}: la base gravable calculada debe ser mayor que 0.";
        }

        // Si hay IVA calculado, debe poder resolverse la cuenta de IVA
        $ivaVal = round($base * $ivaPct / 100, 2);
        if ($ivaVal > 0) {
            $indicadorCuentaId = $p?->impuesto?->cuenta_id ?? null;
            $ctaIVA = self::resolveCuentaIvaCompra($p, (int)$f->socio_negocio_id, $indicadorCuentaId);
            if (!$ctaIVA) {
                $errores[] = "Fila #{$row}: con IVA pero sin cuenta IVA (producto/proveedor/indicador).";
            }
        }
    }

    return $errores;
}


    /* ============================
     * Asiento desde factura compra
     * ============================ */

    public static function asientoDesdeFacturaCompra(Factura $f): Asiento
{
    return DB::transaction(function () use ($f) {
        Log::info('COMPRA::asientoDesdeFacturaCompra -> start', ['factura_id' => $f->id]);

        // Idempotencia (si ya existe)
        $existente = Asiento::query()
            ->where('origen', 'factura')
            ->where('origen_id', $f->id)
            ->where('tipo', 'COMPRA')
            ->first();
        if ($existente) {
            Log::info('COMPRA::asiento ya existía', ['asiento_id' => $existente->id]);
            return $existente;
        }

        // Validación previa
        $errores = self::validarFacturaCompraParaAsiento($f);
        if (!empty($errores)) {
            throw new \RuntimeException(implode(' | ', $errores));
        }

        $f->loadMissing(['detalles','socioNegocio']);
        $cxpId = self::resolveCuentaCxP($f); // cuenta pasivo (22xx)
        if (!$cxpId) {
            throw new \RuntimeException('No se pudo resolver la cuenta por pagar del proveedor.');
        }

        $porCuentaGasto = [];   // acumulado base por cuenta gasto/inventario
        $ivaPorCuenta   = [];   // acumulado por cuenta IVA
        $totalFactura   = 0.0;

        foreach ($f->detalles as $d) {
            /** @var Producto|null $p */
            $p = $d->producto_id ? Producto::with(['cuentas','impuesto'])->find($d->producto_id) : null;

            $base = (float)$d->cantidad * (float)$d->precio_unitario * (1 - (float)$d->descuento_pct / 100);
            $ivaPct = (float)$d->impuesto_pct;
            $ivaVal = round($base * $ivaPct / 100, 2);

            // Cuenta de gasto o inventario
            $ctaGasto = self::resolveCuentaBaseCompra($d->cuenta_ingreso_id ?? null, $p, (int)$f->socio_negocio_id);
            if (!$ctaGasto) {
                throw new \RuntimeException('No se encontró cuenta de gasto/inventario para una línea.');
            }

            // Acumula base
            $porCuentaGasto[$ctaGasto] = ($porCuentaGasto[$ctaGasto] ?? 0) + $base;

            // IVA
            if ($ivaVal > 0) {
                $ctaIVA = self::resolveCuentaIvaCompra($p, (int)$f->socio_negocio_id, $p?->impuesto?->cuenta_id);
                if (!$ctaIVA) {
                    throw new \RuntimeException('No se encontró cuenta de IVA compras para línea con IVA.');
                }
                $ivaPorCuenta[$ctaIVA][] = [
                    'base'  => $base,
                    'tarifa'=> $ivaPct,
                    'valor' => $ivaVal,
                ];
            }

            $totalFactura += $base + $ivaVal;
        }

        // Crear asiento
        $asiento = Asiento::create([
            'fecha'       => $f->fecha,
            'tipo'        => 'COMPRA',
            'glosa'       => sprintf('Factura Compra %s-%s', (string)$f->prefijo, (string)$f->numero),
            'origen'      => 'factura',
            'origen_id'   => $f->id,
            'tercero_id'  => $f->socio_negocio_id,
            'moneda'      => $f->moneda ?? 'COP',
            'total_debe'  => 0,
            'total_haber' => 0,
        ]);

        $meta = ['factura_id' => $f->id, 'tercero_id' => $f->socio_negocio_id];
        $totalDebe = 0.0;
        $totalHaber = 0.0;

        // === DEBE: Gasto / Inventario ===
        foreach ($porCuentaGasto as $cta => $monto) {
            $mov = self::post($asiento, (int)$cta, $monto, 0.0, 'Gasto / Inventario', $meta + [
                'descripcion'   => 'Base de compra',
                'base_gravable' => $monto,
            ]);
            $totalDebe  += $mov->debito;
        }

        // === DEBE: IVA descontable ===
        foreach ($ivaPorCuenta as $ctaIVA => $items) {
            foreach ($items as $item) {
                $mov = self::post($asiento, (int)$ctaIVA, $item['valor'], 0.0, 'IVA descontable en compras', $meta + [
                    'descripcion'    => 'IVA compras',
                    'base_gravable'  => $item['base'],
                    'tarifa_pct'     => $item['tarifa'],
                    'valor_impuesto' => $item['valor'],
                ]);
                $totalDebe  += $mov->debito;
            }
        }

        // === HABER: CxP Proveedor ===
        $mov = self::post($asiento, (int)$cxpId, 0.0, round($totalFactura, 2), 'Cuenta por pagar a proveedor', $meta);
        $totalHaber += $mov->credito;

        // Cierre del asiento
        $asiento->update([
            'total_debe'  => round($totalDebe, 2),
            'total_haber' => round($totalHaber, 2),
        ]);

        Log::info('COMPRA::asiento cerrado correctamente', [
            'asiento_id' => $asiento->id,
            'debe'       => $asiento->total_debe,
            'haber'      => $asiento->total_haber,
        ]);

        return $asiento;
    });
}

    /* ============================
     * Flujo público
     * ============================ */

    public static function emitirFacturaCompra(Factura $f): void
    {
        $errores = self::validarFacturaCompraParaAsiento($f);
        if (!empty($errores)) {
            Log::warning('COMPRA::emitir preflight con errores', ['factura_id' => $f->id, 'errores' => $errores]);
            throw new \RuntimeException(implode(' | ', $errores));
        }

        // 1) Asiento (transacción propia)
        $asiento = DB::transaction(function () use ($f) {
            Log::info('COMPRA::emitirFacturaCompra -> crear ASIENTO', ['factura_id' => $f->id]);
            return self::asientoDesdeFacturaCompra($f);
        }, 3);

        // 2) Inventario (otra transacción)
        try {
            DB::transaction(function () use ($f) {
                Log::info('COMPRA::emitirFacturaCompra -> ENTRAR INVENTARIO', ['factura_id' => $f->id]);
                self::entrarInventarioPorFactura($f);
            }, 3);

            Log::info('COMPRA::emitirFacturaCompra -> FIN OK', ['factura_id' => $f->id, 'asiento_id' => $asiento->id]);
        } catch (\Throwable $e) {
            Log::error('COMPRA::inventario FALLÓ, asiento queda creado', [
                'factura_id' => $f->id,
                'asiento_id' => $asiento->id,
                'msg'        => $e->getMessage(),
            ]);
            throw new \RuntimeException(
                'La compra fue contabilizada, pero no se pudo actualizar el inventario: ' . $e->getMessage()
            );
        }
    }

    public static function revertirPorFacturaCompra(Factura $f): void
    {
        DB::transaction(function () use ($f) {
            Log::info('COMPRA::revertirPorFacturaCompra -> INICIO', ['factura_id' => $f->id]);

            \App\Services\ContabilidadService::revertirPorFactura($f);
            self::revertirEntradaInventarioPorFactura($f);

            Log::info('COMPRA::revertirPorFacturaCompra -> FIN', ['factura_id' => $f->id]);
        }, 3);
    }
}
