<?php

namespace App\Services;

use App\Models\Factura\Factura;
use App\Models\Productos\ProductoBodega;
use Illuminate\Support\Facades\DB;

class InventarioService
{
    /**
     * Verifica si hay stock suficiente para una factura.
     *
     * @throws \RuntimeException
     */
    public static function verificarDisponibilidadParaFactura(Factura $factura): void
    {
        $requeridos = [];

        foreach ($factura->detalles as $d) {
            if (!$d->producto_id || !$d->bodega_id) {
                throw new \RuntimeException("Cada línea debe tener producto y bodega.");
            }

            $key = $d->producto_id . '-' . $d->bodega_id;
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

    /**
     * Descuenta el stock de los productos al emitir una factura.
     *
     * @throws \RuntimeException
     */
    public static function descontarPorFactura(Factura $factura): void
    {
        DB::transaction(function () use ($factura) {
            $requeridos = [];

            foreach ($factura->detalles as $d) {
                $key = $d->producto_id . '-' . $d->bodega_id;
                $requeridos[$key] = ($requeridos[$key] ?? 0) + (float) $d->cantidad;
            }

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
        });
    }

    /**
     * Reversa los movimientos de inventario de una factura (reposición de stock).
     *
     * @throws \RuntimeException
     */
    public static function revertirPorFactura(Factura $factura): void
    {
        DB::transaction(function () use ($factura) {
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
                    // Si no existe, la creamos con stock = 0 antes de sumar
                    $pb = new ProductoBodega([
                        'producto_id' => $d->producto_id,
                        'bodega_id'   => $d->bodega_id,
                        'stock'       => 0,
                    ]);
                }

                $pb->stock = (float) $pb->stock + (float) $d->cantidad;
                $pb->save();
            }
        });
    }
}
