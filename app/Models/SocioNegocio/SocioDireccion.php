<?php

namespace App\Models\SocioNegocio;

use App\Models\Municipio;
use Illuminate\Database\Eloquent\Model;

class SocioDireccion extends Model
{
    protected $table = 'socio_direcciones';

    protected $fillable = [
        'socio_negocio_id',
        'tipo',
        'direccion',
        'barrio',
        'municipio_id',   
        'contacto',
        'telefono',
        'es_principal',
        'activo',
    ];

    protected $casts = [
        'es_principal' => 'boolean',
        'activo'       => 'boolean',
    ];

    public function socio()
    {
        return $this->belongsTo(SocioNegocio::class, 'socio_negocio_id');
    }

    // Si tienes catálogo de municipios
    public function municipio()
    {
        return $this->belongsTo(Municipio::class, 'municipio_id');
    }
}
