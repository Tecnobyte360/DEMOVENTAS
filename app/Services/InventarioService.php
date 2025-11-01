<?php

namespace App\Services;

use App\Models\Factura\Factura;
use App\Models\Movimiento\KardexMovimiento;
use App\Models\Productos\ProductoBodega;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class InventarioService
{
    /* =========================================================
     *  VALIDACIÓN DE DISPONIBILIDAD
     * ========================================================= */
    public static function verificarDisponibilidadParaFactura(Factura $factura): void
    {
        $requeridos = [];

        foreach ($factura->detalles as $d) {
            if (!$d->producto_id || !$d->bodega_id) {
                throw new \RuntimeException("Cada línea debe tener producto y bodega.");
            }
            $key = $d->producto_id.'-'.$d->bodega_id;
            $requeridos[$key] = ($requeridos[$key] ?? 0) + (float) $d->cantidad;
        }

        foreach ($requeridos as $key => $cant) {
            [$productoId, $bodegaId] = array_map('intval', explode('-', $key));

            $pb = ProductoBodega::query()
                ->where('producto_id', $productoId)
                ->where('bodega_id', $bodegaId)
                ->first();

            $stockActual = (float) ($pb->stock ?? 0);
            if ($stockActual < $cant - 1e-6) {
                throw new \RuntimeException(
                    "Stock insuficiente para el producto {$productoId} en bodega {$bodegaId}. ".
                    "Disponible: {$stockActual}, Requerido: {$cant}"
                );
            }
        }
    }

    /* =========================================================
     *  DESCUENTO POR FACTURA (VENTA)
     * ========================================================= */
    public static function descontarPorFactura(Factura $factura): void
    {
        DB::transaction(function () use ($factura) {
            $factura->loadMissing('detalles.producto', 'serie.tipo');

            $requeridos = [];

            foreach ($factura->detalles as $d) {
                if (!$d->producto_id || !$d->bodega_id) {
                    throw new \RuntimeException("Cada línea debe tener producto y bodega.");
                }
                $key = $d->producto_id.'-'.$d->bodega_id;
                $requeridos[$key] = ($requeridos[$key] ?? 0) + (float) $d->cantidad;
            }

            // 1) Descontar stock (lock)
            foreach ($requeridos as $key => $cant) {
                [$productoId, $bodegaId] = array_map('intval', explode('-', $key));

                $pb = ProductoBodega::query()
                    ->where('producto_id', $productoId)
                    ->where('bodega_id', $bodegaId)
                    ->lockForUpdate()
                    ->first();

                if (!$pb) {
                    $pb = new ProductoBodega([
                        'producto_id' => $productoId,
                        'bodega_id'   => $bodegaId,
                        'stock'       => 0,
                    ]);
                }

                $nuevo = (float) $pb->stock - (float) $cant;
                if ($nuevo < -1e-6) {
                    throw new \RuntimeException(
                        "Stock negativo al descontar producto {$productoId} en bodega {$bodegaId}. ".
                        "Actual: {$pb->stock}, Descuento: {$cant}"
                    );
                }

                $pb->stock = $nuevo;
                $pb->save();
            }

            // 2) Kardex: SALIDA por cada línea
            $tipoId = static::tipoDocumentoId('FACTURA', $factura); // usa serie->tipo si existe
            foreach ($factura->detalles as $d) {
                static::registrarKardex(
                    tipoLogico:        'VENTA',
                    signo:             -1,
                    fecha:             $factura->fecha ?? now(),
                    productoId:        (int) $d->producto_id,
                    bodegaId:          (int) $d->bodega_id,
                    cantidad:          (float) $d->cantidad,
                    costoUnitario:     static::resolverCostoUnitarioSalida($d),
                    tipoDocumentoId:   $tipoId,
                    docTipoLegacy:     'FACTURA', // solo si la columna existe
                    docId:             (int) $factura->id,
                    ref:               static::refFactura($factura)
                );
            }
        });
    }

    /* =========================================================
     *  REVERSA POR ANULACIÓN DE FACTURA
     * ========================================================= */
    public static function revertirPorFactura(Factura $factura): void
    {
        DB::transaction(function () use ($factura) {
            $factura->loadMissing('detalles.producto', 'serie.tipo');

            // 1) Sumar stock y 2) Kardex ENTRADA por cada línea
            $tipoId = static::tipoDocumentoId('ANULACION_FACTURA');

            foreach ($factura->detalles as $d) {
                if (!$d->producto_id || !$d->bodega_id) {
                    continue;
                }

                $pb = ProductoBodega::query()
                    ->where('producto_id', $d->producto_id)
                    ->where('bodega_id', $d->bodega_id)
                    ->lockForUpdate()
                    ->first();

                if (!$pb) {
                    $pb = new ProductoBodega([
                        'producto_id' => $d->producto_id,
                        'bodega_id'   => $d->bodega_id,
                        'stock'       => 0,
                    ]);
                }

                $pb->stock = (float) $pb->stock + (float) $d->cantidad;
                $pb->save();

                static::registrarKardex(
                    tipoLogico:        'ANULACION',
                    signo:             1,
                    fecha:             now(),
                    productoId:        (int) $d->producto_id,
                    bodegaId:          (int) $d->bodega_id,
                    cantidad:          (float) $d->cantidad,
                    costoUnitario:     static::resolverCostoUnitarioReversion($d),
                    tipoDocumentoId:   $tipoId,
                    docTipoLegacy:     'ANULACION_FACTURA',
                    docId:             (int) $factura->id,
                    ref:               'Anulación de '.static::refFactura($factura)
                );
            }
        });
    }

    /* =========================================================
     *  NOTA CRÉDITO (REPONE STOCK)
     * ========================================================= */
    public static function reponerPorNotaCredito(\App\Models\NotaCredito $nota): void
    {
        DB::transaction(function () use ($nota) {
            $nota->loadMissing('detalles.producto', 'factura');

            $tipoId = static::tipoDocumentoId('NOTA_CREDITO');

            foreach ($nota->detalles as $d) {
                if (!$d->producto_id || !$d->bodega_id) continue;

                $pb = ProductoBodega::query()
                    ->where('producto_id', $d->producto_id)
                    ->where('bodega_id', $d->bodega_id)
                    ->lockForUpdate()
                    ->first();

                if (!$pb) {
                    $pb = new ProductoBodega([
                        'producto_id' => $d->producto_id,
                        'bodega_id'   => $d->bodega_id,
                        'stock'       => 0,
                    ]);
                }

                $pb->stock = (float) $pb->stock + (float) $d->cantidad;
                $pb->save();

                static::registrarKardex(
                    tipoLogico:        'NOTA_CREDITO',
                    signo:             1,
                    fecha:             $nota->fecha ?? now(),
                    productoId:        (int) $d->producto_id,
                    bodegaId:          (int) $d->bodega_id,
                    cantidad:          (float) $d->cantidad,
                    costoUnitario:     static::resolverCostoUnitarioEntrada($d),
                    tipoDocumentoId:   $tipoId,
                    docTipoLegacy:     'NOTA_CREDITO',
                    docId:             (int) $nota->id,
                    ref:               'NC sobre '.static::refFactura($nota->factura ?? null)
                );
            }
        });
    }

    /* =========================================================
     *  REVERSA DE LA REPOSICIÓN POR NC (RESTAR)
     * ========================================================= */
    public static function revertirReposicionPorNotaCredito(\App\Models\NotaCredito $nota): void
    {
        DB::transaction(function () use ($nota) {
            $nota->loadMissing('detalles.producto', 'factura');

            $tipoId = static::tipoDocumentoId('REV_NOTA_CREDITO');

            foreach ($nota->detalles as $d) {
                if (!$d->producto_id || !$d->bodega_id) continue;

                $pb = ProductoBodega::query()
                    ->where('producto_id', $d->producto_id)
                    ->where('bodega_id', $d->bodega_id)
                    ->lockForUpdate()
                    ->first();

                if (!$pb) {
                    $pb = new ProductoBodega([
                        'producto_id' => $d->producto_id,
                        'bodega_id'   => $d->bodega_id,
                        'stock'       => 0,
                    ]);
                }

                $pb->stock = (float) $pb->stock - (float) $d->cantidad;
                $pb->save();

                static::registrarKardex(
                    tipoLogico:        'REV_NC',
                    signo:             -1,
                    fecha:             now(),
                    productoId:        (int) $d->producto_id,
                    bodegaId:          (int) $d->bodega_id,
                    cantidad:          (float) $d->cantidad,
                    costoUnitario:     static::resolverCostoUnitarioSalida($d),
                    tipoDocumentoId:   $tipoId,
                    docTipoLegacy:     'REV_NOTA_CREDITO',
                    docId:             (int) $nota->id,
                    ref:               'Reversión NC de '.static::refFactura($nota->factura ?? null)
                );
            }
        });
    }

    /* =========================================================
     *  REGISTRO GENÉRICO EN KARDEX
     * ========================================================= */
    private static function registrarKardex(
        string $tipoLogico,
        int $signo,
        $fecha,
        int $productoId,
        int $bodegaId,
        float $cantidad,
        float $costoUnitario,
        ?int $tipoDocumentoId = null,
        ?string $docTipoLegacy = null,  // para compatibilidad si la columna existe
        ?int $docId = null,
        ?string $ref = null
    ): void {
        $cantidad = max(0.0, $cantidad);
        $entrada  = $signo >= 0 ? $cantidad : 0.0;
        $salida   = $signo <  0 ? $cantidad : 0.0;

        $data = [
            'fecha'          => $fecha ?? now(),
            'producto_id'    => $productoId,
            'bodega_id'      => $bodegaId,
            'entrada'        => $entrada,
            'salida'         => $salida,
            'cantidad'       => $cantidad,
            'signo'          => $signo,
            'costo_unitario' => (float) $costoUnitario,
            'total'          => round($cantidad * (float) $costoUnitario, 2),
            'doc_id'         => $docId,
            'ref'            => $ref ?: $tipoLogico,
        ];

        // Escribe tipo_documento_id si existe la columna
        if (Schema::hasColumn('kardex_movimientos', 'tipo_documento_id')) {
            $data['tipo_documento_id'] = $tipoDocumentoId;
        }
        // Mantén doc_tipo textual sólo si existe la columna (compatibilidad)
        if ($docTipoLegacy !== null && Schema::hasColumn('kardex_movimientos', 'doc_tipo')) {
            $data['doc_tipo'] = $docTipoLegacy;
        }

        KardexMovimiento::create($data);
    }

    /* =========================================================
     *  HELPERS: COSTOS / REFERENCIAS / TIPO DOC
     * ========================================================= */
   private static function resolverCostoUnitarioSalida($detalle): float
{
    // Si el detalle tiene costo fijo, úsalo
    if (!empty($detalle->costo_unitario) && $detalle->costo_unitario > 0) {
        return (float) $detalle->costo_unitario;
    }

    // Buscar costo actual del producto en la bodega justo antes de la salida
    $pb = \App\Models\Productos\ProductoBodega::query()
        ->where('producto_id', $detalle->producto_id)
        ->where('bodega_id', $detalle->bodega_id)
        ->first();

    if ($pb && $pb->costo_promedio > 0) {
        return (float) $pb->costo_promedio; // usa el costo promedio actual de la bodega
    }

    // Si la bodega no tiene registro, usar costo promedio del producto
    if ($detalle->producto && $detalle->producto->costo_promedio > 0) {
        return (float) $detalle->producto->costo_promedio;
    }

    // Último recurso: costo estándar del producto
    return (float) ($detalle->producto->costo ?? 0);
}


    private static function resolverCostoUnitarioEntrada($detalle): float
    {
        $cands = [
            $detalle->costo_unitario ?? null,
            $detalle->costo_promedio ?? null,
            $detalle->producto->costo_promedio ?? null,
            $detalle->producto->costo ?? null,
        ];
        foreach ($cands as $v) {
            if (!is_null($v) && is_numeric($v) && (float)$v >= 0) {
                return (float) $v;
            }
        }
        return 0.0;
    }

    private static function resolverCostoUnitarioReversion($detalle): float
    {
        return static::resolverCostoUnitarioEntrada($detalle);
    }

    private static function refFactura(?Factura $f): string
    {
        if (!$f) return '—';
        $pref = $f->prefijo ? ($f->prefijo.'-') : '';
        $num  = $f->numero ? (string) $f->numero : '—';
        return 'FAC '.$pref.$num;
    }

    /**
     * Resuelve el ID de tipo_documento por código.
     * - Si pasas una Factura y el código es FACTURA, usa su serie->tipo si está disponible.
     * - Cachea por 10 minutos.
     */
    private static function tipoDocumentoId(string $codigo, ?Factura $factura = null): ?int
    {
        $codigo = strtoupper(trim($codigo));

        // En FACTURA, prioriza la relación serie->tipo
        if ($codigo === 'FACTURA' && $factura && $factura->relationLoaded('serie') && $factura->serie?->tipo) {
            return (int) $factura->serie->tipo->id;
        }

        return cache()->remember("tipo_doc_id:$codigo", 600, function () use ($codigo) {
            return (int) (\App\Models\TiposDocumento\TipoDocumento::query()
                ->whereRaw('UPPER(codigo) = ?', [$codigo])
                ->value('id') ?? 0) ?: null;
        });
    }

    public static function aumentarPorFacturaCompra(\App\Models\Factura\Factura $factura): void
{
    DB::transaction(function () use ($factura) {
        // Asegura relaciones necesarias
        $factura->loadMissing('detalles.producto', 'serie.tipo');

        // Tipo documento para Kardex (usa serie->tipo si existe)
        $tipoId = static::tipoDocumentoId('FACTURA', $factura);

        foreach ($factura->detalles as $d) {
            if (!$d->producto_id || !$d->bodega_id) {
                throw new \RuntimeException("Cada línea debe tener producto y bodega.");
            }

            // Costo unitario neto de la compra (sin IVA). Si manejas descuento a nivel línea, aplícalo aquí.
            $costoUnit = static::costoUnitarioCompra($d); // ver helper abajo
            $cantidad  = (float) $d->cantidad;

            /** @var \App\Models\Productos\ProductoBodega $pb */
            $pb = \App\Models\Productos\ProductoBodega::query()
                ->where('producto_id', $d->producto_id)
                ->where('bodega_id',   $d->bodega_id)
                ->lockForUpdate()
                ->first();

            if (!$pb) {
                $pb = new \App\Models\Productos\ProductoBodega([
                    'producto_id'     => (int)$d->producto_id,
                    'bodega_id'       => (int)$d->bodega_id,
                    'stock'           => 0,
                    'costo_promedio'  => 0,
                    'ultimo_costo'    => 0,
                ]);
            }

            // === Costo promedio móvil (promedio ponderado) ===
            $stockAnt   = (float) $pb->stock;
            $cpmAnt     = (float) $pb->costo_promedio;
            $stockNuevo = $stockAnt + $cantidad;

            $cpmNuevo = $stockNuevo > 0
                ? round((($stockAnt * $cpmAnt) + ($cantidad * $costoUnit)) / $stockNuevo, 6)
                : round($costoUnit, 6);

            // Actualiza bodega
            $pb->stock          = $stockNuevo;
            $pb->costo_promedio = $cpmNuevo;
            $pb->ultimo_costo   = round($costoUnit, 6);
            $pb->save();

            // Registra ENTRADA en Kardex
            static::registrarKardex(
                tipoLogico:        'COMPRA',
                signo:             1,
                fecha:             $factura->fecha ?? now(),
                productoId:        (int) $d->producto_id,
                bodegaId:          (int) $d->bodega_id,
                cantidad:          $cantidad,
                costoUnitario:     $costoUnit,
                tipoDocumentoId:   $tipoId,
                docTipoLegacy:     'FACTURA_COMPRA',
                docId:             (int) $factura->id,
                ref:               static::refFactura($factura)   
            );
        }
    });
}
private static function costoUnitarioCompra($detalle): float
{
    // Asume que precio_unitario ya es SIN IVA en compras (como manejas arriba).
    $pu   = (float) ($detalle->precio_unitario ?? 0);
    $desc = (float) ($detalle->descuento_pct   ?? 0);

    // Aplica descuento si existe
    $puNeto = $pu * (1 - max(0, min(100, $desc)) / 100);

    // Si por diseño tu precio_unitario viene con IVA, desinfla:
    // $iva = (float) ($detalle->impuesto_pct ?? 0);
    // if ($iva > 0) $puNeto = $puNeto / (1 + $iva / 100);

    // Fallbacks si llega 0
    if ($puNeto <= 0 && !empty($detalle->costo_unitario)) {
        $puNeto = (float) $detalle->costo_unitario;
    }

    return max(0.0, round($puNeto, 6));
}

}
