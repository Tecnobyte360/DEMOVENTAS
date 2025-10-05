<?php

namespace App\Services;

use InvalidArgumentException;
use Mike42\Escpos\Printer;
use Mike42\Escpos\CapabilityProfile;
use Mike42\Escpos\PrintConnectors\WindowsPrintConnector;
use Mike42\Escpos\PrintConnectors\NetworkPrintConnector;
use Mike42\Escpos\PrintConnectors\CupsPrintConnector;
use Mike42\Escpos\EscposImage;
use Illuminate\Support\Facades\Log;

class PosPrinterService
{
    protected Printer $printer;
    protected int $width;

    public string $driverUsed = '';
    public ?string $target = null;
    public ?int $codePageSelected = null;

    // Ancho típico para 80mm (algunas usan 512 px)
    private int $maxImgWidthPx = 576;

    public function __construct()
    {
        $t0 = microtime(true);
        // Driver desde .env (fuerza windows en Windows si alguien puso cups)
        $driver = env('POS_DRIVER', 'windows');
        if (PHP_OS_FAMILY === 'Windows' && $driver === 'cups') {
            $driver = 'windows';
        }
        $this->driverUsed = $driver;
        $this->width      = (int) env('POS_WIDTH', 48);

        Log::info('[POS] Constructor iniciado', [
            'driver_env' => env('POS_DRIVER'),
            'driver'     => $driver,
            'php_os'     => PHP_OS_FAMILY,
            'width'      => $this->width,
        ]);

        // Perfil con fallback
        $profileName = env('POS_PROFILE', 'simple');
        try {
            $profile = CapabilityProfile::load($profileName);
            Log::info('[POS] Perfil de capacidades cargado', ['profile' => $profileName]);
        } catch (InvalidArgumentException $e) {
            Log::warning('[POS] Perfil no encontrado, usando "simple"', [
                'requested' => $profileName,
                'error'     => $e->getMessage(),
            ]);
            $profile = CapabilityProfile::load('simple');
        }

        // Crear conector
        try {
            switch ($driver) {
                case 'windows': {
                    // Acepta: POS80C  |  \\localhost\\POS80C  |  smb://localhost/POS80C
                    $name = env('POS_PRINTER_NAME', 'POS80C');

                    if (PHP_OS_FAMILY === 'Windows' && stripos($name, 'smb://') === 0) {
                        $path = str_replace('/', '\\', substr($name, 6));
                        $name = '\\\\' . ltrim($path, '\\');
                    }

                    $this->target  = $name;
                    Log::info('[POS] Abriendo conector Windows', ['target' => $this->target]);
                    $connector     = new WindowsPrintConnector($name);
                    break;
                }

                case 'network': {
                    $host          = env('POS_PRINTER_HOST', '192.168.1.100');
                    $port          = (int) env('POS_PRINTER_PORT', 9100);
                    $this->target  = "{$host}:{$port}";
                    Log::info('[POS] Abriendo conector de red', ['target' => $this->target]);
                    $connector     = new NetworkPrintConnector($host, $port);
                    break;
                }

                case 'cups': {
                    // Solo Linux/macOS con CUPS instalado
                    $name          = env('POS_PRINTER_NAME', 'receipt-printer');
                    $this->target  = $name;
                    Log::info('[POS] Abriendo conector CUPS', ['target' => $this->target]);
                    $connector     = new CupsPrintConnector($name);
                    break;
                }

                default:
                    throw new \RuntimeException("Driver POS no soportado: {$driver}");
            }
        } catch (\Throwable $e) {
            Log::error('[POS] Error creando conector', [
                'driver' => $driver,
                'target' => $this->target,
                'error'  => $e->getMessage(),
            ]);
            throw new \RuntimeException(
                "No se pudo abrir el conector POS ({$driver}) hacia '{$this->target}'. ".
                "Detalle: ".$e->getMessage()
            );
        }

        // Inicializa impresora
        $this->printer = new Printer($connector, $profile);
        Log::info('[POS] Impresora inicializada', [
            'driver'     => $this->driverUsed,
            'target'     => $this->target,
            'elapsed_ms' => round((microtime(true) - $t0) * 1000),
        ]);

        // Codepage para tildes/ñ con fallback
        $setter = method_exists($this->printer, 'setCharacterTable')
            ? 'setCharacterTable'
            : 'selectCharacterTable';

        foreach ([16, 19, 2, 0] as $t) {
            try {
                $this->printer->{$setter}($t);
                $this->codePageSelected = $t;
                Log::info('[POS] Codepage seleccionado', ['table' => $t]);
                break;
            } catch (\Throwable $e) {
                Log::warning('[POS] No se pudo seleccionar codepage, probando siguiente', [
                    'table' => $t,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    public function __destruct()
    {
        try {
            $this->printer->close();
            Log::info('[POS] Impresora cerrada');
        } catch (\Throwable $e) {
            Log::warning('[POS] Error al cerrar impresora', ['error' => $e->getMessage()]);
        }
    }

    /* ================= Helpers ================= */
    private function line(string $text = ''): void  { $this->printer->text($text . "\n"); }
    private function hr(): void                     { $this->line(str_repeat('-', $this->width)); }
    private function money($v): string              { return '$' . number_format((float) $v, 0, ',', '.'); }
    private function col(string $l, string $r): string {
        $len = mb_strlen($l) + mb_strlen($r);
        $spaces = max(1, $this->width - $len);
        return $l . str_repeat(' ', $spaces) . $r;
    }

    /**
     * Envuelve texto multibyte al ancho indicado, devolviendo arreglo de líneas.
     */
    private function wrapText(string $text, int $width): array
    {
        $text = preg_replace('/\s+/u', ' ', trim($text)) ?? '';
        if ($text === '') return [''];

        $lines = [];
        $current = '';
        $words = preg_split('/\s/u', $text) ?: [];

        foreach ($words as $w) {
            $cand = $current === '' ? $w : ($current . ' ' . $w);
            if (mb_strlen($cand) <= $width) {
                $current = $cand;
            } else {
                if ($current !== '') $lines[] = $current;
                // si la palabra sola excede el ancho, córtala
                while (mb_strlen($w) > $width) {
                    $lines[] = mb_substr($w, 0, $width);
                    $w = mb_substr($w, $width);
                }
                $current = $w;
            }
        }

        if ($current !== '') $lines[] = $current;
        return $lines;
    }

    /* ================ Factura ================== */
    public function printInvoice(array $empresa, array $doc, array $items, array $resumenImpuestos): void
    {
        $t0 = microtime(true);
        Log::info('[POS] Comenzando impresión de factura', [
            'empresa' => $empresa['nombre'] ?? null,
            'cliente' => $doc['cliente_nombre'] ?? null,
            'folio'   => $doc['folio'] ?? null,
            'items'   => count($items),
            'total'   => $doc['total'] ?? null,
        ]);

        $p = $this->printer;

        // Logo (opcional)
        if (!empty($empresa['logo_src'])) {
            try {
                Log::info('[POS] Imprimiendo logo');
                $this->printLogo($empresa['logo_src']);
            } catch (\Throwable $e) {
                Log::warning('[POS] Falló impresión de logo', ['error' => $e->getMessage()]);
            }
        }

        // Encabezado
        $p->setJustification(Printer::JUSTIFY_CENTER);
        $p->selectPrintMode(Printer::MODE_DOUBLE_WIDTH);
        $this->line(mb_strtoupper($empresa['nombre'] ?? 'EMPRESA'));
        $p->selectPrintMode();
        foreach (['slogan','nit','direccion','telefono','regimen'] as $k) {
            if (!empty($empresa[$k])) $this->line($empresa[$k]);
        }
        $this->hr();

        // Datos documento
        $p->setJustification(Printer::JUSTIFY_LEFT);
        $this->line('FACTURA DE VENTA    ' . mb_strtoupper($doc['tipo_pago'] ?? 'CONTADO'));
        $this->line($this->col('No: ' . ($doc['folio'] ?? ''), 'POS: ' . ($empresa['pos'] ?? '')));
        $this->line($this->col('Fecha: ' . ($doc['fecha'] ?? ''), 'Cajero: ' . ($empresa['cajero'] ?? '')));
        $this->line('Vence: ' . ($doc['vence'] ?? ''));
        $this->hr();

        // Cliente
        $this->line('CLIENTE');
        $this->line($doc['cliente_nombre'] ?? '—');
        $this->line('NIT: ' . ($doc['cliente_nit'] ?? '—'));
        if (!empty($doc['cliente_dir'])) $this->line($doc['cliente_dir']);
        if (!empty($doc['cliente_tel'])) $this->line('Tel: ' . $doc['cliente_tel']);
        $this->hr();

        // Ítems (CÓDIGO - NOMBRE - DESCRIPCIÓN) + línea de totales por ítem
        foreach ($items as $it) {
            $codigo = trim((string)($it['codigo'] ?? ''));
            $nombre = trim((string)($it['nombre'] ?? ''));
            $desc   = trim((string)($it['descripcion'] ?? ''));

            $partes = [];
            if ($codigo !== '') $partes[] = $codigo;
            if ($nombre !== '') $partes[] = $nombre;
            if ($desc   !== '') $partes[] = $desc;

            $lineaTitulo = implode(' - ', $partes);
            if ($lineaTitulo === '') {
                $lineaTitulo = '(Producto sin descripción)';
            }

            foreach ($this->wrapText($lineaTitulo, $this->width) as $ln) {
                $this->line($ln);
            }

            $cant   = (float) ($it['cant']   ?? 0);
            $precio = (float) ($it['precio'] ?? 0);
            $descP  = (float) ($it['desc']   ?? 0);
            $ivaP   = (float) ($it['iva']    ?? 0);
            $total  = (float) ($it['total']  ?? 0);

            $left  = rtrim(rtrim(number_format($cant, 3, ',', '.'), '0'), ',')
                   . ' x ' . $this->money($precio)
                   . ($descP ? ' · Desc ' . rtrim(rtrim(number_format($descP, 2, ',', '.'), '0'), ',') . '%' : '')
                   . ' · ' . ($ivaP == 0 ? 'EXCL' : 'IVA ' . rtrim(rtrim(number_format($ivaP, 2, ',', '.'), '0'), ',') . '%');

            $right = $this->money($total);

            $this->line($this->col($left, $right));
            $this->line(str_repeat('.', $this->width));
        }

        // Totales
        $this->hr();
        $this->line($this->col('Subtotal',  $this->money($doc['subtotal']  ?? 0)));
        $this->line($this->col('Impuestos', $this->money($doc['impuestos'] ?? 0)));
        $p->selectPrintMode(Printer::MODE_EMPHASIZED);
        $this->line($this->col('TOTAL',     $this->money($doc['total']     ?? 0)));
        $p->selectPrintMode();
        if (isset($doc['pagado'])) $this->line($this->col('Pagado', $this->money($doc['pagado'])));
        if (isset($doc['saldo']))  $this->line($this->col('Saldo',  $this->money($doc['saldo'])));
        $this->hr();

        // Impuestos
        $this->line('Resumen de impuestos');
        $this->line($this->col('Tarifa     Base', 'Impuesto   %'));
        foreach ($resumenImpuestos as $r) {
            $left  = str_pad($r['tarifa'] ?? 'IVA', 10) . $this->money($r['base'] ?? 0);
            $right = str_pad($this->money($r['imp'] ?? 0), 10, ' ', STR_PAD_LEFT) . ' '
                   . rtrim(rtrim(number_format((float) ($r['pct'] ?? 0), 2, ',', '.'), '0'), ',');
            $this->line($this->col($left, $right));
        }

        // Pie / glosa
        $this->hr();

       

        $glosa = $empresa['glosa'] ?? "Esta factura se asimila en todos sus efectos a una letra de cambio (Art. 774 C.Co.). "
               ."En caso de incumplimiento, su obligacion podra ser reportada a centrales de riesgo y generara intereses moratorios.";
        $this->line($glosa);
        $this->line('');

        if (!empty($empresa['resolucion'])) {
            $p->setJustification(Printer::JUSTIFY_CENTER);
            $this->line($empresa['resolucion']);
        }

       $p->setJustification(Printer::JUSTIFY_CENTER);
        $p->setEmphasis(true);
        $this->line('Software desarrollado por: ');
        $p->setEmphasis(false);
        $this->line('www.tecnobyte360.com');

        // Corte
        $p->feed(3);
        $p->cut();
        $p->pulse();

        Log::info('[POS] Factura impresa', [
            'folio'      => $doc['folio'] ?? null,
            'total'      => $doc['total'] ?? null,
            'elapsed_ms' => round((microtime(true) - $t0) * 1000),
        ]);
    }

    /* ============== Logo / Imagen ============== */
    private function printLogo(string $logoSrc): void
    {
        $tmp = null;

        try {
            if (strpos($logoSrc, 'base64,') !== false) {
                [, $b64] = explode('base64,', $logoSrc, 2);
                $tmp = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'logo_' . uniqid() . '.png';
                file_put_contents($tmp, base64_decode($b64));
                $path = $tmp;
            } else {
                $path = $logoSrc;
                if (!is_file($path)) $path = public_path(ltrim($logoSrc, '/'));
                if (!is_file($path)) $path = public_path('storage/' . ltrim($logoSrc, '/'));
                if (!is_file($path))  {
                    Log::warning('[POS] Logo no encontrado en disco', ['src' => $logoSrc]);
                    return;
                }
            }

            $normalized = $this->normalizePng($path, $this->maxImgWidthPx);
            if ($normalized) $path = $normalized;

            $img = EscposImage::load($path, false);
            $this->printer->setJustification(Printer::JUSTIFY_CENTER);

            if (method_exists($this->printer, 'graphics'))      $this->printer->graphics($img);
            elseif (method_exists($this->printer, 'bitImage'))  $this->printer->bitImage($img);
            else throw new \RuntimeException('La librería escpos-php no soporta graphics() ni bitImage().');

            $this->printer->feed(1);
            Log::info('[POS] Logo impreso');

        } finally {
            if ($tmp && file_exists($tmp)) @unlink($tmp);
            if (isset($normalized) && $normalized && file_exists($normalized)) @unlink($normalized);
        }
    }

    private function normalizePng(string $absPath, int $targetW): ?string
    {
        if (!function_exists('imagecreatefromstring') || !is_file($absPath)) return null;

        $raw = @file_get_contents($absPath);
        $src = @imagecreatefromstring($raw);
        if (!$src) return null;

        $w = imagesx($src); $h = imagesy($src);

        if ($w > $targetW) {
            $ratio = $targetW / $w;
            $nw = $targetW; $nh = (int) round($h * $ratio);
            $dst = imagecreatetruecolor($nw, $nh);
            imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);
            imagedestroy($src);
            $src = $dst; $w = $nw; $h = $nh;
        }

        // Blanco/negro básico (ajusta threshold según papel)
        $threshold = 190;
        imagefilter($src, IMG_FILTER_GRAYSCALE);
        for ($y = 0; $y < $h; $y++) {
            for ($x = 0; $x < $w; $x++) {
                $rgb = imagecolorat($src, $x, $y);
                $r = ($rgb >> 16) & 0xFF;
                $color = ($r < $threshold) ? 0 : 255;
                imagesetpixel($src, $x, $y, imagecolorallocate($src, $color, $color, $color));
            }
        }

        $tmp = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'norm_' . uniqid() . '.png';
        imagepng($src, $tmp);
        imagedestroy($src);

        return $tmp;
    }

    /* ============== Raster directo ============== */
    public function printImage(string $absolutePngPath): void
    {
        $t0 = microtime(true);
        $path = $absolutePngPath;
        $normalized = $this->normalizePng($path, $this->maxImgWidthPx);
        if ($normalized) $path = $normalized;

        $img = EscposImage::load($path, false);

        if (method_exists($this->printer, 'graphics'))      $this->printer->graphics($img);
        elseif (method_exists($this->printer, 'bitImage'))  $this->printer->bitImage($img);
        else throw new \RuntimeException('La librería escpos-php instalada no soporta graphics() ni bitImage().');

        $this->printer->feed(3);
        $this->printer->cut();
        $this->printer->pulse();

        if ($normalized && file_exists($normalized)) @unlink($normalized);

        Log::info('[POS] Imagen raster impresa', [
            'path'       => $absolutePngPath,
            'elapsed_ms' => round((microtime(true) - $t0) * 1000),
        ]);
    }

    public function printImageFromBase64(string $dataUri): void
    {
        if (strpos($dataUri, 'base64,') === false) {
            throw new \InvalidArgumentException('Data URI inválido para imagen base64.');
        }
        [, $b64] = explode('base64,', $dataUri, 2);
        $tmp = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'img_' . uniqid() . '.png';
        file_put_contents($tmp, base64_decode($b64));
        try {
            $this->printImage($tmp);
        } finally {
            @unlink($tmp);
        }
    }
}
