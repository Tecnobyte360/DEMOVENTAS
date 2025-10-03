<?php

namespace App\Services;

use App\Models\Factura\Factura;
use App\Models\NotaCredito;

use Illuminate\Support\Facades\Log;

class PagosService
{
    /**
     * Aplica una Nota Crédito sobre la factura asociada,
     * reduciendo el saldo pendiente o registrando un pago negativo.
     */
    public static function aplicarNotaCreditoSobreFactura(NotaCredito $nc): void
    {
        if (!$nc->factura_id) {
            Log::warning('NC sin factura asociada', ['nc_id' => $nc->id]);
            return;
        }

        $factura = Factura::find($nc->factura_id);
        if (!$factura) {
            Log::warning('Factura no encontrada para aplicar NC', ['nc_id' => $nc->id]);
            return;
        }

        // Aquí decides la lógica:
        // 1. Registrar un "pago negativo"
        // 2. Ajustar campo `pagado` en la factura
        // 3. Crear un movimiento en tu tabla pagos

        $factura->pagado = max(0, ($factura->pagado ?? 0) - (float) $nc->total);
        $factura->save();

        Log::info('Nota Crédito aplicada sobre factura', [
            'factura_id' => $factura->id,
            'nc_id'      => $nc->id,
            'nuevo_pagado' => $factura->pagado,
        ]);
    }
}
