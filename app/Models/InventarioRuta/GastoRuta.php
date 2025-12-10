<?php

namespace App\Models\InventarioRuta;

use App\Models\Finanzas\TipoGasto;
use App\Models\Ruta\Ruta;
use App\Models\User;
use App\Models\Conceptos\ConceptoDocumento;
use Illuminate\Database\Eloquent\Model;

class GastoRuta extends Model
{
    protected $table = 'gastos_ruta';

    protected $fillable = [
        'ruta_id',
        'user_id',
        'tipo_gasto_id',
        'concepto_documento_id',   
          'caja_movimiento_id',  
        'monto',
        'observacion',
    ];

    public function ruta()
    {
        return $this->belongsTo(Ruta::class);
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function tipoGasto()
    {
        return $this->belongsTo(TipoGasto::class, 'tipo_gasto_id');
    }

    public function conceptoDocumento()
    {
        return $this->belongsTo(ConceptoDocumento::class, 'concepto_documento_id');
    }
       public function cajaMovimiento()
    {
        return $this->belongsTo(\App\Models\TurnosCaja\CajaMovimiento::class);
    }
}
