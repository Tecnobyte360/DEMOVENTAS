<?php

namespace App\Models\TiposDocumento;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TipoDocumento extends Model
{
 
    protected $table = 'tipo_documentos';

    protected $fillable = ['codigo', 'nombre', 'modulo', 'config'];

    protected $casts = [
        'config' => 'array',
    ];

    public function series(): HasMany
    {
        return $this->hasMany(\App\Models\Serie\Serie::class, 'tipo_documento_id');
    }
}
