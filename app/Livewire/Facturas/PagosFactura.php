<?php

namespace App\Livewire\Facturas;

use Livewire\Component;
use Livewire\Attributes\On;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Masmerise\Toaster\PendingToast;

use App\Models\Factura\Factura;
use App\Models\Factura\FacturaPago;
use App\Models\MediosPago\MedioPagos;
use App\Services\ContabilidadService;
use App\Models\TurnosCaja\turnos_caja;
use App\Models\TurnosCaja\CajaMovimiento;
use App\Models\Serie\Serie;

class PagosFactura extends Component
{
    public ?int $facturaId = null;

    public bool $show = false;
    public string $fecha = '';
    public ?string $notas = null;

    /** @var Collection<int, MedioPagos> */
    public Collection $medios;

    public float $fac_total  = 0.0;
    public float $fac_pagado = 0.0;
    public float $fac_saldo  = 0.0;

    public array $items = [];

    public float $sumPct   = 0.0;
    public float $sumMonto = 0.0;
    public float $diff     = 0.0;

    public string $buscarFactura = '';
    public string $tipoDocumento = 'venta'; // venta | compra

    protected $rules = [
        'fecha'                 => 'required|date',
        'items'                 => 'required|array|min:1',
        'items.*.medio_pago_id' => 'required|integer',
        'items.*.porcentaje'    => 'nullable|numeric|min:0|max:100',
        'items.*.monto'         => 'required|numeric|min:0.01',
        'items.*.referencia'    => 'nullable|string|max:120',
        'notas'                 => 'nullable|string',
        'facturaId'             => 'nullable|integer',
    ];

    public function mount(): void
    {
        $this->fecha  = now()->toDateString();
        $this->medios = collect();

        $this->items = [[
            'medio_pago_id' => null,
            'porcentaje'    => 0,
            'monto'         => 0,
            'referencia'    => null,
        ]];

        $this->recalc();
    }

    public function render()
    {
        $this->cargarMedios();
        $facturasPendientes = $this->queryFacturasPendientes();

        // Mantener cabecera al refrescar, sin reescribir items mientras edita
        if ($this->facturaId) {
            $this->cargarFacturaSeleccionada($this->facturaId, false);
        }

        $this->recalc();

        return view('livewire.facturas.pagos-factura', [
            'medios'             => $this->medios,
            'facturasPendientes' => $facturasPendientes,
        ]);
    }

    #[On('abrir-modal-pago')]
    public function abrir(?int $facturaId = null): void
    {
        $this->show          = true;
        $this->fecha         = now()->toDateString();
        $this->notas         = null;
        $this->buscarFactura = '';
        $this->tipoDocumento = 'venta';

        $this->cargarMedios();

        $this->facturaId = $facturaId;

        if ($facturaId) {
            $this->cargarFacturaSeleccionada($facturaId, true);
        } else {
            $this->resetFacturaYItems();
        }

        $this->resetErrorBag();
        $this->resetValidation();
        $this->recalc();

        // ✅ Livewire 3
        $this->dispatch('refresh-factura-select');
    }

    public function cerrar(): void
    {
        $this->show = false;
    }

    public function updatedTipoDocumento(): void
    {
        $this->facturaId = null;
        $this->buscarFactura = '';
        $this->resetFacturaYItems();
        $this->dispatch('refresh-factura-select');
    }

    public function updatedBuscarFactura(): void
    {
        $this->facturaId = null;
        $this->resetFacturaYItems();
        $this->dispatch('refresh-factura-select');
    }

    // ✅ Este lo llama el JS: Livewire.dispatch('set-factura-id', { facturaId: ... })
    #[On('set-factura-id')]
    public function setFacturaId(?int $facturaId = null): void
    {
        $this->facturaId = $facturaId;

        if (!$this->facturaId) {
            $this->resetFacturaYItems();
            return;
        }

        $this->cargarMedios();
        $this->cargarFacturaSeleccionada($this->facturaId, true); // ✅ llena totales y primer monto
    }

    private function cargarMedios(): void
    {
        $this->medios = MedioPagos::query()
            ->when(method_exists(MedioPagos::class, 'activos'), fn($q) => $q->activos())
            ->orderBy('nombre')
            ->get(['id', 'codigo', 'nombre', 'requiere_turno', 'contar_en_total', 'crear_movimiento', 'tipo_movimiento', 'clave_turno']);
    }

    private function queryFacturasPendientes()
    {
        $codigoTipo = $this->tipoDocumento === 'compra' ? 'FACTURACOMPRA' : 'FACTURA';

        $seriesIds = Serie::query()
            ->whereHas('tipo', function ($t) use ($codigoTipo) {
                $t->whereRaw('LOWER(codigo) = ?', [strtolower($codigoTipo)]);
            })
            ->pluck('id')
            ->map(fn($v) => (int)$v)
            ->all();

        $q = Factura::query()
            ->with(['socioNegocio', 'serie'])
            ->where('saldo', '>', 0)
            ->when(!empty($seriesIds), fn($qq) => $qq->whereIn('serie_id', $seriesIds))
            ->when(empty($seriesIds), fn($qq) => $qq->whereRaw('1=0'));

        if (trim($this->buscarFactura) !== '') {
            $b = '%' . trim($this->buscarFactura) . '%';
            $q->where(function ($w) use ($b) {
                $w->whereHas(
                    'socioNegocio',
                    fn($qq) =>
                    $qq->where('razon_social', 'like', $b)
                        ->orWhere('numero_documento', 'like', $b)
                )
                    ->orWhere('numero', 'like', $b)
                    ->orWhere('prefijo', 'like', $b);
            });
        }

        return $q->orderByDesc('fecha')
            ->limit(200)
            ->get(['id', 'numero', 'prefijo', 'serie_id', 'socio_negocio_id', 'fecha', 'total', 'saldo']);
    }

    private function cargarFacturaSeleccionada(int $facturaId, bool $rewriteItems = true): void
{
    $factura = Factura::with('pagos')->find($facturaId);

    if (!$factura) {
        $this->resetFacturaYItems();
        return;
    }

    $total  = round((float)($factura->total ?? 0), 2);

    // ✅ pagado real: primero usar columna, si no sirve, sumar pagos
    $pagado = round(
        (float)(
            $factura->pagado
            ?? $factura->pagos->sum('monto')
            ?? 0
        ),
        2
    );

    // ✅ saldo real calculado, no confiar ciegamente en la columna saldo
    $saldo = round(max($total - $pagado, 0), 2);

    $this->fac_total  = $total;
    $this->fac_pagado = $pagado;
    $this->fac_saldo  = $saldo;

    if ($rewriteItems) {
        $medioDefault = $this->medios->first()?->id ?? null;

        $this->items = [[
            'medio_pago_id' => $medioDefault,
            'porcentaje'    => $this->fac_saldo > 0 ? 100.00 : 0.00,
            'monto'         => round($this->fac_saldo, 2),
            'referencia'    => null,
        ]];
    }

    $this->recalc();
}

    private function resetFacturaYItems(): void
    {
        $this->fac_total = 0;
        $this->fac_pagado = 0;
        $this->fac_saldo = 0;

        $this->items = [[
            'medio_pago_id' => null,
            'porcentaje'    => 0,
            'monto'         => 0,
            'referencia'    => null,
        ]];

        $this->recalc();
    }

    public function addItem(): void
    {
        $this->items[] = ['medio_pago_id' => null, 'porcentaje' => 0, 'monto' => 0, 'referencia' => null];
        $this->recalc();
    }

    public function removeItem(int $idx): void
    {
        if (!isset($this->items[$idx])) return;
        array_splice($this->items, $idx, 1);
        if (empty($this->items)) $this->addItem();
        $this->recalc();
    }

    // ✅ Recalcular en caliente
    public function updated($name, $value): void
    {
        if (preg_match('/^items\.(\d+)\.porcentaje$/', $name, $m)) {
            $i = (int)$m[1];
            $pct = max(0, min(100, (float)$value));
            $this->items[$i]['porcentaje'] = $pct;
            $this->items[$i]['monto']      = round($this->fac_saldo * $pct / 100, 2);
        }

        if (preg_match('/^items\.(\d+)\.monto$/', $name, $m)) {
            $i = (int)$m[1];
            $monto = max(0, round((float)$value, 2));
            $this->items[$i]['monto']      = $monto;
            $this->items[$i]['porcentaje'] = $this->fac_saldo > 0
                ? round(($monto / $this->fac_saldo) * 100, 2)
                : 0.0;
        }

        $this->recalc();
    }

    private function recalc(): void
    {
        $this->sumPct   = round(collect($this->items)->sum(fn($r) => (float)($r['porcentaje'] ?? 0)), 2);
        $this->sumMonto = round(collect($this->items)->sum(fn($r) => (float)($r['monto'] ?? 0)), 2);
        $this->diff     = round($this->fac_saldo - $this->sumMonto, 2);
    }

    /** Turno abierto */
    private function turnoPendienteAnterior(): ?turnos_caja
    {
        return turnos_caja::turnoPendienteDeCerrar();
    }

    private function turnoAbiertoActual(): ?turnos_caja
    {
        return turnos_caja::turnoAbiertoDelDia();
    }
    private function metodoDesdeMedio(?MedioPagos $medio): ?string
    {
        if (!$medio) return null;
        $txt = trim(($medio->codigo ? "{$medio->codigo} - " : '') . ($medio->nombre ?? ''));
        return $txt !== '' ? $txt : null;
    }

   public function guardarPago(): void
{
    try {
        $this->validate();
    } catch (\Illuminate\Validation\ValidationException $e) {
        $primerError = collect($e->validator->errors()->all())->first() ?: 'Revisa los datos del pago.';
        PendingToast::create()->error()
            ->message($primerError)
            ->duration(8000);
        throw $e;
    }

    if (!$this->facturaId) {
        PendingToast::create()->warning()
            ->message('Debe seleccionar una factura antes de registrar el pago.')
            ->duration(6000);
        return;
    }

    $factura = Factura::with('pagos')->findOrFail($this->facturaId);

    // ✅ recalcular valores reales por seguridad
    $total  = round((float)($factura->total ?? 0), 2);
    $pagado = round((float)($factura->pagado ?? $factura->pagos->sum('monto') ?? 0), 2);
    $saldo  = round(max($total - $pagado, 0), 2);

    $this->fac_total  = $total;
    $this->fac_pagado = $pagado;
    $this->fac_saldo  = $saldo;
    $this->recalc();

    if ($this->fac_saldo <= 0) {
        PendingToast::create()->warning()
            ->message('La factura no tiene saldo pendiente.')
            ->duration(7000);
        return;
    }

    if (round($this->sumMonto, 2) !== round($this->fac_saldo, 2)) {
        PendingToast::create()->warning()
            ->message('El total distribuido debe ser igual al saldo de la factura.')
            ->duration(7000);
        return;
    }

    $idsMedios = collect($this->items)
        ->pluck('medio_pago_id')
        ->filter()
        ->values()
        ->all();

    $mediosUsados = empty($idsMedios)
        ? collect()
        : MedioPagos::whereIn('id', $idsMedios)->get();

    $requiereTurno = $mediosUsados->contains(fn($m) => $this->medioRequiereTurno($m));

    $turnoPendiente = $this->turnoPendienteAnterior();
    if ($requiereTurno && $turnoPendiente) {
        $fecha = optional($turnoPendiente->fecha_inicio)?->format('d/m/Y');
        $abiertoPor = $turnoPendiente->abiertoPor?->name ?? 'otro usuario';

        PendingToast::create()->warning()
            ->message("Existe una caja abierta del día {$fecha}, abierta por {$abiertoPor}. Debes cerrarla antes de registrar pagos.")
            ->duration(9000);
        return;
    }

    $turno = $this->turnoAbiertoActual();

    if ($requiereTurno && !$turno) {
        PendingToast::create()->warning()
            ->message('No hay una caja abierta para hoy.')
            ->duration(6500);
        return;
    }

    try {
        DB::transaction(function () use ($factura, $turno, $mediosUsados) {
            $pagos = [];

            foreach ($this->items as $row) {
                $monto = (float)($row['monto'] ?? 0);
                if ($monto <= 0) {
                    continue;
                }

                /** @var MedioPagos|null $medio */
                $medio = $mediosUsados->firstWhere('id', (int)($row['medio_pago_id'] ?? 0))
                    ?: (($row['medio_pago_id'] ?? null)
                        ? MedioPagos::find((int)$row['medio_pago_id'])
                        : null);

                if (!$medio) {
                    continue;
                }

                $asociaTurno = $this->medioRequiereTurno($medio) && $turno;

                /** @var FacturaPago $pago */
                $pago = $factura->registrarPago([
                    'fecha'         => $this->fecha,
                    'medio_pago_id' => (int)($row['medio_pago_id'] ?? 0),
                    'metodo'        => $this->metodoDesdeMedio($medio),
                    'referencia'    => $row['referencia'] ?? null,
                    'monto'         => $monto,
                    'notas'         => $this->notas,
                    'turno_id'      => $asociaTurno ? $turno->id : null,
                    'user_id'       => Auth::id(),
                ]);

                $pagos[] = $pago;

                if ($asociaTurno) {
                    $this->acumularEnTurnoDinamico($turno, $medio, $monto, (int)$factura->id);
                }
            }

            if (empty($pagos)) {
                throw new \RuntimeException('No se generó ningún pago válido.');
            }

            $asiento = ContabilidadService::asientoDesdePagos($factura, $pagos, 'Pago aplicado a factura');

            foreach ($pagos as $p) {
                if ($p->isFillable('asiento_id') || Schema::hasColumn($p->getTable(), 'asiento_id')) {
                    $p->update(['asiento_id' => $asiento->id]);
                }
            }

            $factura->refresh()->recalcularTotales()->save();
        }, 3);
    } catch (\Throwable $e) {
        report($e);

        PendingToast::create()->error()
            ->message('No se pudo registrar y contabilizar el pago: ' . $e->getMessage())
            ->duration(9000);
        return;
    }

    PendingToast::create()->success()
        ->message('Pago registrado y contabilizado.')
        ->duration(5000);

    $this->show = false;

    $this->dispatch('pago-registrado', facturaId: $factura->id);
}
    // ==== helpers de config de medios / turno ====

    private function col(string $table, string $column): bool
    {
        try {
            return Schema::hasColumn($table, $column);
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function boolCfg(?MedioPagos $medio, string $attr, bool $default): bool
    {
        if (!$medio) return $default;
        if (!$this->col($medio->getTable(), $attr)) return $default;
        $v = data_get($medio, $attr);
        $res = filter_var($v, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        return is_null($res) ? (bool)$v : $res;
    }

    private function strCfg(?MedioPagos $medio, string $attr, ?string $default = null): ?string
    {
        if (!$medio) return $default;
        if (!$this->col($medio->getTable(), $attr)) return $default;
        $v = trim((string)data_get($medio, $attr));
        return $v !== '' ? $v : $default;
    }

    private function medioRequiereTurno(?MedioPagos $medio): bool
    {
        return $this->boolCfg($medio, 'requiere_turno', false);
    }

    private function medioContarEnTotal(?MedioPagos $medio): bool
    {
        return $this->boolCfg($medio, 'contar_en_total', true);
    }

    private function medioCrearMovimiento(?MedioPagos $medio): bool
    {
        return $this->boolCfg($medio, 'crear_movimiento', false);
    }

    private function medioTipoMovimiento(?MedioPagos $medio): string
    {
        return $this->strCfg($medio, 'tipo_movimiento', 'INGRESO') ?: 'INGRESO';
    }

    private function medioClaveTurno(?MedioPagos $medio): ?string
    {
        return $this->strCfg($medio, 'clave_turno', null);
    }

    private function acumularEnTurnoDinamico(turnos_caja $turno, MedioPagos $medio, float $monto, int $facturaId): void
    {
        if ($this->medioContarEnTotal($medio) && $this->col('turnos_caja', 'total_ventas')) {
            $turno->increment('total_ventas', $monto);
        }

        $col = $this->medioClaveTurno($medio);
        if ($col && $this->col('turnos_caja', $col)) {
            $turno->increment($col, $monto);
        }

        $resumen = (array)($turno->resumen ?? []);
        $resumen['medios'] = $resumen['medios'] ?? [];
        $mid = (string)$medio->id;

        $prevMonto = (float)($resumen['medios'][$mid]['monto'] ?? 0);
        $prevMovs  = (int)($resumen['medios'][$mid]['movimientos'] ?? 0);

        $resumen['medios'][$mid] = [
            'medio_id'    => (int)$medio->id,
            'codigo'      => (string)($medio->codigo ?? ''),
            'nombre'      => (string)($medio->nombre ?? ''),
            'monto'       => round($prevMonto + $monto, 2),
            'movimientos' => $prevMovs + 1,
        ];

        $turno->update(['resumen' => $resumen]);

        if ($this->medioCrearMovimiento($medio)) {
            CajaMovimiento::create([
                'turno_id' => $turno->id,
                'user_id'  => Auth::id(),
                'tipo'     => $this->medioTipoMovimiento($medio),
                'monto'    => $monto,
                'motivo'   => 'Pago factura ID ' . $facturaId . ' (' . $this->metodoDesdeMedio($medio) . ')',
            ]);
        }
    }
}
