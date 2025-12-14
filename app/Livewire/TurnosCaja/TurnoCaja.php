<?php

namespace App\Livewire\TurnosCaja;

use App\Models\TurnosCaja\turnos_caja;
use App\Models\TurnosCaja\CajaMovimiento;
use App\Models\Factura\FacturaPago;
use App\Models\MediosPago\MedioPagos;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;
use Livewire\Component;
use Masmerise\Toaster\PendingToast;

class TurnoCaja extends Component
{
    // Form apertura
    public float $base_inicial = 0;

    // Form movimientos manuales
    public string $tipo_mov = 'INGRESO';
    public ?float $monto = null;
    public ?string $motivo = null;
    public array $mediosActivos = [];
    public array $mapMediosPorTurno = [];
    // Estado
    public ?turnos_caja $turno = null;

    // Resúmenes para la vista (turno actual)
    public array $resumen  = [];
    public array $porTipo  = [];   // EFECTIVO / DEBITO / ...
    public array $porMedio = [];   // cada medio

    // Filtros e informe histórico
    public ?string $filtro_desde = null;
    public ?string $filtro_hasta = null;

    public array $totalesInforme = [];
    public $turnosInforme = [];

    protected $rules = [
        'base_inicial' => 'required|numeric|min:0',
        'tipo_mov'     => 'required|in:INGRESO,RETIRO,DEVOLUCION',
        'monto'        => 'nullable|numeric|min:0.01',
        'motivo'       => 'nullable|string|max:255',
        'filtro_desde' => 'nullable|date',
        'filtro_hasta' => 'nullable|date|after_or_equal:filtro_desde',
    ];

    public function mount(): void
    {
        try {
            // Turno abierto único por usuario (helper del modelo)
            $this->turno = turnos_caja::turnoAbiertoDe(Auth::id());

            // Rango de fechas por defecto: últimos 30 días
            $this->filtro_desde = now()->subDays(30)->toDateString();
            $this->filtro_hasta = now()->toDateString();

            $this->refrescarResumenes();
            $this->actualizarInforme();   // carga informe inicial
        } catch (\Throwable $e) {
            Log::error('Error en mount TurnoCaja: ' . $e->getMessage());

            PendingToast::create()
                ->error()
                ->message('Ocurrió un error cargando el turno de caja.')
                ->duration(8000)
                ->push();
        }
    }

    public function render()
    {
        // Obtener el turno actual y los detalles de pagos
        if ($this->turno) {
            // Vuelve a leer el turno con los últimos movimientos
            $this->turno = $this->turno->fresh();

            // Recargar el resumen del turno
            $this->refrescarResumenes();

            // Obtener los medios de pago activos con su saldo
            $mediosDePago = MedioPagos::where('activo', true)->get();

            // Calcular el saldo de cada medio de pago
            foreach ($mediosDePago as $medio) {
                $medio->saldo = $this->calcularSaldoMedioPago($medio);
            }

            // Puedes pasar los medios de pago a la vista
            return view('livewire.turnos-caja.turno-caja', [
                'turno'          => $this->turno,
                'mediosDePago'   => $mediosDePago,  // Agregar a la vista
                'resumen'        => $this->resumen,
                'porTipo'        => $this->porTipo,
                'porMedio'       => $this->porMedio,
                'turnosInforme'  => $this->turnosInforme,
                'totalesInforme' => $this->totalesInforme,
            ]);
        }

        // Si no hay turno
        return view('livewire.turnos-caja.turno-caja');
    }

    private function calcularSaldoMedioPago($medio)
    {
        // Asumiendo que tienes una relación con los pagos y que cada pago tiene un medio de pago
        $totalPagado = FacturaPago::where('medio_pago_id', $medio->id)
            ->where('turno_id', $this->turno->id)
            ->sum('monto');

        // Calcular el saldo (puedes modificar la fórmula según tu lógica)
        $saldo = $medio->saldo_inicial - $totalPagado;

        return $saldo;
    }



    /* =========================================================
     * ABRIR TURNO
     * =======================================================*/
    public function abrir(): void
    {
        $this->validateOnly('base_inicial');

        try {
            // Verificar si ya tiene turno abierto
            if (turnos_caja::turnoAbiertoDe(Auth::id())) {
                PendingToast::create()
                    ->error()
                    ->message('Ya tienes un turno abierto.')
                    ->duration(6000)
                    ->push();

                return;
            }

            $this->turno = turnos_caja::create([
                'user_id'      => Auth::id(),
                'fecha_inicio' => now(),
                'base_inicial' => $this->base_inicial,
                'estado'       => 'abierto',
                'resumen'      => [],
            ]);

            PendingToast::create()
                ->success()
                ->message('Turno abierto correctamente.')
                ->duration(6000)
                ->push();

            $this->refrescarResumenes();
            $this->actualizarInforme();
        } catch (\Throwable $e) {
            Log::error('Error al abrir turno de caja: ' . $e->getMessage());

            PendingToast::create()
                ->error()
                ->message('No se pudo abrir el turno de caja.')
                ->duration(8000)
                ->push();
        }
    }

    /* =========================================================
     * MOVIMIENTOS MANUALES (INGRESO / RETIRO / DEVOLUCIÓN)
     * =======================================================*/
    public function agregarMovimiento(): void
    {
        if (!$this->turno || $this->turno->estaCerrado()) {
            PendingToast::create()
                ->error()
                ->message('No hay turno abierto.')
                ->duration(6000)
                ->push();

            return;
        }

        $this->validate([
            'tipo_mov' => 'required|in:INGRESO,RETIRO,DEVOLUCION',
            'monto'    => 'required|numeric|min:0.01',
            'motivo'   => 'nullable|string|max:255',
        ]);

        try {
            DB::transaction(function () {
                // Crear movimiento de caja
                CajaMovimiento::create([
                    'turno_id' => $this->turno->id,
                    'user_id'  => Auth::id(),
                    'tipo'     => $this->tipo_mov,
                    'monto'    => $this->monto,
                    'motivo'   => $this->motivo,
                ]);

                // Actualizar acumulados del turno
                if ($this->tipo_mov === 'INGRESO') {
                    $this->turno->increment('ingresos_efectivo', $this->monto);
                }

                if ($this->tipo_mov === 'RETIRO') {
                    $this->turno->increment('retiros_efectivo', $this->monto);
                }

                if ($this->tipo_mov === 'DEVOLUCION') {
                    $this->turno->increment('devoluciones', $this->monto);
                }

                // Sincronizar el modelo en memoria
                $this->turno->refresh();
            });

            $this->monto  = null;
            $this->motivo = null;

            PendingToast::create()
                ->success()
                ->message('Movimiento registrado.')
                ->duration(6000)
                ->push();

            $this->refrescarResumenes();
            $this->actualizarInforme();
        } catch (\Throwable $e) {
            Log::error('Error al registrar movimiento de caja: ' . $e->getMessage());

            PendingToast::create()
                ->error()
                ->message('Ocurrió un error al registrar el movimiento.')
                ->duration(8000)
                ->push();
        }
    }

    /* =========================================================
     * CERRAR TURNO
     * =======================================================*/
    public function cerrar(): void
    {
        if (!$this->turno || $this->turno->estaCerrado()) {
            PendingToast::create()
                ->error()
                ->message('No hay turno abierto.')
                ->duration(6000)
                ->push();

            return;
        }

        try {
            DB::transaction(function () {
                $pagos = FacturaPago::where('turno_id', $this->turno->id)->get();

                $byTipo  = $pagos->groupBy('medio_tipo')->map->sum('monto')->toArray();
                $byMedio = $pagos->groupBy('medio_codigo')->map(function ($g) {
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
                    'estado'                 => 'cerrado',
                    'fecha_cierre'           => now(),
                    'total_ventas'           => $totalVentas,
                    'ventas_efectivo'        => (float)($byTipo['EFECTIVO'] ?? 0),
                    'ventas_debito'          => (float)($byTipo['DEBITO'] ?? 0),
                    'ventas_credito_tarjeta' => (float)($byTipo['CREDITO'] ?? 0),
                    'ventas_transferencias'  => (float)($byTipo['TRANSFERENCIA'] ?? 0),
                    'ventas_a_credito'       => (float)($byTipo['CREDITO_CLIENTE'] ?? 0),
                    'resumen'                => $resumen,
                ]);
            });

            PendingToast::create()
                ->success()
                ->message('Turno cerrado.')
                ->duration(6000)
                ->push();

            // Quitar turno actual
            $this->turno   = null;
            $this->resumen = [];
            $this->porTipo = [];
            $this->porMedio = [];

            $this->actualizarInforme();
        } catch (\Throwable $e) {
            Log::error('Error al cerrar turno de caja: ' . $e->getMessage());

            PendingToast::create()
                ->error()
                ->message('No se pudo cerrar el turno de caja.')
                ->duration(8000)
                ->push();
        }
    }

    /* =========================================================
     * RESUMEN DEL TURNO ACTUAL
     * =======================================================*/
    private function refrescarResumenes(): void
    {
        $this->resumen = $this->porTipo = $this->porMedio = [];

        if (!$this->turno) {
            return;
        }

        try {
            // Detectar columnas reales en factura_pagos
            $tipoColCandidates   = ['medio_tipo', 'tipo', 'tipo_medio', 'metodo', 'forma_pago'];
            $codigoColCandidates = ['medio_codigo', 'codigo', 'medio', 'metodo_codigo', 'referencia', 'ref'];

            $tipoCol   = collect($tipoColCandidates)->first(fn($c) => Schema::hasColumn('factura_pagos', $c));
            $codigoCol = collect($codigoColCandidates)->first(fn($c) => Schema::hasColumn('factura_pagos', $c));

            $wrap     = fn(string $c) => DB::getQueryGrammar()->wrap($c);
            $bindings = [];

            if ($tipoCol) {
                $tipoExpr = $wrap($tipoCol);
            } else {
                $tipoExpr   = '?';
                $bindings[] = 'OTRO';
            }

            if ($codigoCol) {
                $codigoExpr = $wrap($codigoCol);
            } else {
                $codigoExpr = '?';
                $bindings[] = '-';
            }

            $pagos = FacturaPago::query()
                ->selectRaw(
                    "monto, {$tipoExpr} AS medio_tipo, {$codigoExpr} AS medio_codigo",
                    $bindings
                )
                ->where('turno_id', $this->turno->id)
                ->get();

            $this->resumen = [
                'base_inicial'       => (float) $this->turno->base_inicial,
                'total_ventas'       => (float) $pagos->where('medio_tipo', 'Efectivo')->sum('monto'),
                'devoluciones'       => (float) $this->turno->devoluciones,
                'ingresos'           => (float) $this->turno->ingresos_efectivo,
                'retiros'            => (float) $this->turno->retiros_efectivo,
                'ventas_credito_cxc' => (float) $this->turno->ventas_a_credito,
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
        } catch (\Throwable $e) {
            Log::error('Error al refrescar resúmenes de turno: ' . $e->getMessage());

            PendingToast::create()
                ->error()
                ->message('No se pudo refrescar el resumen del turno.')
                ->duration(8000);
        }
    }

    /* =========================================================
     * INFORME HISTÓRICO POR RANGO
     * =======================================================*/
   public function actualizarInforme(): void
{
    $this->validate([
        'filtro_desde' => 'nullable|date',
        'filtro_hasta' => 'nullable|date|after_or_equal:filtro_desde',
    ]);

    try {
        // 1️⃣ Turnos
        $query = turnos_caja::query()
            ->where('user_id', Auth::id());

        if ($this->filtro_desde) {
            $query->whereDate('fecha_inicio', '>=', $this->filtro_desde);
        }

        if ($this->filtro_hasta) {
            $query->whereDate('fecha_inicio', '<=', $this->filtro_hasta);
        }

        $turnos = $query->orderByDesc('fecha_inicio')->get();
        $this->turnosInforme = $turnos;

        // 2️⃣ Medios de pago activos
        $this->mediosActivos = MedioPagos::query()
            ->where('activo', true)
            ->orderBy('nombre')
            ->get(['id', 'nombre'])
            ->toArray();

        // 3️⃣ Totales por turno + medio
        $turnoIds = $turnos->pluck('id')->all();

        $rows = FacturaPago::query()
            ->selectRaw('turno_id, medio_pago_id, SUM(monto) as total')
            ->whereIn('turno_id', $turnoIds)
            ->whereNotNull('medio_pago_id')
            ->groupBy('turno_id', 'medio_pago_id')
            ->get();

        // 4️⃣ Mapa [turno][medio] = total
        $map = [];
        foreach ($rows as $r) {
            $map[$r->turno_id][$r->medio_pago_id] = (float) $r->total;
        }

        $this->mapMediosPorTurno = $map;

        // 5️⃣ Totales generales
        $this->totalesInforme = [
            'conteo'         => $turnos->count(),
            'total_base'     => (float) $turnos->sum('base_inicial'),
            'total_ventas'   => (float) $turnos->sum('total_ventas'),
            'total_efectivo' => (float) $turnos->sum('ventas_efectivo'),
            'total_ingresos' => (float) $turnos->sum('ingresos_efectivo'),
            'total_retiros'  => (float) $turnos->sum('retiros_efectivo'),
            'total_devol'    => (float) $turnos->sum('devoluciones'),
        ];
    } catch (\Throwable $e) {
        Log::error('Error al actualizar informe: '.$e->getMessage());

        PendingToast::create()
            ->error()
            ->message('No se pudo generar el informe.')
            ->duration(8000)
            ->push();
    }
}

}
