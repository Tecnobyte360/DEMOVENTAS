<?php

namespace App\Services;

use App\Models\Factura\Factura;
use App\Models\NotaCredito;
use App\Models\Productos\ProductoBodega;

class InventarioService
{
    public static function verificarDisponibilidadParaFactura(Factura $factura): void
    {
        $requeridos = [];
        foreach ($factura->detalles as $d) {
            if (!$d->producto_id || !$d->bodega_id) {
                throw new \RuntimeException("Cada línea debe tener producto y bodega.");
            }
            $key = $d->producto_id . '-' . $d->bodega_id;
            $requeridos[$key] = ($requeridos[$key] ?? 0) + (float)$d->cantidad;
        }

        foreach ($requeridos as $key => $cant) {
            [$productoId, $bodegaId] = array_map('intval', explode('-', $key));

            $pb = ProductoBodega::query()
                ->where('producto_id', $productoId)
                ->where('bodega_id', $bodegaId)
                ->first();

            $stockActual = (float)($pb->stock ?? 0);
            if ($stockActual < $cant - 1e-6) {
                throw new \RuntimeException(
                    "Stock insuficiente para el producto {$productoId} en bodega {$bodegaId}. ".
                    "Disponible: {$stockActual}, Requerido: {$cant}"
                );
            }
        }
    }

    public static function descontarPorFactura(Factura $factura): void
    {
        $requeridos = [];
        foreach ($factura->detalles as $d) {
            $key = $d->producto_id . '-' . $d->bodega_id;
            $requeridos[$key] = ($requeridos[$key] ?? 0) + (float)$d->cantidad;
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

            $nuevo = (float)$pb->stock - (float)$cant;
            if ($nuevo < -1e-6) {
                throw new \RuntimeException(
                    "Stock negativo al descontar producto {$productoId} en bodega {$bodegaId}. ".
                    "Actual: {$pb->stock}, Descuento: {$cant}"
                );
            }

            $pb->stock = $nuevo;
            $pb->save();
        }
    }

}