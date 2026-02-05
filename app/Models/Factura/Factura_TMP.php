<?php

namespace App\Models\Factura;

use App\Models\MediosPago\MedioPagos;
use App\Models\Serie\Serie;
use App\Models\SocioNegocio\SocioNegocio;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class Factura extends Model
{
    protected $table = 'facturas';

    protected $fillable = [
        'serie_id',
        'numero',
        'prefijo',
        'socio_negocio_id',
        'cotizacion_id',
        'pedido_id',
        'fecha',
        'vencimiento',
        'moneda',
        'tipo_pago',
        'plazo_dias',
        'subtotal',
        'impuestos',
        'total',
        'pagado',
        'saldo',
        'estado',
        'terminos_pago',
        'notas',
        'pdf_path',
        'cuenta_cobro_id',
        'condicion_pago_id'
    ];

    protected $casts = [
        'fecha'       => 'date',
        'vencimiento' => 'date',
        'subtotal'    => 'decimal:2',
        'impuestos'   => 'decimal:2',
        'total'       => 'decimal:2',
        'pagado'      => 'decimal:2',
        'saldo'       => 'decimal:2',
    ];

    /* ----------------- Hooks: asegura prefijo ----------------- */
    protected static function booted(): void
    {
        // Al crear: si hay serie y prefijo nulo, tómalo de la serie
        static::creating(function (self $f) {
            if (empty($f->prefijo) && !empty($f->serie_id)) {
                $f->prefijo = Serie::whereKey($f->serie_id)->value('prefijo');
            }
        });

        // Al actualizar serie en un registro existente sin prefijo, repónlo
        static::updating(function (self $f) {
            if ($f->isDirty('serie_id') && empty($f->prefijo) && !empty($f->serie_id)) {
                $f->prefijo = Serie::whereKey($f->serie_id)->value('prefijo');
            }
        });
    }

    /* ----------------- Relaciones ----------------- */

    public function serie(): BelongsTo
    {
        return $this->belongsTo(Serie::class, 'serie_id');
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(SocioNegocio::class, 'socio_negocio_id');
    }

    public function detalles(): HasMany
    {
        return $this->hasMany(FacturaDetalle::class, 'factura_id');
    }

    public function pagos(): HasMany
    {
        return $this->hasMany(FacturaPago::class, 'factura_id');
    }

    /* --------------- Helpers de pago/fechas --------------- */

    public function setContado(): self
    {
        $this->tipo_pago   = 'contado';
        $this->plazo_dias  = null;
        $this->vencimiento = $this->fecha ?: now()->toDateString();
        return $this;
    }

    public function setCredito(int $dias = 30): self
    {
        $this->tipo_pago   = 'credito';
        $this->plazo_dias  = $dias;
        $base              = $this->fecha ?: now();
        $this->vencimiento = Carbon::parse($base)->addDays($dias)->toDateString();
        return $this;
    }

    public function getVencidaAttribute(): bool
    {
        return $this->saldo > 0
            && $this->vencimiento
            && now()->toDateString() > $this->vencimiento->toDateString();
    }

    /** Número formateado con prefijo y longitud de la serie. */
    public function getNumeroFormateadoAttribute(): ?string
    {
        if (!$this->numero) return null;
        $len = $this->serie?->longitud ?? 6;
        $num = str_pad((string)$this->numero, $len, '0', STR_PAD_LEFT);
        return $this->prefijo ? "{$this->prefijo}-{$num}" : $num;
    }

    /* --------------- Operaciones de negocio --------------- */

    /** Agrega una línea y recalcula totales. */
    public function agregarLinea(array $data): FacturaDetalle
    {
        $detalle = $this->detalles()->create($data);
        $this->recalcularTotales()->save();
        return $detalle;
    }

    /** Recalcula subtotal/impuestos/total/pagado/saldo y ajusta estado. */
    public function recalcularTotales(): self
    {
        $sub = (float) $this->detalles()->sum('importe_base');
        $imp = (float) $this->detalles()->sum('importe_impuesto');
        $tot = (float) $this->detalles()->sum('importe_total');

        $pag = (float) $this->pagos()->sum('monto');
        $sal = max($tot - $pag, 0);

        $this->subtotal  = $sub;
        $this->impuestos = $imp;
        $this->total     = $tot;
        $this->pagado    = $pag;
        $this->saldo     = $sal;

        if ($this->estado !== 'anulada') {
            if ($tot <= 0)       $this->estado = 'borrador';
            elseif ($sal <= 0)  $this->estado = 'pagada';
            elseif ($pag > 0)   $this->estado = 'parcialmente_pagada';
            else                $this->estado = 'emitida';
        }

        return $this;
    }

    public function registrarPago(array $data): FacturaPago
    {
        $pago = $this->pagos()->create($data);
        $this->recalcularTotales()->save();
        return $pago;
    }
    public function cuentaCobro()
    {
        return $this->belongsTo(\App\Models\CuentasContables\PlanCuentas::class, 'cuenta_cobro_id');
    }
    public function socioNegocio(): BelongsTo
    {
        return $this->belongsTo(SocioNegocio::class, 'socio_negocio_id');
    }
    public function registrarPagosDistribuidos(array $items, string $fecha, ?string $notas = null): void
    {
        DB::transaction(function () use ($items, $fecha, $notas) {
            foreach ($items as $i) {
                $medioId = $i['medio_pago_id'] ?? null;
                $medio   = $medioId ? MedioPagos::find($medioId) : null;

                $this->pagos()->create([
                    'fecha'         => $fecha,
                    'medio_pago_id' => $medioId,
                    'metodo'        => $medio?->codigo,               
                    'monto'         => (float) ($i['monto'] ?? 0),
                    'referencia'    => $i['referencia'] ?? null,
                    'notas'         => $notas,
                ]);
            }

            $this->recalcularTotales()->save();
        });
    }
}
