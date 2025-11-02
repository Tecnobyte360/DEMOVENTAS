<?php

namespace App\Services;

use App\Models\Asiento\Asiento;
use App\Models\Conceptos\ConceptoDocumentoCuenta;
use App\Models\CuentasContables\PlanCuentas;
use App\Models\Inventario\EntradaMercancia;
use App\Models\Movimiento\Movimiento;
use App\Models\Productos\ProductoBodega;
use App\Models\Productos\ProductoCuenta;
use App\Models\Productos\Producto;
use App\Models\Categorias\SubcategoriaCuenta;
use Illuminate\Support\Facades\DB;

class EntradaMercanciaService
{
    /* =========================================================
     *                RESOLUCIÓN DE CUENTAS
     * ========================================================= */

    /** Id del tipo "Inventario" (PUC) configurado */
    protected static function tipoInventarioId(): int
    {
        return (int) config('conta.tipo_inventario_id', 1);
    }

    /** Cuenta de inventario por PRODUCTO (solo imputable/activa) */
    protected static function cuentaInventarioPorProducto(int $productoId): ?int
    {
        return ProductoCuenta::query()
            ->where('producto_id', $productoId)
            ->where('tipo_id', self::tipoInventarioId())
            ->whereHas('cuentaPUC', fn($q) => $q->where('titulo', 0)->where('cuenta_activa', 1))
            ->value('plan_cuentas_id');
    }

    /** Cuenta de inventario por SUBCATEGORÍA (solo imputable/activa) */
    protected static function cuentaInventarioPorSubcategoria(?int $subcategoriaId): ?int
    {
        if (!$subcategoriaId) return null;

        return SubcategoriaCuenta::query()
            ->where('subcategoria_id', $subcategoriaId)
            ->where('tipo_id', self::tipoInventarioId())
            ->whereHas('cuentaPUC', fn($q) => $q->where('titulo', 0)->where('cuenta_activa', 1))
            ->value('plan_cuentas_id');
    }

    /**
     * Resolver cuenta INVENTARIO según mov_contable_segun:
     * - SUBCATEGORIA: usa subcategoria_cuentas
     * - ARTICULO (o vacío): producto_cuentas; si no hay, intenta subcategoría
     */
    protected static function resolverCuentaInventario(int $productoId): ?int
    {
        /** @var Producto|null $p */
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

    /** Wrapper público (por si lo llamas desde Livewire) */
    public static function resolverCuentaInventarioPublic(int $productoId): ?int
    {
        return self::resolverCuentaInventario($productoId);
    }

    /** Cuenta del CONCEPTO (primera por prioridad / por rol), solo imputable/activa */
    public static function cuentaPorConcepto(int $conceptoId, ?string $rol = null): ?int
    {
        $q = ConceptoDocumentoCuenta::query()
            ->where('concepto_documento_id', $conceptoId)
            ->when($rol, fn($qq) => $qq->where('rol', $rol))
            ->whereHas('plan', fn($qq) => $qq->where('titulo', 0)->where('cuenta_activa', 1))
            ->orderBy('prioridad', 'asc');

        return $q->value('plan_cuenta_id'); // null si no existe imputable/activa
    }

    /* =========================================================
     *           Movimiento contable y naturaleza
     * ========================================================= */

    protected static function esDeudora(PlanCuentas $c): bool
    {
        $nat = strtoupper((string) $c->naturaleza);
        if (in_array($nat, ['D', 'DEUDORA', 'ACTIVO', 'GASTO', 'COSTO', 'INVENTARIO'], true)) return true;
        if (in_array($nat, ['C', 'ACREEDORA', 'PASIVO', 'PATRIMONIO', 'INGRESOS'], true)) return false;
        // Fallback: clases típicas
        return in_array(substr((string) $c->codigo, 0, 1), ['1', '5', '6'], true);
    }

    /**
     * Inserta Movimiento y actualiza saldo en PlanCuentas (with lock)
     * Prohíbe cuentas TÍTULO o INACTIVAS.
     */
    protected static function post(Asiento $asiento, int $cuentaId, float $debe, float $haber, ?string $detalle = null, array $meta = []): Movimiento
    {
        /** @var PlanCuentas $c */
        $c = PlanCuentas::lockForUpdate()->findOrFail($cuentaId);

        if ((int) ($c->titulo ?? 0) === 1) {
            throw new \RuntimeException("La cuenta {$c->codigo} — {$c->nombre} es TÍTULO. Configura una cuenta imputable.");
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
     *                       BORRADOR
     * ========================================================= */

    public static function guardarBorrador(array $payload): EntradaMercancia
    {
        return DB::transaction(function () use ($payload) {
            $id = $payload['id'] ?? null;

            /** @var EntradaMercancia $e */
            $e = $id ? EntradaMercancia::findOrFail($id) : new EntradaMercancia();

            $data = [
                'serie_id'              => $payload['serie_id'] ?? null,
                'socio_negocio_id'      => $payload['socio_negocio_id'] ?? null,
                'concepto_documento_id' => $payload['concepto_documento_id'] ?? null,
                'fecha_contabilizacion' => $payload['fecha_contabilizacion'] ?? now()->toDateString(),
                'observaciones'         => $payload['observaciones'] ?? null,
                'estado'                => 'borrador',
            ];

            \Illuminate\Database\Eloquent\Model::unguarded(function () use ($e, $data) {
                $e->forceFill($data)->save();
            });

            // Resetear detalles y reinsertar
            $e->detalles()->delete();

            $lineas = [];
            foreach (($payload['lineas'] ?? []) as $l) {
                $lineas[] = [
                    'producto_id'     => $l['producto_id'] ?? null,
                    'bodega_id'       => $l['bodega_id'] ?? null,

                    'cantidad'        => (float) ($l['cantidad'] ?? 0),
                    'precio_unitario' => (float) ($l['precio_unitario'] ?? 0),
                ];
            }

            if (!empty($lineas)) {
                \Illuminate\Database\Eloquent\Model::unguarded(function () use ($e, $lineas) {
                    $e->detalles()->createMany($lineas);
                });
            }

            return $e->load('detalles');
        }, 3);
    }

    /* =========================================================
     *                    VALIDAR & EMITIR
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
                throw new \RuntimeException("Línea #" . ($idx + 1) . ": no hay cuenta de inventario imputable/activa según ARTICULO/SUBCATEGORIA.");
            }
        }

        $ctaConcepto = self::cuentaPorConcepto((int) $e->concepto_documento_id, null);
        if (!$ctaConcepto) throw new \RuntimeException('El concepto no tiene una cuenta imputable/activa configurada.');
    }

    /**
     * Contabiliza:
     *  - DEBE: inventario por cada línea (cuenta según ARTÍCULO/SUBCATEGORÍA)
     *  - HABER: cuenta del concepto por el total
     */
    protected static function contabilizar(EntradaMercancia $e): Asiento
    {
        $ctaConcepto = self::cuentaPorConcepto((int) $e->concepto_documento_id, null);
        if (!$ctaConcepto) throw new \RuntimeException('No se pudo resolver la cuenta del concepto.');

        $asiento = Asiento::create([
            'fecha'       => $e->fecha_contabilizacion ?? now()->toDateString(),
            'tipo'        => 'ENTRADA',
            'glosa'       => sprintf(
                'Entrada de Mercancía %s%s',
                $e->serie?->prefijo ? $e->serie->prefijo . '-' : '',
                (string) $e->numero
            ),
            'origen'      => 'entrada_mercancia',
            'origen_id'   => $e->id,
            'tercero_id'  => $e->socio_negocio_id,
            'total_debe'  => 0,
            'total_haber' => 0,
        ]);

        $meta  = ['entrada_id' => $e->id, 'tercero_id' => $e->socio_negocio_id];
        $total = 0.0;

        foreach ($e->detalles as $d) {
            $monto = round((float) $d->cantidad * (float) $d->precio_unitario, 2);
            if ($monto <= 0) continue;

            $ctaInv = self::resolverCuentaInventario((int) $d->producto_id);
            self::post($asiento, (int) $ctaInv, $monto, 0.0, 'Entrada inventario', $meta + ['descripcion' => $d->descripcion]);
            $total += $monto;
        }

        $movHaber = self::post($asiento, (int) $ctaConcepto, 0.0, round($total, 2), 'Contrapartida concepto', $meta);

        $asiento->update([
            'total_debe'  => round($total, 2),
            'total_haber' => $movHaber->credito,
        ]);

        return $asiento;
    }

    /* =========================================================
     *                  INVENTARIO (costo promedio)
     * ========================================================= */

    protected static function aumentarInventario(EntradaMercancia $e): void
    {
        foreach ($e->detalles as $d) {
            /** @var ProductoBodega|null $pb */
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
     *                          FLUJO
     * ========================================================= */

    public static function emitir(EntradaMercancia $e): void
    {
        self::validarParaEmitir($e);

        DB::transaction(function () use ($e) {
            // Asignar consecutivo de la serie si no tiene
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

            // Contabilización
            self::contabilizar($e->load('detalles', 'serie'));

            // Inventario
            self::aumentarInventario($e->load('detalles'));

            // Estado final
            $e->estado = 'emitida';
            $e->save();
        }, 3);
    }
}
