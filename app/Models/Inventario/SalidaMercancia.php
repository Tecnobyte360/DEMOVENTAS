<?php

namespace App\Models\Inventario;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\User;
use App\Models\Ruta\Ruta;
use App\Models\SocioNegocio\SocioNegocio;
use App\Models\Inventario\SalidaMercanciaDetalle;

class SalidaMercancia extends Model
{
    use HasFactory;

    protected $table = 'salidas_mercancia';

    protected $fillable = [
        'ruta_id',
        'user_id',
        'socio_negocio_id',
        'fecha',
        'observaciones',
    ];

    protected $casts = [
        'fecha' => 'date',
    ];

    // Relaciones

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

    public function detalles()
    {
        return $this->hasMany(SalidaMercanciaDetalle::class, 'salida_mercancia_id');
    }
}
