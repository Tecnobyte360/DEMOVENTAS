<?php

namespace App\Models\InventarioRuta;

use App\Models\Finanzas\TipoGasto;
use App\Models\Ruta\Ruta;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class GastoRuta extends Model
{
    protected $table = 'gastos_ruta';

    protected $fillable = [
        'ruta_id',
        'user_id',
        'tipo_gasto_id',
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
  

}
