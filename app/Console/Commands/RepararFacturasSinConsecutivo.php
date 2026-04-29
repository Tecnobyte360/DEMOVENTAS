<?php

namespace App\Console\Commands;

use App\Models\Factura\Factura;
use App\Models\Serie\Serie;
use App\Services\ContabilidadService;
use App\Services\InventarioService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class RepararFacturasSinConsecutivo extends Command
{
    protected $signature = 'facturas:reparar-consecutivos
                            {--dry : Solo mostrar lo que se haría, sin aplicar}';

    protected $description = 'Asigna consecutivo y completa la emisión de facturas que quedaron pagadas sin número';

    public function handle(): int
    {
        $dry = (bool) $this->option('dry');

        // Facturas con al menos un pago (pagada o parcialmente pagada) sin consecutivo
        $facturas = Factura::query()
            ->whereIn('estado', ['pagada', 'parcialmente_pagada'])
            ->where(function ($q) {
                $q->whereNull('numero')->orWhere('numero', '');
            })
            ->whereHas('pagos', fn ($p) => $p->where('monto', '>', 0))
            ->with(['detalles', 'serie'])
            ->orderBy('id')
            ->get();

        if ($facturas->isEmpty()) {
            $this->info('No hay facturas para reparar.');
            return 0;
        }

        $this->warn(($dry ? '[DRY-RUN] ' : '') . 'Se procesarán ' . $facturas->count() . ' facturas.');

        $ok = 0;
        $err = 0;

        foreach ($facturas as $f) {
            $this->line("→ #{$f->id} (estado: {$f->estado}, total: {$f->total})");

            $serie = $f->serie;
            if (!$serie) {
                $this->error('  Sin serie. Saltada.');
                $err++;
                continue;
            }

            if ($dry) {
                $this->line("  [DRY] Tomaría consecutivo de '{$serie->nombre}' y emitiría.");
                $ok++;
                continue;
            }

            try {
                DB::transaction(function () use ($f, $serie) {
                    $numero = $serie->tomarConsecutivo();

                    $update = [
                        'serie_id' => $serie->id,
                        'prefijo'  => (string) ($serie->prefijo ?? ''),
                        'numero'   => $numero,
                    ];
                    if (Schema::hasColumn('facturas', 'emitido_en') && empty($f->emitido_en)) {
                        $update['emitido_en'] = now();
                    }

                    Factura::whereKey($f->id)->update($update);
                    $f->refresh();

                    // Crea asiento e inventario solo si no se hizo antes
                    try {
                        ContabilidadService::asientoDesdeFactura($f);
                    } catch (\Throwable $e) {
                        $this->warn('  (asiento ya existía o falló: ' . $e->getMessage() . ')');
                    }

                    try {
                        InventarioService::descontarPorFactura($f);
                    } catch (\Throwable $e) {
                        $this->warn('  (inventario ya descontado o falló: ' . $e->getMessage() . ')');
                    }

                    $f->recalcularTotales()->save();
                });

                $f->refresh();
                $this->info("  ✔ Asignado: {$f->prefijo}-{$f->numero}");
                $ok++;
            } catch (\Throwable $e) {
                $this->error('  ✗ ' . $e->getMessage());
                $err++;
            }
        }

        $this->newLine();
        $this->info("Listas: {$ok}    Fallidas: {$err}");
        return $err === 0 ? 0 : 1;
    }
}
