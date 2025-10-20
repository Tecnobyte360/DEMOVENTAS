<?php

namespace App\Models\TurnosCaja;

use App\Models\Factura\FacturaPago;
use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class turnos_caja extends Model
{
    protected $table = 'turnos_caja';

    protected $fillable = [
        'user_id','fecha_inicio','fecha_cierre','base_inicial',
        'total_ventas','ventas_efectivo','ventas_debito','ventas_credito_tarjeta',
        'ventas_transferencias','ventas_a_credito','devoluciones',
        'ingresos_efectivo','retiros_efectivo','estado','resumen'
    ];

    protected $casts = [
        'fecha_inicio' => 'datetime',
        'fecha_cierre' => 'datetime',
        'resumen'      => AsArrayObject::class,
    ];

    public function pagos(): HasMany
    {
        return $this->hasMany(FacturaPago::class, 'turno_id');
    }

    public function movimientos(): HasMany
    {
        return $this->hasMany(CajaMovimiento::class, 'turno_id');
    }

    /** Turnos abiertos por usuario (sin bodega) */
    public function scopeAbiertoDe($q, int $userId)
    {
        return $q->where('user_id', $userId)->where('estado', 'abierto');
    }
}
