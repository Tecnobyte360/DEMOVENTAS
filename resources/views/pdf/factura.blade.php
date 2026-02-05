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
  $theme = is_object($empresa) && method_exists($empresa, 'pdfTheme')
      ? $empresa->pdfTheme()
      : (is_array($theme ?? null) ? $theme : []);

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

  // 1) Si viene en DB como "empresas/logos/xxx.jpg" (recomendado)
  if (!empty($empresa->logo_path)) {
      $logoSrc = $empresa->logo_path;
  }
  // 2) Si viene como accessor url (http/https)
  elseif (!empty($empresa->logo_url)) {
      $logoSrc = $empresa->logo_url;
  }
  // 3) Si llega como "storage/..." o ruta ya armada
  elseif (!empty($empresa->logo) && is_string($empresa->logo)) {
      $logoSrc = $empresa->logo;
  }
  // 4) fallback
  elseif (!empty($empresa->logo_src)) {
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
          // Si viene como "storage/..." lo pasamos a public_path
          if (str_starts_with($logoSrc, 'storage/')) {
              $logoPdfSrc = public_path($logoSrc);
          } else {
              // Si viene como "empresas/..." también es relativo a public/
              $logoPdfSrc = public_path($logoSrc);
          }
      }
  }

  $E = [
    'nombre'    => $empresa->nombre        ?? 'Empresa',
    'nit'       => !empty($empresa->nit) ? ('NIT '.$empresa->nit) : null,
    'direccion' => $empresa->direccion     ?? null,
    'telefono'  => $empresa->telefono      ?? null,
    'email'     => $empresa->email         ?? null,
    'website'   => $empresa->sitio_web     ?? null,
    'logo_src'  => $logoPdfSrc,
  ];

  $money  = fn($v) => '$'.number_format((float)$v, 2, '.', ',');
  $fmtPct = fn($v) => rtrim(rtrim(number_format((float)$v, 3, '.', ''), '0'), '.').'%';

  $len  = $factura->serie->longitud ?? 6;
  $num  = $factura->numero !== null ? str_pad((string)$factura->numero, $len, '0', STR_PAD_LEFT) : '—';
  $pref = $factura->prefijo ? "{$factura->prefijo}-" : '';
  $folio = "{$pref}{$num}";
@endphp

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Factura {{ $folio }}</title>

  <style>
    /* ✅ Header con más espacio para logos grandes */
    @page { margin: 105px 36px 95px 36px; }

    body {
      font-family: DejaVu Sans, sans-serif;
      color: {{ $ink }};
      font-size: 12px;
      background: {{ $base }};
    }

    header { position: fixed; top: -89px; left: 0; right: 0; height: 93px; }
    footer { position: fixed; bottom: -70px; left: 0; right: 0; height: 70px; }

    .brand-band { height: 5px; background: {{ $primary }}; border-radius: 0 0 6px 6px; }
    .brand { display: table; width:100%; margin-top: 8px; }
    .brand .col { display: table-cell; vertical-align: middle; }
    .brand .right { text-align: right; vertical-align: top; padding-top: 2px; }

    .doc-title {
      font-size: 24px;
      letter-spacing: .5px;
      margin: 0;
      color: {{ $primary }};
      font-weight: 800;
      line-height: 1.1;
    }

    .badge {
      display:inline-block;
      padding: 4px 10px;
      border-radius: 999px;
      font-size: 11px;
      font-weight: 700;
      vertical-align: middle;
    }

    /* ✅ Marca de agua suave */
    .watermark {
      position: fixed;
      top: 43%;
      left: 12%;
      font-size: 82px;
      color: {{ $wmColor }};
      transform: rotate(-20deg);
      font-weight: 800;
      z-index:0;
    }

    .pane { border:1px solid {{ $border }}; border-radius: 8px; padding:10px 12px; }
    .pane h4 { margin:0 0 6px; font-size: 12px; color: {{ $muted }}; text-transform: uppercase; letter-spacing: .4px; }

    table.items { width:100%; border-collapse: collapse; margin-top: 12px; }
    table.items thead th {
      background: {{ $theadBg }};
      color: {{ $theadText }};
      font-weight:700;
      font-size:11px;
      border-bottom:1px solid {{ $border }};
      padding:8px;
      text-transform: uppercase;
      letter-spacing:.3px;
    }
    table.items tbody td { padding:7px 8px; border-bottom:1px solid #f1f5f9; }
    table.items tbody tr:nth-child(even) { background: {{ $stripe }}; }

    .text-right { text-align: right; }
    .text-center { text-align: center; }
    .w-50 { width:50%; }

    .totals { margin-top: 10px; width: 100%; }
    .totals td { padding:5px 8px; }
    .totals .label { color: {{ $muted }}; }
    .totals .grand { background: {{ $grandBg }}; color: {{ $grandTx }}; font-weight:700; border-radius: 8px; }

    .terms { margin-top: 12px; }
    .muted { color: {{ $muted }}; }
    .small { font-size: 10px; }
    .page-number:after { content: counter(page) " / " counter(pages); }

    .brand-name { font-size: 15px; font-weight: 800; color: {{ $ink }}; }
  </style>
</head>

<body>

<header>
  <div class="brand-band"></div>

  <div class="brand">
    <div class="col" style="width:320px;">
      <table style="width:100%">
        <tr>
          <td style="text-align:left; vertical-align:middle; padding:0;">

            {{-- ✅ LOGO OPTIMIZADO - Sin recortes, tamaño balanceado --}}
            @if(!empty($E['logo_src']))
              <div style="
                max-width:320px;
                max-height:65px;
                display:flex;
                align-items:center;
              ">
                <img
                  src="{{ $E['logo_src'] }}"
                  alt="Logo {{ $E['nombre'] }}"
                  style="
                    max-width:320px;
                    max-height:65px;
                    width:auto;
                    height:auto;
                    object-fit:contain;
                    object-position:left center;
                  ">
              </div>
            @else
              <div class="brand-name">{{ $E['nombre'] }}</div>
              @if(!empty($E['nit']))
                <div class="small muted" style="margin-top:2px;">{{ $E['nit'] }}</div>
              @endif
            @endif

          </td>
        </tr>
      </table>
    </div>

    <div class="col right">
      <div class="doc-title">FACTURA</div>

      <div style="margin-top:3px;">
        <span class="small muted">Número:</span>
        <strong style="font-size:14px;">{{ $folio }}</strong>
      </div>

      <div class="small muted" style="margin-top:2px;">
        Fecha: {{ \Illuminate\Support\Carbon::parse($factura->fecha ?? $factura->created_at)->format('d/m/Y') }}
        @if(!empty($factura->vencimiento))
          · Vence: {{ \Illuminate\Support\Carbon::parse($factura->vencimiento)->format('d/m/Y') }}
        @endif
      </div>

      <div style="margin-top:6px;">
        @php
          $estado = $factura->estado ?? 'borrador';
          $colors = [
            'borrador' => ['#e5e7eb', '#374151'],
            'emitida'  => ['#e9edf6', $primary],
            'parcialmente_pagada' => ['#fff7ed', '#9a3412'],
            'pagada'   => ['#dcfce7', '#166534'],
            'anulada'  => ['#ffe4e6', '#9f1239'],
          ][$estado] ?? ['#e5e7eb','#374151'];
        @endphp

        <span class="badge" style="background: {{ $colors[0] }}; color: {{ $colors[1] }};">
          {{ ucwords(str_replace('_', ' ', $estado)) }}
        </span>
      </div>
    </div>
  </div>
</header>

<footer>
  <table style="width:100%">
    <tr>
      <td class="small muted">
        {{ $E['nombre'] }} @if(!empty($E['website'])) · {{ $E['website'] }} @endif
      </td>
      <td class="small muted text-right">Página <span class="page-number"></span></td>
    </tr>
  </table>
</footer>

@if(($factura->estado ?? '') === 'anulada')
  <div class="watermark">ANULADA</div>
@else
  <div class="watermark">FACTURA</div>
@endif

<main style="position: relative; z-index:1">
  <table style="width:100%; border-spacing: 10px 0">
    <tr>
      <td class="w-50">
        <div class="pane">
          <h4>Cliente</h4>
          <div style="font-size:13px; font-weight:700">{{ $factura->cliente->razon_social ?? 'Cliente' }}</div>
          <div class="small muted">
            NIT: {{ $factura->cliente->nit ?? '—' }}<br>
            Email: {{ $factura->cliente->correo ?? '—' }}<br>
            Tel: {{ $factura->cliente->telefono ?? '—' }}
          </div>
        </div>
      </td>
      <td class="w-50">
        <div class="pane">
          <h4>Condiciones</h4>
          <table style="width:100%">
            <tr><td class="small muted">Moneda</td><td class="small" style="text-align:right">{{ $factura->moneda ?? 'COP' }}</td></tr>
            <tr><td class="small muted">Pago</td><td class="small" style="text-align:right">{{ ucfirst($factura->tipo_pago ?? 'contado') }}</td></tr>
            @if(($factura->tipo_pago ?? '') === 'credito')
              <tr><td class="small muted">Plazo</td><td class="small" style="text-align:right">{{ $factura->plazo_dias }} días</td></tr>
            @endif
            @if(!empty($factura->terminos_pago))
              <tr><td class="small muted">Términos</td><td class="small" style="text-align:right">{{ $factura->terminos_pago }}</td></tr>
            @endif
          </table>
        </div>
      </td>
    </tr>
  </table>

  <table class="items">
    <thead>
      <tr>
        <th style="width:34%">Producto</th>
        <th style="width:8%"  class="text-right">Cant.</th>
        <th style="width:12%" class="text-right">Precio</th>
        <th style="width:8%"  class="text-right">Desc</th>
        <th style="width:8%"  class="text-right">IVA</th>
        <th style="width:18%" class="text-right">Total línea</th>
      </tr>
    </thead>
    <tbody>
      @foreach(($factura->detalles ?? []) as $d)
        @php
          $nombre  = $d->producto->nombre ?? ($d->descripcion ?: ('#'.$d->producto_id));
          $cant    = (float) $d->cantidad;
          $precio  = (float) $d->precio_unitario;
          $descPct = (float) ($d->descuento_pct ?? 0);
          $ivaPct  = (float) ($d->impuesto_pct  ?? 0);
          $baseLn  = $cant * $precio * (1 - $descPct/100);
          $ivaLn   = $baseLn * $ivaPct/100;
          $totalLn = $baseLn + $ivaLn;
        @endphp
        <tr>
          <td>{{ $nombre }}</td>
          <td class="text-right">{{ rtrim(rtrim(number_format($cant,3,'.',''), '0'), '.') }}</td>
          <td class="text-right">{{ $money($precio) }}</td>
          <td class="text-right">{{ $fmtPct($descPct) }}</td>
          <td class="text-right">{{ $fmtPct($ivaPct) }}</td>
          <td class="text-right">{{ $money($totalLn) }}</td>
        </tr>
      @endforeach
    </tbody>
  </table>

  <table class="totals">
    <tr><td class="w-50"></td><td class="label text-right">Subtotal</td><td class="text-right">{{ $money($factura->subtotal ?? 0) }}</td></tr>
    <tr><td></td><td class="label text-right">Impuestos</td><td class="text-right">{{ $money($factura->impuestos ?? 0) }}</td></tr>
    <tr><td></td><td class="text-right grand">Total</td><td class="text-right grand">{{ $money($factura->total ?? 0) }}</td></tr>
  </table>

  @if(!empty($factura->notas))
    <div class="terms pane">
      <h4>Notas</h4>
      <div style="white-space: pre-line">{{ $factura->notas }}</div>
    </div>
  @endif
</main>

</body>
</html>