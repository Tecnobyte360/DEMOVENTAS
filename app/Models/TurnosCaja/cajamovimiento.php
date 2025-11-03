<?php

namespace App\Models\TurnosCaja;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CajaMovimiento extends Model
{
    protected $table = 'caja_movimientos';

    protected $fillable = [
        'turno_id',
        'user_id',
        'tipo',
        'monto',
        'motivo',
    ];

    protected $casts = [
        'monto' => 'decimal:2',
    ];

    public function turno(): BelongsTo
    {
        return $this->belongsTo(turnos_caja::class, 'turno_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Helpers
    public function esIngreso(): bool
    {
        return $this->tipo === 'INGRESO';
    }

    public function esRetiro(): bool
    {
        return $this->tipo === 'RETIRO';
    }

    public function esDevolucion(): bool
    {
        return $this->tipo === 'DEVOLUCION';
    }
}
