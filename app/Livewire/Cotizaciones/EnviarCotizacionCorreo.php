<?php

namespace App\Livewire\Cotizaciones;

use Livewire\Component;
use Livewire\Attributes\On;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;
use Barryvdh\DomPDF\Facade\Pdf;
use Masmerise\Toaster\PendingToast;
use App\Models\cotizaciones\cotizacione;
use App\Models\SocioNegocio\SocioNegocio;
use App\Models\ConfiguracionEmpresas\Empresa;

class EnviarCotizacionCorreo extends Component
{
    public ?int $cotizacionId = null;

    public bool $show = false;
    public string $email_to = '';
    public string $email_cc = '';
    public string $email_subject = '';
    public string $email_body = '';
    public ?string $email_attachment_path = null;
    public ?string $email_attachment_name = null;

    public function mount(?int $cotizacionId = null): void
    {
        $this->cotizacionId = $cotizacionId;
        $this->resetModal();
    }

    public function render()
    {
        return view('livewire.cotizaciones.enviar-cotizacion-correo');
    }

    #[On('abrir-modal-enviar')]
    public function abrir(?int $cotizacionId = null): void
    {
        if ($cotizacionId) $this->cotizacionId = $cotizacionId;
        if (!$this->cotizacionId) return; // guard sencillo

        $c = cotizacione::with('detalles')->findOrFail($this->cotizacionId);

        $ref   = 'S'.str_pad((string)$c->id, 5, '0', STR_PAD_LEFT);
        $total = number_format(($c->subtotal + $c->impuestos), 2, '.', ',');

        $cliente = SocioNegocio::find($c->socio_negocio_id);
        $this->email_to = trim((string)($cliente->correo ?? ''));

        $empNombre = (Empresa::where('is_activa', true)->value('nombre')) ?? 'TECNOBYTE360';
        $this->email_subject = "{$empNombre} · Cotización (Ref {$ref})";
        $this->email_body =
            "Hola,\n\n".
            "Su cotización {$ref} por un total de \$ {$total} está lista para su revisión.\n\n".
            "No dude en contactarnos si tiene alguna pregunta.\n\n".
            "--\n".
            "{$empNombre}";

        // Generar PDF
        [$path, $name] = $this->generarPdf($c, $ref);
        $this->email_attachment_path = $path;
        $this->email_attachment_name = $name;

        $this->show = true;
    }

    protected function generarPdf(cotizacione $c, string $ref): array
    {
        $fileName = "Cotización - {$ref}.pdf";
        $path = storage_path('app/tmp/'.Str::uuid().'.pdf');

        $emp = Empresa::where('is_activa', true)->first() ?? Empresa::first();

        $logoSrc = null;
        if ($emp?->logo_path) {
            $abs = Storage::disk('public')->path($emp->logo_path);
            if (is_file($abs)) {
                $ext = pathinfo($abs, PATHINFO_EXTENSION) ?: 'png';
                $logoSrc = 'data:image/'.$ext.';base64,'.base64_encode(file_get_contents($abs));
            }
        }

        $empresa = [
            'nombre'         => $emp->nombre ?? 'TECNOBYTE360',
            'nit'            => $emp->nit ?? null,
            'direccion'      => $emp->direccion ?? null,
            'telefono'       => $emp->telefono ?? null,
            'email'          => $emp->email ?? null,
            'website'        => $emp->sitio_web ?? null,
            'color_primario' => $emp->color_primario ?: '#223361',
            'logo_src'       => $logoSrc,
        ];

        $pdf = Pdf::loadView('pdf.cotizacion', [
            'cotizacion' => $c->fresh('detalles'),
            'lineas'     => $c->detalles->map(function($d){
                return [
                    'producto_id'     => $d->producto_id,
                    'bodega_id'       => $d->bodega_id,
                    'cantidad'        => (float)$d->cantidad,
                    'precio_unitario' => (float)$d->precio_unitario,
                    'descuento_pct'   => (float)$d->descuento_pct,
                    'impuesto_pct'    => (float)$d->impuesto_pct,
                    'importe'         => (float)$d->importe,
                ];
            })->toArray(),
            'ref'     => $ref,
            'empresa' => $empresa,
        ])->setPaper('letter');

        @mkdir(dirname($path), 0775, true);
        $pdf->save($path);

        return [$path, $fileName];
    }

    public function cerrar(): void
    {
        $this->show = false;
    }

    public function enviarCorreo(): void
    {
        $this->validate([
            'email_to'      => 'required|email',
            'email_subject' => 'required|string|max:255',
            'email_body'    => 'required|string',
        ], [], [
            'email_to' => 'destinatario',
            'email_subject' => 'asunto',
            'email_body' => 'mensaje',
        ]);

        try {
            $c  = cotizacione::findOrFail($this->cotizacionId);
            $to = $this->email_to;
            $cc = array_filter(array_map('trim', explode(',', $this->email_cc ?? '')));

            Mail::send([], [], function ($message) use ($to, $cc) {
                $message->to($to)
                        ->subject($this->email_subject)
                        ->html(nl2br(e($this->email_body)));

                foreach ($cc as $addr) {
                    if (filter_var($addr, FILTER_VALIDATE_EMAIL)) {
                        $message->cc($addr);
                    }
                }

                if ($this->email_attachment_path && is_file($this->email_attachment_path)) {
                    $message->attach($this->email_attachment_path, [
                        'as'   => $this->email_attachment_name ?? 'cotizacion.pdf',
                        'mime' => 'application/pdf',
                    ]);
                }
            });

            $c->update(['estado' => 'enviada']);
            $this->show = false;
            PendingToast::create()->success()->message('Cotización enviada por correo.')->duration(5000);

        } catch (Throwable $e) {
            report($e);
            $msg = config('app.debug') ? $e->getMessage() : 'Ocurrió un error al enviar.';
            PendingToast::create()->error()->message($msg)->duration(8000);
        }
    }

    public function quitarAdjunto(): void
    {
        $this->email_attachment_path = null;
        $this->email_attachment_name = null;
    }

    public function regenerarAdjunto(): void
    {
        $c = cotizacione::with('detalles')->findOrFail($this->cotizacionId);
        $ref = 'S'.str_pad((string)$c->id, 5, '0', STR_PAD_LEFT);
        [$path, $name] = $this->generarPdf($c, $ref);
        $this->email_attachment_path = $path;
        $this->email_attachment_name = $name;
    }

    /** -------- manejo de estado del modal -------- */
    private function resetModal(): void
    {
        $this->reset([
            'show',
            'email_to', 'email_cc', 'email_subject', 'email_body',
            'email_attachment_path', 'email_attachment_name',
        ]);
        $this->show = false;
    }

    public function updatedCotizacionId(): void
    {
        $this->resetModal();
    }

    #[On('cerrar-modal-enviar')]
    public function cerrarPorEvento(): void
    {
        $this->resetModal();
    }
}
