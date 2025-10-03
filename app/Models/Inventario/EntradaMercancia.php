<?php

namespace App\Models\Inventario;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Inventario\EntradaDetalle; // <-- ESTE USE FALTABA
use App\Models\SocioNegocio\SocioNegocio;

class EntradaMercancia extends Model
{
    use HasFactory;

    protected $table = 'entradas_mercancia';

    protected $fillable = [
        'socio_negocio_id',
        'fecha_contabilizacion',
        'lista_precio',
        'observaciones',
    ];

    public function socioNegocio()
    {
        return $this->belongsTo(SocioNegocio::class);
    }

    public function detalles()
    {
        return $this->hasMany(EntradaDetalle::class, 'entrada_mercancia_id');
    }
}
