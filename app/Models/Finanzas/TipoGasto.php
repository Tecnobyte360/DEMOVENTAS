<?php

namespace App\Models\Finanzas;

use Illuminate\Database\Eloquent\Model;

class TipoGasto extends Model
{
    protected $table = 'tipos_gasto';

    protected $fillable = ['nombre', 'descripcion'];
}
