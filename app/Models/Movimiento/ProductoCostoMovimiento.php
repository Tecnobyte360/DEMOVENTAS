<?php

namespace App\Models\Movimiento;

use Illuminate\Database\Eloquent\Model;

class ProductoCostoMovimiento extends Model
{
    protected $table = 'producto_costo_movimientos';

    protected $fillable = [
        'fecha',
        'producto_id','bodega_id',
        'tipo_documento_id','doc_id','ref',
        'cantidad','valor_mov','costo_unit_mov',
        'metodo_costeo',
        'costo_prom_anterior','costo_prom_nuevo',
        'ultimo_costo_anterior','ultimo_costo_nuevo',
        'tipo_evento','user_id',
    ];

    protected $casts = [
        'fecha' => 'datetime',
    ];

    public function tipoDocumento()
    {
        return $this->belongsTo(\App\Models\TiposDocumento\TipoDocumento::class, 'tipo_documento_id');
    }
}
