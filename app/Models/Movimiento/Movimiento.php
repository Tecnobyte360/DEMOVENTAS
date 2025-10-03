<?php

namespace App\Models\Movimiento;

use App\Models\Asiento\Asiento;
use App\Models\CuentasContables\PlanCuentas;
use App\Models\Factura\Factura;
use App\Models\SocioNegocio\SocioNegocio;
// ⬇️ Usa UNA sola clase de Impuesto. Ajusta el namespace si tu proyecto usa otro.
use App\Models\Impuestos\Impuesto as Impuesto; // <-- AJUSTA AQUÍ

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Movimiento extends Model
{
    protected $table = 'movimientos';

    protected $fillable = [
        'asiento_id',
        'cuenta_id',
        'tercero_id',
        'factura_id',
        'factura_detalle_id',
        'impuesto_id',
        'centro_costo_id',
        'bodega_id',

        'debito',
        'credito',
        'debe',
        'haber',
        'detalle',

        'base_gravable',
        'tarifa_pct',
        'valor_impuesto',
    ];

    protected $casts = [
        'debito'         => 'decimal:2',
        'credito'        => 'decimal:2',
        'debe'           => 'decimal:2',
        'haber'          => 'decimal:2',
        'base_gravable'  => 'decimal:2',
        'tarifa_pct'     => 'decimal:4',
        'valor_impuesto' => 'decimal:2',
    ];

    /* ================== RELACIONES ================== */

    public function asiento(): BelongsTo
    {
        return $this->belongsTo(Asiento::class, 'asiento_id');
    }

    public function cuenta(): BelongsTo
    {
        return $this->belongsTo(PlanCuentas::class, 'cuenta_id');
    }

    public function factura(): BelongsTo
    {
        return $this->belongsTo(Factura::class, 'factura_id');
    }

    public function impuesto(): BelongsTo
    {
        return $this->belongsTo(Impuesto::class, 'impuesto_id'); // usa el namespace correcto
    }

    public function tercero(): BelongsTo
    {
        return $this->belongsTo(SocioNegocio::class, 'tercero_id');
    }

    /* ================== ACCESORES ÚTILES (opcionales) ================== */

    // Para mostrar el código del impuesto directamente en la vista: $movimiento->impuesto_codigo
    public function getImpuestoCodigoAttribute(): ?string
    {
        return $this->relationLoaded('impuesto')
            ? ($this->impuesto->codigo ?? null)
            : optional($this->impuesto)->codigo;
    }
}
