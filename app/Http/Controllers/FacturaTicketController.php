<?php

namespace App\Http\Controllers;

use App\Models\Factura\factura; // tu modelo está en minúscula

class FacturaTicketController extends Controller
{
    public function show(factura $factura)
    {
        // Relaciones necesarias para el ticket
        $factura->load(['cliente', 'serie', 'detalles.producto', 'detalles.bodega']);

        // ========= LOGO (desde public/storage/logos) en base64 =========
        $logoSrc = $this->getLogoBase64FromPublicStorage();

        // ========= Datos de empresa (ajústalos a tu modelo si lo tienes) =========
        $empresa = [
            'nombre'     => 'LA CASA DEL CANDELABRO',
            'slogan'     => 'COMERCIALIZACIÓN DE MATERIALES Y PVC SAS',
            'nit'        => 'NIT 900.000.000-1, Medellín - Antioquia',
            'regimen'    => 'Régimen: Responsable de IVA',
            'direccion'  => 'CARRERA 45 #00-123, Medellín',
            'telefono'   => 'Tel: 3111111111',
            'pos'        => 'Caja Principal',
            'cajero'     => 'Juan Pérez',
            'resolucion' => 'Resolución DIAN 12345 de 2025',
            'website'    => 'allegra.com/pos',
            'pie'        => 'Soluciones Alegra S.A.S - NIT 900.559.088-2',
            'logo_src'   => $logoSrc,           // 👈 base64 listo para <img>
            'accent'     => '#0ea5e9',
        ];

        // ¿Solo preview? (para ocultar botones, etc.)
        $isPreview = request()->boolean('preview');

        return view('pos.ticket-factura', compact('factura', 'empresa', 'isPreview'));
    }

    /**
     * Busca el archivo más reciente en public/storage/logos y lo devuelve como data URI base64.
     * Si no hay archivo válido, retorna null.
     */
    private function getLogoBase64FromPublicStorage(): ?string
    {
        // Carpeta pública donde están tus logos subidos
        $dir = public_path('storage/logos');

        if (!is_dir($dir)) {
            return null;
        }

        // Extensiones permitidas
        $patterns = ['*.png', '*.jpg', '*.jpeg', '*.webp', '*.gif'];
        $files = [];

        foreach ($patterns as $pat) {
            foreach (glob($dir . DIRECTORY_SEPARATOR . $pat, GLOB_NOSORT) ?: [] as $f) {
                if (is_file($f)) {
                    $files[] = $f;
                }
            }
        }

        if (empty($files)) {
            return null;
        }

        // Ordena por fecha de modificación (más reciente primero)
        usort($files, fn($a, $b) => filemtime($b) <=> filemtime($a));
        $path = $files[0];

        return $this->fileToDataUri($path);
    }

    /**
     * Convierte un archivo local a data URI base64 con MIME correcto.
     */
    private function fileToDataUri(string $absPath): ?string
    {
        if (!is_file($absPath) || !is_readable($absPath)) {
            return null;
        }

        // Detecta MIME
        $mime = 'image/png';
        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $det   = finfo_file($finfo, $absPath);
            finfo_close($finfo);
            if ($det) $mime = $det;
        } else {
            // Fallback simple por extensión
            $ext = strtolower(pathinfo($absPath, PATHINFO_EXTENSION));
            $mime = match ($ext) {
                'jpg','jpeg' => 'image/jpeg',
                'webp'       => 'image/webp',
                'gif'        => 'image/gif',
                default      => 'image/png',
            };
        }

        $data = @file_get_contents($absPath);
        if ($data === false) {
            return null;
        }

        return 'data:' . $mime . ';base64,' . base64_encode($data);
    }
}
