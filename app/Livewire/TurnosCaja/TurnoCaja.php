<?php

namespace App\Livewire\TurnosCaja;

use Livewire\Component;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

use App\Models\TurnosCaja\CajaMovimiento;
use App\Models\Factura\FacturaPago;
use App\Models\Factura\Factura;
use App\Models\TurnosCaja\turnos_caja as Turno; // 👈 alias para el MODELO

class TurnoCaja extends Component
{
    /** Estado del turno actual (modelo) */
    public ?Turno $turno = null; // 👈 usa el alias del modelo

    /** Apertura */
    public ?int $bodega_id = null;
    public string $base_inicial = '0';

    /** Movimientos (ingreso/retiro/devolución) */
    public string $tipo_mov = 'INGRESO';  // INGRESO | RETIRO | DEVOLUCION
    public string $monto = '';
    public ?string $motivo = null;

    protected function rules(): array
    {
        return [
            'bodega_id'    => ['nullable','integer','exists:bodegas,id'],
            'base_inicial' => ['required','numeric','min:0'],
            'tipo_mov'     => ['required','in:INGRESO,RETIRO,DEVOLUCION'],
            'monto'        => ['required','numeric','gt:0'],
            'motivo'       => ['nullable','string','max:255'],
        ];
    }

    public function mount(?int $bodega = null): void
    {
        $this->bodega_id = $bodega;

        $userId = Auth::id();
        if (!$userId) abort(401);

        // 👇 ahora sí llamamos al MODELO (alias Turno), no al componente
        $this->turno = Turno::query()
            ->where('user_id', $userId)
            ->when($this->bodega_id, fn($q) => $q->where('bodega_id', $this->bodega_id))
            ->where('estado', 'abierto')
            ->first();
    }

    /** Abrir turno (si ya hay uno abierto, devuelve el mismo) */
    public function abrir(): void
    {
        $this->validateOnly('base_inicial');

        // 👇 usar el alias del modelo
        $this->turno = Turno::firstOrCreate(
            [
                'user_id'   => Auth::id(),
                'bodega_id' => $this->bodega_id,
                'estado'    => 'abierto',
            ],
            [
                'fecha_inicio' => now(),
                'base_inicial' => (float) $this->base_inicial,
            ]
        );

        $this->dispatch('$refresh');
        session()->flash('message', '✅ Turno abierto.');
    }

    /** Ingreso / Retiro / Devolución manual */
    public function agregarMovimiento(): void
    {
        if (!$this->turno) {
            session()->flash('error', 'Primero abre un turno.');
            return;
        }

        $this->validate([
            'tipo_mov' => 'required|in:INGRESO,RETIRO,DEVOLUCION',
            'monto'    => 'required|numeric|gt:0',
            'motivo'   => 'nullable|string|max:255',
        ]);

        $this->turno->movimientos()->create([
            'user_id' => Auth::id(),
            'tipo'    => $this->tipo_mov,
            'monto'   => (float) $this->monto,
            'motivo'  => $this->motivo,
        ]);

        $this->monto = '';
        $this->motivo = null;
        $this->turno->refresh();

        session()->flash('message', '💰 Movimiento registrado.');
    }

    /** Cerrar turno y consolidar totales */
    public function cerrar(): void
    {
        if (!$this->turno) {
            session()->flash('error', 'No hay turno abierto.');
            return;
        }

        DB::transaction(function () {
            $t = $this->turno->fresh();

            $pagos = FacturaPago::with('factura')
                ->where('turno_id', $t->id)
                ->get();

            $ventasEfectivo = (float) $pagos->where('metodo','EFECTIVO')->sum('monto');
            $ventasDebito   = (float) $pagos->where('metodo','DEBITO')->sum('monto');
            $ventasTC       = (float) $pagos->where('metodo','CREDITO')->sum('monto');
            $ventasTransf   = (float) $pagos->where('metodo','TRANSFERENCIA')->sum('monto');

            $ventasCredito = (float) Factura::query()
                ->whereBetween('created_at', [$t->fecha_inicio, now()])
                ->where('tipo_pago','credito')
                ->sum('total');

            $ingresos = (float) $t->movimientos()->where('tipo','INGRESO')->sum('monto');
            $retiros  = (float) $t->movimientos()->where('tipo','RETIRO')->sum('monto');
            $devol    = (float) $t->movimientos()->where('tipo','DEVOLUCION')->sum('monto');

            $totalVentas = $ventasEfectivo + $ventasDebito + $ventasTC + $ventasTransf + $ventasCredito;

            $t->update([
                'fecha_cierre'           => now(),
                'estado'                 => 'cerrado',
                'total_ventas'           => $totalVentas,
                'ventas_efectivo'        => $ventasEfectivo,
                'ventas_debito'          => $ventasDebito,
                'ventas_credito_tarjeta' => $ventasTC,
                'ventas_transferencias'  => $ventasTransf,
                'ventas_a_credito'       => $ventasCredito,
                'devoluciones'           => $devol,
                'ingresos_efectivo'      => $ingresos,
                'retiros_efectivo'       => $retiros,
                'resumen' => [
                    // si prefieres JSON plano: ->toArray()
                    'pagos' => $pagos->groupBy('metodo')->map->sum('monto'),
                ],
            ]);

            $this->turno = $t->fresh(['pagos','movimientos']);
        });

        $this->dispatch('$refresh');
        session()->flash('message', '✅ Turno cerrado.');
    }

    /** Resumen para la vista */
    public function getResumenProperty(): array
    {
        $t = $this->turno?->fresh();

        if (!$t) {
            return [
                'total_ventas'          => 0,
                'base_inicial'          => (float) $this->base_inicial,
                'ventas_efectivo'       => 0,
                'ventas_debito'         => 0,
                'ventas_credito'        => 0,
                'ventas_transferencias' => 0,
                'ventas_credito_cxc'    => 0,
                'devoluciones'          => 0,
                'ingresos'              => 0,
                'retiros'               => 0,
            ];
        }

        return [
            'total_ventas'          => (float) $t->total_ventas,
            'base_inicial'          => (float) $t->base_inicial,
            'ventas_efectivo'       => (float) $t->ventas_efectivo,
            'ventas_debito'         => (float) $t->ventas_debito,
            'ventas_credito'        => (float) $t->ventas_credito_tarjeta,
            'ventas_transferencias' => (float) $t->ventas_transferencias,
            'ventas_credito_cxc'    => (float) $t->ventas_a_credito,
            'devoluciones'          => (float) $t->devoluciones,
            'ingresos'              => (float) $t->ingresos_efectivo,
            'retiros'               => (float) $t->retiros_efectivo,
        ];
    }

    public function render()
    {
        return view('livewire.turnos-caja.turno-caja', [
            'turno'   => $this->turno,
            'resumen' => $this->resumen,
        ]);
    }
}
