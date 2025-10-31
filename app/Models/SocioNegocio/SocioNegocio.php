<?php

namespace App\Models\SocioNegocio;

use Illuminate\Database\Eloquent\Model;
use App\Models\Municipio;
use App\Models\CondicionPago\CondicionPago;

class SocioNegocio extends Model
{
    protected $table = 'socio_negocios';

    protected $fillable = [
        'razon_social','nit','telefono_fijo','telefono_movil','direccion','correo',
        'municipio_barrio','saldo_pendiente','Tipo',
        'tipo_persona','regimen_iva','regimen_simple','municipio_id',
        'actividad_economica','direccion_medios_magneticos',

        // FK nueva (default del proveedor/cliente)
        'condicion_pago_id',

        // Campos legado (si los mantienes)
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
        return $this->hasMany(SocioDireccion::class, 'socio_negocio_id');
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
        // Si tu tabla solo tiene: id, nombre, dias
        return $this->belongsTo(CondicionPago::class, 'condicion_pago_id')->withDefault();
    }

    /* ====== Scopes / Helpers ====== */
    public function isCliente(): bool { return $this->tipo === 'C'; }
    public function isProveedor(): bool { return $this->tipo === 'P'; }
    public function scopeClientes($q){ return $q->where('Tipo','C'); }
    public function scopeProveedores($q){ return $q->where('Tipo','P'); }

    /**
     * Condiciones de pago normalizadas:
     * - Prefiere FK `condicion_pago_id` (lee `dias`).
     * - Si no hay FK, cae a los campos legado (`condicion_pago`, `plazo_dias`).
     */
    public function getCondicionesPagoEfectivasAttribute(): array
    {
        if ($this->relationLoaded('condicionPago') || $this->condicion_pago_id) {
            $cp = $this->condicionPago; // withDefault()
            return [
                'id'         => $cp->id,
                'nombre'     => $cp->nombre,
                'dias'       => (int) ($cp->dias ?? 0),
                'tipo'       => ((int)($cp->dias ?? 0) > 0) ? 'credito' : 'contado',
                'source'     => 'fk',
            ];
        }

        // Legado
        $dias = (int) ($this->plazo_dias ?? 0);
        return [
            'id'     => null,
            'nombre' => $this->condicion_pago,        // puede ser 'contado'/'crédito' o texto libre
            'dias'   => $dias,
            'tipo'   => $dias > 0 ? 'credito' : 'contado',
            'source' => 'legacy',
        ];
    }

    public function admiteCredito(): bool
    {
        return (int) data_get($this, 'condiciones_pago_efectivas.dias', 0) > 0;
    }

    public function plazoEfectivo(): int
    {
        return (int) data_get($this, 'condiciones_pago_efectivas.dias', 0);
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
