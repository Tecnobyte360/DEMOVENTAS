<?php

namespace App\Models\TurnosCaja;

use App\Models\Factura\FacturaPago;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class turnos_caja extends Model
{
    protected $table = 'turnos_caja';

    protected $fillable = [
        'user_id',
        'abierto_por_id',
        'cerrado_por_id',
        'fecha_inicio',
        'fecha_cierre',
        'base_inicial',
        'total_ventas',
        'ventas_efectivo',
        'ventas_debito',
        'ventas_credito_tarjeta',
        'ventas_transferencias',
        'ventas_a_credito',
        'devoluciones',
        'ingresos_efectivo',
        'retiros_efectivo',
        'estado',
        'resumen',
    ];

    protected $casts = [
        'fecha_inicio'           => 'datetime',
        'fecha_cierre'           => 'datetime',
        'base_inicial'           => 'decimal:2',
        'total_ventas'           => 'decimal:2',
        'ventas_efectivo'        => 'decimal:2',
        'ventas_debito'          => 'decimal:2',
        'ventas_credito_tarjeta' => 'decimal:2',
        'ventas_transferencias'  => 'decimal:2',
        'ventas_a_credito'       => 'decimal:2',
        'devoluciones'           => 'decimal:2',
        'ingresos_efectivo'      => 'decimal:2',
        'retiros_efectivo'       => 'decimal:2',
        'resumen'                => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function abiertoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'abierto_por_id');
    }

    public function cerradoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cerrado_por_id');
    }

    public function pagos(): HasMany
    {
        return $this->hasMany(FacturaPago::class, 'turno_id');
    }

    public function movimientos(): HasMany
    {
        return $this->hasMany(CajaMovimiento::class, 'turno_id');
    }

    public function scopeAbierto($query)
    {
        return $query->where('estado', 'abierto');
    }

    public function scopeCerrado($query)
    {
        return $query->where('estado', 'cerrado');
    }

    public function scopeDeUsuario($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function estaAbierto(): bool
    {
        return $this->estado === 'abierto';
    }

    public function estaCerrado(): bool
    {
        return $this->estado === 'cerrado';
    }

    public function efectivoEsperado(): float
    {
        return (float) $this->base_inicial
            + (float) $this->ventas_efectivo
            + (float) $this->ingresos_efectivo
            - (float) $this->retiros_efectivo
            - (float) $this->devoluciones;
    }

    public function totalCobrado(): float
    {
        return (float) $this->ventas_efectivo
            + (float) $this->ventas_debito
            + (float) $this->ventas_credito_tarjeta
            + (float) $this->ventas_transferencias;
    }

    public static function turnoAbiertoDe(int $userId): ?self
    {
        return self::with(['abiertoPor:id,name', 'cerradoPor:id,name'])
            ->where('user_id', $userId)
            ->where('estado', 'abierto')
            ->latest('id')
            ->first();
    }
}