<?php

namespace App\Services;

use App\Models\Asiento\Asiento;
use App\Models\NotaCredito;      // <- tu modelo de NC de COMPRA
use App\Models\Productos\Producto;
use App\Models\CuentasContables\PlanCuentas;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class ContabilidadNotaCreditoCompraService
{
    /**
     * Genera el asiento contable de una Nota Crédito de COMPRA.
     * Inversa de la factura de compra:
     *   DEBE   : Proveedores (CxP)   por el total NC
     *   HABER  : Inventario          por la base
     *   HABER  : IVA descontable     por el IVA compra
     *
     * @param  NotaCredito  $nc   NC de COMPRA (socio_negocio_id = proveedor)
     * @return Asiento
     */
    public static function asientoDesdeNotaCreditoCompra(NotaCredito $nc): Asiento
    {
        // Carga relaciones necesarias (ajusta si tu modelo usa otros nombres)
        $nc->loadMissing([
            'detalles.producto.cuentas.cuentaPUC',
            'detalles.producto.impuesto',
            'proveedor',   // <- si tu relación se llama cliente(), cambia por proveedor()
            'serie',
        ]);

        if ($nc->detalles->isEmpty())   throw new RuntimeException('La nota crédito de compra no tiene líneas.');
        if (!$nc->fecha)                throw new RuntimeException('La nota crédito de compra no tiene fecha.');

        // Asegura totales (por si aún no han sido recalculados)
        if (method_exists($nc, 'recalcularTotales')) {
            $nc->recalcularTotales();
        }

        // Glosa bonita
        $numStr = $nc->prefijo
            ? ($nc->prefijo.'-'.str_pad((string)($nc->numero ?? $nc->id), ($nc->serie->longitud ?? 6), '0', STR_PAD_LEFT))
            : (string)($nc->numero ?? $nc->id);
        $tercero = ($nc->proveedor->razon_social ?? $nc->proveedor->nombre ?? 'Proveedor #'.$nc->socio_negocio_id);
        $glosa   = 'NC Compra '.$numStr.' · '.$tercero;

        return DB::transaction(function () use ($nc, $glosa) {

            // ===============================
            // 1) Cabecera de asiento
            // ===============================
            $asiento = Asiento::create([
                'fecha'       => $nc->fecha,
                'tipo'        => 'NC_COMPRA',
                'glosa'       => $glosa,
                'origen'      => 'nota_credito_compra',
                'origen_id'   => $nc->id,
                'tercero_id'  => $nc->socio_negocio_id,          // proveedor
                'moneda'      => $nc->moneda ?? 'COP',
                'total_debe'  => 0,
                'total_haber' => 0,
            ]);

            $metaBase = [
                'nota_credito_id' => $nc->id,
                'tercero_id'      => $nc->socio_negocio_id,
            ];

            $totalDebe  = 0.0;
            $totalHaber = 0.0;

            // ============================================================
            // 2) Acumulados por cuenta: INVENTARIO (haber) e IVA compra (haber)
            // ============================================================
            $inventarioPorCuenta = [];
            $ivaPorCuentaTarifa  = [];

            foreach ($nc->detalles as $d) {
                $cant   = max(0, (float)$d->cantidad);
                $pu     = max(0, (float)$d->precio_unitario);   // precio neto (sin IVA)
                $desc   = min(100, max(0, (float)$d->descuento_pct));
                $ivaPct = min(100, max(0, (float)$d->impuesto_pct));

                $base = round($cant * $pu * (1 - $desc/100), 2);
                $iva  = round($base * $ivaPct/100, 2);

                // ==== INVENTARIO (HABER): sale del inventario lo devuelto
                if ($d->producto_id && $base > 0) {
                    $p = $d->relationLoaded('producto') ? $d->producto : Producto::with('cuentas')->find($d->producto_id);
                    if (!$p) throw new RuntimeException("Producto {$d->producto_id} no existe.");

                    // Cuenta de INVENTARIO del artículo (según tu configuración SUBCATEGORIA/ARTICULO)
                    $ctaInv = ContabilidadService::cuentaSegunConfiguracion($p, 'INVENTARIO');
                    if (!$ctaInv) throw new RuntimeException("Producto {$p->id} sin cuenta de INVENTARIO configurada.");

                    $inventarioPorCuenta[$ctaInv] = ($inventarioPorCuenta[$ctaInv] ?? 0) + $base;
                }

                // ==== IVA descontable (HABER): reversa del IVA compra
                if ($iva > 0 && $d->producto_id) {
                    $p = isset($p) && $p?->id === $d->producto_id ? $p : Producto::with('impuesto')->find($d->producto_id);

                    // Resolver cuenta de IVA compra:
                    // 1) por config (tipo 'IVA'), 2) por cuenta del propio impuesto
                    $ctaIva = ContabilidadService::cuentaSegunConfiguracion($p, 'IVA');
                    if (!$ctaIva) {
                        $imp = $p?->impuesto;
                        if ($imp && !empty($imp->cuenta_id) && PlanCuentas::whereKey($imp->cuenta_id)->exists()) {
                            $ctaIva = (int)$imp->cuenta_id;
                        }
                    }
                    if (!$ctaIva) {
                        throw new RuntimeException("No se pudo resolver la cuenta de IVA compra para el producto {$d->producto_id}.");
                    }

                    $ivaPorCuentaTarifa[$ctaIva][$ivaPct]['base'] = ($ivaPorCuentaTarifa[$ctaIva][$ivaPct]['base'] ?? 0) + $base;
                    $ivaPorCuentaTarifa[$ctaIva][$ivaPct]['iva']  = ($ivaPorCuentaTarifa[$ctaIva][$ivaPct]['iva']  ?? 0) + $iva;
                }
            }

            // ===============================
            // 3) HABER: inventario
            // ===============================
            foreach ($inventarioPorCuenta as $ctaInv => $monto) {
                $mov = ContabilidadService::post(
                    $asiento,
                    (int)$ctaInv,
                    0.0,
                    round($monto, 2),
                    'Reversa inventario por NC compra',
                    $metaBase
                );
                $totalDebe  += $mov->debito;
                $totalHaber += $mov->credito;
            }

            // ===============================
            // 4) HABER: IVA compra (descontable)
            // ===============================
            foreach ($ivaPorCuentaTarifa as $ctaIva => $porTarifa) {
                foreach ($porTarifa as $pct => $vals) {
                    $iva = round($vals['iva'] ?? 0, 2);
                    if ($iva <= 0) continue;

                    $mov = ContabilidadService::post(
                        $asiento,
                        (int)$ctaIva,
                        0.0,
                        $iva,
                        'Reversa IVA compra (NC)',
                        $metaBase + [
                            'base_gravable'  => round($vals['base'] ?? 0, 2),
                            'tarifa_pct'     => (float)$pct,
                            'valor_impuesto' => $iva,
                        ]
                    );
                    $totalDebe  += $mov->debito;
                    $totalHaber += $mov->credito;
                }
            }

            // ===================================================
            // 5) DEBE: Proveedores (CxP) por el TOTAL de la NC
            //     (si ya estaba pagada y tu flujo es con devoluciones,
            //      podrías usar una cuenta de deudores/anticipos del proveedor)
            // ===================================================
            $totalNc = round((float)$nc->total, 2);
            if ($totalNc <= 0) {
                throw new RuntimeException('El total de la Nota Crédito de compra es cero.');
            }

            // Resolver cuenta CxP Proveedores:
            $ctaCxp = null;

            // a) si el usuario selecciona una cuenta explícita en la NC (opcional)
            if (!empty($nc->cuenta_cobro_id) && PlanCuentas::whereKey($nc->cuenta_cobro_id)->exists()) {
                $ctaCxp = (int)$nc->cuenta_cobro_id;
            }

            // b) CxP del proveedor (si la tienes en sus cuentas)
            if (!$ctaCxp && $nc->relationLoaded('proveedor')) {
                foreach (['cuenta_cxp_id', 'plan_cuenta_id'] as $f) {
                    if (!empty($nc->proveedor->{$f}) && PlanCuentas::whereKey($nc->proveedor->{$f})->exists()) {
                        $ctaCxp = (int)$nc->proveedor->{$f};
                        break;
                    }
                }
                if (!$ctaCxp && method_exists($nc->proveedor, 'cuentas')) {
                    $id = $nc->proveedor->cuentas()->value('cuenta_cxp_id');
                    if ($id && PlanCuentas::whereKey($id)->exists()) $ctaCxp = (int)$id;
                }
            }

            // c) Fallback por clase de cuenta
            if (!$ctaCxp) {
                $ctaCxp = PlanCuentas::where('clase_cuenta', 'CXP_PROVEEDORES')
                    ->where('cuenta_activa', 1)
                    ->where(function ($q) { $q->where('titulo', 0)->orWhereNull('titulo'); })
                    ->value('id');
            }

            if (!$ctaCxp) throw new RuntimeException('No se encontró una cuenta de Proveedores (CxP) para la NC de compra.');

            $movDebe = ContabilidadService::post(
                $asiento,
                (int)$ctaCxp,
                $totalNc,
                0.0,
                'Compensación a Proveedores por NC compra',
                $metaBase
            );
            $totalDebe  += $movDebe->debito;
            $totalHaber += $movDebe->credito;

            // ===============================
            // 6) Cierre de totales del asiento
            // ===============================
            $asiento->update([
                'total_debe'  => round($totalDebe, 2),
                'total_haber' => round($totalHaber, 2),
            ]);

            // Check cuadratura
            if (round($totalDebe - $totalHaber, 2) !== 0.0) {
                Log::error('Asiento NC compra descuadrado', [
                    'nc_id' => $nc->id, 'debe' => $totalDebe, 'haber' => $totalHaber
                ]);
                throw new RuntimeException('El asiento de la NC de compra no cuadra.');
            }

            return $asiento;
        });
    }
}
