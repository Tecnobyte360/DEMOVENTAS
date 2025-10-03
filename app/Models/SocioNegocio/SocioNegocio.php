<?php

namespace App\Models\SocioNegocio;

use Illuminate\Database\Eloquent\Model;
use App\Models\Municipio;
use App\Models\CondicionPago\CondicionPago;

class SocioNegocio extends Model
{
    protected $table = 'socio_negocios';

    protected $fillable = [
        // Datos base
        'razon_social','nit','telefono_fijo','telefono_movil','direccion','correo',
        'municipio_barrio','saldo_pendiente','Tipo',
        'tipo_persona','regimen_iva','regimen_simple','municipio_id',
        'actividad_economica','direccion_medios_magneticos',

        // FK nueva (opcional si ya la tienes)
        'condicion_pago_id',

        // Campos legado (si los mantienes en la tabla)
        'condicion_pago','plazo_dias','interes_mora_pct','limite_credito',
        'tolerancia_mora_dias','dia_corte',
    ];

    protected $casts = [
        'regimen_simple'        => 'boolean',
        'plazo_dias'            => 'integer',
        'interes_mora_pct'      => 'float',
        'limite_credito'        => 'float',
        'tolerancia_mora_dias'  => 'integer',
        'dia_corte'             => 'integer',
        'condicion_pago_id'     => 'integer',
    ];

    /* ====== Atributo Tipo (C/P) ====== */
    public function getTipoAttribute(): ?string
    {
        return isset($this->attributes['Tipo'])
            ? strtoupper(trim($this->attributes['Tipo']))
            : null;
    }
    public function setTipoAttribute($value): void
    {
        $this->attributes['Tipo'] = strtoupper(trim((string)$value));
    }

    /* ====== Relaciones ====== */
    public function cuentas()
    {
        return $this->hasOne(SocioNegocioCuenta::class, 'socio_negocio_id')->withDefault();
    }

    public function direcciones()
    {
        return $this->hasMany(\App\Models\SocioNegocio\SocioDireccion::class, 'socio_negocio_id');
    }

    public function pedidos()
    {
        return $this->hasMany(\App\Models\Pedidos\Pedido::class, 'socio_negocio_id');
    }

    public function municipio()
    {
        return $this->belongsTo(Municipio::class, 'municipio_id');
    }

    public function condicionPago()
    {
        return $this->belongsTo(CondicionPago::class, 'condicion_pago_id')->withDefault();
    }

    /* ====== Scopes / Helpers ====== */
    public function isCliente(): bool { return $this->tipo === 'C'; }
    public function isProveedor(): bool { return $this->tipo === 'P'; }
    public function scopeClientes($q){ return $q->where('Tipo','C'); }
    public function scopeProveedores($q){ return $q->where('Tipo','P'); }

    /** Condiciones de pago normalizadas (prioriza la FK; cae a legado) */
    public function getCondicionesPagoEfectivasAttribute(): array
    {
        if ($this->condicionPago && ($this->condicion_pago_id || $this->condicionPago->nombre)) {
            return [
                'id'                   => $this->condicionPago->id,
                'nombre'               => $this->condicionPago->nombre,
                'tipo'                 => $this->condicionPago->tipo,
                'plazo_dias'           => $this->condicionPago->plazo_dias,
                'interes_mora_pct'     => $this->condicionPago->interes_mora_pct,
                'limite_credito'       => $this->condicionPago->limite_credito,
                'tolerancia_mora_dias' => $this->condicionPago->tolerancia_mora_dias,
                'dia_corte'            => $this->condicionPago->dia_corte,
                'notas'                => $this->condicionPago->notas,
                'activo'               => (bool)$this->condicionPago->activo,
                'source'               => 'fk',
            ];
        }

        return [
            'id'                   => null,
            'nombre'               => null,
            'tipo'                 => $this->condicion_pago ?: 'contado',
            'plazo_dias'           => $this->plazo_dias,
            'interes_mora_pct'     => $this->interes_mora_pct,
            'limite_credito'       => $this->limite_credito,
            'tolerancia_mora_dias' => $this->tolerancia_mora_dias,
            'dia_corte'            => $this->dia_corte,
            'notas'                => null,
            'activo'               => true,
            'source'               => 'legacy',
        ];
    }

    public function admiteCredito(): bool
    {
        return strtolower((string) data_get($this, 'condiciones_pago_efectivas.tipo')) === 'credito';
    }

    public function plazoEfectivo(): ?int
    {
        return $this->admiteCredito()
            ? (int) (data_get($this, 'condiciones_pago_efectivas.plazo_dias') ?? 30)
            : null;
    }

    public function moraMensualPct(): ?float
    {
        $v = data_get($this, 'condiciones_pago_efectivas.interes_mora_pct');
        return is_null($v) ? null : (float) $v;
    }

    /* ====== Atajos direcciones ====== */
    public function direccionesEntrega()
    { return $this->direcciones()->where('tipo','entrega'); }

    public function direccionEntregaPrincipal()
    { return $this->direccionesEntrega()->where('es_principal', true)->first(); }

    public function getDireccionEntregaPrincipalAttribute()
    { return $this->direccionesEntrega()->where('es_principal', true)->first(); }

    /* ====== Alias ====== */
    public function getNombreAttribute()
    { return $this->razon_social; }
}
