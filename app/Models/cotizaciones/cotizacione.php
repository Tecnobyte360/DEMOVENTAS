<?php

namespace App\Models\cotizaciones;

use Illuminate\Database\Eloquent\Model;
use App\Models\SocioNegocio\SocioNegocio;
use App\Models\User;

class cotizacione extends Model
{
    protected $table = 'cotizaciones';

    protected $fillable = [
        'socio_negocio_id',
        'fecha',
        'vencimiento',
        'lista_precio',
        'terminos_pago',
        'estado',
        'notas',
        'subtotal',
        'impuestos',
        'total',
        'pedido_id',
        'aprobada_at',
        'aprobada_por',
        'creado_por_id',
        'actualizado_por_id',
        'anulado_por_id',
        'anulado_en',
        'emitido_por_id',
        'emitido_en',
    ];

    public function detalles()
    {
        return $this->hasMany(cotizacion_detalle::class, 'cotizacion_id');
    }

    public function cliente()
    {
        return $this->belongsTo(SocioNegocio::class, 'socio_negocio_id');
    }

    public function creadoPor()
    {
        return $this->belongsTo(User::class, 'creado_por_id');
    }

    public function actualizadoPor()
    {
        return $this->belongsTo(User::class, 'actualizado_por_id');
    }

    public function anuladoPor()
    {
        return $this->belongsTo(User::class, 'anulado_por_id');
    }

    public function emitidoPor()
    {
        return $this->belongsTo(User::class, 'emitido_por_id');
    }

    public function getNumeroAttribute(): string
    {
        return 'S' . str_pad($this->id, 5, '0', STR_PAD_LEFT);
    }

    public function recalcularTotales(): void
    {
        $sub = $this->detalles()->sum('importe');
        $imp = $this->detalles->sum(function ($d) {
            $base = ($d->cantidad * $d->precio_unitario) * (1 - $d->descuento_pct / 100);
            return $base * ($d->impuesto_pct / 100);
        });

        $this->subtotal  = $sub;
        $this->impuestos = $imp;
        $this->total     = $sub + $imp;
        $this->save();
    }
    public function facturas()
    {
        return $this->hasMany(\App\Models\Factura\Factura::class, 'cotizacion_id');
    }
    public function socioNegocio()
    {
        return $this->belongsTo(\App\Models\SocioNegocio\SocioNegocio::class, 'socio_negocio_id');
    }
}
