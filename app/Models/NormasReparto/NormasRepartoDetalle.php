<?php

namespace App\Models\NormasReparto;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NormasRepartoDetalle extends Model
{
    protected $table = 'normas_reparto_detalles';

    protected $fillable = ['norma_reparto_id','codigo_centro','nombre_centro','valor'];

    // public $timestamps = false; // ← si aplica

    public function norma(): BelongsTo
    {
        return $this->belongsTo(NormasReparto::class, 'norma_reparto_id');
    }
}
