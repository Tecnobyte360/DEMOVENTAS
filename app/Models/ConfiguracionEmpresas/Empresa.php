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

    /* ============================================================
     |  ACCESSORS (WEB): URL pública correcta usando /storage
     ============================================================ */
    public function getLogoUrlAttribute(): ?string
    {
        return $this->toStorageUrl($this->logo_path);
    }

    public function getLogoDarkUrlAttribute(): ?string
    {
        return $this->toStorageUrl($this->logo_dark_path);
    }

    public function getFaviconUrlAttribute(): ?string
    {
        return $this->toStorageUrl($this->favicon_path);
    }

    /* ============================================================
     |  ACCESSORS (PDF): PATH absoluto para DomPDF / Snappy
     ============================================================ */
    public function getLogoPdfPathAttribute(): ?string
    {
        return $this->toStoragePublicPath($this->logo_path);
    }

    public function getLogoDarkPdfPathAttribute(): ?string
    {
        return $this->toStoragePublicPath($this->logo_dark_path);
    }

    public function getFaviconPdfPathAttribute(): ?string
    {
        return $this->toStoragePublicPath($this->favicon_path);
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

    /* ============================================================
     |  HELPERS
     ============================================================ */

    /**
     * Convierte "empresas/logos/x.jpg" => asset("storage/empresas/logos/x.jpg")
     * Si ya viene con "storage/..." lo respeta.
     */
    private function toStorageUrl(?string $path): ?string
    {
        if (!$path) return null;

        $path = ltrim($path, '/');

        if (str_starts_with($path, 'data:image/')) return $path;

        // si ya viene storage/...
        if (str_starts_with($path, 'storage/')) {
            return asset($path);
        }

        // si viene empresas/logos/... => /storage/empresas/logos/...
        return asset('storage/' . $path);
    }

    /**
     * Convierte "empresas/logos/x.jpg" => public_path("storage/empresas/logos/x.jpg")
     * Esto es lo que el PDF necesita.
     */
    private function toStoragePublicPath(?string $path): ?string
    {
        if (!$path) return null;

        $path = ltrim($path, '/');

        if (str_starts_with($path, 'storage/')) {
            $path = substr($path, strlen('storage/'));
            $path = ltrim($path, '/');
        }

        $abs = public_path('storage/' . $path);

        return file_exists($abs) ? $abs : null;
    }

    private function normalizeHex(?string $hex): ?string
    {
        if ($hex === null) return null;

        $hex = trim($hex);
        if ($hex === '') return null;

        $hex = ltrim($hex, '#');

        // soporta RGB corto
        if (strlen($hex) === 3) {
            $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        }

        if (!preg_match('/^[0-9a-fA-F]{6}$/', $hex)) {
            return null;
        }

        return '#' . strtoupper($hex);
    }
}
