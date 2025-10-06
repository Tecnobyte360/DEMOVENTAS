<?php

namespace App\Livewire\Facturas;

use App\Mail\FacturaPdfMail;
use App\Models\ConfiguracionEmpresas\Empresa;
use App\Models\Factura\Factura;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\Attributes\On;
use Masmerise\Toaster\PendingToast;

class EnviarFactura extends Component
{
    public ?int $facturaId = null;
    public bool $show = false;

    public string  $para = '';
    public ?string $cc = null;      
    public ?string $asunto = null;

    protected $rules = [
        'para'   => 'required|email',
        'cc'     => 'nullable|string',
        'asunto' => 'nullable|string|max:150',
    ];

    #[On('abrir-enviar-factura')]
    public function abrir(int $id): void
    {
        $f = Factura::with('cliente','serie')->findOrFail($id);

        $this->facturaId = $f->id;
        $this->para      = $f->cliente->correo ?? '';

        $len  = $f->serie->longitud ?? 6;
        $num  = $f->numero !== null ? str_pad((string)$f->numero, $len, '0', STR_PAD_LEFT) : '';
        $pref = $f->prefijo ? "{$f->prefijo}-" : '';
        $this->asunto = "Factura {$pref}{$num}";

        $this->cc   = null;
        $this->show = true;

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

        $tmpRel = null;

        try {
            $factura = Factura::with('cliente','serie','detalles.producto','detalles.bodega')
                ->findOrFail($this->facturaId);

            // ====== Empresa desde tu tabla/configuración
            $empresa = $this->empresaActual(); // ← arreglo listo para la vista PDF

            // ====== PDF
            $len  = $factura->serie->longitud ?? 6;
            $num  = $factura->numero !== null ? str_pad((string)$factura->numero, $len, '0', STR_PAD_LEFT) : '000001';
            $pref = $factura->prefijo ? "{$factura->prefijo}-" : '';
            $name = "FACT-{$pref}{$num}.pdf";

            $pdf = Pdf::loadView('pdf.factura', [
                'factura' => $factura,
                'empresa' => $empresa,
            ])->setPaper('a4');

            $tmpRel = "tmp/{$name}";
            Storage::put($tmpRel, $pdf->output());
            $tmpAbs = Storage::path($tmpRel);

            // ====== CCs válidos
            $ccs = collect(preg_split('/[;, ]+/', (string)$this->cc, -1, PREG_SPLIT_NO_EMPTY))
                ->filter(fn($v) => filter_var($v, FILTER_VALIDATE_EMAIL))
                ->values()
                ->all();

            // ====== Envío
            Mail::to($this->para)
                ->cc($ccs)
                ->send(new FacturaPdfMail(
                    factura: $factura,
                    pdfPath: $tmpAbs,
                    asunto: (string)($this->asunto ?: "Factura {$pref}{$num}")
                ));

            // Éxito
            PendingToast::create()->success()->message('Factura enviada con éxito.')->duration(6000);
            $this->show = false;

            // refresca el form si está abierto
            $this->dispatch('$refresh')->to(\App\Livewire\Facturas\FacturaForm::class);

        } catch (\Throwable $e) {
            $msg = mb_strimwidth($e->getMessage() ?: 'Error desconocido', 0, 220, '…');
            PendingToast::create()->error()->message('No se pudo enviar la factura: ' . $msg)->duration(11000);
        } finally {
            if ($tmpRel && Storage::exists($tmpRel)) {
                Storage::delete($tmpRel);
            }
        }
    }

    /**
     * Construye el arreglo de empresa a partir de tu modelo Empresa (tabla `empresas`).
     * - Toma la empresa activa (`is_activa = 1`) o, si no hay, la primera.
     * - Convierte `logo_path`/`logo_dark_path` a data URL si el archivo existe en disk `public`.
     */
    private function empresaActual(): array
    {
        $e = Empresa::where('is_activa', true)->first() ?: Empresa::first();

        if (!$e) {
            // Fallback sin registros
            return [
                'nombre'         => 'Mi Empresa',
                'logo_src'       => null,
                'email'          => null,
                'website'        => null,
                'nit'            => null,
                'telefono'       => null,
                'direccion'      => null,
                'color_primario' => '#223361',
            ];
        }

        // Elige logo dark si existe, si no el normal
        $logo = $this->toDataUrlFromPublic($e->logo_dark_path ?: $e->logo_path);

        return [
            'nombre'         => (string)$e->nombre,
            'logo_src'       => $logo,
            'email'          => $e->email ?: null,
            'website'        => $e->sitio_web ?: null,
            'nit'            => $e->nit ?: null,
            'telefono'       => $e->telefono ?: null,
            'direccion'      => $e->direccion ?: null,
            'color_primario' => $e->color_primario ?: '#223361',
        ];
    }

    /**
     * Si $path es URL absoluta => la devuelve tal cual.
     * Si es ruta relativa en disk `public` => devuelve data URL si existe; si no, null.
     */
    private function toDataUrlFromPublic(?string $path): ?string
    {
        if (!$path) return null;

        if (preg_match('#^https?://#i', $path)) {
            return $path; // ya es URL
        }

        $normalized = preg_replace('#^storage/#', '', ltrim($path, '/')); // admite "storage/..."
        $disk = Storage::disk('public');

        if (!$disk->exists($normalized)) return null;

        $abs  = $disk->path($normalized);
        $ext  = strtolower(pathinfo($abs, PATHINFO_EXTENSION));
        $mime = $ext === 'jpg' ? 'jpeg' : $ext;

        $data = @file_get_contents($abs);
        if ($data === false) return null;

        return 'data:image/'.$mime.';base64,'.base64_encode($data);
    }

    public function render()
    {
        return view('livewire.facturas.enviar-factura');
    }
}
