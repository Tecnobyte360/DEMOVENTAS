<?php
namespace App\Models\Inventario;

use App\Models\Bodega;
use App\Models\Productos\Producto;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalidaManualDetalle extends Model
{
    protected $table = 'salidas_manuales_detalles';

    protected $fillable = ['salida_manual_id','producto_id','bodega_id','cantidad','observacion'];

    public function salidaManual(): BelongsTo { return $this->belongsTo(SalidaManual::class); }
    public function producto(): BelongsTo     { return $this->belongsTo(Producto::class); }
    public function bodega(): BelongsTo       { return $this->belongsTo(Bodega::class); }
}
