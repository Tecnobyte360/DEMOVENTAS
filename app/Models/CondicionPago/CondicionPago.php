<?php
// app/Models/CondicionPago/CondicionPago.php
namespace App\Models\CondicionPago;

use Illuminate\Database\Eloquent\Model;

class CondicionPago extends Model
{
    protected $table = 'condicion_pagos';  
    protected $fillable = [
        'nombre','tipo','plazo_dias','interes_mora_pct','limite_credito',
        'tolerancia_mora_dias','dia_corte','notas','activo',
    ];
    protected $casts = [
        'activo' => 'bool',
        'plazo_dias' => 'int',
        'tolerancia_mora_dias' => 'int',
        'dia_corte' => 'int',
        'interes_mora_pct' => 'decimal:3',
        'limite_credito' => 'decimal:2',
    ];

     public function socios()
    {
        return $this->hasMany(\App\Models\SocioNegocio\SocioNegocio::class, 'condicion_pago_id');
    }
}
