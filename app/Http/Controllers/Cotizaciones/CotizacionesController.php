<?php

namespace App\Http\Controllers\Cotizaciones;

use App\Http\Controllers\Controller;
use App\Models\cotizaciones\cotizacione;

class CotizacionesController extends Controller
{
    public function print(int $id)
    {
        $cotizacion = cotizacione::with(['cliente'])->findOrFail($id);

        return view('pdf.cotizacion', [
            'cotizacion' => $cotizacion,
            'autoPrint'  => true,
        ]);
    }
}