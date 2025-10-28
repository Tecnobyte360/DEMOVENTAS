<?php

namespace App\Services;

use App\Models\Factura\Factura;
use App\Models\Factura\FacturaPago;
use App\Models\NotaCredito;
use App\Models\Asiento\Asiento;
use App\Models\CuentasContables\PlanCuentas;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PagosService
{
    /** Compatibilidad con código viejo */
    public static function aplicarNotaCreditoSobreFactura(NotaCredito $nc): void
    {
        try {
            self::revertirPagoSiCorresponde($nc);
        } catch (\Throwable $e) {
            Log::warning('PagosService::aplicarNotaCreditoSobreFactura falló', [
                'nc_id' => $nc->id,
                'msg'   => $e->getMessage(),
            ]);
        }
    }

    /**
     * Si la NC cubre parte o todo lo cobrado:
     * - Asiento de reversión (Cr Caja/Bancos, Dr CxC)
     * - Pagos negativos (estado = 'reversado') distribuidos por medios
     * - Recalcula totales y ANULA si cubre 100%
     */
    public static function revertirPagoSiCorresponde(NotaCredito $nc): void
    {
        if (!$nc->factura_id) {
            Log::info('NC sin factura asociada; solo ajusta contabilidad de ventas/IVA', ['nc_id' => $nc->id]);
            return;
        }

        $factura = Factura::with(['pagos.medioPago'])->find($nc->factura_id);
        if (!$factura) {
            Log::warning('Factura no encontrada para aplicar NC', ['nc_id' => $nc->id]);
            return;
        }

        $montoARevertir = min((float) $nc->total, (float) $factura->pagado);

        DB::transaction(function () use ($factura, $nc, $montoARevertir) {
            if ($montoARevertir > 0.0) {
                self::asientoReversionCobro($factura, $montoARevertir);

                // Pagos negativos por orden cronológico
                $restante = $montoARevertir;
                foreach ($factura->pagos()->orderBy('fecha')->get() as $pago) {
                    if ($restante <= 0) break;

                    $aplicar = min($restante, (float) $pago->monto);
                    if ($aplicar <= 0) continue;

                    $factura->pagos()->create([
                        'fecha'         => now()->toDateString(),
                        'medio_pago_id' => $pago->medio_pago_id,
                        'metodo'        => $pago->metodo,
                        'monto'         => -$aplicar,            // reverso
                        'referencia'    => 'Reverso por NC ' . $nc->id,
                        'notas'         => 'Reversión de cobro por Nota Crédito #' . $nc->id,
                        'estado'        => 'reversado',          // 👈 NUEVO: marca el estado
                    ]);

                    $restante -= $aplicar;
                }
            }

            // Recalcular totales
            $factura->recalcularTotales()->save();

            // Anular si cubre 100%
            if (round($nc->total, 2) >= round($factura->total, 2)) {
                $factura->update([
                    'estado' => 'anulada',
                    'saldo'  => 0,
                    'pagado' => 0,
                ]);
            }
        });
    }

    /**
     * Asiento de reversión de cobro:
     *  - Cr: Caja/Bancos (según medios cobrados)
     *  - Dr: CxC (cuenta_cobro_id)
     */
    private static function asientoReversionCobro(Factura $f, float $monto): Asiento
    {
        return DB::transaction(function () use ($f, $monto) {
            $asiento = Asiento::create([
                'fecha'       => now()->toDateString(),
                'tipo'        => 'REVERSION_COBRO',
                'glosa'       => 'Reversión de cobro por NC · ' . $f->numero_formateado,
                'origen'      => 'factura',
                'origen_id'   => $f->id,
                'tercero_id'  => $f->socio_negocio_id,
                'moneda'      => $f->moneda ?? 'COP',
                'total_debe'  => 0,
                'total_haber' => 0,
            ]);

            $meta = ['factura_id' => $f->id, 'tercero_id' => $f->socio_negocio_id];
            $totalDebe = 0.0;
            $totalHaber = 0.0;
            $restante = $monto;

            foreach ($f->pagos as $pago) {
                if ($restante <= 0) break;

                $ap = min($restante, (float) $pago->monto);
                if ($ap <= 0) continue;

                $ctaMedio = \App\Services\ContabilidadService::cuentaDesdeMedioPago($pago->medioPago);
                if (!$ctaMedio || !PlanCuentas::whereKey($ctaMedio)->exists()) {
                    throw new \RuntimeException('No se pudo resolver la cuenta contable del medio de pago.');
                }

                // ⚠️ Asegúrate de que ContabilidadService::post sea PUBLIC static
                $movCaja = \App\Services\ContabilidadService::post(
                    $asiento,
                    (int) $ctaMedio,
                    0.0,
                    round($ap, 2),
                    'Reembolso / reverso de cobro',
                    $meta
                );
                $totalDebe += $movCaja->debito;
                $totalHaber += $movCaja->credito;

                $restante -= $ap;
            }

            // Dr: reabrir CxC
            $movCxC = \App\Services\ContabilidadService::post(
                $asiento,
                (int) $f->cuenta_cobro_id,
                round($monto, 2),
                0.0,
                'Reapertura de CxC por NC',
                $meta
            );
            $totalDebe += $movCxC->debito;
            $totalHaber += $movCxC->credito;

            $asiento->update([
                'total_debe'  => round($totalDebe, 2),
                'total_haber' => round($totalHaber, 2),
            ]);

            return $asiento;
        });
    }
}
