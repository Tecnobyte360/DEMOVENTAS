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

    /** 
     * Retorna directamente el tema PDF almacenado, 
     * o un set básico de respaldo si no hay datos 
     */
    public function pdfTheme(): array
    {
     return (array) ($this->pdf_theme ?? []);
    }
  public function facturas()
    {
        return $this->hasMany(\App\Models\Factura\Factura::class, 'empresa_id');
    }
}
