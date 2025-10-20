<?php

namespace App\Models\TurnosCaja;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CajaMovimiento extends Model
{
    protected $table = 'cajamovimientos';

    protected $fillable = ['turno_id','user_id','tipo','monto','motivo'];

    public function turno(): BelongsTo
    {
        return $this->belongsTo(turnos_caja::class, 'turno_id');
    }
}
