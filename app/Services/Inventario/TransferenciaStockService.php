<?php

namespace App\Services\Inventario;

use App\Models\Productos\ProductoBodega;
use Illuminate\Support\Facades\DB;

class TransferenciaStockService
{
    public function transferir(
        int $productoId,
        int $bodegaOrigenId,
        int $bodegaDestinoId,
        float $cantidad,
        ?int $userId = null,
        ?string $observacion = null
    ): array {
        if ($cantidad <= 0) {
            throw new \InvalidArgumentException('La cantidad debe ser mayor que 0.');
        }
        if ($bodegaOrigenId === $bodegaDestinoId) {
            throw new \InvalidArgumentException('Origen y destino no pueden ser la misma bodega.');
        }

        return DB::transaction(function () use ($productoId, $bodegaOrigenId, $bodegaDestinoId, $cantidad, $userId, $observacion) {

            // 1) Origen (debe existir)
            $origen = ProductoBodega::where('producto_id', $productoId)
                ->where('bodega_id', $bodegaOrigenId)
                ->lockForUpdate()
                ->firstOrFail();

            // 2) Destino (si no existe, se crea)
            $destino = ProductoBodega::firstOrCreate(
                ['producto_id' => $productoId, 'bodega_id' => $bodegaDestinoId],
                [
                    'stock' => 0,
                    'stock_minimo' => 0,
                    'stock_maximo' => 0,
                    'costo_promedio' => 0,
                    'ultimo_costo' => 0,
                    'metodo_costeo' => 'PROMEDIO',
                ]
            );

            // Lock destino (ya existe id)
            $destino = ProductoBodega::whereKey($destino->id)->lockForUpdate()->first();

            // 3) Validar stock origen
            $stockOrigen = (float)($origen->stock ?? 0);
            if ($cantidad > $stockOrigen) {
                throw new \RuntimeException('Stock insuficiente en bodega origen.');
            }

            // 4) Costo unitario a transferir (CPU origen)
            $cpu = (float)$origen->costoUnitarioSalida();
            $costoTotal = round($cpu * $cantidad, 2);

            // 5) Salida en origen
            $origen->update([
                'stock' => round($stockOrigen - $cantidad, 6),
            ]);

            // 6) Entrada en destino (PROMEDIO ponderado)
            $stockDestino = (float)($destino->stock ?? 0);
            $cpuDestino   = (float)($destino->costo_promedio ?? 0);

            $nuevoStockDestino = $stockDestino + $cantidad;

            $nuevoCpuDestino = $cpuDestino;
            if ($nuevoStockDestino > 0) {
                if ($cpuDestino > 0) {
                    $nuevoCpuDestino = (($cpuDestino * $stockDestino) + ($cpu * $cantidad)) / $nuevoStockDestino;
                } else {
                    $nuevoCpuDestino = $cpu;
                }
            }

            $destino->update([
                'stock'          => round($nuevoStockDestino, 6),
                'costo_promedio' => round($nuevoCpuDestino, 6),
                'ultimo_costo'   => round($cpu, 6),
                'metodo_costeo'  => $destino->metodo_costeo ?: 'PROMEDIO',
            ]);

            // 7) Guardar historial (tabla transferencias_stock)
            DB::table('transferencias_stock')->insert([
                'producto_id'       => $productoId,
                'bodega_origen_id'  => $bodegaOrigenId,
                'bodega_destino_id' => $bodegaDestinoId,
                'cantidad'          => round($cantidad, 6),
                'costo_unitario'    => round($cpu, 6),
                'costo_total'       => $costoTotal,
                'observacion'       => $observacion,
                'user_id'           => $userId,
                'created_at'        => now(),
                'updated_at'        => now(),
            ]);

            return [
                'cpu' => round($cpu, 6),
                'costo_total' => $costoTotal,
                'stock_origen' => round($stockOrigen - $cantidad, 6),
                'stock_destino' => round($nuevoStockDestino, 6),
            ];
        });
    }
}
