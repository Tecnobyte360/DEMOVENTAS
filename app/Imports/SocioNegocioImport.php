<?php

namespace App\Imports;

use App\Models\SocioNegocio\SocioNegocio;
use Maatwebsite\Excel\Concerns\ToModel;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;

class SocioNegocioImport implements ToCollection
{
    protected $errores;

    public function __construct(&$errores)
    {
        $this->errores = &$errores;
    }

    public function collection(Collection $rows)
    {
        foreach ($rows as $index => $row) {
            $nit = trim($row[1]);

            if (SocioNegocio::where('nit', $nit)->exists()) {
                $this->errores[] = "Fila ".($index + 1).": El NIT '{$nit}' ya está registrado.";
                continue;
            }

            SocioNegocio::create([
                'razon_social' => $row[0],
                'nit' => $row[1],
                'telefono_fijo' => $row[2],
                'telefono_movil' => $row[3],
                'direccion' => $row[4],
                'correo' => $row[5],
                'municipio_barrio' => $row[6],
                'saldo_pendiente' => $row[7],
                'Tipo' => $row[8],
            ]);
        }
    }
}
