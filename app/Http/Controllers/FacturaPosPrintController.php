<?php

namespace App\Http\Controllers;

use App\Models\Factura\factura;
use App\Services\PosPrinterService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Throwable;

class FacturaPosPrintController extends Controller
{
    public function print(factura $factura, PosPrinterService $pos): JsonResponse
    {
        try {
            $factura->load(['cliente','serie','detalles.producto']);

            // ===== Empresa (ajusta a tu tabla si la tienes) =====
            $empresa = [
                'nombre'     => 'LA CASA DEL CANDELABRO',
                'slogan'     => 'COMERCIALIZACION DE MATERIALES Y PVC SAS',
                'nit'        => 'NIT 900.000.000-1, Medellin - Antioquia',
                'direccion'  => 'CARRERA 45 #00-123, Medellin',
                'telefono'   => '3216499744',
                'regimen'    => 'Régimen: Responsable de IVA',
                'pos'        => 'Caja Principal',
                'cajero'     => 'Juan Pérez',
                'website'    => 'Tecnobyte360.com/pos',
                'resolucion' => 'Resolución DIAN 12345 de 2025',
            ];

            // ===== Folio =====
            $len   = $factura->serie->longitud ?? 6;
            $num   = $factura->numero !== null ? str_pad((string)$factura->numero, $len, '0', STR_PAD_LEFT) : '—';
            $pref  = $factura->prefijo ? "{$factura->prefijo}-" : '';
            $folio = "{$pref}{$num}";

            // ===== Doc =====
            $fechaEmi = Carbon::parse($factura->fecha ?? $factura->created_at);
            $doc = [
                'folio'            => $folio,
                'tipo_pago'        => $factura->tipo_pago ?? 'contado',
                'fecha'            => $fechaEmi->format('d/m/Y g:i a'),
                'vence'            => ($factura->tipo_pago === 'credito' && $factura->vencimiento)
                                        ? Carbon::parse($factura->vencimiento)->format('d/m/Y')
                                        : $fechaEmi->format('d/m/Y'),
                'cliente_nombre'   => $factura->cliente->razon_social ?? '—',
                'cliente_nit'      => $factura->cliente->nit ?? '—',
                'cliente_dir'      => $factura->cliente->direccion ?? null,
                'cliente_tel'      => $factura->cliente->telefono ?? null,
                'subtotal'         => (float) ($factura->subtotal ?? 0),
                'impuestos'        => (float) ($factura->impuestos ?? 0),
                'total'            => (float) ($factura->total ?? 0),
                'pagado'           => (float) ($factura->pagado ?? 0),
                'saldo'            => (float) ($factura->saldo  ?? 0),
                'conteo_lineas'    => count($factura->detalles ?? []),
                'conteo_productos' => array_sum(
                                        collect($factura->detalles ?? [])
                                          ->map(fn($d) => (float)($d->cantidad ?? 0))
                                          ->all()
                                      ),
            ];

            // ===== Ítems + resumen de impuestos =====
            $items   = [];
            $resumen = []; // pct => [base, imp]

            foreach (($factura->detalles ?? []) as $d) {
                $cant    = (float)($d->cantidad ?? 0);
                $precio  = (float)($d->precio_unitario ?? 0);
                $descPct = (float)($d->descuento_pct ?? 0);
                $ivaPct  = (float)($d->impuesto_pct  ?? 0);
                $base    = $cant * $precio * (1 - $descPct / 100);
                $iva     = $base * $ivaPct / 100;
                $totalLn = $base + $iva;

                $items[] = [
                    'codigo' => $d->producto->codigo ?? $d->producto->item_code ?? null,
                    'nombre' => $d->producto->nombre ?? ($d->descripcion ?: ('#'.$d->producto_id)),
                    'cant'   => $cant,
                    'precio' => $precio,
                    'desc'   => $descPct,
                    'iva'    => $ivaPct,
                    'total'  => $totalLn,
                ];

                $resumen[$ivaPct] = [
                    'base' => ($resumen[$ivaPct]['base'] ?? 0) + $base,
                    'imp'  => ($resumen[$ivaPct]['imp']  ?? 0) + $iva,
                ];
            }

            ksort($resumen);
            $resumenImpuestos = [];
            foreach ($resumen as $pct => $vals) {
                $resumenImpuestos[] = [
                    'tarifa' => $pct == 0 ? 'EXCL' : 'IVA',
                    'base'   => $vals['base'],
                    'imp'    => $vals['imp'],
                    'pct'    => $pct,
                ];
            }

            // ===== Imprimir =====
            $pos->printInvoice($empresa, $doc, $items, $resumenImpuestos);

            return response()->json(['ok' => true]); // 200
        } catch (Throwable $e) {
            // Log + devuelve el motivo REAL del fallo
            Log::error('POS print failed', [
                'msg' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response($e->getMessage(), 500);
        }
    }
}
