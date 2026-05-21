<?php
namespace App\Models\Inventario;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SalidaManual extends Model
{
    protected $table = 'salidas_manuales';

    protected $fillable = ['user_id','fecha','motivo','referencia','observaciones'];

    protected $casts = ['fecha' => 'date'];

    public function user(): BelongsTo   { return $this->belongsTo(User::class); }
    public function detalles(): HasMany { return $this->hasMany(SalidaManualDetalle::class, 'salida_manual_id'); }

    public static function motivoLabel(string $key): string {
        return match($key) {
            'ajuste'          => 'Ajuste de inventario',
            'merma'           => 'Merma / Perdida',
            'consumo_interno' => 'Consumo interno',
            'dano'            => 'Danio / Deterioro',
            'transferencia'   => 'Transferencia',
            default           => 'Otro',
        };
    }
}
