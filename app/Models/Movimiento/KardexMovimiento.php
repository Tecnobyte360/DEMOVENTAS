<?php

namespace App\Models\Movimiento;

use App\Models\Bodega;
use App\Models\Productos\Producto;
use App\Models\TiposDocumento\TipoDocumento;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KardexMovimiento extends Model
{
   use HasFactory;

    protected $table = 'kardex_movimientos';

    protected $fillable = [
        'fecha',
        'producto_id',
        'bodega_id',
        'tipo_documento_id',
        'entrada',
        'salida',
        'cantidad',
        'signo',
        'costo_unitario',
        'total',
        'doc_id',
        'ref'
    ];

    protected $casts = [
        'fecha' => 'datetime',
        'entrada' => 'float',
        'salida' => 'float',
        'cantidad' => 'float',
        'signo' => 'integer',
        'costo_unitario' => 'float',
        'total' => 'float',
    ];

    public function tipoDocumento()
    {
        return $this->belongsTo(TipoDocumento::class, 'tipo_documento_id');
    }

    public function producto()
    {
        return $this->belongsTo(Producto::class);
    }

    public function bodega()
    {
        return $this->belongsTo(Bodega::class);
    }
}




 