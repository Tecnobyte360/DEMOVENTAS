<?php

namespace App\Services;

use App\Models\Asiento\Asiento;
use App\Models\Movimiento\Movimiento;
use App\Models\NotaCredito;
use App\Models\Impuestos\Impuesto;
use App\Models\Productos\Producto;
use App\Models\SocioNegocio\SocioNegocio;
use App\Models\CuentasContables\PlanCuentas;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Arr;
use RuntimeException;

class ContabilidadNotaCreditoService
{
    /**
     * Genera y registra el asiento contable de una Nota Crédito emitida.
     * Todas las cuentas se resuelven dinámicamente desde la BD.
     */
    public static function asientoDesdeNotaCredito(NotaCredito $nc): void
    {
        $nc->loadMissing('detalles.producto.cuentaIngreso', 'detalles.producto.cuentas.tipo', 'cliente', 'serie');

        if ($nc->detalles->isEmpty()) {
            throw new RuntimeException('La nota crédito no tiene líneas.');
        }
        if (!$nc->fecha) {
            throw new RuntimeException('La nota crédito no tiene fecha.');
        }
        if (!$nc->cuenta_cobro_id) {
            throw new RuntimeException('Seleccione una cuenta contable de cobro para la nota crédito.');
        }

        // Asegurar totales (y consistencia)
        $nc->recalcularTotales();

        $fecha = $nc->fecha;
        $glosaPrefix = config('contabilidad.glosa_nc_prefix', 'NC Venta');
        $numFmt = $nc->numero ? str_pad((string)$nc->numero, optional($nc->serie)->longitud ?? 6, '0', STR_PAD_LEFT) : (string)$nc->id;
        $glosaNum = $nc->prefijo ? ($nc->prefijo.'-'.$numFmt) : $numFmt;
        $glosa = "{$glosaPrefix} {$glosaNum} — Cliente: ".($nc->cliente->razon_social ?? ('ID '.$nc->socio_negocio_id));

        $movs = [];

        // ===== DEBITOS: Reversa de ingresos e IVA
        foreach ($nc->detalles as $d) {
            $cantidad = max(0, (float)$d->cantidad);
            $precio   = max(0, (float)$d->precio_unitario);   // se asume NETO (sin IVA)
            $descPct  = min(100, max(0, (float)$d->descuento_pct));
            $ivaPct   = min(100, max(0, (float)$d->impuesto_pct));

            $base = round($cantidad * $precio * (1 - $descPct / 100), 2);
            $iva  = round($base * $ivaPct / 100, 2);

            // Debe: reversa ingreso (100% dinámico según producto/PUC)
            $cuentaIngresoId = self::resolveCuentaIngreso($d->producto);
            if ($cuentaIngresoId && $base > 0) {
                $movs[] = [
                    'cuenta_id' => $cuentaIngresoId,
                    'debito'    => $base,
                    'credito'   => 0.0,
                    'glosa'     => self::recortar('Reversa ingreso · '.($d->descripcion ?? '')),
                ];
            }

            // Debe: reversa impuesto asociado (dinámico, sin códigos quemados)
            if ($iva > 0 && $d->impuesto_id) {
                $imp = Impuesto::find($d->impuesto_id);
                $cuentaIvaId = self::resolveCuentaImpuesto($imp);
                if (!$cuentaIvaId) {
                    $nombreImp = $imp->nombre ?? 'impuesto';
                    throw new RuntimeException("No se encontró cuenta contable asociada para el {$nombreImp}.");
                }
                $movs[] = [
                    'cuenta_id' => $cuentaIvaId,
                    'debito'    => $iva,
                    'credito'   => 0.0,
                    'glosa'     => self::recortar('Reversa impuesto'),
                ];
            }
        }

        // ===== HABER: CxC / Caja / Bancos (según cuenta de cobro elegida)
        $cuentaCobroId = self::resolveCuentaCobro($nc->cuenta_cobro_id, $nc->cliente);
        $totalNc = round((float)$nc->total, 2);

        if ($totalNc > 0 && $cuentaCobroId) {
            $movs[] = [
                'cuenta_id' => $cuentaCobroId,
                'debito'    => 0.0,
                'credito'   => $totalNc,
                'glosa'     => self::recortar('Contrapartida (CxC/Caja/Bancos)'),
            ];
        }

        // Consolidar por cuenta y validar cuadratura
        $movs = self::consolidarMovimientos($movs);
        $debitos  = round(array_sum(array_column($movs, 'debito')), 2);
        $creditos = round(array_sum(array_column($movs, 'credito')), 2);
        $diff = round($debitos - $creditos, 2);

        if (abs($diff) >= 0.01) {
            Log::error('Descuadre en asiento de Nota Crédito', [
                'nota_credito_id' => $nc->id,
                'debitos' => $debitos,
                'creditos' => $creditos,
                'diff' => $diff,
                'movs' => $movs,
            ]);
            throw new RuntimeException("El asiento de la NC no cuadra (Δ = {$diff}).");
        }

        // Registrar asiento
        self::registrarAsiento([
            'fecha'        => $fecha,
            'glosa'        => $glosa,
            'documento'    => 'nota_credito',
            'documento_id' => $nc->id,
            'referencia'   => $glosaNum,
            'movimientos'  => array_values($movs),
        ]);

        // Intentar aplicar NC a la factura si existe servicio de pagos
        if ($nc->factura_id && class_exists(\App\Services\PagosService::class)
            && method_exists(\App\Services\PagosService::class, 'aplicarNotaCreditoSobreFactura')) {
            try {
                \App\Services\PagosService::aplicarNotaCreditoSobreFactura($nc);
            } catch (\Throwable $e) {
                Log::warning('No se pudo aplicar NC sobre la factura: '.$e->getMessage(), ['nc_id' => $nc->id]);
            }
        }
    }

    /** Inserta el asiento y sus movimientos ya validados (cuadrados). */
    public static function registrarAsiento(array $payload): Asiento
    {
        $movs = $payload['movimientos'] ?? [];
        if (empty($movs)) {
            throw new RuntimeException('No hay movimientos para registrar.');
        }

        $deb = array_sum(array_column($movs, 'debito'));
        $cre = array_sum(array_column($movs, 'credito'));
        if (round($deb - $cre, 2) !== 0.0) {
            throw new RuntimeException("El asiento no cuadra (Δ = ".round($deb - $cre, 2).").");
        }

        return DB::transaction(function () use ($payload, $movs) {
            $asiento = new Asiento([
                'fecha'        => $payload['fecha']        ?? now()->toDateString(),
                'glosa'        => $payload['glosa']        ?? null,
                'documento'    => $payload['documento']    ?? null,
                'documento_id' => $payload['documento_id'] ?? null,
                'referencia'   => $payload['referencia']   ?? null,
            ]);
            $asiento->save();

            $now = now();
            $rows = collect($movs)->map(fn($m) => [
                'asiento_id' => $asiento->id,
                'cuenta_id'  => (int)($m['cuenta_id'] ?? 0),
                'debito'     => (float)($m['debito']  ?? 0),
                'credito'    => (float)($m['credito'] ?? 0),
                'glosa'      => (string)($m['glosa']  ?? null),
                'created_at' => $now,
                'updated_at' => $now,
            ])->toArray();

            Movimiento::insert($rows);

            $delta = round($asiento->movimientos()->sum('debito') - $asiento->movimientos()->sum('credito'), 2);
            if ($delta !== 0.0) {
                Log::error('Asiento no cuadra luego de insertar movimientos', [
                    'asiento_id' => $asiento->id, 'Δ' => $delta
                ]);
                throw new RuntimeException('El asiento no cuadra tras insertar movimientos.');
            }

            return $asiento;
        });
    }

    /** Crea un asiento inverso (para anular). */
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
                ->latest('id')->first();

            if (!$asiento) {
                throw new RuntimeException("No se encontró asiento para {$documento} #{$documentoId}.");
            }

            $rev = new Asiento([
                'fecha'        => now()->toDateString(),
                'glosa'        => 'Reversión de: '.($asiento->glosa ?? "{$documento} {$documentoId}"),
                'documento'    => "{$documento}_reversion",
                'documento_id' => $documentoId,
                'referencia'   => ($asiento->referencia ?? null).' (REV)',
            ]);
            $rev->save();

            $now = now();
            $rows = $asiento->movimientos->map(fn($m) => [
                'asiento_id' => $rev->id,
                'cuenta_id'  => (int)$m->cuenta_id,
                'debito'     => (float)$m->credito,
                'credito'    => (float)$m->debito,
                'glosa'      => 'Reversión · '.($m->glosa ?? ''),
                'created_at' => $now,
                'updated_at' => $now,
            ])->toArray();

            Movimiento::insert($rows);

            $delta = round($rev->movimientos()->sum('debito') - $rev->movimientos()->sum('credito'), 2);
            if ($delta !== 0.0) {
                Log::error('Asiento de reversión no cuadra', [
                    'asiento_id' => $rev->id, 'Δ' => $delta
                ]);
                throw new RuntimeException('El asiento de reversión no cuadra.');
            }
        });
    }

    /** Agrupa movimientos por cuenta y compensa débitos/créditos. */
    private static function consolidarMovimientos(array $movs): array
    {
        $byAccount = [];

        foreach ($movs as $m) {
            $id = (int)$m['cuenta_id'];
            if (!isset($byAccount[$id])) {
                $byAccount[$id] = ['cuenta_id' => $id, 'debito' => 0.0, 'credito' => 0.0, 'glosa' => []];
            }
            $byAccount[$id]['debito']  += (float)($m['debito']  ?? 0);
            $byAccount[$id]['credito'] += (float)($m['credito'] ?? 0);
            if (!empty($m['glosa'])) $byAccount[$id]['glosa'][] = self::recortar($m['glosa']);
        }

        foreach ($byAccount as $id => &$r) {
            $d = round($r['debito'], 2);
            $c = round($r['credito'], 2);

            if (abs($d - $c) < 0.01) { unset($byAccount[$id]); continue; }

            $r['debito']  = max(0, $d - $c);
            $r['credito'] = max(0, $c - $d);
            $r['glosa']   = self::recortar(implode(' · ', array_filter($r['glosa'])));
        }

        return array_values($byAccount);
    }

    private static function recortar(string $s, int $max = 120): string
    {
        $s = trim($s);
        return mb_strlen($s) > $max ? mb_substr($s, 0, $max - 1).'…' : $s;
    }

    /* ============================================================
     * ===== Helpers internos de resolución 100 % dinámica ========
     * ============================================================
     */

    /**
     * Resuelve la cuenta de ingreso desde el producto:
     * 1) $producto->cuentaIngreso (relación directa)
     * 2) $producto->cuentas por tipo 'INGRESO' (si usas tabla puente)
     * 3) Fallback opcional por clase de cuenta en el PUC (configurable si tu esquema lo soporta)
     */
    private static function resolveCuentaIngreso(?Producto $producto): ?int
    {
        if (!$producto) return null;

        // 1) Relación directa cuentaIngreso
        if ($producto->relationLoaded('cuentaIngreso') && $producto->cuentaIngreso?->id) {
            return (int)$producto->cuentaIngreso->id;
        }
        if (method_exists($producto, 'cuentaIngreso') && ($cu = $producto->cuentaIngreso()->value('id'))) {
            return (int)$cu;
        }

        // 2) Relación muchas cuentas con tipo 'INGRESO'
        if ($producto->relationLoaded('cuentas')) {
            $cu = optional($producto->cuentas->firstWhere(fn($c) => optional($c->tipo)->codigo === 'INGRESO'))->plan_cuentas_id;
            if ($cu) return (int)$cu;
        } elseif (method_exists($producto, 'cuentas')) {
            $cu = $producto->cuentas()
                ->whereHas('tipo', fn($q) => $q->where('codigo', 'INGRESO'))
                ->value('plan_cuentas_id');
            if ($cu) return (int)$cu;
        }

        // 3) Fallback opcional (si tu PlanCuentas tiene algún flag para ingresos)
        // Evita "quemar" códigos; solo usa si tu PUC expone un atributo confiable.
        // return PlanCuentas::where('clase_cuenta', 'LIKE', 'INGRESO%')->where('cuenta_activa', 1)->value('id') ?: null;

        return null;
    }

    /**
     * Resuelve la cuenta asociada a un impuesto:
     * 1) $impuesto->plan_cuenta_id (si tu modelo la tiene)
     * 2) $impuesto->plan_cuenta_pasivo_id o similar (si tu esquema lo maneja)
     * 3) Fallback en PUC por metadatos del impuesto (evitando códigos fijos)
     * 4) Fallback genérico: cualquier cuenta marcada como "de impuesto" activa
     */
    private static function resolveCuentaImpuesto(?Impuesto $imp): ?int
    {
        if (!$imp) return null;

        // 1) Campo directo en la tabla de impuestos
        foreach (['plan_cuenta_id', 'plan_cuenta_pasivo_id', 'cuenta_id'] as $field) {
            if (isset($imp->{$field}) && $imp->{$field}) {
                $id = (int)$imp->{$field};
                if (PlanCuentas::whereKey($id)->exists()) return $id;
            }
        }

        // 2) Alguna relación definida en el modelo Impuesto (si existe)
        if (method_exists($imp, 'cuentaContable') && ($id = $imp->cuentaContable()->value('id'))) {
            return (int)$id;
        }

        // 3) Buscar por nombre/código del impuesto en el PUC (sin "IVA_VENTAS" quemado)
        //    Si tus cuentas guardan el código del impuesto (p.ej. en un campo 'impuesto_codigo' o similar), úsalo:
        foreach (['impuesto_codigo', 'codigo_impuesto', 'tag_impuesto'] as $col) {
            if (isset($imp->codigo) && $imp->codigo) {
                $id = PlanCuentas::where($col, $imp->codigo)->where('cuenta_activa', 1)
                    ->where(fn($q) => $q->where('titulo', 0)->orWhereNull('titulo'))
                    ->value('id');
                if ($id) return (int)$id;
            }
        }

        // 4) Último recurso: una cuenta de impuestos activa (si tu PUC marca algo como 'es_impuesto')
        foreach (['es_impuesto', 'es_iva', 'tipo', 'naturaleza'] as $col) {
            if (SchemaHasColumn(PlanCuentas::class, $col)) {
                $id = PlanCuentas::where($col, 1)->where('cuenta_activa', 1)
                    ->where(fn($q) => $q->where('titulo', 0)->orWhereNull('titulo'))
                    ->value('id');
                if ($id) return (int)$id;
            }
        }

        // Si tu PUC no tiene flags, podrías, como penúltimo recurso, buscar por nombre conteniendo el nombre del impuesto:
        if (!empty($imp->nombre)) {
            $id = PlanCuentas::where('cuenta_activa', 1)
                ->where(fn($q) => $q->where('nombre', 'LIKE', '%'.trim($imp->nombre).'%'))
                ->where(fn($q) => $q->where('titulo', 0)->orWhereNull('titulo'))
                ->value('id');
            if ($id) return (int)$id;
        }

        return null;
    }

    /**
     * Resuelve la cuenta de cobro:
     * - Valida la cuenta seleccionada por el usuario.
     * - Si no existe, intenta usar la cuenta CxC del cliente.
     * - Como último recurso, toma caja/bancos disponibles en el PUC.
     */
    private static function resolveCuentaCobro(?int $cuentaIdSeleccionada, ?SocioNegocio $cliente): ?int
    {
        if ($cuentaIdSeleccionada && PlanCuentas::whereKey($cuentaIdSeleccionada)->exists()) {
            return (int)$cuentaIdSeleccionada;
        }

        // Cuenta CxC del cliente (si tu modelo la expone)
        if ($cliente) {
            foreach (['plan_cuenta_id', 'cuenta_cxc_id'] as $field) {
                if (isset($cliente->{$field}) && $cliente->{$field} && PlanCuentas::whereKey($cliente->{$field})->exists()) {
                    return (int)$cliente->{$field};
                }
            }
            if (method_exists($cliente, 'cuentas') && ($id = $cliente->cuentas()->value('cuenta_cxc_id'))) {
                if (PlanCuentas::whereKey($id)->exists()) return (int)$id;
            }
        }

        // Caja / Bancos del PUC (sin quemar códigos; busca por posibles flags o nombres)
        $id = self::pickCuentaActivaPorHints([
            ['col' => 'clase_cuenta', 'val' => 'CAJA_GENERAL'],
            ['col' => 'clase_cuenta', 'val' => 'BANCOS'],
            ['col' => 'nombre', 'like' => '%CAJA%'],
            ['col' => 'nombre', 'like' => '%BANCO%'],
        ]);
        return $id ?: null;
    }

    /**
     * Busca una cuenta activa no-título que coincida con hints dados
     * (pares de columna/valor o columna/like), sin depender de códigos "quemados".
     */
    private static function pickCuentaActivaPorHints(array $hints): ?int
    {
        foreach ($hints as $h) {
            $q = PlanCuentas::query()->where('cuenta_activa', 1)
                ->where(fn($q) => $q->where('titulo', 0)->orWhereNull('titulo'));

            if (isset($h['col'], $h['val'])) {
                $q->where($h['col'], $h['val']);
            } elseif (isset($h['col'], $h['like'])) {
                $q->where($h['col'], 'LIKE', $h['like']);
            } else {
                continue;
            }

            $id = $q->value('id');
            if ($id) return (int)$id;
        }
        return null;
    }
}

/**
 * Helper liviano para comprobar si el modelo/tabla de PlanCuentas tiene una columna.
 * Evita romper si tu esquema no incluye ciertos flags (es_impuesto, etc.).
 */
if (!function_exists('SchemaHasColumn')) {
    function SchemaHasColumn(string $modelClass, string $column): bool
    {
        try {
            /** @var \Illuminate\Database\Eloquent\Model $m */
            $m = new $modelClass;
            $table = $m->getTable();
            return \Illuminate\Support\Facades\Schema::hasColumn($table, $column);
        } catch (\Throwable $e) {
            return false;
        }
    }
}
