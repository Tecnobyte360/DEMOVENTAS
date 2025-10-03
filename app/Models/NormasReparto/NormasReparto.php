<?php

namespace App\Models\NormasReparto;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NormasReparto extends Model
{
    // ✅ Define el nombre real de la tabla si no es el plural por defecto
    protected $table = 'normas_repartos';

    protected $fillable = [
        'codigo','descripcion','dimension',
        'valido_desde','valido_hasta',
        'valido','imputacion_directa','asignar_importes_fijos',
    ];

    protected $casts = [
        'valido' => 'boolean',
        'imputacion_directa' => 'boolean',
        'asignar_importes_fijos' => 'boolean',
        'valido_desde' => 'date',
        'valido_hasta' => 'date',
    ];

    // ✅ Si tu tabla NO tiene created_at/updated_at:
    // public $timestamps = false;

    public function detalles(): HasMany
    {
        return $this->hasMany(NormasRepartoDetalle::class, 'norma_reparto_id');
    }
}

