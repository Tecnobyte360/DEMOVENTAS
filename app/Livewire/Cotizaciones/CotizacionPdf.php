<?php

namespace App\Livewire\Cotizaciones;

use Livewire\Component;
use App\Models\cotizaciones\cotizacione;
use Barryvdh\DomPDF\Facade\Pdf;

class CotizacionPdf extends Component
{
    public int $id;

    public function mount(int $id): void
    {
        $this->id = $id;
    }

    public function render()
    {
        // 1) Cargar datos
        $cotizacion = cotizacione::with(['cliente'])->findOrFail($this->id);

        // 2) Generar PDF desde una vista Blade (puede ser otra vista, no la de Livewire)
        $pdf = Pdf::loadView('pdf.cotizaciones.cotizacion', compact('cotizacion'))
            ->setPaper('letter');

        // 3) Responder el PDF (stream = abre en pestaña)
        return response()->streamDownload(
            fn () => print($pdf->output()),
            "Cotizacion_{$this->id}.pdf",
            ['Content-Type' => 'application/pdf']
        );
    }
}
