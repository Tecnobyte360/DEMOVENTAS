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

    /** Detecta si el valor es base64 o una ruta */
    protected function asBase64Url(?string $value): ?string
    {
        if (!$value) return null;
        return str_starts_with($value, 'data:image')
            ? $value
            : asset('storage/'.$value);
    }

    public function getLogoUrlAttribute(): ?string
    {
        return $this->asBase64Url($this->logo_path);
    }

    public function getLogoDarkUrlAttribute(): ?string
    {
        return $this->asBase64Url($this->logo_dark_path);
    }

    public function getFaviconUrlAttribute(): ?string
    {
        return $this->asBase64Url($this->favicon_path);
    }
}
