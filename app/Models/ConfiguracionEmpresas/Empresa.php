<?php

namespace App\Models\ConfiguracionEmpresas;

use Illuminate\Database\Eloquent\Model;

class Empresa extends Model
{
    protected $table = 'empresas';

    protected $fillable = [
        'nombre', 'nit', 'email', 'telefono', 'sitio_web', 'direccion',
        'logo_path', 'logo_dark_path', 'favicon_path',
        'color_primario', 'color_secundario', 'is_activa', 'extra',
    ];

    protected $casts = [
        'is_activa' => 'boolean',
        'extra'     => 'array',
    ];

    public function getLogoUrlAttribute(): ?string
    {
        return $this->logo_path ? asset('storage/'.$this->logo_path) : null;
    }

    public function getLogoDarkUrlAttribute(): ?string
    {
        return $this->logo_dark_path ? asset('storage/'.$this->logo_dark_path) : null;
    }

    public function getFaviconUrlAttribute(): ?string
    {
        return $this->favicon_path ? asset('storage/'.$this->favicon_path) : null;
    }
}
