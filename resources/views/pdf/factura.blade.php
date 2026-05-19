{{-- resources/views/pdf/factura.blade.php --}}
@php
    // ============ Normalización ============
    if (isset($empresa) && is_array($empresa)) {
        $empresa = (object) $empresa;
    }

    if (!isset($empresa) || $empresa === null) {
        $empresa = (object) [];
    }

    /** -----------------------------------------------------------
     *  Solo trabajar con el pdf_theme de la empresa
     * ---------------------------------------------------------- */
    $theme =
        is_object($empresa) && method_exists($empresa, 'pdfTheme')
            ? $empresa->pdfTheme()
            : (is_array($theme ?? null)
                ? $theme
                : []);

    // Colores base
    $primary   = $theme['primary']   ?? '#223361';
    $base      = $theme['base']      ?? '#ffffff';
    $ink       = $theme['ink']       ?? '#1f2937';
    $muted     = $theme['muted']     ?? '#6b7280';
    $border    = $theme['border']    ?? '#e5e7eb';
    $theadBg   = $theme['theadBg']   ?? '#eef2f8';
    $theadText = $theme['theadText'] ?? $primary;
    $stripe    = $theme['stripe']    ?? '#f7f9fc';
    $grandBg   = $theme['grandBg']   ?? $primary;
    $grandTx   = $theme['grandTx']   ?? '#ffffff';
    $wmColor   = $theme['wmColor']   ?? 'rgba(34, 51, 97, .06)';

    // ✅ Logo: para PDF lo ideal es PATH absoluto.
    $logoSrc = null;

    if (!empty($empresa->logo_path)) {
        $logoSrc = $empresa->logo_path;
    } elseif (!empty($empresa->logo_url)) {
        $logoSrc = $empresa->logo_url;
    } elseif (!empty($empresa->logo) && is_string($empresa->logo)) {
        $logoSrc = $empresa->logo;
    } elseif (!empty($empresa->logo_src)) {
        $logoSrc = $empresa->logo_src;
    }

    // ✅ Convertir a PATH absoluto si no es URL/data
    $logoPdfSrc = null;
    if ($logoSrc) {
        if (
            str_starts_with($logoSrc, 'http://') ||
            str_starts_with($logoSrc, 'https://') ||
            str_starts_with($logoSrc, 'data:image/')
        ) {
            $logoPdfSrc = $logoSrc;
        } else {
            $logoPdfSrc = public_path($logoSrc);
        }
    }

    $E = [
        'nombre'    => $empresa->nombre ?? 'Empresa',
        'nit'       => !empty($empresa->nit) ? 'NIT ' . $empresa->nit : null,
        'direccion' => $empresa->direccion ?? null,
        'telefono'  => $empresa->telefono ?? '3004385756',
        'whatsapp'  => $empresa->whatsapp ?? '3104530264',
        'email'     => $empresa->email ?? null,
        'website'   => $empresa->sitio_web ?? null,
        'logo_src'  => $logoPdfSrc,
    ];

    $money = fn($v) => '$' . number_format((float) $v, 2, '.', ',');
    $fmtPct = fn($v) => rtrim(rtrim(number_format((float) $v, 3, '.', ''), '0'), '.') . '%';

    $len = $factura->serie->longitud ?? 6;
    $num = $factura->numero !== null ? str_pad((string) $factura->numero, $len, '0', STR_PAD_LEFT) : '—';
    $pref = $factura->prefijo ? "{$factura->prefijo}-" : '';
    $folio = "{$pref}{$num}";

    // =========================================================
    // ✅ Detectar si es FACTURA DE COMPRA (si no, es REMISIÓN)
    // =========================================================
    $docCodigo = strtoupper((string) ($documento ?? ($factura->serie->tipo->codigo ?? '')));

    $esCompra = false;
    if (isset($modo) && strtolower((string) $modo) === 'compra') {
        $esCompra = true;
    } elseif (!empty($factura->modo) && strtolower((string) $factura->modo) === 'compra') {
        $esCompra = true;
    } elseif (!empty($factura->es_compra)) {
        $esCompra = true;
    } elseif (str_contains($docCodigo, 'COMPRA')) {
        $esCompra = true;
    }

    $docTitulo = $esCompra ? 'FACTURA DE COMPRA' : 'REMISIÓN';
    $wmTexto   = $esCompra ? 'FACTURA' : 'REMISIÓN';

    // =========================================================
    // ✅ Tercero: Proveedor en compra / Cliente en venta
    // =========================================================
    $tercero = $esCompra
        ? $factura->proveedor ?? ($factura->socioNegocio ?? ($factura->cliente ?? null))
        : $factura->cliente ?? ($factura->socioNegocio ?? null);

    $labelTercero = $esCompra ? 'Proveedor' : 'Cliente';

    $estado = $factura->estado ?? 'borrador';

    $colors = [
        'borrador'             => ['#e5e7eb', '#374151'],
        'emitida'              => ['#e9edf6', $primary],
        'parcialmente_pagada'  => ['#fff7ed', '#9a3412'],
        'pagada'               => ['#dcfce7', '#166534'],
        'anulada'              => ['#ffe4e6', '#9f1239'],
    ][$estado] ?? ['#e5e7eb', '#374151'];

    $estadoLabel = ucwords(str_replace('_', ' ', $estado));

    $fechaDocumento = \Illuminate\Support\Carbon::parse($factura->fecha ?? $factura->created_at)->format('d/m/Y');
    $fechaVencimiento = !empty($factura->vencimiento)
        ? \Illuminate\Support\Carbon::parse($factura->vencimiento)->format('d/m/Y')
        : null;
@endphp

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>{{ $docTitulo }} {{ $folio }}</title>

    <style>
        @page {
            margin: 110px 34px 85px 34px;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            color: {{ $ink }};
            background: {{ $base }};
        }

        header {
            position: fixed;
            top: -90px;
            left: 0;
            right: 0;
            height: 98px;
        }

        footer {
            position: fixed;
            bottom: -62px;
            left: 0;
            right: 0;
            height: 62px;
        }

        .brand-band {
            height: 5px;
            background: {{ $primary }};
            border-radius: 0 0 6px 6px;
        }

        .brand {
            display: table;
            width: 100%;
            margin-top: 8px;
        }

        .brand .col {
            display: table-cell;
            vertical-align: top;
        }

        .brand .right {
            width: 290px;
            text-align: right;
            vertical-align: top;
            padding-top: 2px;
        }

        .brand-name {
            font-size: 14px;
            font-weight: 800;
            color: {{ $ink }};
        }

        .doc-title {
            margin: 0;
            font-size: 22px;
            line-height: 1.1;
            letter-spacing: .4px;
            font-weight: 800;
            color: {{ $primary }};
        }

        .badge {
            display: inline-block;
            padding: 3px 9px;
            border-radius: 999px;
            font-size: 10px;
            font-weight: 700;
            vertical-align: middle;
        }

        .watermark {
            position: fixed;
            top: 43%;
            left: 12%;
            z-index: 0;
            font-size: 80px;
            font-weight: 800;
            color: {{ $wmColor }};
            transform: rotate(-20deg);
        }

        .pane {
            border: 1px solid {{ $border }};
            border-radius: 8px;
            padding: 8px 10px;
        }

        .pane h4 {
            margin: 0 0 5px;
            font-size: 11px;
            color: {{ $muted }};
            text-transform: uppercase;
            letter-spacing: .4px;
        }

        .header-meta {
            margin-top: 5px;
        }

        .header-meta-row {
            margin-top: 2px;
            font-size: 10px;
            color: {{ $muted }};
        }

        .header-contact {
            margin-top: 6px;
            margin-left: auto;
            display: inline-table;
            border-collapse: collapse;
        }

        .header-contact .contact-row {
            display: table-row;
        }

        .header-contact .contact-label,
        .header-contact .contact-value {
            display: table-cell;
            font-size: 10px;
            color: {{ $muted }};
            padding: 1px 0;
            vertical-align: top;
        }

        .header-contact .contact-label {
            padding-right: 8px;
            text-align: right;
            white-space: nowrap;
            font-weight: 700;
        }

        .header-contact .contact-value {
            text-align: left;
            white-space: nowrap;
        }

        .header-status {
            margin-top: 7px;
        }

        table.info-grid {
            width: 100%;
            border-spacing: 10px 0;
        }

        table.info-grid td {
            vertical-align: top;
        }

        table.condiciones-table {
            width: 100%;
            table-layout: fixed;
        }

        table.condiciones-table td {
            padding: 2px 0;
            vertical-align: top;
        }

        .cond-label {
            width: 34%;
            color: {{ $muted }};
            font-size: 9.5px;
            padding-right: 8px;
        }

        .cond-value {
            width: 66%;
            font-size: 9.5px;
            text-align: right;
            word-wrap: break-word;
            word-break: break-word;
            white-space: normal;
            line-height: 1.25;
        }

        table.items {
            width: 100%;
            margin-top: 10px;
            border-collapse: collapse;
        }

        table.items thead th {
            padding: 6px;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .2px;
            background: {{ $theadBg }};
            color: {{ $theadText }};
            border-bottom: 1px solid {{ $border }};
        }

        table.items tbody td {
            padding: 5px 6px;
            border-bottom: 1px solid #f1f5f9;
            font-size: 10px;
        }

        table.items tbody tr:nth-child(even) {
            background: {{ $stripe }};
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .w-50 {
            width: 50%;
        }

        .small {
            font-size: 9.5px;
        }

        .muted {
            color: {{ $muted }};
        }

        .totals {
            width: 100%;
            margin-top: 8px;
        }

        .totals td {
            padding: 4px 6px;
            font-size: 10px;
        }

        .totals .label {
            color: {{ $muted }};
        }

        .totals .grand {
            font-weight: 700;
            background: {{ $grandBg }};
            color: {{ $grandTx }};
            border-radius: 6px;
        }

        .payment-box {
            margin-top: 10px;
            border: 1px solid #dbe3f1;
            background: #f8fbff;
            border-radius: 8px;
            padding: 8px 10px;
        }

        .payment-title {
            font-size: 10px;
            font-weight: 800;
            text-transform: uppercase;
            color: #223361;
            margin-bottom: 4px;
            letter-spacing: .4px;
        }

        .payment-row {
            font-size: 10px;
            color: #374151;
            line-height: 1.35;
        }

        .payment-bank {
            font-weight: 700;
            color: #223361;
        }

        .payment-account {
            display: inline-block;
            margin-left: 6px;
            padding: 3px 10px;
            border-radius: 20px;
            background: #e8f0ff;
            border: 1px solid #c9d8ff;
            font-weight: 800;
            letter-spacing: 1px;
            font-size: 11px;
            color: #1e3a8a;
        }

        .payment-owner {
            font-size: 9.5px;
            color: #6b7280;
            margin-top: 4px;
        }

        .drawing-space {
            margin-top: 10px;
            height: 110px;
            border: 1px dashed {{ $border }};
            border-radius: 8px;
            background: #ffffff;
        }

        .terms {
            margin-top: 10px;
        }

        .page-number:after {
            content: counter(page) " / " counter(pages);
        }
    </style>
</head>
<body>

    <header>
        <div class="brand-band"></div>

        <div class="brand">
            <div class="col" style="width:320px;">
                <table style="width:100%;">
                    <tr>
                        <td style="text-align:left; vertical-align:middle; padding:0;">
                            @if (!empty($E['logo_src']))
                                <div style="max-width:300px; max-height:58px;">
                                    <img
                                        src="{{ $E['logo_src'] }}"
                                        alt="Logo {{ $E['nombre'] }}"
                                        style="max-width:300px; max-height:58px; width:auto; height:auto; object-fit:contain; object-position:left center;"
                                    >
                                </div>
                            @else
                                <div class="brand-name">{{ $E['nombre'] }}</div>
                                @if (!empty($E['nit']))
                                    <div class="small muted" style="margin-top:2px;">{{ $E['nit'] }}</div>
                                @endif
                            @endif
                        </td>
                    </tr>
                </table>
            </div>

            <div class="col right">
                <div class="doc-title">{{ $docTitulo }}</div>

                <div class="header-meta">
                    <div class="header-meta-row">
                        <span class="small muted">Número:</span>
                        <strong style="font-size:13px; color: {{ $ink }};">{{ $folio }}</strong>
                    </div>

                    <div class="header-meta-row">
                        Fecha: {{ $fechaDocumento }}
                        @if ($fechaVencimiento)
                            · Vence: {{ $fechaVencimiento }}
                        @endif
                    </div>

                    <div class="header-contact">
                        <div class="contact-row">
                            <div class="contact-label">WhatsApp:</div>
                            <div class="contact-value">{{ $E['whatsapp'] }}</div>
                        </div>
                        <div class="contact-row">
                            <div class="contact-label">Celular:</div>
                            <div class="contact-value">{{ $E['telefono'] }}</div>
                        </div>
                    </div>

                    <div class="header-status">
                        <span class="badge" style="background: {{ $colors[0] }}; color: {{ $colors[1] }};">
                            {{ $estadoLabel }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <footer>
        <table style="width:100%;">
            <tr>
                <td class="small muted">
                    {{ $E['nombre'] }}
                    @if (!empty($E['website']))
                        · {{ $E['website'] }}
                    @endif
                </td>
                <td class="small muted text-right">
                    Página <span class="page-number"></span>
                </td>
            </tr>
        </table>
    </footer>

    @if (($factura->estado ?? '') === 'anulada')
        <div class="watermark">ANULADA</div>
    @else
        <div class="watermark">{{ $wmTexto }}</div>
    @endif

    <main style="position: relative; z-index:1;">

        <table class="info-grid">
            <tr>
                <td class="w-50">
                    <div class="pane">
                        <h4>{{ $labelTercero }}</h4>

                        <div style="font-size:12px; font-weight:700;">
                            {{ $tercero->razon_social ?? $labelTercero }}
                        </div>

                        <div class="small muted" style="line-height:1.35;">
                            NIT: {{ $tercero->nit ?? '—' }}<br>
                            Email: {{ $tercero->correo ?? ($tercero->email ?? '—') }}<br>
                            Tel: {{ $tercero->telefono ?? '—' }}
                        </div>
                    </div>
                </td>

                <td class="w-50">
                    <div class="pane">
                        <h4>Condiciones</h4>

                        <table class="condiciones-table">
                            <tr>
                                <td class="cond-label">Moneda</td>
                                <td class="cond-value">{{ $factura->moneda ?? 'COP' }}</td>
                            </tr>

                            <tr>
                                <td class="cond-label">{{ $esCompra ? 'Tipo' : 'Pago' }}</td>
                                <td class="cond-value">{{ ucfirst($factura->tipo_pago ?? 'contado') }}</td>
                            </tr>

                            @if (($factura->tipo_pago ?? '') === 'credito')
                                <tr>
                                    <td class="cond-label">Plazo</td>
                                    <td class="cond-value">{{ $factura->plazo_dias ?? 0 }} días</td>
                                </tr>
                            @endif

                            @if (!empty($factura->terminos_pago))
                                <tr>
                                    <td class="cond-label">Términos</td>
                                    <td class="cond-value">{{ $factura->terminos_pago }}</td>
                                </tr>
                            @endif

                            <tr>
                                <td class="cond-label">Validez</td>
                                <td class="cond-value">Documento vigente según emisión</td>
                            </tr>
                        </table>
                    </div>
                </td>
            </tr>
        </table>

        <table class="items">
            <thead>
                <tr>
                    <th style="width:34%;">Producto</th>
                    <th style="width:12%;" class="text-center">Bodega</th>
                    <th style="width:8%;" class="text-right">Cant.</th>
                    <th style="width:12%;" class="text-right">Precio</th>
                    <th style="width:8%;" class="text-right">Desc</th>
                    <th style="width:8%;" class="text-right">IVA</th>
                    <th style="width:18%;" class="text-right">Total línea</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($factura->detalles ?? [] as $d)
                    @php
                        $nombre  = $d->producto->nombre ?? ($d->descripcion ?? '#' . $d->producto_id);
                        $bodega  = $d->bodega->nombre ?? '—';
                        $cant    = (float) $d->cantidad;
                        $precio  = (float) $d->precio_unitario;
                        $descPct = (float) ($d->descuento_pct ?? 0);
                        $ivaPct  = (float) ($d->impuesto_pct ?? 0);

                        $baseLn  = $cant * $precio * (1 - $descPct / 100);
                        $ivaLn   = ($baseLn * $ivaPct) / 100;
                        $totalLn = $baseLn + $ivaLn;
                    @endphp

                    <tr>
                        <td>{{ $nombre }}</td>
                        <td class="text-center">{{ $bodega }}</td>
                        <td class="text-right">{{ rtrim(rtrim(number_format($cant, 3, '.', ''), '0'), '.') }}</td>
                        <td class="text-right">{{ $money($precio) }}</td>
                        <td class="text-right">{{ $fmtPct($descPct) }}</td>
                        <td class="text-right">{{ $fmtPct($ivaPct) }}</td>
                        <td class="text-right">{{ $money($totalLn) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <table class="totals">
            <tr>
                <td class="w-50"></td>
                <td class="label text-right">Subtotal</td>
                <td class="text-right">{{ $money($factura->subtotal ?? 0) }}</td>
            </tr>
            <tr>
                <td></td>
                <td class="label text-right">Impuestos</td>
                <td class="text-right">{{ $money($factura->impuestos ?? 0) }}</td>
            </tr>
            <tr>
                <td></td>
                <td class="text-right grand">Total</td>
                <td class="text-right grand">{{ $money($factura->total ?? 0) }}</td>
            </tr>
        </table>

        @php
            $infoPago  = (array)(($empresa->extra ?? [])['info_pago'] ?? []);
            $ipBanco   = trim(($infoPago['banco'] ?? '') . ($infoPago['tipo_cuenta'] ? ' · ' . $infoPago['tipo_cuenta'] : ''));
            $ipNumero  = $infoPago['numero'] ?? '';
            $ipTitular = $infoPago['titular'] ?? '';
        @endphp
        @if($ipBanco || $ipNumero || $ipTitular)
        <div class="payment-box">
            <div class="payment-title">Información de pago</div>
            @if($ipBanco || $ipNumero)
            <div class="payment-row">
                @if($ipBanco)<span class="payment-bank">{{ $ipBanco }}</span>@endif
                @if($ipNumero)<span class="payment-account">{{ $ipNumero }}</span>@endif
            </div>
            @endif
            @if($ipTitular)
            <div class="payment-owner">Titular: {{ $ipTitular }}</div>
            @endif
        </div>
        @endif
            @if()
            <div class="payment-owner">Titular: {{  }}</div>
            @endif
        </div>
        @endif

        <div class="drawing-space"></div>

        <div class="terms pane">
            <h4>Notas y condiciones</h4>

            @if (!empty($factura->notas))
                <div style="white-space: pre-line;">{{ $factura->notas }}</div>
            @else
                <div class="muted small" style="line-height:1.45;">
                    • Precios en moneda local.<br>
                    • Documento sujeto a validación según emisión.<br>
                    • Entrega sujeta a disponibilidad.<br>
                    • Garantía según fabricante.
                </div>
            @endif
        </div>

        <table style="width:100%; margin-top:14px;">
            <tr>
                <td class="w-50">
                    <div class="small muted">Aprobado por (cliente):</div>
                    <div style="margin-top:42px; border-top:1px solid {{ $border }}; width:80%;"></div>
                </td>
                <td class="w-50">
                    <div class="small muted">Firma y sello:</div>
                    <div style="margin-top:42px; border-top:1px solid {{ $border }}; width:80%;"></div>
                </td>
            </tr>
        </table>

    </main>

</body>
</html>

@push('scripts')
    @if (!empty($autoPrint))
        <script>
            window.addEventListener('load', function () {
                window.focus();
                window.print();
            });
        </script>
    @endif
@endpush