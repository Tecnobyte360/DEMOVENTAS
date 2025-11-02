<?php

namespace App\Models\Inventario;

use App\Livewire\Conceptos\ConceptosDocumentos;
use App\Models\Bodega;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Inventario\EntradaMercancia;
use App\Models\Productos\Producto;

class EntradaDetalle extends Model
{
    use HasFactory;

    protected $table = 'entrada_detalles';

 protected $fillable = [
  'socio_negocio_id','fecha_contabilizacion','lista_precio','observaciones',
  'estado','serie_id','prefijo','numero',
  'concepto_documento_id',   // ← nuevo
];

    public function entrada()
    {
        return $this->belongsTo(EntradaMercancia::class, 'entrada_mercancia_id');
    }

    public function producto()
    {
        return $this->belongsTo(Producto::class);
    }

    public function bodega()
    {
        return $this->belongsTo(Bodega::class);
    }
    public function concepto()
{
    return $this->belongsTo(ConceptosDocumentos::class, 'concepto_documento_id');
}
}
