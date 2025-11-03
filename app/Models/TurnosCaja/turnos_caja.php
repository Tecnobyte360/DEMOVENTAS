<?php

namespace App\Models\TurnosCaja;

use App\Models\Factura\FacturaPago;
use App\Models\User;
use App\Models\Bodega;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class turnos_caja extends Model
{
    protected $table = 'turnos_caja';

    protected $fillable = [
        'user_id',
        'bodega_id',
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
        'fecha_inicio' => 'datetime',
        'fecha_cierre' => 'datetime',
        'base_inicial' => 'decimal:2',
        'total_ventas' => 'decimal:2',
        'ventas_efectivo' => 'decimal:2',
        'ventas_debito' => 'decimal:2',
        'ventas_credito_tarjeta' => 'decimal:2',
        'ventas_transferencias' => 'decimal:2',
        'ventas_a_credito' => 'decimal:2',
        'devoluciones' => 'decimal:2',
        'ingresos_efectivo' => 'decimal:2',
        'retiros_efectivo' => 'decimal:2',
        'resumen' => 'array',
    ];

    // Relaciones
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function bodega(): BelongsTo
    {
        return $this->belongsTo(Bodega::class);
    }

    public function pagos(): HasMany
    {
        return $this->hasMany(FacturaPago::class, 'turno_id');
    }

    public function movimientos(): HasMany
    {
        return $this->hasMany(CajaMovimiento::class, 'turno_id');
    }

    // Scopes
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

    public function scopeDeBodega($query, int $bodegaId)
    {
        return $query->where('bodega_id', $bodegaId);
    }

    // Helpers
    public function estaAbierto(): bool
    {
        return $this->estado === 'abierto';
    }

    public function estaCerrado(): bool
    {
        return $this->estado === 'cerrado';
    }

    /**
     * Calcula el efectivo esperado en caja
     */
    public function efectivoEsperado(): float
    {
        return (float) $this->base_inicial
            + (float) $this->ventas_efectivo
            + (float) $this->ingresos_efectivo
            - (float) $this->retiros_efectivo
            - (float) $this->devoluciones;
    }

    /**
     * Calcula el total de ventas cobradas (sin CXC)
     */
    public function totalCobrado(): float
    {
        return (float) $this->ventas_efectivo
            + (float) $this->ventas_debito
            + (float) $this->ventas_credito_tarjeta
            + (float) $this->ventas_transferencias;
    }
}