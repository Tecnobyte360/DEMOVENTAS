<?php

namespace App\Models\ConfiguracionEmpresas;

use App\Models\Factura\Factura;
use Illuminate\Database\Eloquent\Model;

class Empresa extends Model
{
    protected $table = 'empresas';

    protected $fillable = [
        'nombre',
        'nit',
        'email',
        'telefono',
        'sitio_web',
        'direccion',
        'logo_path',
        'logo_dark_path',
        'favicon_path',
        'color_primario',
        'color_secundario',
        'is_activa',
        'extra',
        'pdf_theme',
    ];

    protected $casts = [
        'is_activa' => 'boolean',
        'extra'     => 'array',
        'pdf_theme' => 'array',
    ];

    /** Tema PDF con respaldo por defecto */
    public function pdfTheme(): array
    {
        return (array) ($this->pdf_theme ?? []);
    }

    public function facturas()
    {
        return $this->hasMany(Factura::class, 'empresa_id');
    }

    /** === Accessors de URLs === */
 public function getLogoUrlAttribute(): ?string
{
    return $this->logo_path ? asset($this->logo_path) : null;
}

public function getLogoDarkUrlAttribute(): ?string
{
    return $this->logo_dark_path ? asset($this->logo_dark_path) : null;
}

public function getFaviconUrlAttribute(): ?string
{
    return $this->favicon_path ? asset($this->favicon_path) : null;
}


    /** === Accessors de color normalizado para UI === */
    public function getColorPrimarioHexAttribute(): string
    {
        return $this->normalizeHex($this->color_primario) ?? '#1F2937';
    }

    public function getColorSecundarioHexAttribute(): string
    {
        return $this->normalizeHex($this->color_secundario) ?? '#1F2937';
    }

    private function toPublicUrl(?string $path): ?string
    {
        if (!$path) return null;
        if (str_starts_with($path, 'data:image/')) return $path;

        return asset($path);
    }

    private function normalizeHex(?string $hex): ?string
    {
        if ($hex === null) return null;

        $hex = trim($hex);
        if ($hex === '') return null;

        $hex = ltrim($hex, '#');

        // soporta RGB corto
        if (strlen($hex) === 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }

        if (!preg_match('/^[0-9a-fA-F]{6}$/', $hex)) {
            return null;
        }

        return '#' . strtoupper($hex);
    }
}
