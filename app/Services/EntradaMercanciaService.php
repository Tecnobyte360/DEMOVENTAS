<?php

// app/Services/EntradaMercanciaService.php
namespace App\Services;

use App\Models\Inventario\EntradaMercancia;
use App\Models\Inventario\EntradaDetalle;
use App\Models\Productos\ProductoBodega;
use App\Models\Movimiento\KardexMovimiento;
use App\Models\TiposDocumento\TipoDocumento;
use Illuminate\Support\Facades\DB;

class EntradaMercanciaService
{
    /** Devuelve el id del tipo_documento para Kardex (crea uno por código si deseas manejarlo aparte). */
    protected static function tipoDocEntradaId(): ?int
    {
        // Busca un tipo con código 'ENTRADA_MANUAL' (ajusta a tu catálogo)
        return TipoDocumento::where('codigo', 'ENTRADA_MANUAL')->value('id')
            ?? TipoDocumento::first()?->id; // fallback
    }

    /** Emite (confirma) la entrada: crea/actualiza pivot, costo promedio y escribe Kardex. */
    public static function emitir(EntradaMercancia $e): EntradaMercancia
    {
        if ($e->estado === 'emitida') return $e;

        $tipoDocId = self::tipoDocEntradaId();

        DB::transaction(function () use ($e, $tipoDocId) {
            $e->loadMissing('detalles');

            foreach ($e->detalles as $d) {
                if (!$d->producto_id || !$d->bodega_id || $d->cantidad <= 0) {
                    throw new \RuntimeException("Cada línea debe tener producto, bodega y cantidad > 0.");
                }

                /** @var ProductoBodega $pb */
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

                $cant = (float)$d->cantidad;
                $pu   = (float)$d->precio_unitario;

                $stockAnt   = (float)$pb->stock;
                $cpuAnt     = (float)$pb->costo_promedio;
                $stockNuevo = $stockAnt + $cant;

                $cpuNuevo = $stockNuevo > 0
                    ? round((($stockAnt * $cpuAnt) + ($cant * $pu)) / $stockNuevo, 4)
                    : round($pu, 4);

                $pb->stock          = $stockNuevo;
                $pb->costo_promedio = $cpuNuevo;
                $pb->ultimo_costo   = round($pu, 4);
                $pb->save();

                // Kardex (+)
                KardexMovimiento::create([
                    'fecha'            => $e->fecha_contabilizacion,
                    'producto_id'      => $d->producto_id,
                    'bodega_id'        => $d->bodega_id,
                    'tipo_documento_id'=> $tipoDocId,
                    'entrada'          => $cant,
                    'salida'           => 0,
                    'cantidad'         => $cant,
                    'signo'            => 1,
                    'costo_unitario'   => round($pu, 4),
                    'costo_promedio'   => $cpuNuevo,
                    'referencia'       => 'Entrada manual #'.$e->id,
                    'origen'           => 'entrada_manual',
                    'origen_id'        => $e->id,
                ]);
            }

            $e->estado = 'emitida';
            $e->save();
        });

        return $e->fresh('detalles');
    }

    /** Reversa: baja stock y escribe kardex de salida (simple). */
    public static function revertir(EntradaMercancia $e): void
    {
        if ($e->estado !== 'emitida') return;

        DB::transaction(function () use ($e) {
            $e->loadMissing('detalles');

            foreach ($e->detalles as $d) {
                /** @var ProductoBodega|null $pb */
                $pb = ProductoBodega::query()
                    ->where('producto_id', $d->producto_id)
                    ->where('bodega_id',   $d->bodega_id)
                    ->lockForUpdate()
                    ->first();

                if (!$pb) continue;

                $cant = (float)$d->cantidad;

                $pb->stock = max(0, (float)$pb->stock - $cant);
                $pb->save();

                KardexMovimiento::create([
                    'fecha'            => now(),
                    'producto_id'      => $d->producto_id,
                    'bodega_id'        => $d->bodega_id,
                    'tipo_documento_id'=> self::tipoDocEntradaId(),
                    'entrada'          => 0,
                    'salida'           => $cant,
                    'cantidad'         => -$cant,
                    'signo'            => -1,
                    'costo_unitario'   => (float)$pb->costo_promedio, 
                    'costo_promedio'   => (float)$pb->costo_promedio,
                    'referencia'       => 'Reversa entrada #'.$e->id,
                    'origen'           => 'reversa_entrada_manual',
                    'origen_id'        => $e->id,
                ]);
            }

            $e->estado = 'anulada';
            $e->save();
        });
    }
}
