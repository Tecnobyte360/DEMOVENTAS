<?php

namespace App\Mail;

use App\Models\Factura\factura;
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

        // --- Datos dinámicos ---
        $empresa = $this->factura->empresa->nombre ?? 'Doblamos S.A.S.';
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
        $asunto = $this->asunto ?: "Factura N° {$pref}{$num} – {$empresa}";

        // --- Cuerpo de correo elegante y minimalista ---
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
              
    <p style="margin:0 0 12px;">Estimado(a) {$this->esc($cliente)},</p>

    <p style="margin:0 0 12px;">
      Adjuntamos la factura correspondiente a la compra realizada
      <strong>({$pref}{$num})</strong> emitida el <strong>{$fecha}</strong>.
      {$this->esc($totalFmt ? "Valor total: {$totalFmt}." : "")}
    </p>

    <p style="margin:0 0 12px;">
      Agradecemos su confianza en <strong>{$this->esc($empresa)}</strong>.
    </p>

    <p style="margin:0 0 12px;">
      Quedamos atentos a cualquier duda o requerimiento.
    </p>

    <p style="margin:16px 0 0;">
      Atentamente,<br>
      Equipo Tecnobyte360<br>
      <a href="https://www.tecnobyte360.com" target="_blank"
         style="color:#223361;text-decoration:none;">www.tecnobyte360.com</a>
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

    /** Escapar texto seguro para HTML */
    protected function esc(?string $text): string
    {
        return htmlspecialchars((string)$text, ENT_QUOTES, 'UTF-8');
    }
}
