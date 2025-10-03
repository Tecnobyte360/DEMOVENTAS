<?php

namespace App\Mail;

use App\Models\Factura\factura;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

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
        $len  = $this->factura->serie->longitud ?? 6;
        $num  = $this->factura->numero !== null ? str_pad((string)$this->factura->numero, $len, '0', STR_PAD_LEFT) : '000001';
        $pref = $this->factura->prefijo ? "{$this->factura->prefijo}-" : '';
        $name = "FACT-{$pref}{$num}.pdf";

        return $this->subject($this->asunto ?: "Factura {$pref}{$num}")
            ->html('') // cuerpo vacío
            ->attach($this->pdfPath, [
                'as'   => $name,
                'mime' => 'application/pdf',
            ]);
    }
}
