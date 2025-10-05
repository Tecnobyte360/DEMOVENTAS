<?php

namespace App\Http\Controllers;

use App\Models\Factura\factura;
use App\Models\ConfiguracionEmpresas\Empresa;
use App\Services\PosPrinterService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Throwable;

class FacturaPosPrintController extends Controller
{
    public function print(factura $factura, PosPrinterService $pos): JsonResponse
    {
        try {
            Log::info('Iniciando impresión POS', [
                'factura_id' => $factura->id,
                'driver'     => $pos->driverUsed,
                'target'     => $pos->target,
                'codepage'   => $pos->codePageSelected,
            ]);

            // Carga relaciones necesarias
            $factura->load(['cliente', 'serie', 'detalles.producto']);

            // ===== Empresa desde BD =====
            $e = Empresa::query()->first(); // ajusta si manejas multi-empresa
            $logoSrc = $e->logo_path ?: null; // puede ser data:image/...;base64,... o ruta accesible en public/

            $empresa = [
                'nombre'     => $e->nombre           ?? '—',
                'slogan'     => $e->slogan           ?? null,
                'nit'        => $e?->nit             ? ('NIT '.$e->nit) : null,
                'direccion'  => $e->direccion        ?? null,
                'telefono'   => $e->telefono         ?? null,
                'regimen'    => $e->regimen          ?? 'Regimen: Responsable de IVA',
                'pos'        => 'Caja Principal',
                'cajero'     => Auth::user()?->name  ?? '—',
                'website'    => $e->sitio_web        ?: $e->email,
                'resolucion' => $e->resolucion       ?? null,
                'logo_src'   => $logoSrc,
                'accent'     => $e->color_primario   ?: '#0ea5e9',
            ];

            // Diagnóstico de logo
            Log::info('[POS] Estado del logo', [
                'has_logo' => !empty($empresa['logo_src']),
                'prefix'   => substr((string)($empresa['logo_src'] ?? ''), 0, 30),
                'length'   => strlen((string)($empresa['logo_src'] ?? '')),
            ]);

            // ===== Folio =====
            $len   = $factura->serie->longitud ?? 6;
            $num   = $factura->numero !== null
                ? str_pad((string)$factura->numero, $len, '0', STR_PAD_LEFT)
                : '—';
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
                    collect($factura->detalles ?? [])->map(fn($d) => (float)($d->cantidad ?? 0))->all()
                ),
            ];

            // ===== Ítems + resumen de impuestos =====
            $items   = [];
            $resumen = [];

            foreach (($factura->detalles ?? []) as $d) {
                $cant    = (float)($d->cantidad ?? 0);
                $precio  = (float)($d->precio_unitario ?? 0);
                $descPct = (float)($d->descuento_pct ?? 0);
                $ivaPct  = (float)($d->impuesto_pct  ?? 0);

                $base    = $cant * $precio * (1 - $descPct / 100);
                $iva     = $base * $ivaPct / 100;
                $totalLn = $base + $iva;

                // nombre + descripción (para mostrar: CÓDIGO - NOMBRE - DESCRIPCIÓN)
                $nombre       = $d->producto->nombre ?? ($d->descripcion ?: ('#'.$d->producto_id));
                $descripcion  = trim($d->producto->descripcion ?? $d->descripcion ?? '');

                $items[] = [
                    'codigo'      => $d->producto->codigo ?? $d->producto->item_code ?? null,
                    'nombre'      => $nombre,
                    'descripcion' => $descripcion, // se imprimirá junto al código y nombre
                    'cant'        => $cant,
                    'precio'      => $precio,
                    'desc'        => $descPct,
                    'iva'         => $ivaPct,
                    'total'       => $totalLn,
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

            Log::info('Datos preparados, enviando a imprimir', [
                'items_count' => count($items),
                'total'       => $doc['total'],
            ]);

            // ===== Imprimir =====
            $pos->printInvoice($empresa, $doc, $items, $resumenImpuestos);

            Log::info('Impresión enviada exitosamente');
            return response()->json(['ok' => true]);

        } catch (Throwable $e) {
            Log::error('POS print failed', [
                'factura_id' => $factura->id ?? null,
                'msg'        => $e->getMessage(),
                'file'       => $e->getFile(),
                'line'       => $e->getLine(),
                'trace'      => $e->getTraceAsString(),
            ]);

            return response()->json([
                'ok'     => false,
                'error'  => $e->getMessage(),
                'driver' => $pos->driverUsed ?? null,
                'target' => $pos->target ?? null,
            ], 500);
        }
    }
}
