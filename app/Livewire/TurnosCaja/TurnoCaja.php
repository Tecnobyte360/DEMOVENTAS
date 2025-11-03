<?php

namespace App\Livewire\TurnosCaja;

use Livewire\Component;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

use App\Models\TurnosCaja\CajaMovimiento;
use App\Models\Factura\FacturaPago;
use App\Models\Factura\Factura;
use App\Models\TurnosCaja\turnos_caja as Turno;

class TurnoCaja extends Component
{
    public ?Turno $turno = null;

    // Apertura
    public ?int $bodega_id = null;
    public string $base_inicial = '0';

    // Movimientos
    public string $tipo_mov = 'INGRESO';
    public string $monto = '';
    public ?string $motivo = null;

    protected function rules(): array
    {
        return [
            'bodega_id'    => ['nullable', 'integer', 'exists:bodegas,id'],
            'base_inicial' => ['required', 'numeric', 'min:0'],
            'tipo_mov'     => ['required', 'in:INGRESO,RETIRO,DEVOLUCION'],
            'monto'        => ['required', 'numeric', 'gt:0'],
            'motivo'       => ['nullable', 'string', 'max:255'],
        ];
    }

    public function mount(?int $bodega = null): void
    {
        $this->bodega_id = $bodega;

        $userId = Auth::id();
        if (!$userId) abort(401);

        // Buscar turno abierto
        $this->turno = Turno::query()
            ->where('user_id', $userId)
            ->when($this->bodega_id, fn($q) => $q->where('bodega_id', $this->bodega_id))
            ->where('estado', 'abierto')
            ->first();
    }

    /**
     * Abrir turno de caja
     */
    public function abrir(): void
    {
        $this->validateOnly('base_inicial');

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
        session()->flash('message', '✅ Turno abierto correctamente.');
    }

    /**
     * Agregar movimiento manual (ingreso/retiro/devolución)
     */
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

    /**
     * Cerrar turno y consolidar totales
     */
    public function cerrar(): void
    {
        if (!$this->turno) {
            session()->flash('error', 'No hay turno abierto.');
            return;
        }

        DB::transaction(function () {
            $t = $this->turno->fresh();

            // Pagos del turno agrupados por método
            $pagos = FacturaPago::query()
                ->leftJoin('medio_pagos as mp', 'mp.id', '=', 'factura_pagos.medio_pago_id')
                ->where('factura_pagos.turno_id', $t->id)
                ->get([
                    'factura_pagos.*',
                    'mp.id as medio_id',
                    'mp.codigo as medio_codigo',
                    'mp.nombre as medio_nombre',
                ]);

            // Agrupar por TIPO y por MEDIO
            $porTipo  = [];
            $porMedio = [];

            foreach ($pagos as $p) {
                $tipo = $this->tipoDesdePagoRow($p);

                $porTipo[$tipo] = ($porTipo[$tipo] ?? 0) + (float)$p->monto;

                $keyMedio = $p->medio_id ? (string)$p->medio_id : 'sin_medio';
                $porMedio[$keyMedio] = [
                    'medio_id' => $p->medio_id,
                    'codigo'   => $p->medio_codigo,
                    'nombre'   => $p->medio_nombre,
                    'tipo'     => $tipo,
                    'total'    => ($porMedio[$keyMedio]['total'] ?? 0) + (float)$p->monto,
                ];
            }

            // Totales por tipo
            $ventasEfectivo = (float)($porTipo['EFECTIVO']      ?? 0);
            $ventasDebito   = (float)($porTipo['DEBITO']        ?? 0);
            $ventasTC       = (float)($porTipo['CREDITO']       ?? 0);
            $ventasTransf   = (float)($porTipo['TRANSFERENCIA'] ?? 0);

            // Ventas a crédito (CXC)
            $ventasCredito = (float) Factura::query()
                ->whereBetween('created_at', [$t->fecha_inicio, now()])
                ->where('tipo_pago', 'credito')
                ->where('estado', 'emitida')
                ->sum('total');

            // Movimientos manuales
            $ingresos = (float) $t->movimientos()->where('tipo', 'INGRESO')->sum('monto');
            $retiros  = (float) $t->movimientos()->where('tipo', 'RETIRO')->sum('monto');
            $devol    = (float) $t->movimientos()->where('tipo', 'DEVOLUCION')->sum('monto');

            // Total de ventas
            $totalVentas = array_sum($porTipo) + $ventasCredito;

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
                    'por_tipo'  => $porTipo,
                    'por_medio' => array_values($porMedio),
                ],
            ]);

            $this->turno = $t->fresh(['pagos', 'movimientos']);
        });

        $this->dispatch('$refresh');
        session()->flash('message', '✅ Turno cerrado correctamente.');
    }

    /**
     * Resumen computed property
     */
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
                'efectivo_esperado'     => (float) $this->base_inicial,
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
            'efectivo_esperado'     => $t->efectivoEsperado(),
        ];
    }

    public function render()
    {
        $porTipo  = (array) data_get($this->turno, 'resumen.por_tipo', []);
        $porMedio = (array) data_get($this->turno, 'resumen.por_medio', []);

        return view('livewire.turnos-caja.turno-caja', [
            'turno'    => $this->turno,
            'resumen'  => $this->resumen,
            'porTipo'  => $porTipo,
            'porMedio' => $porMedio,
        ]);
    }

    /**
     * Determinar tipo de pago desde el registro
     */
    private function tipoDesdePagoRow($row): string
    {
        if (!empty($row->metodo)) {
            return strtoupper((string)$row->metodo);
        }

        $cod = strtoupper((string)($row->medio_codigo ?? ''));
        $nom = strtoupper((string)($row->medio_nombre ?? ''));

        if ($cod === 'EFECTIVO' || str_contains($nom, 'EFECTIVO')) return 'EFECTIVO';
        if (str_contains($cod, 'DEBIT') || str_contains($nom, 'DEBIT')) return 'DEBITO';
        if (str_contains($cod, 'CRED') || str_contains($nom, 'CREDITO')) return 'CREDITO';
        if (str_contains($cod, 'TRANS') || str_contains($nom, 'TRANSFER')) return 'TRANSFERENCIA';
        if (str_contains($nom, 'CHEQUE')) return 'CHEQUE';
        if (str_contains($nom, 'BONO')) return 'BONO';

        return 'OTRO';
    }
}