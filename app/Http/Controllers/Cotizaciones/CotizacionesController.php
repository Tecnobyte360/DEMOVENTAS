<?php

namespace App\Http\Controllers\Cotizaciones;

use App\Http\Controllers\Controller;
use App\Models\cotizaciones\cotizacione;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class CotizacionesController extends Controller
{
 public function printView(int $id)
{
    $cotizacion = cotizacione::with('cliente', 'items')->findOrFail($id);
    $pdf = Pdf::loadView('pdfs.cotizacion', compact('cotizacion'));

    // IMPORTANTE: stream() para que QZ Tray pueda leerlo por URL
    return $pdf->stream("cotizacion-{$id}.pdf");
}

}
