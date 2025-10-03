{{-- resources/views/pos/ticket-factura.blade.php --}}
@php
  // ===== Helpers =====
  $money = fn($v, $dec=0) => '$'.number_format((float)$v, $dec, ',', '.'); // $1.417.500
  $len   = $factura->serie->longitud ?? 6;
  $num   = $factura->numero !== null ? str_pad((string)$factura->numero, $len, '0', STR_PAD_LEFT) : '—';
  $pref  = $factura->prefijo ? "{$factura->prefijo}-" : '';
  $folio = "{$pref}{$num}";

  $fechaEmi = \Illuminate\Support\Carbon::parse($factura->fecha ?? $factura->created_at);
  $fechaFmt = $fechaEmi->format('d/m/Y');
  $horaFmt  = $fechaEmi->format('g:i a');

  $cliNombre = $factura->cliente->razon_social ?? '—';
  $cliNit    = $factura->cliente->nit ?? '—';
  $cliDir    = $factura->cliente->direccion ?? null;
  $cliTel    = $factura->cliente->telefono ?? null;

  // Impuestos agrupados por % para el resumen
  $impResumen = [];   // [ tasaPct => ['base'=>x, 'imp'=>y] ]
  $lineas     = 0;
  $itemsCant  = 0;

  foreach (($factura->detalles ?? []) as $d) {
      $cant    = (float)($d->cantidad ?? 0);
      $precio  = (float)($d->precio_unitario ?? 0);
      $descPct = (float)($d->descuento_pct ?? 0);
      $ivaPct  = (float)($d->impuesto_pct  ?? 0);
      $base    = $cant * $precio * (1 - $descPct/100);
      $iva     = $base * $ivaPct/100;

      $impResumen[$ivaPct] = [
        'base' => ($impResumen[$ivaPct]['base'] ?? 0) + $base,
        'imp'  => ($impResumen[$ivaPct]['imp']  ?? 0) + $iva,
      ];

      $lineas   += 1;
      $itemsCant += $cant;
  }
  ksort($impResumen);

  // Paleta y acentos
  $brand = [
    'name'     => strtoupper($empresa['nombre'] ?? 'NOMBRE DEL COMERCIO'),
    'slogan'   => $empresa['slogan']   ?? null,
    'nit'      => $empresa['nit']      ?? null,
    'regimen'  => $empresa['regimen']  ?? null,
    'dir'      => $empresa['direccion']?? null,
    'tel'      => $empresa['telefono'] ?? null,
    'pos'      => $empresa['pos']      ?? null,
    'cajero'   => $empresa['cajero']   ?? null,
    'website'  => $empresa['website']  ?? null,
    'resol'    => $empresa['resolucion'] ?? null,
    'pie'      => $empresa['pie']      ?? null,
    'logo'     => $empresa['logo_src'] ?? null,
    'accent'   => $empresa['accent']   ?? '#0ea5e9', // cian bonito
  ];
@endphp
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Factura {{ $folio }}</title>
<style>
  /* ===== Variables / Layout ===== */
  :root {
    --accent: {{ $brand['accent'] }};
    --ink: #0b1220;
    --muted: #5b6474;
    --hair: #d7dbe3;
    --hair-2: #eceff4;
  }
  @page { size: 80mm auto; margin: 3mm; }
  * { box-sizing: border-box; }
  body {
    font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, "Liberation Mono", monospace;
    font-size: 12px; line-height: 1.28; color: var(--ink); margin: 0;
  }
  .wrap { width: 72mm; margin: 0 auto; }
  .center { text-align: center; }
  .right { text-align: right; }
  .muted { color: var(--muted); }
  .hr    { border-top: 1px dashed var(--hair); margin: 8px 0; }
  .pair  { display:flex; justify-content:space-between; gap:8px; }

  /* ===== Header bonito ===== */
  .brandband {
    height: 4px; background: var(--accent);
    border-radius: 999px; margin: 6px 0 10px;
  }
  .title {
    font-weight: 900; letter-spacing: .5px; font-size: 14px;
  }
  .subtitle { margin-top: 2px; color: var(--muted); }

  /* Pills / tags */
  .tag {
    display:inline-block; padding: 2px 8px; border-radius: 999px;
    font-size: 11px; font-weight: 700; letter-spacing:.2px;
    background: color-mix(in srgb, var(--accent) 10%, white);
    color: color-mix(in srgb, var(--accent) 60%, black);
    border: 1px solid color-mix(in srgb, var(--accent) 45%, white);
  }

  /* ===== Items compactos con buen ritmo ===== */
  .line { padding: 3px 0 4px; }
  .desc { font-weight: 700; }
  .meta { display:flex; justify-content:space-between; gap:8px; color: var(--muted); }
  .meta .left  { max-width: 43mm; }
  .meta .right { min-width: 25mm; text-align: right; }
  .hair-light  { border-top: 1px dashed var(--hair-2); margin: 4px 0; }

  /* ===== Totales “card” ===== */
  .card {
    border: 1px solid var(--hair);
    border-radius: 10px;
    padding: 6px 8px;
    background: #fff;
    box-shadow: 0 0 0 1px color-mix(in srgb, var(--accent) 6%, transparent);
  }
  .totals .row { display:flex; justify-content:space-between; padding: 2px 0; }
  .totals .big { font-weight: 900; font-size: 13px; }

  /* ===== Resumen de impuestos ===== */
  table { width:100%; border-collapse: collapse; }
  th, td { padding: 3px 0; vertical-align: top; }
  th {
    font-weight:800; color: var(--muted);
    border-bottom: 1px dashed var(--hair);
  }
  tbody tr td { border-bottom: 1px dashed var(--hair-2); }
  .col-tarifa { width: 18mm; }
  .col-base   { width: 24mm; text-align: right; }
  .col-imp    { width: 24mm; text-align: right; }
  .col-pct    { width: 10mm; text-align: right; }

  /* ===== Footer ===== */
  .legal {
    margin-top: 8px; color: var(--muted); text-align: justify;
  }
  .foot { text-align:center; margin-top: 8px; }
  .thanks { font-weight: 800; }
  .tiny { font-size: 11px; color: var(--muted); }

  @media print {
    .no-print { display: none !important; }
    body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
  }
</style>
</head>
<body>
<div class="wrap">

  {{-- ===== ENCABEZADO ===== --}}
  <div class="center" style="margin-top:2px">
    @if($brand['logo'])
      <img src="{{ $brand['logo'] }}" alt="Logo" style="max-width:50mm; max-height:20mm; object-fit:contain; display:block; margin:0 auto 4px;">
    @endif
    <div class="title">{{ $brand['name'] }}</div>
    @if($brand['slogan']) <div class="subtitle">{{ $brand['slogan'] }}</div> @endif
    <div class="brandband"></div>
    @if($brand['nit'])   <div class="tiny">{{ $brand['nit'] }}</div> @endif
    @if($brand['regimen'])<div class="tiny">{{ $brand['regimen'] }}</div> @endif
    @if($brand['dir'])   <div class="tiny">{{ $brand['dir'] }}</div> @endif
    @if($brand['tel'])   <div class="tiny">Tel: {{ $brand['tel'] }}</div> @endif
  </div>

  <div class="hr"></div>

  {{-- ===== TÍTULO DOCUMENTO / CHIPS ===== --}}
  <div class="pair">
    <div><strong>FACTURA DE VENTA</strong></div>
    <div class="tag">{{ strtoupper($factura->tipo_pago ?? 'CONTADO') }}</div>
  </div>
  <div class="pair" style="margin-top:2px">
    <div><span class="muted">No.</span> {{ $folio }}</div>
    @if($brand['pos']) <div><span class="muted">POS:</span> {{ $brand['pos'] }}</div> @endif
  </div>
  <div class="pair" style="margin-top:2px">
    <div><span class="muted">Fecha:</span> {{ $fechaFmt }} {{ $horaFmt }}</div>
    @if($brand['cajero']) <div><span class="muted">Cajero:</span> {{ $brand['cajero'] }}</div> @endif
  </div>
  <div class="pair" style="margin-top:2px">
    @if(($factura->tipo_pago ?? '') === 'credito' && !empty($factura->vencimiento))
      <div><span class="muted">Vence:</span> {{ \Illuminate\Support\Carbon::parse($factura->vencimiento)->format('d/m/Y') }}</div>
    @else
      <div><span class="muted">Vence:</span> {{ $fechaFmt }}</div>
    @endif
  </div>

  <div class="hr"></div>

  {{-- ===== CLIENTE ===== --}}
  <div style="margin-bottom:4px">
    <div style="font-weight:800">CLIENTE</div>
    <div>{{ $cliNombre }}</div>
    <div class="tiny">NIT: {{ $cliNit }}</div>
    @if($cliDir) <div class="tiny">{{ $cliDir }}</div> @endif
    @if($cliTel) <div class="tiny">Tel: {{ $cliTel }}</div> @endif
  </div>

  <div class="hr"></div>

  {{-- ===== ÍTEMS (bonito/compacto) ===== --}}
  @foreach(($factura->detalles ?? []) as $d)
    @php
      $cant    = (float)($d->cantidad ?? 0);
      $precio  = (float)($d->precio_unitario ?? 0);
      $descPct = (float)($d->descuento_pct ?? 0);
      $ivaPct  = (float)($d->impuesto_pct  ?? 0);
      $base    = $cant * $precio * (1 - $descPct/100);
      $iva     = $base * $ivaPct/100;
      $totalLn = $base + $iva;

      $codigo  = $d->producto->codigo ?? $d->producto->item_code ?? null;
      $nombre  = $d->producto->nombre ?? ($d->descripcion ?: ('#'.$d->producto_id));
      $tarifa  = $ivaPct == 0 ? 'EXCL' : 'IVA';
    @endphp

    <div class="line">
      <div class="desc">@if($codigo){{ $codigo }} · @endif{{ $nombre }}</div>
      <div class="meta">
        <div class="left">
          {{ rtrim(rtrim(number_format($cant,3,',','.'),'0'),',') }}
          × {{ $money($precio) }}
          @if($descPct) · Desc {{ rtrim(rtrim(number_format($descPct,2,',','.'),'0'),',') }}% @endif
          · {{ $tarifa }}@if($ivaPct) {{ rtrim(rtrim(number_format($ivaPct,2,',','.'),'0'),',') }}% @endif
        </div>
        <div class="right">{{ $money($totalLn) }}</div>
      </div>
    </div>
    <div class="hair-light"></div>
  @endforeach

  {{-- ===== TOTALES (card) ===== --}}
  <div class="card totals" style="margin:6px 0 4px">
    <div class="row">
      <div>Subtotal</div>
      <div>{{ $money($factura->subtotal ?? 0) }}</div>
    </div>
    <div class="row">
      <div>Impuestos</div>
      <div>{{ $money($factura->impuestos ?? 0) }}</div>
    </div>
    <div class="row big">
      <div>Total</div>
      <div>{{ $money($factura->total ?? 0) }}</div>
    </div>
    @if(($factura->pagado ?? 0) > 0 || ($factura->saldo ?? 0) > 0)
      <div class="hr" style="margin:6px 0"></div>
      <div class="row">
        <div>Pagado</div>
        <div>{{ $money($factura->pagado ?? 0) }}</div>
      </div>
      <div class="row">
        <div>Saldo</div>
        <div>{{ $money($factura->saldo ?? 0) }}</div>
      </div>
    @endif
  </div>

  {{-- ===== RESUMEN DE IMPUESTOS ===== --}}
  <div style="font-weight:800; margin:6px 0 2px">Resumen de impuestos</div>
  <table>
    <thead>
      <tr>
        <th class="col-tarifa">Tarifa</th>
        <th class="col-base">Base</th>
        <th class="col-imp">Impuesto</th>
        <th class="col-pct">%</th>
      </tr>
    </thead>
    <tbody>
      @forelse($impResumen as $pct => $vals)
        <tr>
          <td class="col-tarifa">{{ $pct == 0 ? 'EXCL' : 'IVA' }}</td>
          <td class="col-base">{{ $money($vals['base']) }}</td>
          <td class="col-imp">{{ $money($vals['imp']) }}</td>
          <td class="col-pct">{{ rtrim(rtrim(number_format($pct,2,',','.'),'0'),',') }}</td>
        </tr>
      @empty
        <tr><td colspan="4" class="tiny">N/A</td></tr>
      @endforelse
    </tbody>
  </table>

  <div class="hr"></div>

  {{-- ===== INFO EXTRA ===== --}}
  <div class="pair">
    <div class="tiny">Total de líneas: {{ $lineas }}</div>
    <div class="tiny">Total de productos: {{ rtrim(rtrim(number_format($itemsCant,0,',','.'),'0'),',') }}</div>
  </div>

  <div class="legal">
    Esta factura se asimila en todos sus efectos a una letra de cambio (Art. 774 C.Co.). En caso de incumplimiento, su obligación podrá ser reportada a centrales de riesgo y generará intereses moratorios.
  </div>

  <div class="foot">
    @if($brand['resol']) <div class="tiny" style="margin-top:6px">{{ $brand['resol'] }}</div> @endif
    <div class="thanks" style="margin-top:6px">¡GRACIAS POR SU COMPRA!</div>
    @if($brand['website']) <div class="tiny">{{ $brand['website'] }}</div> @endif
    @if($brand['pie']) <div class="tiny" style="margin-top:4px">{{ $brand['pie'] }}</div> @endif
  </div>

  {{-- Botón (solo fuera de previsualización embebida) --}}
  @unless(request()->boolean('preview'))
    <div class="no-print center" style="margin-top:10px">
      <button onclick="window.print()" style="padding:6px 10px;font-size:12px;border-radius:8px;border:1px solid var(--hair);background:#fff;">
        Imprimir
      </button>
    </div>
  @endunless
</div>
</body>
</html>
