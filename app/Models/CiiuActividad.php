<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

class CiiuActividad extends Model
{
    protected $table = 'ciiu_actividades';

    protected $fillable = ['codigo','descripcion'];

    /**
     * Normalizar el código: trim + uppercase
     */
    protected function codigo(): Attribute
    {
        return Attribute::make(
            set: fn ($value) => strtoupper(trim((string) $value))
        );
    }

    /**
     * Scope de búsqueda por código o descripción
     */
    public function scopeSearch($query, ?string $term)
    {
        if (!filled($term)) {
            return $query;
        }

        $t = '%' . $term . '%';

        return $query->where(function ($q) use ($t) {
            $q->where('codigo', 'like', $t)
              ->orWhere('descripcion', 'like', $t);
        });
    }
}
