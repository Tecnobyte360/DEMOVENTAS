<?php

namespace App\Models\Vehiculo;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Ruta\Ruta;

class Vehiculo extends Model
{
    use HasFactory;

    protected $table = 'vehiculos';

    protected $fillable = [
        'placa',
        'modelo',
        'marca',
         'estado'
    ];

    public function rutas()
    {
        return $this->hasMany(Ruta::class);
    }
}
