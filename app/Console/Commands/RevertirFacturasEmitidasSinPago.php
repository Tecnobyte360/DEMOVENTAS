<?php

namespace App\Console\Commands;

use App\Models\Factura\Factura;
use Illuminate\Console\Command;

class RevertirFacturasEmitidasSinPago extends Command
{
    protected $signature = 'facturas:revertir-emitidas-sin-pago
                            {--id=* : IDs específicos a revertir}
                            {--dry : Solo mostrar lo que se haría}';

    protected $description = 'Regresa a borrador facturas en estado Emitida que no tienen pagos (libera el número asignado por error)';

    public function handle(): int
    {
        $dry = (bool) $this->option('dry');
        $ids = $this->option('id');

        $q = Factura::query()
            ->where('estado', 'emitida')
            ->whereDoesntHave('pagos');

        if (!empty($ids)) {
            $q->whereIn('id', $ids);
        }

        $facturas = $q->get();

        if ($facturas->isEmpty()) {
            $this->info('No hay facturas emitidas sin pago.');
            return 0;
        }

        $this->warn(($dry ? '[DRY] ' : '') . "Se revertirán {$facturas->count()} facturas a borrador.");

        foreach ($facturas as $f) {
            $this->line("→ #{$f->id}  {$f->prefijo}-{$f->numero}  total: {$f->total}");

            if ($dry) continue;

            $f->update([
                'estado' => 'borrador',
                'numero' => null,
                'prefijo' => '',
            ]);

            $this->info("  ✔ Revertida a borrador");
        }

        $this->newLine();
        $this->info('Listo. Recuerda que el consecutivo de la serie NO retrocede automáticamente.');
        return 0;
    }
}
