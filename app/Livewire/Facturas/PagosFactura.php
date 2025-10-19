<?php

namespace App\Livewire\Facturas;

use Livewire\Component;
use Livewire\Attributes\On;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Masmerise\Toaster\PendingToast;

use App\Models\Factura\Factura;
use App\Models\Factura\FacturaPago;
use App\Models\MediosPago\MedioPagos;
use App\Services\ContabilidadService;
use App\Models\TurnosCaja\turnos_caja;

class PagosFactura extends Component
{
    public ?int $facturaId = null;

    public bool $show = false;
    public string $fecha = '';
    public ?string $notas = null;

    /** @var Collection<int,\App\Models\MediosPago\MedioPagos> */
    public Collection $medios;

    public float $fac_total  = 0.0;
    public float $fac_pagado = 0.0;
    public float $fac_saldo  = 0.0;

    public array $items = [];

    public float $sumPct   = 0.0;
    public float $sumMonto = 0.0;
    public float $diff     = 0.0;

    protected $rules = [
        'fecha'                      => 'required|date',
        'items'                      => 'required|array|min:1',
        'items.*.medio_pago_id'      => 'required|integer',
        'items.*.porcentaje'         => 'nullable|numeric|min:0|max:100',
        'items.*.monto'              => 'required|numeric|min:0.01',
        'items.*.referencia'         => 'nullable|string|max:120',
        'notas'                      => 'nullable|string',
    ];

    public function mount(): void
    {
        $this->fecha  = now()->toDateString();
        $this->medios = collect();
        $this->items  = [
            ['medio_pago_id' => null, 'porcentaje' => 0, 'monto' => 0, 'referencia' => null],
        ];
    }

    public function render()
    {
        $this->medios = MedioPagos::query()
            ->when(method_exists(MedioPagos::class, 'activos'), fn($q) => $q->activos())
            ->orderBy('nombre')
            ->get(['id','codigo','nombre']);

        if ($this->facturaId) {
            $f = Factura::find($this->facturaId);
            if ($f) {
                $this->fac_total  = (float) $f->total;
                $this->fac_pagado = (float) $f->pagado;
                $this->fac_saldo  = (float) $f->saldo;
            }
        }

        $this->recalc();

        return view('livewire.facturas.pagos-factura', [
            'factura' => $this->facturaId ? Factura::with('pagos')->find($this->facturaId) : null,
            'medios'  => $this->medios,
        ]);
    }

    #[On('abrir-modal-pago')]
    public function abrir(?int $facturaId = null): void
    {
        $this->facturaId = $facturaId;
        $this->show      = true;
        $this->fecha     = now()->toDateString();
        $this->notas     = null;

        // cargar medios
        $this->medios = MedioPagos::query()
            ->when(method_exists(MedioPagos::class, 'activos'), fn($q) => $q->activos())
            ->orderBy('nombre')
            ->get(['id','codigo','nombre']);

        // si se abre desde una factura existente
        if ($facturaId) {
            $f = Factura::findOrFail($facturaId);
            $this->fac_total  = (float) $f->total;
            $this->fac_pagado = (float) $f->pagado;
            $this->fac_saldo  = (float) $f->saldo;

            $medioDefault = $this->medios->first()?->id ?? null;
            $this->items = [[
                'medio_pago_id' => $medioDefault,
                'porcentaje'    => $this->fac_saldo > 0 ? 100 : 0,
                'monto'         => round($this->fac_saldo, 2),
                'referencia'    => null,
            ]];
        } else {
            // pago manual (sin factura)
            $this->fac_total  = 0;
            $this->fac_pagado = 0;
            $this->fac_saldo  = 0;

            $this->items = [[
                'medio_pago_id' => null,
                'porcentaje'    => 0,
                'monto'         => 0,
                'referencia'    => null,
            ]];
        }

        $this->resetErrorBag();
        $this->resetValidation();
        $this->recalc();
    }

    public function cerrar(): void
    {
        $this->show = false;
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

    public function updated($name, $value): void
    {
        if (preg_match('/^items\.(\d+)\.porcentaje$/', $name, $m)) {
            $i = (int) $m[1];
            $pct = max(0, min(100, (float) $value));
            $this->items[$i]['porcentaje'] = $pct;
            $this->items[$i]['monto']      = round($this->fac_saldo * $pct / 100, 2);
        }

        if (preg_match('/^items\.(\d+)\.monto$/', $name, $m)) {
            $i = (int) $m[1];
            $monto = max(0, round((float) $value, 2));
            $this->items[$i]['monto']      = $monto;
            $this->items[$i]['porcentaje'] = $this->fac_saldo > 0 ? round(($monto / $this->fac_saldo) * 100, 2) : 0.0;
        }

        $this->recalc();
    }

    private function recalc(): void
    {
        $this->sumPct   = round(collect($this->items)->sum(fn($r) => (float)($r['porcentaje'] ?? 0)), 2);
        $this->sumMonto = round(collect($this->items)->sum(fn($r) => (float)($r['monto'] ?? 0)), 2);
        $this->diff     = round($this->fac_saldo - $this->sumMonto, 2);
    }

    /** Turno abierto del usuario actual */
    private function turnoAbiertoActual(): ?turnos_caja
    {
        $userId = Auth::id();
        if (!$userId) return null;

        return turnos_caja::query()
            ->where('user_id', $userId)
            ->where('estado', 'abierto')
            ->first();
    }

    public function guardarPago(): void
    {
        $this->validate();

        if (!$this->facturaId) return;
        $factura = Factura::findOrFail($this->facturaId);

        // Validar que el total distribuido sea igual al saldo
        if (round($this->sumMonto, 2) !== round($this->fac_saldo, 2)) {
            PendingToast::create()->warning()
                ->message('El total distribuido debe ser igual al saldo de la factura.')
                ->duration(7000);
            return;
        }

        // 🔎 ¿Hay efectivo? (exige turno abierto si lo hay)
        $idsMedios = collect($this->items)->pluck('medio_pago_id')->filter()->values()->all();
        $mediosUsados = empty($idsMedios) ? collect() : MedioPagos::whereIn('id', $idsMedios)->get(['id','codigo','nombre']);
        $hayEfectivo = $mediosUsados->contains(function ($m) {
            $cod = strtoupper((string)($m->codigo ?? ''));
            $nom = strtoupper((string)($m->nombre ?? ''));
            return $cod === 'EFECTIVO' || str_contains($nom, 'EFECTIVO') || $cod === 'CASH';
        });

        $turno = $this->turnoAbiertoActual();
        if ($hayEfectivo && !$turno) {
            PendingToast::create()->warning()
                ->message('No hay un turno de caja abierto para registrar pagos en EFECTIVO. Abre un turno e inténtalo de nuevo.')
                ->duration(6500);
            return;
        }

        try {
            DB::transaction(function () use ($factura, $turno) {
                $pagos    = [];
                $aplicado = 0.0;

                foreach ($this->items as $row) {
                    $monto = (float) ($row['monto'] ?? 0);
                    if ($monto <= 0) continue;

                    /** @var FacturaPago $pago */
                    $pago = $factura->registrarPago([
                        'fecha'         => $this->fecha,
                        'medio_pago_id' => (int)($row['medio_pago_id'] ?? 0),
                        'metodo'        => $this->safeMetodo((int)($row['medio_pago_id'] ?? 0)),
                        'referencia'    => $row['referencia'] ?? null,
                        'monto'         => $monto,
                        'notas'         => $this->notas,
                        'turno_id'      => $turno?->id,
                    ]);

                    if (!$pago instanceof FacturaPago) {
                        throw new \RuntimeException('registrarPago() no devolvió FacturaPago.');
                    }

                    $pagos[]   = $pago;
                    $aplicado += $monto;
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
                ->message('No se pudo registrar y contabilizar el pago: '.$e->getMessage())
                ->duration(9000);
            return;
        }

        // ✅ Éxito
        $factura->refresh();

        // 🚀 Emitir automáticamente si es contado y pago total
        if ($factura->tipo_pago === 'contado' && $factura->saldo <= 0.01) {
            PendingToast::create()->info()
                ->message('Factura de contado: pago completo recibido, emitiendo automáticamente...')
                ->duration(4000);
        }

        // 🔄 Notificar al formulario principal (ahí se llama a emitir y cerrar)
        $this->dispatch('pago-registrado', facturaId: $factura->id)
            ->to(\App\Livewire\Facturas\FacturaForm::class);

        PendingToast::create()->success()
            ->message('Pago registrado y contabilizado.')
            ->duration(4000);

        $this->dispatch('abrir-factura', id: $factura->id)
            ->to(\App\Livewire\Facturas\FacturaForm::class);

        $this->show = false;
    }

    private function safeMetodo(int $medioId, int $maxLen = 60): ?string
    {
        if ($this->medios->isNotEmpty()) {
            $m = $this->medios->firstWhere('id', $medioId);
            if ($m) {
                $texto = trim(($m->codigo ? "{$m->codigo} - " : '').($m->nombre ?? ''));
                return $texto !== '' ? mb_strimwidth($texto, 0, $maxLen, '') : null;
            }
        }

        $m = MedioPagos::find($medioId);
        if (!$m) return null;

        $texto = trim(($m->codigo ? "{$m->codigo} - " : '').($m->nombre ?? ''));
        return $texto !== '' ? mb_strimwidth($texto, 0, $maxLen, '') : null;
    }
}
