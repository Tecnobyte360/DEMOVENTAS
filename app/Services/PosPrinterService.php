<?php

namespace App\Services;

use InvalidArgumentException;
use Mike42\Escpos\Printer;
use Mike42\Escpos\CapabilityProfile;
use Mike42\Escpos\PrintConnectors\WindowsPrintConnector;
use Mike42\Escpos\PrintConnectors\NetworkPrintConnector;
use Mike42\Escpos\PrintConnectors\CupsPrintConnector;
use Mike42\Escpos\EscposImage;

class PosPrinterService
{
    protected Printer $printer;
    protected int $width;

    public string $driverUsed = '';
    public ?string $target = null;
    public ?int $codePageSelected = null;

    // Ancho típico para papel 80 mm (ajusta a 512 si tu modelo lo requiere)
    private int $maxImgWidthPx = 576;

    public function __construct()
    {
        $driver = env('POS_DRIVER', 'network');
        $this->driverUsed = $driver;
        $this->width = (int) env('POS_WIDTH', 48);

        // Perfil con fallback
        $profileName = env('POS_PROFILE', 'simple');
        try {
            $profile = CapabilityProfile::load($profileName);
        } catch (InvalidArgumentException $e) {
            $profile = CapabilityProfile::load('simple');
        }

        // Conector
        switch ($driver) {
            case 'windows':
                $name = env('POS_PRINTER_NAME', 'POS');
                $this->target = $name;
                $connector = new WindowsPrintConnector($name);
                break;

            case 'cups':
                $name = env('POS_PRINTER_NAME', 'POS');
                $this->target = $name;
                $connector = new CupsPrintConnector($name);
                break;

            default: // network
                $host = env('POS_PRINTER_HOST', '127.0.0.1');
                $port = (int) env('POS_PRINTER_PORT', 9100);
                $this->target = $host . ':' . $port;
                $connector = new NetworkPrintConnector($host, $port, 5);
                break;
        }

        $this->printer = new Printer($connector, $profile);

        // Codepage para tildes/ñ (fallback: 16→19→2→0)
        $setter = method_exists($this->printer, 'setCharacterTable')
            ? 'setCharacterTable'
            : 'selectCharacterTable';

        foreach ([16, 19, 2, 0] as $t) {
            try { $this->printer->{$setter}($t); $this->codePageSelected = $t; break; }
            catch (\Throwable $e) { /* intenta la siguiente */ }
        }
    }

    public function __destruct()
    {
        try { $this->printer->close(); } catch (\Throwable $e) {}
    }

    /* ================= HELPER TEXTO ================= */
    private function line(string $text = ''): void  { $this->printer->text($text . "\n"); }
    private function hr(): void                     { $this->line(str_repeat('-', $this->width)); }
    private function money($v): string              { return '$' . number_format((float) $v, 0, ',', '.'); }
    private function col(string $l, string $r): string {
        $len = mb_strlen($l) + mb_strlen($r);
        $spaces = max(1, $this->width - $len);
        return $l . str_repeat(' ', $spaces) . $r;
    }

    /* ================= IMPRESIÓN POR TEXTO (con LOGO) ================= */
    public function printInvoice(array $empresa, array $doc, array $items, array $resumenImpuestos): void
    {
        $p = $this->printer;

        // --- LOGO (si viene) ---
        if (!empty($empresa['logo_src'])) {
            try {
                $this->printLogo($empresa['logo_src']); // centra y alimenta 1 línea
            } catch (\Throwable $e) {
                // si falla el logo, continuamos con el resto
            }
        }

        // Encabezado textual
        $p->setJustification(Printer::JUSTIFY_CENTER);
        $p->selectPrintMode(Printer::MODE_DOUBLE_WIDTH);
        $this->line(mb_strtoupper($empresa['nombre'] ?? 'EMPRESA'));
        $p->selectPrintMode();
        foreach (['slogan','nit','direccion','telefono','regimen'] as $k) {
            if (!empty($empresa[$k])) $this->line($empresa[$k]);
        }
        $this->hr();

        // Doc
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

        // Ítems
        foreach ($items as $it) {
            $desc = (!empty($it['codigo']) ? $it['codigo'] . ' · ' : '') . ($it['nombre'] ?? '');
            $this->line($desc);

            $cant   = (float) ($it['cant']   ?? 0);
            $precio = (float) ($it['precio'] ?? 0);
            $descP  = (float) ($it['desc']   ?? 0);
            $ivaP   = (float) ($it['iva']    ?? 0);
            $total  = (float) ($it['total']  ?? 0);

            $left  = rtrim(rtrim(number_format($cant, 3, ',', '.'), '0'), ',') . ' x ' . $this->money($precio)
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

        $this->hr();
        $p->setJustification(Printer::JUSTIFY_CENTER);
        $p->setEmphasis(true);
        $this->line('¡GRACIAS POR SU COMPRA!');
        $p->setEmphasis(false);

        $p->feed(3);
        $p->cut();
        $p->pulse();
    }

    /* ================== LOGO / IMAGEN ================== */

    /**
     * Imprime el logo recibido en base64 (data URI) o ruta local.
     * Centra, convierte a B/N y ajusta ancho a $maxImgWidthPx.
     */
    private function printLogo(string $logoSrc): void
    {
        $tmp = null;

        // 1) Obtener ruta a archivo imprimible
        if (strpos($logoSrc, 'base64,') !== false) {
            // Data URI
            [, $b64] = explode('base64,', $logoSrc, 2);
            $tmp = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'logo_' . uniqid() . '.png';
            file_put_contents($tmp, base64_decode($b64));
            $path = $tmp;
        } else {
            // Ruta: absoluta o relativa a public/
            $path = $logoSrc;
            if (!is_file($path)) {
                $path = public_path(ltrim($logoSrc, '/')); // intenta en public/
            }
            if (!is_file($path)) {
                // intenta public/storage/ (por si guardas /storage/logos/...)
                $path = public_path('storage/' . ltrim($logoSrc, '/'));
            }
            if (!is_file($path)) {
                // si sigue sin existir, no imprimimos logo
                return;
            }
        }

        // 2) Normalizar (B/N + ancho)
        $normalized = $this->normalizePng($path, $this->maxImgWidthPx);
        if ($normalized) { $path = $normalized; }

        // 3) Imprimir
        $img = EscposImage::load($path, false);
        $this->printer->setJustification(Printer::JUSTIFY_CENTER);

        if (method_exists($this->printer, 'graphics')) {
            $this->printer->graphics($img);
        } elseif (method_exists($this->printer, 'bitImage')) {
            $this->printer->bitImage($img);
        } else {
            throw new \RuntimeException('La librería escpos-php no soporta graphics() ni bitImage().');
        }

        $this->printer->feed(1);

        // 4) Limpieza
        if ($tmp && file_exists($tmp)) @unlink($tmp);
        if ($normalized && file_exists($normalized)) @unlink($normalized);
    }

    /**
     * Recibe una imagen (cualquier formato), la convierte a PNG B/N y la escala al ancho indicado.
     * Devuelve la ruta del PNG temporal, o null si algo falla.
     */
    private function normalizePng(string $absPath, int $targetW): ?string
    {
        if (!function_exists('imagecreatefromstring') || !is_file($absPath)) {
            return null;
        }

        $raw = @file_get_contents($absPath);
        $src = @imagecreatefromstring($raw);
        if (!$src) return null;

        $w = imagesx($src); $h = imagesy($src);

        if ($w > $targetW) {
            $ratio = $targetW / $w;
            $nw = $targetW;
            $nh = (int) round($h * $ratio);
            $dst = imagecreatetruecolor($nw, $nh);
            imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);
            imagedestroy($src);
            $src = $dst; $w = $nw; $h = $nh;
        }

        // Blanco y negro con umbral
        $threshold = 190; // ajusta 160-210 según tu papel/cabezal
        imagefilter($src, IMG_FILTER_GRAYSCALE);
        for ($y = 0; $y < $h; $y++) {
            for ($x = 0; $x < $w; $x++) {
                $rgb = imagecolorat($src, $x, $y);
                $r = ($rgb >> 16) & 0xFF;
                $val = $r;
                $color = ($val < $threshold) ? 0 : 255;
                imagesetpixel($src, $x, $y, imagecolorallocate($src, $color, $color, $color));
            }
        }

        $tmp = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'norm_' . uniqid() . '.png';
        imagepng($src, $tmp);
        imagedestroy($src);

        return $tmp;
    }

    /* ===== IMPRESIÓN RASTER COMPLETA (para preview a imagen) ===== */

    /**
     * Imprime un PNG (ruta absoluta). Auto-ajusta ancho y B/N.
     */
    public function printImage(string $absolutePngPath): void
    {
        $path = $absolutePngPath;
        $normalized = $this->normalizePng($path, $this->maxImgWidthPx);
        if ($normalized) { $path = $normalized; }

        $img = EscposImage::load($path, false);

        if (method_exists($this->printer, 'graphics')) {
            $this->printer->graphics($img);
        } elseif (method_exists($this->printer, 'bitImage')) {
            $this->printer->bitImage($img);
        } else {
            throw new \RuntimeException('La librería escpos-php instalada no soporta graphics() ni bitImage().');
        }

        $this->printer->feed(3);
        $this->printer->cut();
        $this->printer->pulse();

        if ($normalized && file_exists($normalized)) @unlink($normalized);
    }

    /**
     * Imprime una imagen recibida en Data URI base64.
     */
    public function printImageFromBase64(string $dataUri): void
    {
        if (strpos($dataUri, 'base64,') === false) {
            throw new \InvalidArgumentException('Data URI inválido para imagen base64.');
        }
        [, $b64] = explode('base64,', $dataUri, 2);
        $tmp = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'img_' . uniqid() . '.png';
        file_put_contents($tmp, base64_decode($b64));
        $this->printImage($tmp);
        @unlink($tmp);
    }
}
