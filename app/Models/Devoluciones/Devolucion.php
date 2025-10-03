<?php

namespace App\Models\Devoluciones;

use App\Models\Ruta\Ruta;
use App\Models\SocioNegocio\SocioNegocio;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class Devolucion extends Model
{

    protected $table = 'devoluciones';
    protected $fillable = [
        'ruta_id',
        'user_id',
        'socio_negocio_id',
        'fecha',
        'observaciones',
    ];

    public function detalles()
    {
        return $this->hasMany(DevolucionDetalle::class);
    }

    public function ruta()
    {
        return $this->belongsTo(Ruta::class);
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
    
public function socioNegocio()
{
    return $this->belongsTo(SocioNegocio::class, 'socio_negocio_id');
}
}
