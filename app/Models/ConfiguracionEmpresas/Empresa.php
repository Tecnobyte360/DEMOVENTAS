<?php

namespace App\Models\ConfiguracionEmpresas;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Empresa extends Model
{
    protected $table = 'empresas';

    protected $fillable = [
        'nombre','nit','email','telefono','sitio_web','direccion',
        'logo_path','logo_dark_path','favicon_path',
        'color_primario','color_secundario','is_activa','extra',
        'pdf_theme',
    ];

    protected $casts = [
        'is_activa' => 'boolean',
        'extra'     => 'array',
        'pdf_theme' => 'array',
    ];

    public function pdfTheme(): array
    {
        return (array) ($this->pdf_theme ?? []);
    }

    public function facturas()
    {
        return $this->hasMany(\App\Models\Factura\Factura::class, 'empresa_id');
    }

    // --- Accessors para mostrar URLs seguras en Blade ---
    public function getLogoUrlAttribute(): ?string
    {
        return $this->pathToUrl($this->logo_path);
    }
    public function getLogoDarkUrlAttribute(): ?string
    {
        return $this->pathToUrl($this->logo_dark_path);
    }
    public function getFaviconUrlAttribute(): ?string
    {
        return $this->pathToUrl($this->favicon_path);
    }

    private function pathToUrl(?string $path): ?string
    {
        if (!$path) return null;

        // Si quedó Base64 (caso legado), devuélvelo tal cual para no romper UI:
        if (str_starts_with($path, 'data:image/')) {
            return $path;
        }

        // Caso normal: ruta en disk "public"
        if (Storage::disk('public')->exists($path)) {
            return Storage::disk('public')->url($path);
        }

        return null;
    }
}
