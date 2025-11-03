<?php

namespace App\Services;

use App\Models\Asiento\Asiento;
use App\Models\Conceptos\ConceptoDocumentoCuenta;
use App\Models\CuentasContables\PlanCuentas;
use App\Models\Inventario\EntradaMercancia;
use App\Models\Movimiento\Movimiento;
use App\Models\Movimiento\ProductoCostoMovimiento;
use App\Models\Productos\ProductoBodega;
use App\Models\Productos\ProductoCuenta;
use App\Models\Productos\Producto;
use App\Models\Categorias\SubcategoriaCuenta;
use App\Models\TiposDocumento\TipoDocumento;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class EntradaMercanciaService
{
    /* =========================================================
     *                RESOLUCIÓN DE CUENTAS
     * ========================================================= */

    protected static function tipoInventarioId(): int
    {
        return cache()->remember('tipo_inventario_id', 3600, function () {
            return (int) \App\Models\Productos\ProductoCuentaTipo::query()
                ->where('codigo', 'INVENTARIO')
                ->value('id') ?: 4;
        });
    }

    protected static function cuentaInventarioPorProducto(int $productoId): ?int
    {
        return ProductoCuenta::query()
            ->where('producto_id', $productoId)
            ->where('tipo_id', self::tipoInventarioId())
            ->whereHas('cuentaPUC', fn($q) => $q->where('titulo', 0)->where('cuenta_activa', 1))
            ->value('plan_cuentas_id');
    }

    protected static function cuentaInventarioPorSubcategoria(?int $subcategoriaId): ?int
    {
        if (!$subcategoriaId) return null;

        return SubcategoriaCuenta::query()
            ->where('subcategoria_id', $subcategoriaId)
            ->where('tipo_id', self::tipoInventarioId())
            ->whereHas('cuentaPUC', fn($q) => $q->where('titulo', 0)->where('cuenta_activa', 1))
            ->value('plan_cuentas_id');
    }

    protected static function resolverCuentaInventario(int $productoId): ?int
    {
        $p = Producto::query()->select('id', 'subcategoria_id', 'mov_contable_segun')->find($productoId);
        if (!$p) return null;

        $segun = strtoupper((string) $p->mov_contable_segun);

        if ($segun === 'SUBCATEGORIA') {
            return self::cuentaInventarioPorSubcategoria($p->subcategoria_id);
        }

        // ARTICULO o vacío
        return self::cuentaInventarioPorProducto($p->id)
            ?? self::cuentaInventarioPorSubcategoria($p->subcategoria_id);
    }

    public static function resolverCuentaInventarioPublic(int $productoId): ?int
    {
        return self::resolverCuentaInventario($productoId);
    }

    public static function cuentaPorConcepto(int $conceptoId, ?string $rol = null): ?int
    {
        $q = ConceptoDocumentoCuenta::query()
            ->where('concepto_documento_id', $conceptoId)
            ->when($rol, fn($qq) => $qq->where('rol', $rol))
            ->whereHas('plan', fn($qq) => $qq->where('titulo', 0)->where('cuenta_activa', 1))
            ->orderBy('prioridad', 'asc');

        return $q->value('plan_cuenta_id');
    }

    /* =========================================================
     *           MOVIMIENTOS CONTABLES
     * ========================================================= */

    protected static function esDeudora(PlanCuentas $c): bool
    {
        $nat = strtoupper((string) $c->naturaleza);
        if (in_array($nat, ['D', 'DEUDORA', 'ACTIVO', 'GASTO', 'COSTO', 'INVENTARIO'], true)) return true;
        if (in_array($nat, ['C', 'ACREEDORA', 'PASIVO', 'PATRIMONIO', 'INGRESOS'], true)) return false;
        return in_array(substr((string) $c->codigo, 0, 1), ['1', '5', '6'], true);
    }

    protected static function post(Asiento $asiento, int $cuentaId, float $debe, float $haber, ?string $detalle = null, array $meta = []): Movimiento
    {
        $c = PlanCuentas::lockForUpdate()->findOrFail($cuentaId);

        if ((int) ($c->titulo ?? 0) === 1) {
            throw new \RuntimeException("La cuenta {$c->codigo} — {$c->nombre} es TÍTULO.");
        }
        if (!(bool) ($c->cuenta_activa ?? 0)) {
            throw new \RuntimeException("La cuenta {$c->codigo} — {$c->nombre} está inactiva.");
        }

        $mov = Movimiento::create(array_merge([
            'asiento_id' => $asiento->id,
            'cuenta_id'  => $c->id,
            'debito'     => round($debe, 2),
            'credito'    => round($haber, 2),
            'detalle'    => $detalle,
        ], $meta));

        $delta = self::esDeudora($c) ? ($mov->debito - $mov->credito) : ($mov->credito - $mov->debito);

        PlanCuentas::whereKey($c->id)->update([
            'saldo' => DB::raw('saldo + ' . number_format($delta, 2, '.', '')),
        ]);

        return $mov;
    }

    /* =========================================================
     *                   VALIDAR & EMITIR
     * ========================================================= */

    protected static function validarParaEmitir(EntradaMercancia $e): void
    {
        if (!$e->socio_negocio_id) throw new \RuntimeException('Debes seleccionar el proveedor.');
        if (!$e->concepto_documento_id) throw new \RuntimeException('Debes seleccionar el concepto.');

        $e->loadMissing('detalles');
        if ($e->detalles->isEmpty()) throw new \RuntimeException('La entrada no tiene líneas.');

        foreach ($e->detalles as $idx => $d) {
            if (!$d->producto_id || !$d->bodega_id) {
                throw new \RuntimeException("Línea #" . ($idx + 1) . ": falta producto o bodega.");
            }
            if ((float) $d->cantidad <= 0) {
                throw new \RuntimeException("Línea #" . ($idx + 1) . ": la cantidad debe ser > 0.");
            }
            $ctaInv = self::resolverCuentaInventario((int) $d->producto_id);
            if (!$ctaInv) {
                throw new \RuntimeException("Línea #" . ($idx + 1) . ": no hay cuenta de inventario activa.");
            }
        }

        $ctaConcepto = self::cuentaPorConcepto((int) $e->concepto_documento_id, null);
        if (!$ctaConcepto) throw new \RuntimeException('El concepto no tiene una cuenta imputable/activa configurada.');
    }

    protected static function contabilizar(EntradaMercancia $e): Asiento
    {
        $ctaConcepto = self::cuentaPorConcepto((int) $e->concepto_documento_id, null);
        $asiento = Asiento::create([
            'fecha'       => $e->fecha_contabilizacion ?? now()->toDateString(),
            'tipo'        => 'ENTRADA',
            'glosa'       => sprintf('Entrada de Mercancía %s%s', $e->serie?->prefijo ? $e->serie->prefijo . '-' : '', (string) $e->numero),
            'origen'      => 'entrada_mercancia',
            'origen_id'   => $e->id,
            'tercero_id'  => $e->socio_negocio_id,
        ]);

        $meta  = ['entrada_id' => $e->id, 'tercero_id' => $e->socio_negocio_id];
        $total = 0.0;

        foreach ($e->detalles as $d) {
            $monto = round((float) $d->cantidad * (float) $d->precio_unitario, 2);
            if ($monto <= 0) continue;

            $ctaInv = self::resolverCuentaInventario((int) $d->producto_id);
            self::post($asiento, (int) $ctaInv, $monto, 0.0, 'Entrada inventario', $meta);
            $total += $monto;
        }

        self::post($asiento, (int) $ctaConcepto, 0.0, round($total, 2), 'Contrapartida concepto', $meta);

        $asiento->update([
            'total_debe'  => round($total, 2),
            'total_haber' => round($total, 2),
        ]);

        return $asiento;
    }

    /* =========================================================
     *                INVENTARIO + HISTORIAL COSTOS
     * ========================================================= */

    protected static function registrarMovimientoCosto(EntradaMercancia $e, $detalle): void
    {
        $pb = ProductoBodega::query()
            ->where('producto_id', $detalle->producto_id)
            ->where('bodega_id', $detalle->bodega_id)
            ->first();

        $costoProm   = (float) ($pb?->costo_promedio ?? 0);
        $ultimoCosto = (float) ($pb?->ultimo_costo ?? 0);
        $pu          = (float) $detalle->precio_unitario;

        ProductoCostoMovimiento::create([
            'fecha'                  => now(),
            'producto_id'            => $detalle->producto_id,
            'bodega_id'              => $detalle->bodega_id,
            'tipo_documento_id'      => optional($e->tipoDocumento)->id,
            'doc_id'                 => $e->id,
            'ref'                    => ($e->prefijo ? $e->prefijo.'-' : '').$e->numero,
            'cantidad'               => (float) $detalle->cantidad,
            'valor_mov'              => round($detalle->cantidad * $pu, 4),
            'costo_unit_mov'         => round($pu, 4),
            'metodo_costeo'          => 'PROMEDIO',
            'costo_prom_anterior'    => $costoProm,
            'costo_prom_nuevo'       => $pb?->costo_promedio ?? $pu,
            'ultimo_costo_anterior'  => $ultimoCosto,
            'ultimo_costo_nuevo'     => $pu,
            'tipo_evento'            => 'ENTRADA_MERCANCIA',
            'user_id'                => Auth::id(),
        ]);
    }

    protected static function aumentarInventario(EntradaMercancia $e): void
    {
        foreach ($e->detalles as $d) {
            $pb = ProductoBodega::query()
                ->where('producto_id', $d->producto_id)
                ->where('bodega_id',   $d->bodega_id)
                ->lockForUpdate()
                ->first();

            if (!$pb) {
                $pb = new ProductoBodega([
                    'producto_id'     => $d->producto_id,
                    'bodega_id'       => $d->bodega_id,
                    'stock'           => 0,
                    'costo_promedio'  => 0,
                    'ultimo_costo'    => null,
                ]);
            }

            $cant     = (float) $d->cantidad;
            $pu       = (float) $d->precio_unitario;
            $stockAnt = (float) $pb->stock;
            $cpuAnt   = (float) $pb->costo_promedio;

            $stockNew = $stockAnt + $cant;

            $cpuNew = $stockNew > 0
                ? round((($stockAnt * $cpuAnt) + ($cant * $pu)) / $stockNew, 4)
                : round($pu, 4);

            $pb->stock          = $stockNew;
            $pb->costo_promedio = $cpuNew;
            $pb->ultimo_costo   = round($pu, 4);
            $pb->save();
        }
    }

    /* =========================================================
     *                        FLUJO PRINCIPAL
     * ========================================================= */

    public static function emitir(EntradaMercancia $e): void
    {
        self::validarParaEmitir($e);

        DB::transaction(function () use ($e) {
            if ($e->serie_id && empty($e->numero)) {
                $serie = $e->serie()->lockForUpdate()->first();
                if ($serie) {
                    $n = max((int) $serie->proximo, (int) $serie->desde);
                    $e->numero   = $n;
                    $e->prefijo  = $serie->prefijo;
                    $serie->proximo = $n + 1;
                    $serie->save();
                }
            }

            self::contabilizar($e->load('detalles', 'serie'));
            self::aumentarInventario($e->load('detalles'));

            foreach ($e->detalles as $d) {
                self::registrarMovimientoCosto($e, $d);
            }

            $e->estado = 'emitida';
            $e->save();
        }, 3);
    }
}
