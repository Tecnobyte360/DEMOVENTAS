<?php

namespace App\Http\Controllers\Cotizaciones;

use App\Http\Controllers\Controller;
use App\Models\cotizaciones\cotizacione;
use App\Models\ConfiguracionEmpresas\Empresa;
use Barryvdh\DomPDF\Facade\Pdf;

class CotizacionPdfController extends Controller
{
    public function __invoke(int $id)
    {
        // 1) Cotización con relaciones necesarias (incluye bodega)
        $cotizacion = cotizacione::with([
                'cliente',
                'detalles.producto',
                'detalles.bodega',
            ])
            ->findOrFail($id);

        // 2) Empresa activa (la misma lógica que usas en factura, si aplica)
        $empresa = Empresa::query()
            ->where('is_activa', true)
            ->first();

        // 3) Render PDF
        return Pdf::loadView('pdf.cotizacion', [
                'cotizacion' => $cotizacion,
                'empresa'    => $empresa,   // ✅ clave para que se vea igual a factura
                // 'ref'      => null,       // (opcional) si quieres forzar un folio custom
            ])
            ->stream('Cotizacion_'.$id.'.pdf');
    }
}
