@php
    // Paleta corporativa desde empresa o defaults
    $primary   = $empresa['color_primario'] ?? '#223361';
    $base      = '#ffffff';
    $ink       = '#1f2937';
    $muted     = '#6b7280';
    $border    = '#e5e7eb';
    $theadBg   = '#eef2f8';
    $theadText = $primary;
    $stripe    = '#f7f9fc';
    $grandBg   = $primary;
    $grandTx   = '#ffffff';
    $wmColor   = 'rgba(34, 51, 97, .06)';

    $badge = [
        'borrador'   => ['#e5e7eb', '#374151'],
        'enviada'    => ['#e9edf6', $primary],
        'confirmada' => ['#dbeafe', '#1e40af'],
        'convertida' => ['#dcfce7', '#166534'],
        'cancelada'  => ['#ffe4e6', '#9f1239'],
    ];

    $money  = fn($v) => '$'.number_format((float)$v, 2, '.', ',');
    $fmtPct = fn($v) => rtrim(rtrim(number_format((float)$v, 3, '.', ''), '0'), '.').'%';
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>Cotización {{ $ref ?? ('S'.str_pad($cotizacion->id,5,'0',STR_PAD_LEFT)) }}</title>
<style>
  @page { margin: 130px 36px 120px 36px; }
  body { font-family: DejaVu Sans, sans-serif; color: {{ $ink }}; font-size: 12px; background: {{ $base }}; }
  header { position: fixed; top: -110px; left: 0; right: 0; height: 120px; }
  footer { position: fixed; bottom: -90px; left: 0; right: 0; height: 100px; }

  .brand-band { height: 6px; background: {{ $primary }}; border-radius: 0 0 6px 6px; }
  .brand { display: table; width:100%; margin-top: 10px; }
  .brand .col { display: table-cell; vertical-align: top; }
  .brand .right { text-align: right; }
  .logo { height: 48px; max-width: 220px; object-fit: contain; }

  .doc-title { font-size: 22px; letter-spacing: .5px; margin: 2px 0 0; color: {{ $primary }}; font-weight: 800; }
  .badge { display:inline-block; padding: 3px 8px; border-radius: 999px; font-size: 10px; font-weight: 700; vertical-align: middle; }

  .watermark { position: fixed; top: 40%; left: 12%; font-size: 90px; color: {{ $wmColor }}; transform: rotate(-20deg); font-weight: 800; z-index:0; }

  .pane { border:1px solid {{ $border }}; border-radius: 8px; padding:10px 12px; }
  .pane h4 { margin:0 0 6px; font-size: 12px; color: {{ $muted }}; text-transform: uppercase; letter-spacing: .4px; }

  table.items { width:100%; border-collapse: collapse; margin-top: 14px; }
  table.items thead th { background: {{ $theadBg }}; color: {{ $theadText }}; font-weight:700; font-size:11px; border-bottom:1px solid {{ $border }}; padding:8px; text-transform: uppercase; letter-spacing:.3px; }
  table.items tbody td { padding:7px 8px; border-bottom:1px solid #f1f5f9; }
  table.items tbody tr:nth-child(even) { background: {{ $stripe }}; }

  .text-right { text-align: right; } .text-center { text-align: center; } .w-50 { width:50%; }

  .totals { margin-top: 12px; width: 100%; }
  .totals td { padding:5px 8px; }
  .totals .label { color: {{ $muted }}; }
  .totals .grand { background: {{ $grandBg }}; color: {{ $grandTx }}; font-weight:700; border-radius: 8px; }

  .terms { margin-top: 14px; } .muted { color: {{ $muted }}; } .small { font-size: 10px; }
  .page-number:after { content: counter(page) " / " counter(pages); }
</style>
</head>
<body>
<header>
  <div class="brand-band"></div>
  <div class="brand">
    {{-- Columna izquierda: logo + nombre + datos --}}
    <div class="col">
      <table class="brand-head">
        <tr>
          <td class="logo-cell">
            @if(!empty($empresa['logo_src']))
              <img class="logo" src="{{ $empresa['logo_src'] }}" alt="Logo {{ $empresa['nombre'] }}">
            @endif
          </td>
          <td class="name-cell">
            <div class="brand-name">{{ strtoupper($empresa['nombre'] ?? 'Empresa') }}</div>
            <div class="small muted">
              @if(!empty($empresa['nit'])) {{ $empresa['nit'] }} · @endif
              @if(!empty($empresa['direccion'])) {{ $empresa['direccion'] }} · @endif
              @if(!empty($empresa['telefono'])) {{ $empresa['telefono'] }} @endif
              <br>
              @if(!empty($empresa['email'])) {{ $empresa['email'] }} @endif
              @if(!empty($empresa['website'])) · {{ $empresa['website'] }} @endif
            </div>
          </td>
        </tr>
      </table>
    </div>

    {{-- Columna derecha: título, referencia, fechas y badge --}}
    <div class="col right">
      @php
        $estado = $cotizacion->estado ?? 'borrador';
        [$bBg, $bTx] = $badge[$estado] ?? $badge['borrador'];
      @endphp

      <div class="doc-title">COTIZACIÓN</div>

      <div>
        <span class="small muted">Referencia:</span>
        <strong>{{ $ref ?? ('S'.str_pad($cotizacion->id,5,'0',STR_PAD_LEFT)) }}</strong>
      </div>

      <div class="small muted">
        Fecha: {{ \Illuminate\Support\Carbon::parse($cotizacion->fecha ?? $cotizacion->created_at)->format('d/m/Y') }}
        @if(!empty($cotizacion->vencimiento))
          · Vence: {{ \Illuminate\Support\Carbon::parse($cotizacion->vencimiento)->format('d/m/Y') }}
        @endif
      </div>

      <div style="margin-top:6px">
        <span class="badge" style="background: {{ $bBg }}; color: {{ $bTx }}">
          {{ $estado === 'convertida' ? 'Orden de venta' : ucfirst($estado) }}
        </span>
      </div>
    </div>
  </div>
</header>


<footer>
  <table style="width:100%">
    <tr>
      <td class="small muted">{{ $empresa['nombre'] ?? 'Empresa' }} @if(!empty($empresa['website'])) · {{ $empresa['website'] }} @endif</td>
      <td class="small muted text-right">Página <span class="page-number"></span></td>
    </tr>
  </table>
</footer>

@if(($cotizacion->estado ?? '') === 'cancelada')
  <div class="watermark">CANCELADA</div>
@else
  <div class="watermark">COTIZACIÓN</div>
@endif

<main style="position: relative; z-index:1">
  {{-- Cliente / Info comercial --}}
  <table style="width:100%; border-spacing: 10px 0">
    <tr>
      <td class="w-50">
        <div class="pane">
          <h4>Cliente</h4>
          <div style="font-size:13px; font-weight:700">{{ $cotizacion->cliente->razon_social ?? 'Cliente' }}</div>
          <div class="small muted">
            NIT: {{ $cotizacion->cliente->nit ?? '—' }}<br>
            Email: {{ $cotizacion->cliente->correo ?? '—' }}<br>
            Tel: {{ $cotizacion->cliente->telefono ?? '—' }}
          </div>
        </div>
      </td>
      <td class="w-50">
        <div class="pane">
          <h4>Condiciones</h4>
          <table style="width:100%">
            <tr><td class="small muted">Lista de precios</td><td class="small" style="text-align:right">{{ $cotizacion->lista_precio ?? '—' }}</td></tr>
            <tr><td class="small muted">Términos de pago</td><td class="small" style="text-align:right">{{ $cotizacion->terminos_pago ?? '—' }}</td></tr>
            <tr><td class="small muted">Validez</td><td class="small" style="text-align:right">15 días (salvo acuerdo)</td></tr>
          </table>
        </div>
      </td>
    </tr>
  </table>

  {{-- Ítems --}}
  <table class="items">
    <thead>
      <tr>
        <th style="width:34%">Producto</th>
        <th style="width:12%" class="text-center">Bodega</th>
        <th style="width:8%"  class="text-right">Cant.</th>
        <th style="width:12%" class="text-right">Precio</th>
        <th style="width:8%"  class="text-right">Desc</th>
        <th style="width:8%"  class="text-right">IVA</th>
        <th style="width:18%" class="text-right">Total línea</th>
      </tr>
    </thead>
    <tbody>
      @foreach(($cotizacion->detalles ?? []) as $d)
        @php
          $nombre  = $d->producto->nombre ?? ('#'.$d->producto_id);
          $bodega  = $d->bodega->nombre  ?? '—';
          $cant    = (float) $d->cantidad;
          $precio  = (float) $d->precio_unitario;
          $descPct = (float) ($d->descuento_pct ?? 0);
          $ivaPct  = (float) ($d->impuesto_pct  ?? 0);
          $base    = $cant * $precio * (1 - $descPct/100);
          $iva     = $base * $ivaPct/100;
          $totalLn = $base + $iva;
        @endphp
        <tr>
          <td>{{ $nombre }}</td>
          <td class="text-center">{{ $bodega }}</td>
          <td class="text-right">{{ rtrim(rtrim(number_format($cant,3,'.',''), '0'), '.') }}</td>
          <td class="text-right">{{ $money($precio) }}</td>
          <td class="text-right">{{ $fmtPct($descPct) }}</td>
          <td class="text-right">{{ $fmtPct($ivaPct) }}</td>
          <td class="text-right">{{ $money($totalLn) }}</td>
        </tr>
      @endforeach

      @if(($cotizacion->detalles ?? collect())->isEmpty() && !empty($lineas))
        @foreach($lineas as $l)
          @php
            $cant    = (float) $l['cantidad'];
            $precio  = (float) $l['precio_unitario'];
            $descPct = (float) ($l['descuento_pct'] ?? 0);
            $ivaPct  = (float) ($l['impuesto_pct']  ?? 0);
            $base    = $cant * $precio * (1 - $descPct/100);
            $iva     = $base * $ivaPct/100;
            $totalLn = $base + $iva;
          @endphp
          <tr>
            <td>Producto #{{ $l['producto_id'] }}</td>
            <td class="text-center">{{ $l['bodega_id'] ?? '—' }}</td>
            <td class="text-right">{{ rtrim(rtrim(number_format($cant,3,'.',''), '0'), '.') }}</td>
            <td class="text-right">{{ $money($precio) }}</td>
            <td class="text-right">{{ $fmtPct($descPct) }}</td>
            <td class="text-right">{{ $fmtPct($ivaPct) }}</td>
            <td class="text-right">{{ $money($totalLn) }}</td>
          </tr>
        @endforeach
      @endif
    </tbody>
  </table>

  {{-- Totales --}}
  @php
    $subtotal = (float) ($cotizacion->subtotal ?? collect($lineas)->sum('importe'));
    $ivaCalc  = 0.0;
    foreach(($cotizacion->detalles ?? []) as $d){
        $base = (float)$d->cantidad * (float)$d->precio_unitario * (1 - (float)($d->descuento_pct ?? 0)/100);
        $ivaCalc += $base * (float)($d->impuesto_pct ?? 0)/100;
    }
    $impuestos = (float) ($cotizacion->impuestos ?? $ivaCalc);
    $granTotal = (float) ($cotizacion->total ?? ($subtotal + $impuestos));
  @endphp

  <table class="totals">
    <tr><td class="w-50"></td><td class="label text-right">Subtotal</td><td class="text-right">{{ $money($subtotal) }}</td></tr>
    <tr><td></td><td class="label text-right">Impuestos</td><td class="text-right">{{ $money($impuestos) }}</td></tr>
    <tr><td></td><td class="text-right grand">Total</td><td class="text-right grand">{{ $money($granTotal) }}</td></tr>
  </table>

  {{-- Notas --}}
  <div class="terms pane">
    <h4>Notas y condiciones</h4>
    @if(!empty($cotizacion->notas))
      <div style="white-space: pre-line">{{ $cotizacion->notas }}</div>
    @else
      <div class="muted small">• Precios en moneda local. • Validez: 15 días. • Entrega sujeta a disponibilidad. • Garantía según fabricante.</div>
    @endif
  </div>

  {{-- Aceptación --}}
  <table style="width:100%; margin-top:16px">
    <tr>
      <td class="w-50">
        <div class="small muted">Aprobado por (cliente):</div>
        <div style="margin-top:50px; border-top:1px solid {{ $border }}; width:80%"></div>
      </td>
      <td class="w-50">
        <div class="small muted">Firma y sello:</div>
        <div style="margin-top:50px; border-top:1px solid {{ $border }}; width:80%"></div>
      </td>
    </tr>
  </table>
</main>
</body>
</html>
