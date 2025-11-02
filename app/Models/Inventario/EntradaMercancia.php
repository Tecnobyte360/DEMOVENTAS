<?php

namespace App\Models\Inventario;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Inventario\EntradaDetalle; 
use App\Models\SocioNegocio\SocioNegocio;
use App\Models\Serie\Serie;

class EntradaMercancia extends Model
{
    use HasFactory;

    protected $table = 'entradas_mercancia';

    protected $fillable = [
        'socio_negocio_id',
        'fecha_contabilizacion',
        'lista_precio',
        'observaciones',
        'estado',          // ← NUEVO
        'serie_id',        // ← NUEVO
        'prefijo',         // ← NUEVO
        'numero',          // ← NUEVO
    ];

    protected $casts = [
        'fecha_contabilizacion' => 'datetime',
        'numero'                => 'integer',
    ];

    public function socioNegocio()
    {
        return $this->belongsTo(SocioNegocio::class);
    }

    public function detalles()
    {
        return $this->hasMany(EntradaDetalle::class, 'entrada_mercancia_id');
    }

    public function serie()
    {
        return $this->belongsTo(Serie::class, 'serie_id');
    }

public function concepto()
{
    return $this->belongsTo(\App\Models\Conceptos\ConceptoDocumento::class, 'concepto_documento_id');
}

}
