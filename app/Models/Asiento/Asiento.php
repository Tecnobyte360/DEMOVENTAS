<?php

namespace App\Models\Asiento;

use App\Models\Movimiento\Movimiento;
use App\Models\SocioNegocio\SocioNegocio;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Asiento extends Model
{
    protected $table = 'asientos';

    protected $fillable = [
        'fecha',
        'glosa',
        'origen',
        'origen_id',
        'moneda',
        'total_debe',
        'total_haber',
        'tercero_id',
    ];

    protected $casts = [
        'fecha'       => 'date',
        'total_debe'  => 'decimal:2',
        'total_haber' => 'decimal:2',
    ];

    public function movimientos(): HasMany
    {
        return $this->hasMany(Movimiento::class);
    }

    public function tercero(): BelongsTo
    {
        return $this->belongsTo(SocioNegocio::class, 'tercero_id');
    }

    // Útil en listados
    public function getBalanceAttribute(): float
    {
        return round((float)($this->total_debe ?? 0) - (float)($this->total_haber ?? 0), 2);
    }
}
