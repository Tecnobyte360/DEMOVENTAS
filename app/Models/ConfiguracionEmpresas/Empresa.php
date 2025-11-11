<?php

namespace App\Models\ConfiguracionEmpresas;

use Illuminate\Database\Eloquent\Model;

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

    /** Retorna el tema PDF almacenado o arreglo vacío */
    public function pdfTheme(): array
    {
        return (array) ($this->pdf_theme ?? []);
    }

    public function facturas()
    {
        return $this->hasMany(\App\Models\Factura\Factura::class, 'empresa_id');
    }

    // ==== Accessors para mostrar URLs seguras en Blade ====
    public function getLogoUrlAttribute(): ?string
    {
        return $this->toPublicUrl($this->logo_path);
    }

    public function getLogoDarkUrlAttribute(): ?string
    {
        return $this->toPublicUrl($this->logo_dark_path);
    }

    public function getFaviconUrlAttribute(): ?string
    {
        return $this->toPublicUrl($this->favicon_path);
    }

    private function toPublicUrl(?string $path): ?string
    {
        if (!$path) return null;

        // Soporte legado: si quedó Base64 en BD, úsalo tal cual
        if (str_starts_with($path, 'data:image/')) {
            return $path;
        }

        // Disk "public" → /storage/...
        return asset('storage/'.$path);
    }
}
