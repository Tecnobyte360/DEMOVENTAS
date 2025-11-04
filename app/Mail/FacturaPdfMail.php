<?php

namespace App\Mail;

use App\Models\Factura\factura;
use App\Models\ConfiguracionEmpresas\Empresa;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Carbon\Carbon;

class FacturaPdfMail extends Mailable
{
  use Queueable, SerializesModels;

  public function __construct(
    public factura $factura,
    public string $pdfPath,
    public ?string $asunto = null,
  ) {}

  public function build()
  {
    // --- Número de factura ---
    $len  = $this->factura->serie->longitud ?? 6;
    $num  = $this->factura->numero !== null
      ? str_pad((string)$this->factura->numero, $len, '0', STR_PAD_LEFT)
      : '000001';
    $pref = $this->factura->prefijo ? "{$this->factura->prefijo}-" : '';
    $name = "FACT-{$pref}{$num}.pdf";

    // --- Empresa dinámica ---
    $empresa = $this->factura->empresa ?? Empresa::first();
    $nombreEmpresa = $empresa?->nombre ?? 'Empresa';
    $logo = $empresa?->logo_dark_path ?? $empresa?->logo_path ?? null;
    $sitio = $empresa?->sitio_web ?? 'https://www.tecnobyte360.com';

    // --- Cliente y datos base ---
    $cliente = $this->factura->socioNegocio->razon_social
      ?? $this->factura->socioNegocio->nombre_comercial
      ?? 'Cliente';

    $fecha = $this->factura->fecha
      ? Carbon::parse($this->factura->fecha)->format('Y-m-d')
      : Carbon::now()->format('Y-m-d');

    $moneda = $this->factura->moneda ?? 'COP';
    $totalRaw = $this->factura->total ?? $this->factura->total_neto ?? null;
    $totalFmt = $totalRaw ? '$' . number_format($totalRaw, 0, ',', '.') : null;

    // --- Asunto profesional ---
    $asunto = $this->asunto ?: "Factura N° {$pref}{$num} – {$nombreEmpresa}";

    // --- Construcción del cuerpo minimalista y elegante ---
    $htmlLogo = $logo
      ? "<div style='text-align:center;margin-bottom:12px;'>
                 <img src='{$logo}' alt='{$this->esc($nombreEmpresa)}' style='max-width:160px;max-height:60px;object-fit:contain;'>
               </div>"
      : '';

    $html = <<<HTML
<!doctype html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="x-apple-disable-message-reformatting">
</head>
<body style="margin:0;padding:0;background:#ffffff;">
  <div style="max-width:580px;margin:0 auto;padding:24px;
              font-family:Arial,Helvetica,sans-serif;font-size:14px;
              line-height:1.6;color:#111827;">

    {$htmlLogo}

    <p style="margin:0 0 12px;">Estimado(a) {$this->esc($cliente)},</p>

    <p style="margin:0 0 12px;">
      Adjuntamos la factura correspondiente a la compra realizada
      <strong>({$pref}{$num})</strong> emitida el <strong>{$fecha}</strong>.
      {$this->esc($totalFmt ? "Valor total: {$totalFmt}." : "")}
    </p>

    <p style="margin:0 0 12px;">
      Agradecemos su confianza en <strong>{$this->esc($nombreEmpresa)}</strong>.
    </p>
    </p>
  </div>
</body>
</html>
HTML;

    return $this->subject($asunto)
      ->html($html)
      ->attach($this->pdfPath, [
        'as'   => $name,
        'mime' => 'application/pdf',
      ]);
  }

  protected function esc(?string $text): string
  {
    return htmlspecialchars((string)$text, ENT_QUOTES, 'UTF-8');
  }
}
