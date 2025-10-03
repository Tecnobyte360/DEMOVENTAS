<?php

namespace App\Services;

use App\Models\Asiento\Asiento;
use App\Models\Movimiento\Movimiento;
use App\Models\NotaCredito;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Arr;
use RuntimeException;

class ContabilidadNotaCreditoService
{
    public static function asientoDesdeNotaCredito(NotaCredito $nc): void
    {
        $nc->loadMissing('detalles', 'cliente', 'serie');
        if ($nc->detalles->isEmpty()) {
            throw new RuntimeException('La nota crédito no tiene líneas.');
        }
        if (!$nc->fecha) {
            throw new RuntimeException('La nota crédito no tiene fecha.');
        }
        if (!$nc->cuenta_cobro_id) {
            throw new RuntimeException('Seleccione una cuenta contable de cobro para la nota crédito.');
        }

        $nc->recalcularTotales();

        $fecha         = $nc->fecha;
        $glosaPrefix   = config('contabilidad.glosa_nc_prefix', 'NC Venta');
        $longitud      = optional($nc->serie)->longitud ?? 6;
        $numFmt        = $nc->numero ? str_pad((string)$nc->numero, $longitud, '0', STR_PAD_LEFT) : (string)($nc->id);
        $glosaNumero   = $nc->prefijo ? ($nc->prefijo.'-'.$numFmt) : $numFmt;
        $glosa         = trim($glosaPrefix.' '.$glosaNumero.' — Cliente: '.($nc->cliente->razon_social ?? ('ID '.$nc->socio_negocio_id)));

        $cuentaIvaId   = (int) (config('contabilidad.cuenta_iva_por_pagar_id') ?? 0);
        $cuentaCobroId = (int) $nc->cuenta_cobro_id;

        $movs = [];

        foreach ($nc->detalles as $d) {
            $cantidad   = max(0, (float) $d->cantidad);
            $precio     = max(0, (float) $d->precio_unitario);
            $descPct    = min(100, max(0, (float) $d->descuento_pct));
            $ivaPct     = min(100, max(0, (float) $d->impuesto_pct));

            $base       = round($cantidad * $precio * (1 - $descPct/100), 2);
            $iva        = round($base * $ivaPct / 100, 2);

            if (!empty($d->cuenta_ingreso_id) && $base > 0) {
                $movs[] = [
                    'cuenta_id' => (int) $d->cuenta_ingreso_id,
                    'debito'    => $base,
                    'credito'   => 0.0,
                    'glosa'     => self::recortar("Reversa ingreso · {$d->descripcion}"),
                ];
            }

            if ($cuentaIvaId && $iva > 0) {
                $movs[] = [
                    'cuenta_id' => $cuentaIvaId,
                    'debito'    => $iva,
                    'credito'   => 0.0,
                    'glosa'     => self::recortar('Reversa IVA por pagar'),
                ];
            }
        }

        $totalNc = round((float) $nc->total, 2);
        if ($totalNc > 0) {
            $movs[] = [
                'cuenta_id' => $cuentaCobroId,
                'debito'    => 0.0,
                'credito'   => $totalNc,
                'glosa'     => self::recortar('Contrapartida (CxC/Caja/Bancos)'),
            ];
        }

        $movs = self::consolidarMovimientos($movs);

        $debitos  = round(array_sum(array_column($movs, 'debito')), 2);
        $creditos = round(array_sum(array_column($movs, 'credito')), 2);
        $diff     = round($debitos - $creditos, 2);

        if (abs($diff) >= 0.01) {
            Log::error('Descuadre en asiento de Nota Crédito', [
                'nota_credito_id' => $nc->id,
                'debitos'  => $debitos,
                'creditos' => $creditos,
                'diff'     => $diff,
                'movs'     => $movs,
            ]);
            throw new RuntimeException("El asiento de la NC no cuadra (Δ = {$diff}).");
        }

        $payload = [
            'fecha'        => $fecha,
            'glosa'        => $glosa,
            'documento'    => 'nota_credito',
            'documento_id' => $nc->id,
            'referencia'   => $glosaNumero,
            'movimientos'  => array_values($movs),
        ];

        self::registrarAsiento($payload);

        if ($nc->factura_id && class_exists(\App\Services\PagosService::class)
            && method_exists(\App\Services\PagosService::class, 'aplicarNotaCreditoSobreFactura')) {
            try {
                \App\Services\PagosService::aplicarNotaCreditoSobreFactura($nc);
            } catch (\Throwable $e) {
                Log::warning('No se pudo aplicar NC sobre pagos de la factura: '.$e->getMessage(), ['nc_id' => $nc->id]);
            }
        }
    }

    public static function registrarAsiento(array $payload): Asiento
    {
        $movs = $payload['movimientos'] ?? [];
        if (empty($movs)) {
            throw new RuntimeException('No hay movimientos para registrar.');
        }

        $deb = 0.0; $cre = 0.0;
        foreach ($movs as $m) {
            $deb += (float)($m['debito'] ?? 0);
            $cre += (float)($m['credito'] ?? 0);
        }
        if (round($deb - $cre, 2) !== 0.0) {
            throw new RuntimeException("El asiento no cuadra (Δ = " . round($deb - $cre, 2) . ").");
        }

        return DB::transaction(function () use ($payload, $movs) {
            $asiento               = new Asiento();
            $asiento->fecha        = $payload['fecha']        ?? now()->toDateString();
            $asiento->glosa        = $payload['glosa']        ?? null;
            $asiento->documento    = $payload['documento']    ?? null;
            $asiento->documento_id = $payload['documento_id'] ?? null;
            $asiento->referencia   = $payload['referencia']   ?? null;
            $asiento->save();

            $rows = [];
            $now  = now();
            foreach ($movs as $m) {
                $rows[] = [
                    'asiento_id' => $asiento->id,
                    'cuenta_id'  => (int)($m['cuenta_id'] ?? 0),
                    'debito'     => (float)($m['debito']    ?? 0),
                    'credito'    => (float)($m['credito']   ?? 0),
                    'glosa'      => (string)($m['glosa']    ?? null),
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            Movimiento::insert($rows);

            $deb   = (float) $asiento->movimientos()->sum('debito');
            $cre   = (float) $asiento->movimientos()->sum('credito');
            $delta = round($deb - $cre, 2);
            if ($delta !== 0.0) {
                Log::error('Asiento no cuadra luego de insertar movimientos', [
                    'asiento_id' => $asiento->id, 'Δ' => $delta, 'deb' => $deb, 'cre' => $cre
                ]);
                throw new RuntimeException('El asiento no cuadra tras insertar movimientos.');
            }

            return $asiento;
        });
    }

    public static function revertirAsientoNotaCredito(NotaCredito $nc): void
    {
        self::revertirAsientoPorDocumento('nota_credito', (int)$nc->id);
    }

    public static function revertirAsientoPorDocumento(string $documento, int $documentoId): void
    {
        DB::transaction(function () use ($documento, $documentoId) {
            $asiento = Asiento::with('movimientos')
                ->where('documento', $documento)
                ->where('documento_id', $documentoId)
                ->latest('id')
                ->first();

            if (!$asiento) {
                throw new RuntimeException("No se encontró asiento para {$documento} #{$documentoId}.");
            }
            if ($asiento->movimientos->isEmpty()) {
                throw new RuntimeException('El asiento no tiene movimientos.');
            }

            $rev = new Asiento();
            $rev->fecha        = now()->toDateString();
            $rev->glosa        = 'Reversión de: ' . ($asiento->glosa ?? "{$documento} {$documentoId}");
            $rev->documento    = "{$documento}_reversion";
            $rev->documento_id = $documentoId;
            $rev->referencia   = ($asiento->referencia ?? null).' (REV)';
            $rev->save();

            $now  = now();
            $rows = [];
            foreach ($asiento->movimientos as $m) {
                $rows[] = [
                    'asiento_id' => $rev->id,
                    'cuenta_id'  => (int)$m->cuenta_id,
                    'debito'     => (float)$m->credito,
                    'credito'    => (float)$m->debito,
                    'glosa'      => 'Reversión · '.(string)($m->glosa ?? ''),
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            Movimiento::insert($rows);

            $deb   = (float) $rev->movimientos()->sum('debito');
            $cre   = (float) $rev->movimientos()->sum('credito');
            $delta = round($deb - $cre, 2);
            if ($delta !== 0.0) {
                Log::error('Asiento de reversión no cuadra', [
                    'asiento_id' => $rev->id, 'Δ' => $delta, 'deb' => $deb, 'cre' => $cre
                ]);
                throw new RuntimeException('El asiento de reversión no cuadra.');
            }
        });
    }

    private static function consolidarMovimientos(array $movs): array
    {
        $byAccount = [];

        foreach ($movs as $m) {
            $id = (int) $m['cuenta_id'];
            if (!isset($byAccount[$id])) {
                $byAccount[$id] = [
                    'cuenta_id' => $id,
                    'debito'    => 0.0,
                    'credito'   => 0.0,
                    'glosa'     => [],
                ];
            }
            $byAccount[$id]['debito']  += (float) ($m['debito']  ?? 0);
            $byAccount[$id]['credito'] += (float) ($m['credito'] ?? 0);
            if (!empty($m['glosa'])) {
                $byAccount[$id]['glosa'][] = self::recortar($m['glosa']);
            }
        }

        foreach ($byAccount as $id => &$row) {
            $d = round($row['debito'], 2);
            $c = round($row['credito'], 2);

            if ($d > $c) {
                $row['debito']  = round($d - $c, 2);
                $row['credito'] = 0.0;
            } elseif ($c > $d) {
                $row['credito'] = round($c - $d, 2);
                $row['debito']  = 0.0;
            } else {
                unset($byAccount[$id]);
                continue;
            }

            $row['glosa'] = self::recortar(implode(' · ', Arr::where($row['glosa'], fn ($g) => (string)$g !== '')));
        }

        return array_values($byAccount);
    }

    private static function recortar(string $s, int $max = 120): string
    {
        $s = trim($s);
        return mb_strlen($s) > $max ? mb_substr($s, 0, $max - 1) . '…' : $s;
    }
}
