<?php

namespace App\Livewire\TurnosCaja;

use App\Models\TurnosCaja\turnos_caja;
use App\Models\TurnosCaja\CajaMovimiento;
use App\Models\Factura\FacturaPago;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Component;

class TurnoCaja extends Component
{
    // Form apertura
    public ?int $bodega_id = null;
    public float $base_inicial = 0;

    // Form movimientos
    public string $tipo_mov = 'INGRESO';
    public ?float $monto = null;
    public ?string $motivo = null;

    // Estado
    public ?turnos_caja $turno = null;

    // Resúmenes para la vista
    public array $resumen = [];
    public array $porTipo = [];   // EFECTIVO/DEBITO/...
    public array $porMedio = [];  // cada medio

    protected $rules = [
        'bodega_id'    => 'nullable|integer',
        'base_inicial' => 'required|numeric|min:0',
        'tipo_mov'     => 'required|in:INGRESO,RETIRO,DEVOLUCION',
        'monto'        => 'nullable|numeric|min:0.01',
        'motivo'       => 'nullable|string|max:255',
    ];

    public function mount(): void
    {
        $this->turno = turnos_caja::query()
            ->where('user_id', Auth::id())
            ->where('estado', 'abierto')
            ->latest('id')
            ->first();

        $this->refrescarResumenes(); // ✅ nombre correcto
    }

    public function render()
    {
        return view('livewire.turnos-caja.turno-caja', [
            'turno'    => $this->turno,
            'resumen'  => $this->resumen,
            'porTipo'  => $this->porTipo,
            'porMedio' => $this->porMedio,
        ]);
    }

    /** Abrir turno */
    public function abrir(): void
    {
        $this->validateOnly('base_inicial');

        if (turnos_caja::where('user_id', Auth::id())->where('estado','abierto')->exists()) {
            session()->flash('error', 'Ya tienes un turno abierto.');
            return;
        }

        $this->turno = turnos_caja::create([
            'user_id'      => Auth::id(),
            'bodega_id'    => $this->bodega_id,
            'fecha_inicio' => now(),
            'base_inicial' => $this->base_inicial,
            'estado'       => 'abierto',
            'resumen'      => [],
        ]);

        session()->flash('message', 'Turno abierto.');
        $this->refrescarResumenes();
    }

    /** Registrar ingreso/retiro/devolución manual */
    public function agregarMovimiento(): void
    {
        if (!$this->turno || $this->turno->estaCerrado()) {
            session()->flash('error', 'No hay turno abierto.');
            return;
        }
        $this->validate([
            'tipo_mov',
            'monto'  => 'required|numeric|min:0.01',
            'motivo' => 'nullable|string|max:255',
        ]);

        DB::transaction(function () {
            CajaMovimiento::create([
                'turno_id' => $this->turno->id,
                'user_id'  => Auth::id(),
                'tipo'     => $this->tipo_mov,
                'monto'    => $this->monto,
                'motivo'   => $this->motivo,
            ]);

            if ($this->tipo_mov === 'INGRESO')    $this->turno->increment('ingresos_efectivo', $this->monto);
            if ($this->tipo_mov === 'RETIRO')     $this->turno->increment('retiros_efectivo',  $this->monto);
            if ($this->tipo_mov === 'DEVOLUCION') $this->turno->increment('devoluciones',     $this->monto);
        });

        $this->monto = $this->motivo = null;
        session()->flash('message', 'Movimiento registrado.');
        $this->refrescarResumenes();
    }

    /** Cerrar turno con arqueo */
    public function cerrar(): void
    {
        if (!$this->turno || $this->turno->estaCerrado()) {
            session()->flash('error','No hay turno abierto.');
            return;
        }

        DB::transaction(function () {
            $pagos = FacturaPago::where('turno_id', $this->turno->id)->get();

            $byTipo  = $pagos->groupBy('medio_tipo')->map->sum('monto')->toArray();
            $byMedio = $pagos->groupBy('medio_codigo')->map(function($g){
                return [
                    'codigo' => $g->first()->medio_codigo,
                    'tipo'   => $g->first()->medio_tipo,
                    'nombre' => $g->first()->medio_codigo,
                    'total'  => $g->sum('monto'),
                ];
            })->values()->toArray();

            $totalVentas = (float) $pagos->sum('monto');

            $resumen = [
                'base_inicial'          => (float) $this->turno->base_inicial,
                'total_ventas'          => $totalVentas,
                'por_tipo'              => $byTipo,
                'por_medio'             => $byMedio,
                'ingresos_efectivo'     => (float) $this->turno->ingresos_efectivo,
                'retiros_efectivo'      => (float) $this->turno->retiros_efectivo,
                'devoluciones'          => (float) $this->turno->devoluciones,
                'efectivo_esperado'     => $this->turno->efectivoEsperado(),
                'total_cobrado_sin_cxc' => $this->turno->totalCobrado(),
                'cerrado_por'           => Auth::id(),
            ];

            $this->turno->update([
                'estado'        => 'cerrado',
                'fecha_cierre'  => now(),
                'total_ventas'  => $totalVentas,
                'ventas_efectivo'        => (float)($byTipo['EFECTIVO'] ?? 0),
                'ventas_debito'          => (float)($byTipo['DEBITO'] ?? 0),
                'ventas_credito_tarjeta' => (float)($byTipo['CREDITO'] ?? 0),
                'ventas_transferencias'  => (float)($byTipo['TRANSFERENCIA'] ?? 0),
                'ventas_a_credito'       => (float)($byTipo['CREDITO_CLIENTE'] ?? 0),
                'resumen'       => $resumen,
            ]);
        });

        session()->flash('message','Turno cerrado.');
        $this->turno = null;
        $this->refrescarResumenes();
    }

    /** ✅ Nombre correcto */
    private function refrescarResumenes(): void
    {
        $this->resumen = $this->porTipo = $this->porMedio = [];
        if (!$this->turno) return;

        // Detectar columnas reales en factura_pagos
        $tipoColCandidates   = ['medio_tipo','tipo','tipo_medio','metodo','forma_pago'];
        $codigoColCandidates = ['medio_codigo','codigo','medio','metodo_codigo','referencia','ref'];

        $tipoCol   = collect($tipoColCandidates)->first(fn($c) => Schema::hasColumn('factura_pagos', $c));
        $codigoCol = collect($codigoColCandidates)->first(fn($c) => Schema::hasColumn('factura_pagos', $c));

        $tipoExpr   = $tipoCol   ? '['.$tipoCol.']'   : "CAST('OTRO' AS varchar(30))";
        $codigoExpr = $codigoCol ? '['.$codigoCol.']' : "CAST('—' AS varchar(50))";

        $pagos = FacturaPago::query()
            ->selectRaw("monto, {$tipoExpr} AS medio_tipo, {$codigoExpr} AS medio_codigo")
            ->where('turno_id', $this->turno->id)
            ->get();

        $this->resumen = [
            'base_inicial'        => (float) $this->turno->base_inicial,
            'total_ventas'        => (float) $pagos->sum('monto'),
            'devoluciones'        => (float) $this->turno->devoluciones,
            'ingresos'            => (float) $this->turno->ingresos_efectivo,
            'retiros'             => (float) $this->turno->retiros_efectivo,
            'ventas_credito_cxc'  => (float) $this->turno->ventas_a_credito,
        ];

        $this->porTipo = $pagos->groupBy('medio_tipo')
            ->map(fn($g) => (float) $g->sum('monto'))
            ->sortDesc()
            ->toArray();

        $this->porMedio = $pagos->groupBy('medio_codigo')
            ->map(function ($g) {
                return [
                    'codigo' => $g->first()->medio_codigo,
                    'nombre' => $g->first()->medio_codigo,
                    'tipo'   => $g->first()->medio_tipo,
                    'total'  => (float) $g->sum('monto'),
                ];
            })
            ->values()
            ->all();
    }

    /** 🔁 Alias por si quedó alguna llamada antigua con “resumenses” */
    private function refrescarResumenses(): void
    {
        $this->refrescarResumenes();
    }

    /** Helper público para otros flujos */
    public static function turnoAbiertoDe(int $userId): ?turnos_caja
    {
        return turnos_caja::where('user_id',$userId)
            ->where('estado','abierto')
            ->latest('id')
            ->first();
    }
}
