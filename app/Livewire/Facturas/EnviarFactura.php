<?php

namespace App\Livewire\Facturas;

use App\Mail\FacturaPdfMail;
use App\Models\Factura\factura;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\Attributes\On;

class EnviarFactura extends Component
{
    public ?int $facturaId = null;
    public bool $show = false;

    public string  $para = '';
    public ?string $cc = null;   // Emails separados por coma, ; o espacios
    public ?string $asunto = null;

    protected $rules = [
        'para'   => 'required|email',
        'cc'     => 'nullable|string',
        'asunto' => 'nullable|string|max:150',
    ];

    #[On('abrir-enviar-factura')]
    public function abrir(int $id): void
    {
        $f = factura::with('cliente','serie','detalles')->findOrFail($id);

        $this->facturaId = $f->id;
        $this->para      = $f->cliente->correo ?? '';

        $len  = $f->serie->longitud ?? 6;
        $num  = $f->numero !== null ? str_pad((string)$f->numero, $len, '0', STR_PAD_LEFT) : '';
        $pref = $f->prefijo ? "{$f->prefijo}-" : '';

        $this->asunto = "Factura {$pref}{$num}";
        $this->cc     = null;
        $this->show   = true;

        $this->resetErrorBag();
        $this->resetValidation();
    }

    public function cerrar(): void
    {
        $this->show = false;
    }

    public function enviar(): void
    {
        $this->validate();

        $factura = factura::with('cliente','serie','detalles.producto','detalles.bodega')
            ->findOrFail($this->facturaId);

        // === Logo desde public/storage/empresas/logos (última imagen) ===
        $logoSrc = $this->logoEmpresaSrcFromPublicStorage();

        $empresa = [
            'nombre'         => 'LOS-VULCANOS',
            'logo_src'       => $logoSrc,   // ⬅️ aquí va el base64 del logo
            'email'          => 'ventas@losvulcanos.com',
            'website'        => null,
            'nit'            => '222222222',
            'telefono'       => '3216499744',
            'direccion'      => 'MEDELLIN ANTIOQUIA',
            'color_primario' => '#223361',
        ];

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.factura', [
            'factura' => $factura,
            'empresa' => $empresa,
        ])->setPaper('a4');

        $len  = $factura->serie->longitud ?? 6;
        $num  = $factura->numero !== null ? str_pad((string)$factura->numero, $len, '0', STR_PAD_LEFT) : '000001';
        $pref = $factura->prefijo ? "{$factura->prefijo}-" : '';
        $name = "FACT-{$pref}{$num}.pdf";
        $path = "tmp/{$name}";

        Storage::put($path, $pdf->output());
        $abs = Storage::path($path);

        $ccs = collect(preg_split('/[;, ]+/', (string)$this->cc, -1, PREG_SPLIT_NO_EMPTY))
            ->filter(fn($v) => filter_var($v, FILTER_VALIDATE_EMAIL))
            ->values()
            ->all();

        try {
            Mail::to($this->para)
                ->cc($ccs)
                ->send(new \App\Mail\FacturaPdfMail(
                    factura: $factura,
                    pdfPath: $abs,
                    asunto: (string)($this->asunto ?: "Factura {$pref}{$num}")
                ));

            $this->dispatch('toast', type:'success', msg:'Factura enviada por correo.');
            $this->show = false;
            $this->dispatch('$refresh')->to(\App\Livewire\Facturas\FacturaForm::class);
        } finally {
            Storage::delete($path);
        }
    }

    /**
     * Devuelve el logo en base64 tomando la imagen MÁS RECIENTE
     * dentro de public/storage/empresas/logos (disk 'public' => storage/app/public).
     */
    protected function logoEmpresaSrcFromPublicStorage(): ?string
    {
        $disk = Storage::disk('public');
        $dir  = 'empresas/logos';

        if (!$disk->exists($dir)) {
            return null;
        }

        $files = collect($disk->files($dir))
            ->filter(fn($f) => preg_match('/\.(png|jpe?g|webp)$/i', $f))
            ->sortByDesc(fn($f) => $disk->lastModified($f))
            ->values();

        if ($files->isEmpty()) {
            return null;
        }

        $abs  = $disk->path($files[0]);
        $ext  = strtolower(pathinfo($abs, PATHINFO_EXTENSION));
        $mime = $ext === 'jpg' ? 'jpeg' : $ext;

        return 'data:image/'.$mime.';base64,'.base64_encode(file_get_contents($abs));
    }
    public function render()
    {
        return view('livewire.facturas.enviar-factura');
    }
}
