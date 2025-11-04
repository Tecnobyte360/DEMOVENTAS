<?php

namespace App\Http\Controllers;

use App\Models\Factura\Factura;
use App\Models\ConfiguracionEmpresas\Empresa;
use App\Services\PosPrinterService;
use Barryvdh\DomPDF\Facade\Pdf as PdfFacade;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Throwable;

class FacturaPosPrintController extends Controller
{
    /** ===========================
     *  Impresión POS (ticket)
     *  =========================== */
    public function print(Factura $factura, PosPrinterService $pos): JsonResponse
    {
        try {
            $factura->load(['cliente', 'serie', 'detalles.producto', 'empresa']);

            Log::info('Iniciando impresión POS', [
                'factura_id' => $factura->id,
                'driver'     => $pos->driverUsed ?? null,
                'target'     => $pos->target ?? null,
                'codepage'   => $pos->codePageSelected ?? null,
            ]);

            /** @var \App\Models\ConfiguracionEmpresas\Empresa|null $empresaModel */
            $empresaModel = $factura->empresa ?: Empresa::query()->first();

            // ⚠️ Array SOLO para POS. No usar este array en el Blade del PDF.
            $empresaPos = [
                'nombre'     => $empresaModel->nombre           ?? '—',
                'slogan'     => $empresaModel->slogan           ?? null,
                'nit'        => $empresaModel?->nit             ? ('NIT '.$empresaModel->nit) : null,
                'direccion'  => $empresaModel->direccion        ?? null,
                'telefono'   => $empresaModel->telefono         ?? null,
                'regimen'    => $empresaModel->regimen          ?? 'Régimen: Responsable de IVA',
                'pos'        => 'Caja Principal',
                'cajero'     => Auth::user()?->name             ?? '—',
                'website'    => $empresaModel->sitio_web        ?: $empresaModel->email,
                'resolucion' => $empresaModel->resolucion       ?? null,
                'logo_src'   => $empresaModel->logo_path        ?: null,
                'accent'     => $empresaModel->color_primario   ?: '#0ea5e9',
            ];

            $len   = $factura->serie->longitud ?? 6;
            $num   = $factura->numero !== null ? str_pad((string)$factura->numero, $len, '0', STR_PAD_LEFT) : '—';
            $pref  = $factura->prefijo ? "{$factura->prefijo}-" : '';
            $folio = "{$pref}{$num}";

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

            $items = [];
            $resumen = [];

            foreach (($factura->detalles ?? []) as $d) {
                $cant    = (float)($d->cantidad ?? 0);
                $precio  = (float)($d->precio_unitario ?? 0);
                $descPct = (float)($d->descuento_pct ?? 0);
                $ivaPct  = (float)($d->impuesto_pct  ?? 0);

                $base    = $cant * $precio * (1 - $descPct / 100);
                $iva     = $base * $ivaPct / 100;
                $totalLn = $base + $iva;

                $nombre      = $d->producto->nombre ?? ($d->descripcion ?: ('#'.$d->producto_id));
                $descripcion = trim($d->producto->descripcion ?? $d->descripcion ?? '');

                $items[] = [
                    'codigo'      => $d->producto->codigo ?? $d->producto->item_code ?? null,
                    'nombre'      => $nombre,
                    'descripcion' => $descripcion,
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

            $pos->printInvoice($empresaPos, $doc, $items, $resumenImpuestos);

            return response()->json(['ok' => true]);
        } catch (Throwable $e) {
            Log::error('POS print failed', [
                'factura_id' => $factura->id ?? null,
                'msg'        => $e->getMessage(),
                'file'       => $e->getFile(),
                'line'       => $e->getLine(),
            ]);

            return response()->json([
                'ok'     => false,
                'error'  => $e->getMessage(),
            ], 500);
        }
    }

    /** ===========================
     *  Vista previa PDF
     *  =========================== */
    public function preview(Factura $factura)
    {
        $factura->load(['cliente', 'serie', 'detalles.producto', 'detalles.bodega', 'empresa']);

        /** @var \App\Models\ConfiguracionEmpresas\Empresa|null $empresaModel */
        $empresaModel = $factura->empresa ?: Empresa::query()->first();

        $theme = method_exists($empresaModel, 'pdfTheme') ? $empresaModel->pdfTheme() : null;

        $len   = $factura->serie->longitud ?? 6;
        $num   = $factura->numero !== null ? str_pad((string)$factura->numero, $len, '0', STR_PAD_LEFT) : '—';
        $pref  = $factura->prefijo ? "{$factura->prefijo}-" : '';
        $folio = "{$pref}{$num}";

        return PdfFacade::loadView('pdf.factura', [
            'factura' => $factura,
            'empresa' => $empresaModel, 
            'theme'   => $theme,
            'folio'   => $folio,
        ])->stream('factura-'.$folio.'.pdf');
    }
}
