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
            throw new \RuntimeException("Stock insuficiente: " . implode(' | ', $faltantes));
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
            // Si tu Serie tiene el tipo_documento relacionado, úsalo.
            $tipoDocumentoId = optional($f->serie)->tipo_documento_id;

            foreach ($f->detalles as $d) {
                if (!$d->producto_id || !$d->bodega_id || $d->cantidad <= 0) {
                    continue;
                }

                // Costo promedio unitario a usar en la salida
                $cpu = \App\Services\ContabilidadService::costoPromedioParaLinea(
                    $d->producto()->with('cuentas')->first(),
                    $d->bodega_id
                );
                $costoTotal = round($cpu * (float)$d->cantidad, 2);

                // 1) Actualiza stock (SALIDA)
                $pb = ProductoBodega::lockForUpdate()
                    ->where('producto_id', $d->producto_id)
                    ->where('bodega_id',  $d->bodega_id)
                    ->firstOrFail();

                $stockAntes = (float)$pb->stock;
                $stockDespues = round($stockAntes - (float)$d->cantidad, 6);
                $pb->stock = $stockDespues;
                // En salida no se recalcula costo_promedio
                $pb->save();

                // 2) Kárdex (SALIDA) — usa 'total' (no 'costo_total')
                $dataKardex = [
                    'fecha'             => $f->fecha,
                    'producto_id'       => $d->producto_id,
                    'bodega_id'         => $d->bodega_id,
                    'entrada'           => 0,
                    'salida'            => (float)$d->cantidad,
                    'cantidad'          => -1 * (float)$d->cantidad,
                    'signo'             => -1,
                    'costo_unitario'    => $cpu,
                    'total'             => $costoTotal,
                    'doc_id'            => (string)$f->id,
                    'ref'               => "FV " . static::refFactura($f),
                ];

                if (Schema::hasColumn('kardex_movimientos', 'tipo_documento_id')) {
                    $dataKardex['tipo_documento_id'] = $tipoDocumentoId;
                }
                if (Schema::hasColumn('kardex_movimientos', 'metodo')) {
                    $dataKardex['metodo'] = 'PROMEDIO';
                }
                if (Schema::hasColumn('kardex_movimientos', 'evento')) {
                    $dataKardex['evento'] = 'FACTURA_VENTA';
                }
                if (Schema::hasColumn('kardex_movimientos', 'origen')) {
                    $dataKardex['origen'] = 'factura';
                }
                if (Schema::hasColumn('kardex_movimientos', 'origen_id')) {
                    $dataKardex['origen_id'] = $f->id;
                }
                if (Schema::hasColumn('kardex_movimientos', 'documento')) {
                    $dataKardex['documento'] = "facturaventa #{$f->id} ({$f->prefijo} {$f->numero})";
                }
                if (Schema::hasColumn('kardex_movimientos', 'detalle')) {
                    $dataKardex['detalle'] = "Factura {$f->prefijo}-{$f->numero}";
                }
                if (Schema::hasColumn('kardex_movimientos', 'doc_tipo')) {
                    $dataKardex['doc_tipo'] = 'FACTURA_VENTA';
                }

                KardexMovimiento::create($dataKardex);

                // 3) Auditoría de costos (historial) - ✅ COMPLETO
                $dataCostoMov = [
                    'fecha'                 => $f->fecha,
                    'producto_id'           => $d->producto_id,
                    'bodega_id'             => $d->bodega_id,
                    'tipo'                  => 'SALIDA',
                    'cantidad'              => (float)$d->cantidad,
                    'costo_unitario'        => $cpu,
                    'costo_total'           => $costoTotal,
                    'stock_antes'           => $stockAntes,
                    'stock_despues'         => $stockDespues,
                    'origen'                => 'factura',
                    'origen_id'             => $f->id,
                    'detalle'               => "Factura {$f->prefijo}-{$f->numero}",
                    'tipo_evento'           => 'VENTA',
                    'user_id'               => Auth::id(),
                ];

                // Agregar campos opcionales si existen
                if (Schema::hasColumn('producto_costo_movimientos', 'tipo_documento_id')) {
                    $dataCostoMov['tipo_documento_id'] = $tipoDocumentoId;
                }
                if (Schema::hasColumn('producto_costo_movimientos', 'doc_id')) {
                    $dataCostoMov['doc_id'] = (string)$f->id;
                }
                if (Schema::hasColumn('producto_costo_movimientos', 'ref')) {
                    $dataCostoMov['ref'] = "FV " . static::refFactura($f);
                }
                if (Schema::hasColumn('producto_costo_movimientos', 'valor_mov')) {
                    $dataCostoMov['valor_mov'] = $costoTotal;
                }
                if (Schema::hasColumn('producto_costo_movimientos', 'costo_unit_mov')) {
                    $dataCostoMov['costo_unit_mov'] = $cpu;
                }
                if (Schema::hasColumn('producto_costo_movimientos', 'metodo_costeo')) {
                    $dataCostoMov['metodo_costeo'] = 'PROMEDIO';
                }
                if (Schema::hasColumn('producto_costo_movimientos', 'costo_prom_anterior')) {
                    $dataCostoMov['costo_prom_anterior'] = (float)($pb->costo_promedio ?? 0);
                }
                if (Schema::hasColumn('producto_costo_movimientos', 'costo_prom_nuevo')) {
                    $dataCostoMov['costo_prom_nuevo'] = (float)($pb->costo_promedio ?? 0);
                }
                if (Schema::hasColumn('producto_costo_movimientos', 'ultimo_costo_anterior')) {
                    $dataCostoMov['ultimo_costo_anterior'] = (float)($pb->ultimo_costo ?? 0);
                }
                if (Schema::hasColumn('producto_costo_movimientos', 'ultimo_costo_nuevo')) {
                    $dataCostoMov['ultimo_costo_nuevo'] = (float)($pb->ultimo_costo ?? 0);
                }

                ProductoCostoMovimiento::create($dataCostoMov);
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
                        'costo_promedio' => 0,
                        'ultimo_costo' => 0,
                    ]);
                }

                $stockAntes = (float)$pb->stock;
                $pb->stock = (float)$pb->stock + (float)$d->cantidad;
                $pb->save();

                $costoUnit = static::resolverCostoUnitarioReversion($d);

                // Registrar en kárdex
                static::registrarKardex(
                    tipoLogico: 'ANULACION',
                    signo: 1,
                    fecha: now(),
                    productoId: (int)$d->producto_id,
                    bodegaId: (int)$d->bodega_id,
                    cantidad: (float)$d->cantidad,
                    costoUnitario: $costoUnit,
                    tipoDocumentoId: $tipoId,
                    docTipoLegacy: 'ANULACION_FACTURA',
                    docId: (int)$factura->id,
                    ref: 'Anulación de ' . static::refFactura($factura)
                );

                // ✅ Registrar en producto_costo_movimientos
                static::registrarCostoMovimiento(
                    fecha: now(),
                    productoId: (int)$d->producto_id,
                    bodegaId: (int)$d->bodega_id,
                    tipo: 'ENTRADA',
                    cantidad: (float)$d->cantidad,
                    costoUnitario: $costoUnit,
                    stockAntes: $stockAntes,
                    stockDespues: (float)$pb->stock,
                    tipoDocumentoId: $tipoId,
                    docId: (int)$factura->id,
                    origen: 'factura',
                    detalle: 'Anulación de ' . static::refFactura($factura),
                    tipoEvento: 'ANULACION'
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
                        'costo_promedio' => 0,
                        'ultimo_costo' => 0,
                    ]);
                }

                $stockAntes = (float)$pb->stock;
                $pb->stock = (float)$pb->stock + (float)$d->cantidad;
                $pb->save();

                $costoUnit = static::resolverCostoUnitarioEntrada($d);

                static::registrarKardex(
                    tipoLogico: 'NOTA_CREDITO',
                    signo: 1,
                    fecha: $nota->fecha ?? now(),
                    productoId: (int)$d->producto_id,
                    bodegaId: (int)$d->bodega_id,
                    cantidad: (float)$d->cantidad,
                    costoUnitario: $costoUnit,
                    tipoDocumentoId: $tipoId,
                    docTipoLegacy: 'NOTA_CREDITO',
                    docId: (int)$nota->id,
                    ref: 'NC sobre ' . static::refFactura($nota->factura ?? null)
                );

                // ✅ Registrar en producto_costo_movimientos
                static::registrarCostoMovimiento(
                    fecha: $nota->fecha ?? now(),
                    productoId: (int)$d->producto_id,
                    bodegaId: (int)$d->bodega_id,
                    tipo: 'ENTRADA',
                    cantidad: (float)$d->cantidad,
                    costoUnitario: $costoUnit,
                    stockAntes: $stockAntes,
                    stockDespues: (float)$pb->stock,
                    tipoDocumentoId: $tipoId,
                    docId: (int)$nota->id,
                    origen: 'nota_credito',
                    detalle: 'NC sobre ' . static::refFactura($nota->factura ?? null),
                    tipoEvento: 'NOTA_CREDITO'
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
                        'costo_promedio' => 0,
                        'ultimo_costo' => 0,
                    ]);
                }

                $stockAntes = (float)$pb->stock;
                $pb->stock = (float)$pb->stock - (float)$d->cantidad;
                $pb->save();

                $costoUnit = static::resolverCostoUnitarioSalida($d);

                static::registrarKardex(
                    tipoLogico: 'REV_NC',
                    signo: -1,
                    fecha: now(),
                    productoId: (int)$d->producto_id,
                    bodegaId: (int)$d->bodega_id,
                    cantidad: (float)$d->cantidad,
                    costoUnitario: $costoUnit,
                    tipoDocumentoId: $tipoId,
                    docTipoLegacy: 'REV_NOTA_CREDITO',
                    docId: (int)$nota->id,
                    ref: 'Reversión NC de ' . static::refFactura($nota->factura ?? null)
                );

                // ✅ Registrar en producto_costo_movimientos
                static::registrarCostoMovimiento(
                    fecha: now(),
                    productoId: (int)$d->producto_id,
                    bodegaId: (int)$d->bodega_id,
                    tipo: 'SALIDA',
                    cantidad: (float)$d->cantidad,
                    costoUnitario: $costoUnit,
                    stockAntes: $stockAntes,
                    stockDespues: (float)$pb->stock,
                    tipoDocumentoId: $tipoId,
                    docId: (int)$nota->id,
                    origen: 'nota_credito',
                    detalle: 'Reversión NC de ' . static::refFactura($nota->factura ?? null),
                    tipoEvento: 'REV_NOTA_CREDITO'
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

            // id del tipo de documento para "facturacompra"
            $tipoDocId = (int)(TipoDocumento::whereRaw('LOWER(codigo)=?', ['facturacompra'])->value('id') ?? 0);

            $factura->loadMissing('detalles.producto');

            foreach ($factura->detalles as $d) {
                if (!$d->producto_id || !$d->bodega_id) {
                    continue;
                }
                $cantidad = (float)$d->cantidad;
                if ($cantidad <= 0) {
                    continue;
                }

                // costo unitario del detalle de compra (neto)
                $costoUnitMov = static::costoUnitarioCompra($d);

                // Bloqueo de la fila de inventario
                $pb = ProductoBodega::query()
                    ->where('producto_id', $d->producto_id)
                    ->where('bodega_id',  $d->bodega_id)
                    ->lockForUpdate()
                    ->first();

                if (!$pb) {
                    $pb = new ProductoBodega([
                        'producto_id'    => (int)$d->producto_id,
                        'bodega_id'      => (int)$d->bodega_id,
                        'stock'          => 0,
                        'costo_promedio' => 0,
                        'ultimo_costo'   => 0,
                        'metodo_costeo'  => 'PROMEDIO',
                    ]);
                }

                /* CAPTURA DE VALORES ANTES */
                $antesProm   = (float)($pb->costo_promedio ?? 0);
                $antesUltimo = (float)($pb->ultimo_costo   ?? 0);
                $existCant   = (float)($pb->stock ?? 0);
                $existVal    = $existCant * $antesProm;
                $movVal      = $cantidad * $costoUnitMov;

                /* 1) KÁRDEX: ENTRADA */
                $dataKx = [
                    'fecha'          => $factura->fecha ?? now(),
                    'producto_id'    => (int)$d->producto_id,
                    'bodega_id'      => (int)$d->bodega_id,
                    'entrada'        => $cantidad,
                    'salida'         => 0,
                    'cantidad'       => $cantidad,
                    'signo'          => 1,
                    'costo_unitario' => $costoUnitMov,
                    'total'          => round($cantidad * $costoUnitMov, 2),
                    'doc_id'         => (string)$factura->id,
                    'ref'            => 'FAC COMP ' . static::refFactura($factura),
                ];
                if (Schema::hasColumn('kardex_movimientos', 'tipo_documento_id')) {
                    $dataKx['tipo_documento_id'] = $tipoDocId ?: null;
                }
                if (Schema::hasColumn('kardex_movimientos', 'doc_tipo')) {
                    $dataKx['doc_tipo'] = 'FACTURA_COMPRA';
                }
                if (Schema::hasColumn('kardex_movimientos', 'metodo')) {
                    $dataKx['metodo'] = 'PROMEDIO';
                }
                if (Schema::hasColumn('kardex_movimientos', 'evento')) {
                    $dataKx['evento'] = 'FACTURA_COMPRA';
                }
                if (Schema::hasColumn('kardex_movimientos', 'origen')) {
                    $dataKx['origen'] = 'factura';
                }
                if (Schema::hasColumn('kardex_movimientos', 'origen_id')) {
                    $dataKx['origen_id'] = $factura->id;
                }
                
                KardexMovimiento::create($dataKx);

                /* CÁLCULO DE NUEVOS VALORES */
                $nuevoCant = $existCant + $cantidad;
                $nuevoProm = $nuevoCant > 1e-9 ? ($existVal + $movVal) / $nuevoCant : $costoUnitMov;

                /* 2) ✅ HISTORIAL COSTO COMPLETO */
                $dataCostoMov = [
                    'fecha'                 => $factura->fecha ?? now(),
                    'producto_id'           => (int)$d->producto_id,
                    'bodega_id'             => (int)$d->bodega_id,
                    'tipo'                  => 'ENTRADA',
                    'cantidad'              => $cantidad,
                    'costo_unitario'        => $costoUnitMov,
                    'costo_total'           => $movVal,
                    'stock_antes'           => $existCant,
                    'stock_despues'         => $nuevoCant,
                    'origen'                => 'factura',
                    'origen_id'             => $factura->id,
                    'detalle'               => 'FAC COMP ' . static::refFactura($factura),
                    'tipo_evento'           => 'COMPRA',
                    'user_id'               => Auth::id(),
                ];

                // Agregar campos opcionales dinámicamente
                if (Schema::hasColumn('producto_costo_movimientos', 'tipo_documento_id')) {
                    $dataCostoMov['tipo_documento_id'] = $tipoDocId ?: null;
                }
                if (Schema::hasColumn('producto_costo_movimientos', 'doc_id')) {
                    $dataCostoMov['doc_id'] = (string)$factura->id;
                }
                if (Schema::hasColumn('producto_costo_movimientos', 'ref')) {
                    $dataCostoMov['ref'] = 'FAC COMP ' . static::refFactura($factura);
                }
                if (Schema::hasColumn('producto_costo_movimientos', 'valor_mov')) {
                    $dataCostoMov['valor_mov'] = $movVal;
                }
                if (Schema::hasColumn('producto_costo_movimientos', 'costo_unit_mov')) {
                    $dataCostoMov['costo_unit_mov'] = $costoUnitMov;
                }
                if (Schema::hasColumn('producto_costo_movimientos', 'metodo_costeo')) {
                    $dataCostoMov['metodo_costeo'] = $pb->metodo_costeo ?: 'PROMEDIO';
                }
                if (Schema::hasColumn('producto_costo_movimientos', 'costo_prom_anterior')) {
                    $dataCostoMov['costo_prom_anterior'] = $antesProm;
                }
                if (Schema::hasColumn('producto_costo_movimientos', 'costo_prom_nuevo')) {
                    $dataCostoMov['costo_prom_nuevo'] = $nuevoProm;
                }
                if (Schema::hasColumn('producto_costo_movimientos', 'ultimo_costo_anterior')) {
                    $dataCostoMov['ultimo_costo_anterior'] = $antesUltimo;
                }
                if (Schema::hasColumn('producto_costo_movimientos', 'ultimo_costo_nuevo')) {
                    $dataCostoMov['ultimo_costo_nuevo'] = $costoUnitMov;
                }

                ProductoCostoMovimiento::create($dataCostoMov);

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
        ?string $docTipoLegacy = null,
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
            'costo_unitario' => (float)$costoUnitario,
            'total'          => round($cantidad * (float)$costoUnitario, 2),
            'doc_id'         => $docId,
            'ref'            => $ref ?: $tipoLogico,
        ];

        if (Schema::hasColumn('kardex_movimientos', 'tipo_documento_id')) {
            $data['tipo_documento_id'] = $tipoDocumentoId;
        }
        if ($docTipoLegacy !== null && Schema::hasColumn('kardex_movimientos', 'doc_tipo')) {
            $data['doc_tipo'] = $docTipoLegacy;
        }
        if (Schema::hasColumn('kardex_movimientos', 'metodo')) {
            $data['metodo'] = 'PROMEDIO';
        }
        if (Schema::hasColumn('kardex_movimientos', 'origen')) {
            $data['origen'] = 'factura';
        }
        if (Schema::hasColumn('kardex_movimientos', 'origen_id')) {
            $data['origen_id'] = $docId;
        }

        KardexMovimiento::create($data);
    }

    /* =========================================================
     * ✅ NUEVO: REGISTRO GENÉRICO EN PRODUCTO_COSTO_MOVIMIENTOS
     * ========================================================= */
    private static function registrarCostoMovimiento(
        $fecha,
        int $productoId,
        int $bodegaId,
        string $tipo,
        float $cantidad,
        float $costoUnitario,
        float $stockAntes,
        float $stockDespues,
        ?int $tipoDocumentoId = null,
        ?int $docId = null,
        ?string $origen = 'factura',
        ?string $detalle = null,
        ?string $tipoEvento = null
    ): void {
        $costoTotal = round($cantidad * $costoUnitario, 2);

        $data = [
            'fecha'         => $fecha ?? now(),
            'producto_id'   => $productoId,
            'bodega_id'     => $bodegaId,
            'tipo'          => $tipo,
            'cantidad'      => $cantidad,
            'costo_unitario'=> $costoUnitario,
            'costo_total'   => $costoTotal,
            'stock_antes'   => $stockAntes,
            'stock_despues' => $stockDespues,
            'origen'        => $origen,
            'origen_id'     => $docId,
            'detalle'       => $detalle,
            'tipo_evento'   => $tipoEvento,
            'user_id'       => Auth::id(),
        ];

        // Campos opcionales
        if (Schema::hasColumn('producto_costo_movimientos', 'tipo_documento_id')) {
            $data['tipo_documento_id'] = $tipoDocumentoId;
        }
        if (Schema::hasColumn('producto_costo_movimientos', 'doc_id')) {
            $data['doc_id'] = (string)$docId;
        }
        if (Schema::hasColumn('producto_costo_movimientos', 'ref')) {
            $data['ref'] = $detalle;
        }
        if (Schema::hasColumn('producto_costo_movimientos', 'valor_mov')) {
            $data['valor_mov'] = $costoTotal;
        }
        if (Schema::hasColumn('producto_costo_movimientos', 'costo_unit_mov')) {
            $data['costo_unit_mov'] = $costoUnitario;
        }
        if (Schema::hasColumn('producto_costo_movimientos', 'metodo_costeo')) {
            $data['metodo_costeo'] = 'PROMEDIO';
        }

        ProductoCostoMovimiento::create($data);
    }

    /* =========================================================
     * HELPERS: COSTOS / REFERENCIAS / TIPO DOC
     * ========================================================= */
    private static function resolverCostoUnitarioSalida($detalle): float
    {
        if (!empty($detalle->costo_unitario) && $detalle->costo_unitario > 0) {
            return (float)$detalle->costo_unitario;
        }

        $pb = ProductoBodega::query()
            ->where('producto_id', $detalle->producto_id)
            ->where('bodega_id', $detalle->bodega_id)
            ->first();

        if ($pb && $pb->costo_promedio > 0) {
            return (float)$pb->costo_promedio;
        }

        if ($detalle->producto && $detalle->producto->costo_promedio > 0) {
            return (float)$detalle->producto->costo_promedio;
        }

        return (float)($detalle->producto->costo ?? 0);
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
                return (float)$v;
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
        $pref = $f->prefijo ? ($f->prefijo . '-') : '';
        $num  = $f->numero ? (string)$f->numero : '—';
        return $pref . $num;
    }

    private static function tipoDocumentoId(string $codigo, ?Factura $factura = null): ?int
    {
        $codigo = strtoupper(trim($codigo));

        if ($codigo === 'FACTURA' && $factura && $factura->relationLoaded('serie') && $factura->serie?->tipo) {
            return (int)$factura->serie->tipo->id;
        }

        return cache()->remember("tipo_doc_id:$codigo", 600, function () use ($codigo) {
            return (int)(TipoDocumento::query()
                ->whereRaw('UPPER(codigo) = ?', [$codigo])
                ->value('id') ?? 0) ?: null;
        });
    }

    private static function costoUnitarioCompra($detalle): float
    {
        $pu   = (float)($detalle->precio_unitario ?? 0);
        $desc = (float)($detalle->descuento_pct   ?? 0);

        // Neto con descuento
        $puNeto = $pu * (1 - max(0, min(100, $desc)) / 100);

        // Si tu precio viene con IVA y quieres neto sin IVA, descomenta:
        // $iva = (float)($detalle->impuesto_pct ?? 0);
        // if ($iva > 0) $puNeto = $puNeto / (1 + $iva / 100);

        if ($puNeto <= 0 && !empty($detalle->costo_unitario)) {
            $puNeto = (float)$detalle->costo_unitario;
        }

        return max(0.0, round($puNeto, 6));
    }
}