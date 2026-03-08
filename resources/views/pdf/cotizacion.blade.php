{{-- resources/views/pdf/cotizacion.blade.php --}}
@php
    // ============================================
    // Normalización de empresa
    // ============================================
    if (isset($empresa) && is_array($empresa)) {
        $empresa = (object) $empresa;
    }

    if (!isset($empresa) || $empresa === null) {
        $empresa = (object) [];
    }

    // ============================================
    // Theme PDF (igual lógica de factura)
    // ============================================
    $theme = is_object($empresa) && method_exists($empresa, 'pdfTheme')
        ? $empresa->pdfTheme()
        : (is_array($theme ?? null) ? $theme : []);

    // ============================================
    // Colores base
    // ============================================
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

    // ============================================
    // Resolución del logo
    // ============================================
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

    // ============================================
    // Datos empresa
    // ============================================
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

    // ============================================
    // Helpers
    // ============================================
    $money = fn($v) => '$' . number_format((float) $v, 2, '.', ',');
    $fmtPct = fn($v) => rtrim(rtrim(number_format((float) $v, 3, '.', ''), '0'), '.') . '%';

    // ============================================
    // Folio / referencia
    // ============================================
    $folio = $ref ?? 'S' . str_pad($cotizacion->id, 5, '0', STR_PAD_LEFT);

    // ============================================
    // Estado
    // ============================================
    $estado = $cotizacion->estado ?? 'borrador';

    $colors = [
        'borrador'   => ['#e5e7eb', '#374151'],
        'enviada'    => ['#e9edf6', $primary],
        'confirmada' => ['#dbeafe', '#1e40af'],
        'convertida' => ['#dcfce7', '#166534'],
        'cancelada'  => ['#ffe4e6', '#9f1239'],
    ][$estado] ?? ['#e5e7eb', '#374151'];

    $estadoLabel = $estado === 'convertida' ? 'Orden de venta' : ucfirst($estado);

    // ============================================
    // Fechas
    // ============================================
    $fechaDocumento = \Illuminate\Support\Carbon::parse($cotizacion->fecha ?? $cotizacion->created_at)->format('d/m/Y');
    $fechaVencimiento = !empty($cotizacion->vencimiento)
        ? \Illuminate\Support\Carbon::parse($cotizacion->vencimiento)->format('d/m/Y')
        : null;
@endphp

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Cotización {{ $folio }}</title>

    <style>
        @page {
            margin: 118px 36px 95px 36px;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            color: {{ $ink }};
            background: {{ $base }};
        }

        header {
            position: fixed;
            top: -95px;
            left: 0;
            right: 0;
            height: 105px;
        }

        footer {
            position: fixed;
            bottom: -70px;
            left: 0;
            right: 0;
            height: 70px;
        }

        .brand-band {
            height: 5px;
            background: {{ $primary }};
            border-radius: 0 0 6px 6px;
        }

        .brand {
            display: table;
            width: 100%;
            margin-top: 10px;
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
            font-size: 15px;
            font-weight: 800;
            color: {{ $ink }};
        }

        .doc-title {
            margin: 0;
            font-size: 24px;
            line-height: 1.1;
            letter-spacing: .5px;
            font-weight: 800;
            color: {{ $primary }};
        }

        .badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 700;
            vertical-align: middle;
        }

        .watermark {
            position: fixed;
            top: 43%;
            left: 12%;
            z-index: 0;
            font-size: 82px;
            font-weight: 800;
            color: {{ $wmColor }};
            transform: rotate(-20deg);
        }

        .pane {
            border: 1px solid {{ $border }};
            border-radius: 8px;
            padding: 10px 12px;
        }

        .pane h4 {
            margin: 0 0 6px;
            font-size: 12px;
            color: {{ $muted }};
            text-transform: uppercase;
            letter-spacing: .4px;
        }

        .header-meta {
            margin-top: 6px;
        }

        .header-meta-row {
            margin-top: 2px;
            font-size: 11px;
            color: {{ $muted }};
        }

        .header-contact {
            margin-top: 8px;
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
            font-size: 11px;
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
            margin-top: 8px;
        }

        table.info-grid {
            width: 100%;
            border-spacing: 12px 0;
        }

        table.info-grid td {
            vertical-align: top;
        }

        table.condiciones-table {
            width: 100%;
            table-layout: fixed;
        }

        table.condiciones-table td {
            padding: 3px 0;
            vertical-align: top;
        }

        .cond-label {
            width: 34%;
            color: {{ $muted }};
            font-size: 10px;
            padding-right: 10px;
        }

        .cond-value {
            width: 66%;
            font-size: 10px;
            text-align: right;
            word-wrap: break-word;
            word-break: break-word;
            white-space: normal;
            line-height: 1.35;
        }

        table.items {
            width: 100%;
            margin-top: 12px;
            border-collapse: collapse;
        }

        table.items thead th {
            padding: 8px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .3px;
            background: {{ $theadBg }};
            color: {{ $theadText }};
            border-bottom: 1px solid {{ $border }};
        }

        table.items tbody td {
            padding: 7px 8px;
            border-bottom: 1px solid #f1f5f9;
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
            font-size: 10px;
        }

        .muted {
            color: {{ $muted }};
        }

        .totals {
            width: 100%;
            margin-top: 10px;
        }

        .totals td {
            padding: 5px 8px;
        }

        .totals .label {
            color: {{ $muted }};
        }

        .totals .grand {
            font-weight: 700;
            background: {{ $grandBg }};
            color: {{ $grandTx }};
            border-radius: 8px;
        }

        .terms {
            margin-top: 12px;
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
                                <div style="max-width:320px; max-height:65px; display:flex; align-items:center;">
                                    <img
                                        src="{{ $E['logo_src'] }}"
                                        alt="Logo {{ $E['nombre'] }}"
                                        style="max-width:320px; max-height:65px; width:auto; height:auto; object-fit:contain; object-position:left center;"
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
                <div class="doc-title">COTIZACIÓN</div>

                <div class="header-meta">
                    <div class="header-meta-row">
                        <span class="small muted">Número:</span>
                        <strong style="font-size:14px; color: {{ $ink }};">{{ $folio }}</strong>
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

    @if (($cotizacion->estado ?? '') === 'cancelada')
        <div class="watermark">CANCELADA</div>
    @else
        <div class="watermark">COTIZACIÓN</div>
    @endif

    <main style="position: relative; z-index:1;">

        {{-- Cliente / Condiciones --}}
        <table class="info-grid">
            <tr>
                <td class="w-50">
                    <div class="pane">
                        <h4>Cliente</h4>

                        <div style="font-size:13px; font-weight:700;">
                            {{ $cotizacion->cliente->razon_social ?? 'Cliente' }}
                        </div>

                        <div class="small muted" style="line-height:1.45;">
                            NIT: {{ $cotizacion->cliente->nit ?? '—' }}<br>
                            Email: {{ $cotizacion->cliente->correo ?? '—' }}<br>
                            Tel: {{ $cotizacion->cliente->telefono ?? '—' }}
                        </div>
                    </div>
                </td>

                <td class="w-50">
                    <div class="pane">
                        <h4>Condiciones</h4>

                        <table class="condiciones-table">
                            <tr>
                                <td class="cond-label">Moneda</td>
                                <td class="cond-value">{{ $cotizacion->moneda ?? 'COP' }}</td>
                            </tr>

                            @if (!empty($cotizacion->lista_precio))
                                <tr>
                                    <td class="cond-label">Lista de precios</td>
                                    <td class="cond-value">{{ $cotizacion->lista_precio }}</td>
                                </tr>
                            @endif

                            @if (!empty($cotizacion->terminos_pago))
                                <tr>
                                    <td class="cond-label">Términos</td>
                                    <td class="cond-value">{{ $cotizacion->terminos_pago }}</td>
                                </tr>
                            @endif

                            <tr>
                                <td class="cond-label">Validez</td>
                                <td class="cond-value">15 días (salvo acuerdo)</td>
                            </tr>
                        </table>
                    </div>
                </td>
            </tr>
        </table>

        {{-- Ítems --}}
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
                @foreach ($cotizacion->detalles ?? [] as $d)
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

        {{-- Totales --}}
        <table class="totals">
            <tr>
                <td class="w-50"></td>
                <td class="label text-right">Subtotal</td>
                <td class="text-right">{{ $money($cotizacion->subtotal ?? 0) }}</td>
            </tr>
            <tr>
                <td></td>
                <td class="label text-right">Impuestos</td>
                <td class="text-right">{{ $money($cotizacion->impuestos ?? 0) }}</td>
            </tr>
            <tr>
                <td></td>
                <td class="text-right grand">Total</td>
                <td class="text-right grand">{{ $money($cotizacion->total ?? 0) }}</td>
            </tr>
        </table>

        {{-- Notas --}}
        <div class="terms pane">
            <h4>Notas y condiciones</h4>

            @if (!empty($cotizacion->notas))
                <div style="white-space: pre-line;">{{ $cotizacion->notas }}</div>
            @else
                <div class="muted small" style="line-height:1.5;">
                    • Precios en moneda local.
                    • Validez: 15 días.
                    • Entrega sujeta a disponibilidad.
                    • Garantía según fabricante.
                </div>
            @endif
        </div>

        {{-- Aceptación --}}
        <table style="width:100%; margin-top:16px;">
            <tr>
                <td class="w-50">
                    <div class="small muted">Aprobado por (cliente):</div>
                    <div style="margin-top:50px; border-top:1px solid {{ $border }}; width:80%;"></div>
                </td>
                <td class="w-50">
                    <div class="small muted">Firma y sello:</div>
                    <div style="margin-top:50px; border-top:1px solid {{ $border }}; width:80%;"></div>
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