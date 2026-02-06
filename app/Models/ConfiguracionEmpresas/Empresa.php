<?php

namespace App\Models\ConfiguracionEmpresas;

use App\Models\Factura\Factura;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Empresa extends Model
{
    /* ============================================================
     |  CONFIGURACIÓN BÁSICA
     ============================================================ */

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

    /* ============================================================
     |  RELACIONES
     ============================================================ */

    public function facturas(): HasMany
    {
        return $this->hasMany(Factura::class, 'empresa_id');
    }

    /* ============================================================
     |  TEMA PDF
     ============================================================ */

    public function pdfTheme(): array
    {
        return (array) ($this->pdf_theme ?? []);
    }

    /* ============================================================
     |  ACCESSORS WEB (PUBLIC)
     |  Guardas: "empresas/1/logos/archivo.png"
     |  Sirves:  https://dominio.com/empresas/1/logos/archivo.png
     ============================================================ */

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

    /* ============================================================
     |  ACCESSORS PDF (PUBLIC PATH)
     ============================================================ */

    public function getLogoPdfPathAttribute(): ?string
    {
        return $this->toPublicPath($this->logo_path);
    }

    public function getLogoDarkPdfPathAttribute(): ?string
    {
        return $this->toPublicPath($this->logo_dark_path);
    }

    public function getFaviconPdfPathAttribute(): ?string
    {
        return $this->toPublicPath($this->favicon_path);
    }

    /* ============================================================
     |  ACCESSORS COLORES NORMALIZADOS
     ============================================================ */

    public function getColorPrimarioHexAttribute(): string
    {
        return $this->normalizeHex($this->color_primario) ?? '#1F2937';
    }

    public function getColorSecundarioHexAttribute(): string
    {
        return $this->normalizeHex($this->color_secundario) ?? '#1F2937';
    }

    /* ============================================================
     |  HELPERS
     ============================================================ */

    private function toPublicUrl(?string $path): ?string
    {
        if (!$path) return null;

        $path = ltrim($path, '/');

        if (str_starts_with($path, 'data:image/')) return $path;

        // si viene ya como url absoluta
        if (preg_match('/^https?:\/\//i', $path)) return $path;

        return asset($path);
    }

    private function toPublicPath(?string $path): ?string
    {
        if (!$path) return null;

        $path = ltrim($path, '/');

        // data:image no aplica para PDF path
        if (str_starts_with($path, 'data:image/')) return null;

        $abs = public_path($path);

        return file_exists($abs) ? $abs : null;
    }

    private function normalizeHex(?string $hex): ?string
    {
        if (!$hex) return null;

        $hex = strtoupper(ltrim(trim($hex), '#'));

        if (strlen($hex) === 3) {
            $hex = "{$hex[0]}{$hex[0]}{$hex[1]}{$hex[1]}{$hex[2]}{$hex[2]}";
        }

        return preg_match('/^[0-9A-F]{6}$/', $hex) ? "#{$hex}" : null;
    }
}
