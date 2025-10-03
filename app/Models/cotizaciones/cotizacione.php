<?php

namespace App\Models\cotizaciones;

use Illuminate\Database\Eloquent\Model;
use App\Models\SocioNegocio\SocioNegocio;

class cotizacione extends Model
{
    protected $table = 'cotizaciones';

    protected $fillable = [
        'socio_negocio_id','fecha','vencimiento','lista_precio','terminos_pago',
        'estado','notas','subtotal','impuestos','total','pedido_id',
        'aprobada_at','aprobada_por'
    ];

    // Relaciones
    public function detalles()
    {
        return $this->hasMany(cotizacion_detalle::class, 'cotizacion_id');
    }

    public function cliente()
    {
        return $this->belongsTo(SocioNegocio::class, 'socio_negocio_id');
    }

    // (opcional) número legible
    public function getNumeroAttribute(): string
    {
        return 'S'.str_pad($this->id, 5, '0', STR_PAD_LEFT);
    }

    // (opcional) recalcular totales
    public function recalcularTotales(): void
    {
        $sub = $this->detalles()->sum('importe');
        $imp = $this->detalles->sum(function($d){
            $base = ($d->cantidad * $d->precio_unitario) * (1 - $d->descuento_pct/100);
            return $base * ($d->impuesto_pct/100);
        });

        $this->subtotal  = $sub;
        $this->impuestos = $imp;
        $this->total     = $sub + $imp;
        $this->save();
    }
}
