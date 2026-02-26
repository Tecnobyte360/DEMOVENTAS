<?php

namespace App\Http\Controllers\Cotizaciones;

use App\Http\Controllers\Controller;
use App\Models\cotizaciones\cotizacione;
use App\Models\ConfiguracionEmpresas\Empresa;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class CotizacionPdfController extends Controller
{
    public function __invoke(Request $request, int $id)
    {
        $cotizacion = cotizacione::with([
                'cliente',
                'detalles.producto',
                'detalles.bodega',
            ])
            ->findOrFail($id);

        $empresa = Empresa::query()
            ->where('is_activa', true)
            ->first();

        $isPrint = $request->boolean('print'); 

        return Pdf::loadView('pdf.cotizacion', [
                'cotizacion' => $cotizacion,
                'empresa'    => $empresa,
                'isPrint'    => $isPrint,
            ])
            ->stream('Cotizacion_'.$id.'.pdf');
    }
}