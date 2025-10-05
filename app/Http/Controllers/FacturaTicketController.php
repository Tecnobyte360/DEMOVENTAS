<?php

namespace App\Http\Controllers;

use App\Models\ConfiguracionEmpresas\Empresa;
use App\Models\Factura\factura; // tu modelo en minúscula
use Illuminate\Support\Facades\Auth;

class FacturaTicketController extends Controller
{
     public function show(factura $factura)
    {
        $factura->load(['cliente','serie','detalles.producto','detalles.bodega']);

        $e = Empresa::query()->first();

        $empresa = [
            'nombre'     => $e->nombre ?? '—',
            'slogan'     => $e->slogan ?? null,
            'nit'        => $e?->nit ? ('NIT '.$e->nit) : null,
            'regimen'    => $e->regimen ?? 'Régimen: Responsable de IVA',
            'direccion'  => $e->direccion ?? null,
            'telefono'   => $e->telefono ?? null,
            'pos'        => 'Caja Principal',
            'cajero'     => Auth::user()?->name ?? '—',
            'resolucion' => $e->resolucion ?? null,
            'website'    => $e->sitio_web ?: $e->email,
            'pie'        => null,
            'logo_src'   => $e->logo_path ?: null,   // data:image/...;base64,xxxx
            'accent'     => $e->color_primario ?: '#0ea5e9',
        ];

        $isPreview = request()->boolean('preview');

        return view('pos.ticket-factura', compact('factura','empresa','isPreview'));
    }

    /**
     * Busca el archivo más reciente en public/storage/logos y lo devuelve como data URI base64.
     */
    private function getLogoBase64FromPublicStorage(): ?string
    {
        $dir = public_path('storage/logos');
        if (!is_dir($dir)) return null;

        $patterns = ['*.png', '*.jpg', '*.jpeg', '*.webp', '*.gif'];
        $files = [];
        foreach ($patterns as $pat) {
            foreach (glob($dir . DIRECTORY_SEPARATOR . $pat, GLOB_NOSORT) ?: [] as $f) {
                if (is_file($f)) $files[] = $f;
            }
        }
        if (empty($files)) return null;

        usort($files, fn($a, $b) => filemtime($b) <=> filemtime($a));
        return $this->fileToDataUri($files[0]);
    }

    /**
     * Convierte un archivo local a data URI base64 con MIME correcto.
     */
    private function fileToDataUri(string $absPath): ?string
    {
        if (!is_file($absPath) || !is_readable($absPath)) return null;

        $mime = 'image/png';
        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $det   = finfo_file($finfo, $absPath);
            finfo_close($finfo);
            if ($det) $mime = $det;
        } else {
            $ext = strtolower(pathinfo($absPath, PATHINFO_EXTENSION));
            $mime = match ($ext) {
                'jpg','jpeg' => 'image/jpeg',
                'webp'       => 'image/webp',
                'gif'        => 'image/gif',
                default      => 'image/png',
            };
        }

        $data = @file_get_contents($absPath);
        return $data === false ? null : 'data:' . $mime . ';base64,' . base64_encode($data);
    }
}
