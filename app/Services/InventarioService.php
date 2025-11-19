<?php

namespace App\Services;

use App\Models\Factura\Factura;
use App\Models\Movimiento\KardexMovimiento;
use App\Models\Movimiento\ProductoCostoMovimiento;
use App\Models\NotaCredito;
use App\Models\Productos\ProductoBodega;
use App\Models\TiposDocumento\TipoDocumento;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class InventarioService
{
    /* =========================================================
     *  VALIDACIÓN DE DISPONIBILIDAD (VENTA)
     * ========================================================= */
    public static function verificarDisponibilidadParaFactura(Factura $f): void
    {
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

    /* =========================================================
     *  ENTRADA POR FACTURA DE COMPRA (envoltura)
     * ========================================================= */
    public static function aumentarPorFacturaCompra(Factura $factura): void
    {
        static::aplicarCompraYCosteo($factura);
    }

    /* =========================================================
     *  SALIDA POR FACTURA DE VENTA
     * ========================================================= */
    public static function descontarPorFactura(Factura $f): void
    {
        DB::transaction(function () use ($f) {
            $tipoDocumentoId = optional($f->serie)->tipo_documento_id;

            foreach ($f->detalles as $d) {
                if (!$d->producto_id || !$d->bodega_id || $d->cantidad <= 0) continue;

                // CPU a usar en la salida
                $cpu = \App\Services\ContabilidadService::costoPromedioParaLinea(
                    $d->producto()->with('cuentas')->first(),
                    $d->bodega_id
                );
                $cant       = (float)$d->cantidad;
                $costoTotal = round($cpu * $cant, 2);

                // 1) Stock (SALIDA)
                $pb = ProductoBodega::lockForUpdate()
                    ->where('producto_id', $d->producto_id)
                    ->where('bodega_id',  $d->bodega_id)
                    ->firstOrFail();

                $stockAntes   = (float)$pb->stock;
                $stockDespues = round($stockAntes - $cant, 6);
                $pb->update(['stock' => $stockDespues]); // en salida no se recalcula cpm

                // 2) Kardex (SALIDA)
                $dataKardex = [
                    'fecha'          => $f->fecha,
                    'producto_id'    => $d->producto_id,
                    'bodega_id'      => $d->bodega_id,
                    'entrada'        => 0,
                    'salida'         => $cant,
                    'cantidad'       => $cant,           // positivo; separado por entrada/salida
                    'signo'          => -1,
                    'costo_unitario' => $cpu,
                    'total'          => $costoTotal,
                ];
                if (Schema::hasColumn('kardex_movimientos', 'tipo_documento_id')) $dataKardex['tipo_documento_id'] = $tipoDocumentoId;
                if (Schema::hasColumn('kardex_movimientos', 'metodo'))            $dataKardex['metodo']            = 'PROMEDIO';
                if (Schema::hasColumn('kardex_movimientos', 'evento'))             $dataKardex['evento']             = 'FACTURA_VENTA';
                if (Schema::hasColumn('kardex_movimientos', 'origen'))             $dataKardex['origen']             = 'factura';
                if (Schema::hasColumn('kardex_movimientos', 'origen_id'))          $dataKardex['origen_id']          = $f->id;
                if (Schema::hasColumn('kardex_movimientos', 'doc_tipo'))           $dataKardex['doc_tipo']           = 'FACTURA_VENTA';
                if (Schema::hasColumn('kardex_movimientos', 'doc_id'))             $dataKardex['doc_id']             = (string)$f->id;
                if (Schema::hasColumn('kardex_movimientos', 'ref'))                $dataKardex['ref']                = 'FV ' . static::refFactura($f);
                if (Schema::hasColumn('kardex_movimientos', 'detalle'))            $dataKardex['detalle']            = "Factura {$f->prefijo}-{$f->numero}";

                KardexMovimiento::create($dataKardex);

                // 3) Historial de costos (SALIDA)
                $dataCosto = [
                    'fecha'          => $f->fecha,
                    'producto_id'    => $d->producto_id,
                    'bodega_id'      => $d->bodega_id,
                    'tipo'           => 'SALIDA',
                    'cantidad'       => $cant,
                    'costo_unitario' => $cpu,
                    'costo_total'    => $costoTotal,
                    'stock_antes'    => $stockAntes,
                    'stock_despues'  => $stockDespues,
                    'origen'         => 'factura',
                    'origen_id'      => $f->id,
                    'detalle'        => 'Factura ' . static::refFactura($f),
                    'tipo_evento'    => 'VENTA',
                    'user_id'        => Auth::id(),
                ];
                if (Schema::hasColumn('producto_costo_movimientos', 'tipo_documento_id')) $dataCosto['tipo_documento_id'] = $tipoDocumentoId;
                if (Schema::hasColumn('producto_costo_movimientos', 'doc_id'))           $dataCosto['doc_id']           = (string)$f->id;
                if (Schema::hasColumn('producto_costo_movimientos', 'ref'))              $dataCosto['ref']              = 'FV ' . static::refFactura($f);
                if (Schema::hasColumn('producto_costo_movimientos', 'valor_mov'))        $dataCosto['valor_mov']        = $costoTotal;
                if (Schema::hasColumn('producto_costo_movimientos', 'costo_unit_mov'))   $dataCosto['costo_unit_mov']   = $cpu;
                if (Schema::hasColumn('producto_costo_movimientos', 'metodo_costeo'))    $dataCosto['metodo_costeo']    = 'PROMEDIO';

                ProductoCostoMovimiento::create($dataCosto);
            }
        });
    }

    /* =========================================================
     *  REVERSA POR ANULACIÓN DE FACTURA (devuelve stock)
     * ========================================================= */
    public static function revertirPorFactura(Factura $factura): void
    {
        DB::transaction(function () use ($factura) {
            $factura->loadMissing('detalles.producto', 'serie.tipo');
            $tipoId = static::tipoDocumentoId('ANULACION_FACTURA');

            foreach ($factura->detalles as $d) {
                if (!$d->producto_id || !$d->bodega_id) continue;

                $pb = ProductoBodega::lockForUpdate()
                    ->firstOrNew(['producto_id'=>$d->producto_id, 'bodega_id'=>$d->bodega_id],
                        ['stock'=>0,'costo_promedio'=>0,'ultimo_costo'=>0]);

                $stockAntes = (float)$pb->stock;
                $pb->stock  = $stockAntes + (float)$d->cantidad;
                $pb->save();

                $cpu = static::resolverCostoUnitarioReversion($d);

                // Kárdex (ENTRADA)
                static::registrarKardex(
                    tipoLogico: 'ANULACION',
                    signo: 1,
                    fecha: now(),
                    productoId: (int)$d->producto_id,
                    bodegaId: (int)$d->bodega_id,
                    cantidad: (float)$d->cantidad,
                    costoUnitario: $cpu,
                    tipoDocumentoId: $tipoId,
                    docTipoLegacy: 'ANULACION_FACTURA',
                    docId: (int)$factura->id,
                    ref: 'Anulación ' . static::refFactura($factura)
                );

                // Historial costo
                static::registrarCostoMovimiento(
                    fecha: now(),
                    productoId: (int)$d->producto_id,
                    bodegaId: (int)$d->bodega_id,
                    tipo: 'ENTRADA',
                    cantidad: (float)$d->cantidad,
                    costoUnitario: $cpu,
                    stockAntes: $stockAntes,
                    stockDespues: (float)$pb->stock,
                    tipoDocumentoId: $tipoId,
                    docId: (int)$factura->id,
                    origen: 'factura',
                    detalle: 'Anulación ' . static::refFactura($factura),
                    tipoEvento: 'ANULACION'
                );
            }
        });
    }

    /* =========================================================
     *  NOTA CRÉDITO: repone stock
     * ========================================================= */
    public static function reponerPorNotaCredito(NotaCredito $nc): void
    {
        $nc->loadMissing('detalles.producto');

        DB::transaction(function () use ($nc) {
            foreach ($nc->detalles as $d) {
                $cant = (float)$d->cantidad;
                if ($cant <= 0 || !$d->producto_id || !$d->bodega_id) continue;

                $cpu        = \App\Services\ContabilidadService::costoPromedioParaLinea($d->producto, (int)$d->bodega_id);
                $costoTotal = round($cpu * $cant, 2);

                $pb = ProductoBodega::lockForUpdate()
                    ->firstOrNew(['producto_id'=>$d->producto_id, 'bodega_id'=>$d->bodega_id],
                        ['stock'=>0,'costo_promedio'=>$cpu ?: 0]);

                $stockAct   = (float)$pb->stock;
                $cpuAct     = (float)$pb->costo_promedio;
                $nuevoStock = $stockAct + $cant;
                $nuevoCPU   = $nuevoStock > 0
                    ? ($stockAct > 0 ? round((($stockAct*$cpuAct)+($cant*$cpu))/$nuevoStock, 4) : round($cpu,4))
                    : round($cpu,4);

                $pb->update(['stock'=>$nuevoStock, 'costo_promedio'=>$nuevoCPU]);

                // Kardex (ENTRADA) - protegido por columnas opcionales
                $kx = [
                    'fecha'          => $nc->fecha ?? now(),
                    'producto_id'    => $d->producto_id,
                    'bodega_id'      => $d->bodega_id,
                    'entrada'        => $cant,
                    'salida'         => 0,
                    'cantidad'       => $cant,
                    'signo'          => 1,
                    'costo_unitario' => $cpu,
                    'total'          => $costoTotal,
                    'detalle'        => 'Reposición por Nota Crédito',
                ];
                if (Schema::hasColumn('kardex_movimientos','tipo_documento_id')) $kx['tipo_documento_id'] = optional($nc->serie)->tipo_documento_id;
                if (Schema::hasColumn('kardex_movimientos','origen'))             $kx['origen']  = 'nota_credito';
                if (Schema::hasColumn('kardex_movimientos','origen_id'))          $kx['origen_id']= $nc->id;
                if (Schema::hasColumn('kardex_movimientos','doc_tipo'))           $kx['doc_tipo']= 'NOTA_CREDITO';
                if (Schema::hasColumn('kardex_movimientos','doc_id'))             $kx['doc_id']  = (string)$nc->id;
                if (Schema::hasColumn('kardex_movimientos','ref'))                $kx['ref']     = 'NC ' . ($nc->prefijo ? "{$nc->prefijo}-" : '') . (string)$nc->numero;

                KardexMovimiento::create($kx);

                // Historial de costos (ENTRADA_NC) - protegido por columnas opcionales
                $hc = [
                    'fecha'          => $nc->fecha ?? now(),
                    'producto_id'    => $d->producto_id,
                    'bodega_id'      => $d->bodega_id,
                    'tipo'           => 'ENTRADA',
                    'cantidad'       => $cant,
                    'costo_unitario' => $cpu,
                    'costo_total'    => $costoTotal,
                    'stock_antes'    => $stockAct,
                    'stock_despues'  => $nuevoStock,
                    'origen'         => 'nota_credito',
                    'origen_id'      => $nc->id,
                    'detalle'        => 'Reposición NC ' . ($nc->prefijo ? "{$nc->prefijo}-" : '') . (string)$nc->numero,
                    'tipo_evento'    => 'NOTA_CREDITO',
                    'user_id'        => Auth::id(),
                ];
                if (Schema::hasColumn('producto_costo_movimientos','tipo_documento_id')) $hc['tipo_documento_id'] = optional($nc->serie)->tipo_documento_id;
                if (Schema::hasColumn('producto_costo_movimientos','doc_id'))           $hc['doc_id']           = (string)$nc->id;
                if (Schema::hasColumn('producto_costo_movimientos','ref'))              $hc['ref']              = 'NC ' . ($nc->prefijo ? "{$nc->prefijo}-" : '') . (string)$nc->numero;
                if (Schema::hasColumn('producto_costo_movimientos','valor_mov'))        $hc['valor_mov']        = $costoTotal;
                if (Schema::hasColumn('producto_costo_movimientos','costo_unit_mov'))   $hc['costo_unit_mov']   = $cpu;
                if (Schema::hasColumn('producto_costo_movimientos','metodo_costeo'))    $hc['metodo_costeo']    = 'PROMEDIO';

                ProductoCostoMovimiento::create($hc);
            }
        });
    }

    /* =========================================================
     *  REVERSA de la reposición por NC
     * ========================================================= */
    public static function revertirReposicionPorNotaCredito(NotaCredito $nc): void
    {
        $nc->loadMissing('detalles.producto');

        DB::transaction(function () use ($nc) {
            foreach ($nc->detalles as $d) {
                $cant = (float)$d->cantidad;
                if ($cant <= 0 || !$d->producto_id || !$d->bodega_id) continue;

                $cpu        = \App\Services\ContabilidadService::costoPromedioParaLinea($d->producto, (int)$d->bodega_id);
                $costoTotal = round($cpu * $cant, 2);

                $pb = ProductoBodega::lockForUpdate()
                    ->where('producto_id', $d->producto_id)
                    ->where('bodega_id',  $d->bodega_id)
                    ->first();

                $stockAct   = (float)($pb->stock ?? 0);
                $nuevoStock = max(0, $stockAct - $cant);
                if ($pb) $pb->update(['stock'=>$nuevoStock]);

                // Kardex (SALIDA)
                $kx = [
                    'fecha'          => $nc->fecha ?? now(),
                    'producto_id'    => $d->producto_id,
                    'bodega_id'      => $d->bodega_id,
                    'entrada'        => 0,
                    'salida'         => $cant,
                    'cantidad'       => $cant,
                    'signo'          => -1,
                    'costo_unitario' => $cpu,
                    'total'          => $costoTotal,
                    'detalle'        => 'Reverso reposición NC',
                ];
                if (Schema::hasColumn('kardex_movimientos','tipo_documento_id')) $kx['tipo_documento_id'] = optional($nc->serie)->tipo_documento_id;
                if (Schema::hasColumn('kardex_movimientos','origen'))             $kx['origen']  = 'nota_credito';
                if (Schema::hasColumn('kardex_movimientos','origen_id'))          $kx['origen_id']= $nc->id;
                if (Schema::hasColumn('kardex_movimientos','doc_tipo'))           $kx['doc_tipo']= 'NOTA_CREDITO_REVERSO';
                if (Schema::hasColumn('kardex_movimientos','doc_id'))             $kx['doc_id']  = (string)$nc->id;
                if (Schema::hasColumn('kardex_movimientos','ref'))                $kx['ref']     = 'REV NC ' . ($nc->prefijo ? "{$nc->prefijo}-" : '') . (string)$nc->numero;

                KardexMovimiento::create($kx);

                // Historial (SALIDA)
                $hc = [
                    'fecha'          => $nc->fecha ?? now(),
                    'producto_id'    => $d->producto_id,
                    'bodega_id'      => $d->bodega_id,
                    'tipo'           => 'SALIDA',
                    'cantidad'       => $cant,
                    'costo_unitario' => $cpu,
                    'costo_total'    => $costoTotal,
                    'stock_antes'    => $stockAct,
                    'stock_despues'  => $nuevoStock,
                    'origen'         => 'nota_credito',
                    'origen_id'      => $nc->id,
                    'detalle'        => 'Reverso reposición NC ' . ($nc->prefijo ? "{$nc->prefijo}-" : '') . (string)$nc->numero,
                    'tipo_evento'    => 'REVERSO_NC',
                    'user_id'        => Auth::id(),
                ];
                if (Schema::hasColumn('producto_costo_movimientos','tipo_documento_id')) $hc['tipo_documento_id'] = optional($nc->serie)->tipo_documento_id;
                if (Schema::hasColumn('producto_costo_movimientos','doc_id'))           $hc['doc_id']           = (string)$nc->id;
                if (Schema::hasColumn('producto_costo_movimientos','ref'))              $hc['ref']              = 'REV NC ' . ($nc->prefijo ? "{$nc->prefijo}-" : '') . (string)$nc->numero;
                if (Schema::hasColumn('producto_costo_movimientos','valor_mov'))        $hc['valor_mov']        = $costoTotal;
                if (Schema::hasColumn('producto_costo_movimientos','costo_unit_mov'))   $hc['costo_unit_mov']   = $cpu;
                if (Schema::hasColumn('producto_costo_movimientos','metodo_costeo'))    $hc['metodo_costeo']    = 'PROMEDIO';

                ProductoCostoMovimiento::create($hc);
            }
        });
    }

    /* =========================================================
     *  COMPRA: Kardex + historial costo + actualizar PB
     * ========================================================= */
    public static function aplicarCompraYCosteo(Factura $factura): void
    {
        DB::transaction(function () use ($factura) {
            $tipoDocId = (int)(TipoDocumento::whereRaw('LOWER(codigo)=?', ['facturacompra'])->value('id') ?? 0);
            $factura->loadMissing('detalles.producto');

            foreach ($factura->detalles as $d) {
                if (!$d->producto_id || !$d->bodega_id) continue;

                $cantidad   = (float)$d->cantidad;
                if ($cantidad <= 0) continue;

                $costoUnit  = static::costoUnitarioCompra($d);
                $valorMov   = round($cantidad * $costoUnit, 2);

                $pb = ProductoBodega::lockForUpdate()
                    ->firstOrNew(['producto_id'=>$d->producto_id,'bodega_id'=>$d->bodega_id],
                        ['stock'=>0,'costo_promedio'=>0,'ultimo_costo'=>0,'metodo_costeo'=>'PROMEDIO']);

                // valores previos
                $existCant = (float)$pb->stock;
                $existVal  = $existCant * (float)$pb->costo_promedio;

                // 1) Kardex (ENTRADA)
                $dataKx = [
                    'fecha'          => $factura->fecha ?? now(),
                    'producto_id'    => (int)$d->producto_id,
                    'bodega_id'      => (int)$d->bodega_id,
                    'entrada'        => $cantidad,
                    'salida'         => 0,
                    'cantidad'       => $cantidad,
                    'signo'          => 1,
                    'costo_unitario' => $costoUnit,
                    'total'          => $valorMov,
                ];
                if (Schema::hasColumn('kardex_movimientos', 'tipo_documento_id')) $dataKx['tipo_documento_id'] = $tipoDocId ?: null;
                if (Schema::hasColumn('kardex_movimientos', 'doc_tipo'))           $dataKx['doc_tipo']           = 'FACTURA_COMPRA';
                if (Schema::hasColumn('kardex_movimientos', 'metodo'))             $dataKx['metodo']             = 'PROMEDIO';
                if (Schema::hasColumn('kardex_movimientos', 'evento'))             $dataKx['evento']             = 'FACTURA_COMPRA';
                if (Schema::hasColumn('kardex_movimientos', 'origen'))             $dataKx['origen']             = 'factura';
                if (Schema::hasColumn('kardex_movimientos', 'origen_id'))          $dataKx['origen_id']          = $factura->id;
                if (Schema::hasColumn('kardex_movimientos', 'doc_id'))             $dataKx['doc_id']             = (string)$factura->id;
                if (Schema::hasColumn('kardex_movimientos', 'ref'))                $dataKx['ref']                = 'FAC COMP ' . static::refFactura($factura);

                KardexMovimiento::create($dataKx);

                // 2) Historial costo
                $nuevoCant = $existCant + $cantidad;
                $nuevoProm = $nuevoCant > 1e-9 ? ($existVal + $valorMov) / $nuevoCant : $costoUnit;

                $dataCosto = [
                    'fecha'          => $factura->fecha ?? now(),
                    'producto_id'    => (int)$d->producto_id,
                    'bodega_id'      => (int)$d->bodega_id,
                    'tipo'           => 'ENTRADA',
                    'cantidad'       => $cantidad,
                    'costo_unitario' => $costoUnit,
                    'costo_total'    => $valorMov,
                    'stock_antes'    => $existCant,
                    'stock_despues'  => $nuevoCant,
                    'origen'         => 'factura',
                    'origen_id'      => $factura->id,
                    'detalle'        => 'FAC COMP ' . static::refFactura($factura),
                    'tipo_evento'    => 'COMPRA',
                    'user_id'        => Auth::id(),
                ];
                if (Schema::hasColumn('producto_costo_movimientos', 'tipo_documento_id')) $dataCosto['tipo_documento_id'] = $tipoDocId ?: null;
                if (Schema::hasColumn('producto_costo_movimientos', 'doc_id'))           $dataCosto['doc_id']           = (string)$factura->id;
                if (Schema::hasColumn('producto_costo_movimientos', 'ref'))              $dataCosto['ref']              = 'FAC COMP ' . static::refFactura($factura);
                if (Schema::hasColumn('producto_costo_movimientos', 'valor_mov'))        $dataCosto['valor_mov']        = $valorMov;
                if (Schema::hasColumn('producto_costo_movimientos', 'costo_unit_mov'))   $dataCosto['costo_unit_mov']   = $costoUnit;
                if (Schema::hasColumn('producto_costo_movimientos', 'metodo_costeo'))    $dataCosto['metodo_costeo']    = $pb->metodo_costeo ?: 'PROMEDIO';

                ProductoCostoMovimiento::create($dataCosto);

                // 3) Actualiza PB
                $pb->stock          = $nuevoCant;
                $pb->costo_promedio = round($nuevoProm, 6);
                $pb->ultimo_costo   = round($costoUnit, 6);
                $pb->metodo_costeo  = $pb->metodo_costeo ?: 'PROMEDIO';
                $pb->save();
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
            'cantidad'       => $cantidad, // positivo siempre
            'signo'          => $signo,
            'costo_unitario' => (float)$costoUnitario,
            'total'          => round($cantidad * (float)$costoUnitario, 2),
        ];

        if (Schema::hasColumn('kardex_movimientos', 'tipo_documento_id')) $data['tipo_documento_id'] = $tipoDocumentoId;
        if ($docTipoLegacy !== null && Schema::hasColumn('kardex_movimientos', 'doc_tipo')) $data['doc_tipo'] = $docTipoLegacy;
        if (Schema::hasColumn('kardex_movimientos', 'metodo'))    $data['metodo']    = 'PROMEDIO';
        if (Schema::hasColumn('kardex_movimientos', 'origen'))    $data['origen']    = 'factura';
        if (Schema::hasColumn('kardex_movimientos', 'origen_id')) $data['origen_id'] = $docId;
        if (Schema::hasColumn('kardex_movimientos', 'doc_id'))    $data['doc_id']    = $docId ? (string)$docId : null;
        if (Schema::hasColumn('kardex_movimientos', 'ref'))       $data['ref']       = $ref ?: $tipoLogico;

        KardexMovimiento::create($data);
    }

    /* =========================================================
     *  REGISTRO GENÉRICO EN HISTORIAL DE COSTOS
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
            'fecha'          => $fecha ?? now(),
            'producto_id'    => $productoId,
            'bodega_id'      => $bodegaId,
            'tipo'           => $tipo,
            'cantidad'       => $cantidad,
            'costo_unitario' => $costoUnitario,
            'costo_total'    => $costoTotal,
            'stock_antes'    => $stockAntes,
            'stock_despues'  => $stockDespues,
            'origen'         => $origen,
            'origen_id'      => $docId,
            'detalle'        => $detalle,
            'tipo_evento'    => $tipoEvento,
            'user_id'        => Auth::id(),
        ];

        if (Schema::hasColumn('producto_costo_movimientos', 'tipo_documento_id')) $data['tipo_documento_id'] = $tipoDocumentoId;
        if (Schema::hasColumn('producto_costo_movimientos', 'doc_id'))           $data['doc_id']           = $docId ? (string)$docId : null;
        if (Schema::hasColumn('producto_costo_movimientos', 'ref'))              $data['ref']              = $detalle;
        if (Schema::hasColumn('producto_costo_movimientos', 'valor_mov'))        $data['valor_mov']        = $costoTotal;
        if (Schema::hasColumn('producto_costo_movimientos', 'costo_unit_mov'))   $data['costo_unit_mov']   = $costoUnitario;
        if (Schema::hasColumn('producto_costo_movimientos', 'metodo_costeo'))    $data['metodo_costeo']    = 'PROMEDIO';

        ProductoCostoMovimiento::create($data);
    }

    /* =========================================================
     *  HELPERS
     * ========================================================= */
    private static function resolverCostoUnitarioReversion($detalle): float
    {
        $cand = [
            $detalle->costo_unitario ?? null,
            optional(ProductoBodega::where('producto_id',$detalle->producto_id)->where('bodega_id',$detalle->bodega_id)->first())->costo_promedio,
            optional($detalle->producto)->costo_promedio,
            optional($detalle->producto)->costo,
        ];
        foreach ($cand as $v) if (is_numeric($v) && (float)$v >= 0) return (float)$v;
        return 0.0;
    }

    private static function refFactura(?Factura $f): string
    {
        if (!$f) return '—';
        $pref = $f->prefijo ? "{$f->prefijo}-" : '';
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
        $puN  = $pu * (1 - max(0, min(100, $desc)) / 100);
        return max(0.0, round($puN, 6));
    }

    
}
