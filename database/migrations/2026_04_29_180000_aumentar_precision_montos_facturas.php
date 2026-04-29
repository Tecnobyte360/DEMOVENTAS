<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Aumenta precisión de columnas de dinero en facturas y detalles
     * para soportar valores muy grandes (hasta ~10^16).
     */
    public function up(): void
    {
        $cambios = [
            'facturas' => [
                'subtotal'        => 'DECIMAL(18,2)',
                'impuestos'       => 'DECIMAL(18,2)',
                'total'           => 'DECIMAL(18,2)',
                'pagado'          => 'DECIMAL(18,2)',
                'saldo'           => 'DECIMAL(18,2)',
                'monto_aplicado' => 'DECIMAL(18,2)',
            ],
            'factura_detalles' => [
                'precio_unitario'  => 'DECIMAL(18,2)',
                'descuento_pct'    => 'DECIMAL(8,3)',
                'impuesto_pct'     => 'DECIMAL(8,3)',
                'importe_base'     => 'DECIMAL(18,2)',
                'importe_impuesto' => 'DECIMAL(18,2)',
                'importe_total'    => 'DECIMAL(18,2)',
                'cantidad'         => 'DECIMAL(15,3)',
            ],
            'factura_pagos' => [
                'monto' => 'DECIMAL(18,2)',
            ],
            'cotizaciones' => [
                'subtotal'  => 'DECIMAL(18,2)',
                'impuestos' => 'DECIMAL(18,2)',
                'total'     => 'DECIMAL(18,2)',
            ],
            'cotizacion_detalles' => [
                'precio_unitario' => 'DECIMAL(18,2)',
                'descuento_pct'   => 'DECIMAL(8,3)',
                'impuesto_pct'    => 'DECIMAL(8,3)',
                'importe'         => 'DECIMAL(18,2)',
                'cantidad'        => 'DECIMAL(15,3)',
            ],
        ];

        foreach ($cambios as $tabla => $columnas) {
            if (!Schema::hasTable($tabla)) {
                continue;
            }
            foreach ($columnas as $col => $tipo) {
                if (Schema::hasColumn($tabla, $col)) {
                    try {
                        DB::statement("ALTER TABLE `{$tabla}` MODIFY `{$col}` {$tipo} NULL");
                    } catch (\Throwable $e) {
                        // ignora si ya está al tamaño correcto o hay conflicto
                    }
                }
            }
        }
    }

    public function down(): void
    {
        // No revertimos por seguridad (perdería precisión y truncaría datos).
    }
};
