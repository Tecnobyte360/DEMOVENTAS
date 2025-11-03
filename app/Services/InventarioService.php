<?php

namespace App\Services;

use App\Models\Factura\Factura;
use App\Models\Movimiento\KardexMovimiento;
use App\Models\Movimiento\ProductoCostoMovimiento;
use App\Models\Productos\ProductoBodega;
use App\Models\TiposDocumento\TipoDocumento;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class InventarioService
{
    /* =========================================================
     * VALIDACIÓN DE DISPONIBILIDAD (VENTA)
     * ========================================================= */
   public static function verificarDisponibilidadParaFactura(Factura $f): void
    {
        // Lanza excepción si falta stock por producto+bodega
        $faltantes = [];
        foreach ($f->detalles as $d) {
            if (!$d->producto_id || !$d->bodega_id) {
                throw new \RuntimeException("Cada línea debe tener producto y bodega.");
            }
            $pb = ProductoBodega::lockForUpdate()
                ->where('producto_id', $d->producto_id)
                ->where('bodega_id',  $d->bodega_id)
                ->first();

            $disp = (float)($pb->stock ?? 0);
            if ($disp < (float)$d->cantidad) {
                $faltantes[] = "Prod {$d->producto_id} en bodega {$d->bodega_id}: disp {$disp}, req {$d->cantidad}";
            }
        }
        if ($faltantes) {
            throw new \RuntimeException("Stock insuficiente: ".implode(' | ', $faltantes));
        }
    }



  public static function aumentarPorFacturaCompra(Factura $factura): void
    {
        // Si ya tienes la lógica en aplicarCompraYCosteo(), reúsala:
        static::aplicarCompraYCosteo($factura);
    }
    /* =========================================================
     * DESCUENTO POR FACTURA (VENTA)
     * ========================================================= */
    public static function descontarPorFactura(Factura $f): void
    {
        DB::transaction(function () use ($f) {
            $tipoDocumentoId = optional($f->serie)->tipo_documento_id; // si tu Serie lo tiene

            foreach ($f->detalles as $d) {
                if (!$d->producto_id || !$d->bodega_id || $d->cantidad <= 0) {
                    continue;
                }

                // Costo promedio unitario de la bodega (o global)
                $cpu = \App\Services\ContabilidadService::costoPromedioParaLinea(
                    $d->producto()->with('cuentas')->first(), 
                    $d->bodega_id
                );
                $costoTotal = round($cpu * (float)$d->cantidad, 2);

                // 1) Actualiza stock en ProductoBodega (SALIDA)
                $pb = ProductoBodega::lockForUpdate()
                    ->where('producto_id', $d->producto_id)
                    ->where('bodega_id',  $d->bodega_id)
                    ->firstOrFail();

                $stockAntes = (float)$pb->stock;
                $pb->stock  = round($stockAntes - (float)$d->cantidad, 6);
                // En salida usualmente NO cambias costo_promedio
                $pb->save();

                // 2) Registra Kárdex (SALIDA)
                KardexMovimiento::create([
                    'fecha'             => $f->fecha,
                    'producto_id'       => $d->producto_id,
                    'bodega_id'         => $d->bodega_id,
                    'tipo_documento_id' => $tipoDocumentoId,
                    'entrada'           => 0,
                    'salida'            => $d->cantidad,
                    'cantidad'          => -1 * (float)$d->cantidad,
                    'signo'             => -1,
                    'costo_unitario'    => $cpu,
                    'costo_total'       => $costoTotal,
                    'origen'            => 'factura',
                    'origen_id'         => $f->id,
                    'detalle'           => "Factura {$f->prefijo}-{$f->numero}",
                ]);

                // 3) Auditoría de costo por movimiento (opcional pero recomendado)
                ProductoCostoMovimiento::create([
                    'producto_id'       => $d->producto_id,
                    'bodega_id'         => $d->bodega_id,
                    'tipo'              => 'SALIDA',
                    'fecha'             => $f->fecha,
                    'cantidad'          => (float)$d->cantidad,
                    'costo_unitario'    => $cpu,
                    'costo_total'       => $costoTotal,
                    'stock_antes'       => $stockAntes,
                    'stock_despues'     => (float)$pb->stock,
                    'origen'            => 'factura',
                    'origen_id'         => $f->id,
                    'detalle'           => "Factura {$f->prefijo}-{$f->numero}",
                ]);
            }
        });
    }


    /* =========================================================
     * REVERSA POR ANULACIÓN DE FACTURA
     * ========================================================= */
    public static function revertirPorFactura(Factura $factura): void
    {
        DB::transaction(function () use ($factura) {
            $factura->loadMissing('detalles.producto', 'serie.tipo');

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
     * NOTA CRÉDITO (REPONE STOCK)
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
     * REVERSA DE LA REPOSICIÓN POR NC (RESTAR)
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
     * COMPRA: Kardex primero + historial costo + actualizar PB
     * ========================================================= */
    public static function aplicarCompraYCosteo(Factura $factura): void
    {
        DB::transaction(function () use ($factura) {

            // id del tipo de documento para "facturacompra" (ajusta si tu código es otro)
            $tipoDocId = (int) (TipoDocumento::whereRaw('LOWER(codigo)=?', ['facturacompra'])->value('id') ?? 0);

            $factura->loadMissing('detalles.producto');

            foreach ($factura->detalles as $d) {
                if (!$d->producto_id || !$d->bodega_id) {
                    continue; // o lanza excepción si es obligatorio
                }
                $cantidad = (float) $d->cantidad;
                if ($cantidad <= 0) {
                    continue;
                }

                // costo del movimiento (unitario) del detalle de compra (neto)
                $costoUnitMov = static::costoUnitarioCompra($d);

                // Bloqueo de la fila de inventario
                $pb = ProductoBodega::query()
                    ->where('producto_id', $d->producto_id)
                    ->where('bodega_id',  $d->bodega_id)
                    ->lockForUpdate()
                    ->first();

                if (!$pb) {
                    $pb = new ProductoBodega([
                        'producto_id'    => (int) $d->producto_id,
                        'bodega_id'      => (int) $d->bodega_id,
                        'stock'          => 0,
                        'costo_promedio' => 0,
                        'ultimo_costo'   => 0,
                        'metodo_costeo'  => 'PROMEDIO',
                    ]);
                }

                /* 1) KÁRDEX: ENTRADA (primero) */
                $dataKx = [
                    'fecha'          => $factura->fecha ?? now(),
                    'producto_id'    => (int) $d->producto_id,
                    'bodega_id'      => (int) $d->bodega_id,
                    'entrada'        => $cantidad,
                    'salida'         => 0,
                    'cantidad'       => $cantidad,
                    'signo'          => 1,
                    'costo_unitario' => $costoUnitMov,
                    'total'          => round($cantidad * $costoUnitMov, 2),
                    'doc_id'         => (string) $factura->id,
                    'ref'            => 'FAC COMP '.static::refFactura($factura),
                ];
                if (Schema::hasColumn('kardex_movimientos', 'tipo_documento_id')) {
                    $dataKx['tipo_documento_id'] = $tipoDocId ?: null;
                }
                if (Schema::hasColumn('kardex_movimientos', 'doc_tipo')) {
                    $dataKx['doc_tipo'] = 'FACTURA_COMPRA';
                }
                KardexMovimiento::create($dataKx);

                /* 2) HISTORIAL COSTO: antes/después (siempre ANTES de guardar nuevos valores) */
                $antesProm   = (float) ($pb->costo_promedio ?? 0);
                $antesUltimo = (float) ($pb->ultimo_costo   ?? 0);

                // Promedio móvil ponderado
                $existCant = (float) ($pb->stock ?? 0);
                $existVal  = $existCant * $antesProm;
                $movVal    = $cantidad * $costoUnitMov;
                $nuevoCant = $existCant + $cantidad;
                $nuevoProm = $nuevoCant > 1e-9 ? ($existVal + $movVal) / $nuevoCant : $costoUnitMov;

                ProductoCostoMovimiento::create([
                    'fecha'                 => $factura->fecha ?? now(),
                    'producto_id'           => (int) $d->producto_id,
                    'bodega_id'             => (int) $d->bodega_id,
                    'tipo_documento_id'     => $tipoDocId ?: null,
                    'doc_id'                => (string) $factura->id,
                    'ref'                   => 'FAC COMP '.static::refFactura($factura),
                    'cantidad'              => $cantidad,
                    'valor_mov'             => $movVal,
                    'costo_unit_mov'        => $costoUnitMov,
                    'metodo_costeo'         => $pb->metodo_costeo ?: 'PROMEDIO',
                    'costo_prom_anterior'   => $antesProm,
                    'costo_prom_nuevo'      => $nuevoProm,
                    'ultimo_costo_anterior' => $antesUltimo,
                    'ultimo_costo_nuevo'    => $costoUnitMov,
                    'tipo_evento'           => 'COMPRA',
                   'user_id' => Auth::id(), // ✅
                ]);

                /* 3) Actualiza producto_bodega */
                $pb->stock          = $nuevoCant;
                $pb->costo_promedio = round($nuevoProm, 6);
                $pb->ultimo_costo   = round($costoUnitMov, 6);
                $pb->metodo_costeo  = $pb->metodo_costeo ?: 'PROMEDIO';
                $pb->save();
            }
        });
    }

    /* =========================================================
     * REGISTRO GENÉRICO EN KARDEX
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

        if (Schema::hasColumn('kardex_movimientos', 'tipo_documento_id')) {
            $data['tipo_documento_id'] = $tipoDocumentoId;
        }
        if ($docTipoLegacy !== null && Schema::hasColumn('kardex_movimientos', 'doc_tipo')) {
            $data['doc_tipo'] = $docTipoLegacy;
        }

        KardexMovimiento::create($data);
    }

    /* =========================================================
     * HELPERS: COSTOS / REFERENCIAS / TIPO DOC
     * ========================================================= */
    private static function resolverCostoUnitarioSalida($detalle): float
    {
        // 1) Si el detalle trae costo “congelado” úsalo
        if (!empty($detalle->costo_unitario) && $detalle->costo_unitario > 0) {
            return (float) $detalle->costo_unitario;
        }

        // 2) Costo promedio de la bodega justo antes de la salida
        $pb = ProductoBodega::query()
            ->where('producto_id', $detalle->producto_id)
            ->where('bodega_id', $detalle->bodega_id)
            ->first();

        if ($pb && $pb->costo_promedio > 0) {
            return (float) $pb->costo_promedio;
        }

        // 3) Promedio del producto
        if ($detalle->producto && $detalle->producto->costo_promedio > 0) {
            return (float) $detalle->producto->costo_promedio;
        }

        // 4) Último recurso: costo estándar del producto
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
        return $pref.$num;
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
            return (int) (TipoDocumento::query()
                ->whereRaw('UPPER(codigo) = ?', [$codigo])
                ->value('id') ?? 0) ?: null;
        });
    }

    /**
     * Costo unitario (neto) para compras a partir del detalle.
     * Ajusta si tu `precio_unitario` viene con/ sin IVA.
     */
    private static function costoUnitarioCompra($detalle): float
    {
        $pu   = (float) ($detalle->precio_unitario ?? 0);
        $desc = (float) ($detalle->descuento_pct   ?? 0);

        // Neto con descuento
        $puNeto = $pu * (1 - max(0, min(100, $desc)) / 100);

        // Si tu precio viene con IVA y quieres neto sin IVA, descomenta:
        // $iva = (float) ($detalle->impuesto_pct ?? 0);
        // if ($iva > 0) $puNeto = $puNeto / (1 + $iva / 100);

        if ($puNeto <= 0 && !empty($detalle->costo_unitario)) {
            $puNeto = (float) $detalle->costo_unitario;
        }

        return max(0.0, round($puNeto, 6));
    }
}
