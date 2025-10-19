<?php

namespace App\Models\TurnosCaja;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class cajamovimiento extends Model
{
    protected $table = 'caja_movimientos';
    protected $fillable = ['turno_id','user_id','tipo','monto','motivo'];

    public function turno(): BelongsTo { return $this->belongsTo(turnos_caja::class, 'turno_id'); }
}
