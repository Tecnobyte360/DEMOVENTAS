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
    public array $porTipo  = [];
    public array $porMedio = [];
    public bool $mostrarTablaRetiros = false;
    public array $retirosDetalle = [];
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

    /**
     * ✅ Toast centralizado (Toaster usa dispatch(), NO push()).
     */
    private function toast(string $type, string $message, int $duration = 7000): void
    {
        $t = PendingToast::create();

        if ($type === 'success') $t->success();
        elseif ($type === 'warning') $t->warning();
        else $t->error();

        $t->message($message)->duration($duration)->dispatch();
    }

    public function mount(): void
    {
        try {
            $this->turno = turnos_caja::turnoAbiertoGlobal();

            $this->filtro_desde = now()->subDays(30)->toDateString();
            $this->filtro_hasta = now()->toDateString();

            $this->refrescarResumenes();
            $this->actualizarInforme();
        } catch (\Throwable $e) {
            Log::error('Error en mount TurnoCaja', [
                'msg' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => Auth::id(),
            ]);

            $this->toast('error', 'Ocurrió un error cargando el turno de caja.', 8000);
        }
    }
    public function toggleRetiros(): void
    {
        try {
            $this->mostrarTablaRetiros = !$this->mostrarTablaRetiros;

            if (!$this->mostrarTablaRetiros) {
                $this->retirosDetalle = [];
                return;
            }

            if (!$this->turno) {
                $this->toast('error', 'No hay turno abierto.', 6000);
                $this->mostrarTablaRetiros = false;
                return;
            }

            $this->retirosDetalle = CajaMovimiento::query()
                ->with('user:id,name')
                ->where('turno_id', $this->turno->id)
                ->where('tipo', 'RETIRO')
                ->latest('id')
                ->get()
                ->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'fecha' => optional($item->created_at)?->format('Y-m-d H:i:s'),
                        'usuario' => $item->user?->name ?? '—',
                        'monto' => (float) $item->monto,
                        'motivo' => $item->motivo ?? '—',
                    ];
                })
                ->toArray();
        } catch (\Throwable $e) {
            Log::error('Error al cargar retiros del turno', [
                'msg' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'turno_id' => $this->turno?->id,
                'user_id' => Auth::id(),
            ]);

            $this->mostrarTablaRetiros = false;
            $this->retirosDetalle = [];

            $this->toast('error', 'No se pudieron cargar los retiros.', 8000);
        }
    }
    public function render()
    {
        try {
            $this->turno = turnos_caja::turnoAbiertoGlobal();

            if ($this->turno) {
                $this->refrescarResumenes();

                $mediosDePago = MedioPagos::where('activo', true)->get();

                foreach ($mediosDePago as $medio) {
                    $medio->saldo = $this->calcularSaldoMedioPago($medio);
                }

                return view('livewire.turnos-caja.turno-caja', [
                    'turno'             => $this->turno,
                    'mediosDePago'      => $mediosDePago,
                    'resumen'           => $this->resumen,
                    'porTipo'           => $this->porTipo,
                    'porMedio'          => $this->porMedio,
                    'turnosInforme'     => $this->turnosInforme,
                    'totalesInforme'    => $this->totalesInforme,
                    'mediosActivos'     => $this->mediosActivos,
                    'mapMediosPorTurno' => $this->mapMediosPorTurno,
                ]);
            }

            return view('livewire.turnos-caja.turno-caja', [
                'turno'             => null,
                'turnosInforme'     => $this->turnosInforme,
                'totalesInforme'    => $this->totalesInforme,
                'mediosActivos'     => $this->mediosActivos,
                'mapMediosPorTurno' => $this->mapMediosPorTurno,
            ]);
        } catch (\Throwable $e) {
            Log::error('Error en render TurnoCaja', [
                'msg' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => Auth::id(),
            ]);

            $this->toast('error', 'Error renderizando Turno de Caja.', 8000);

            return view('livewire.turnos-caja.turno-caja', [
                'turno' => null,
                'turnosInforme' => [],
                'totalesInforme' => [],
                'mediosActivos' => [],
                'mapMediosPorTurno' => [],
            ]);
        }
    }
    public function abiertoPor()
    {
        return $this->belongsTo(\App\Models\User::class, 'abierto_por_id');
    }

    public function cerradoPor()
    {
        return $this->belongsTo(\App\Models\User::class, 'cerrado_por_id');
    }
    public static function turnoPendienteDeCerrar(): ?self
    {
        return self::with(['abiertoPor:id,name', 'cerradoPor:id,name'])
            ->where('estado', 'abierto')
            ->whereDate('fecha_inicio', '<', now()->toDateString())
            ->orderBy('fecha_inicio')
            ->first();
    }
    private function calcularSaldoMedioPago($medio)
    {
        try {
            if (!$this->turno) {
                return (float)($medio->saldo_inicial ?? 0);
            }

            $totalPagado = FacturaPago::where('medio_pago_id', $medio->id)
                ->where('turno_id', $this->turno->id)
                ->sum('monto');

            $saldoInicial = (float)($medio->saldo_inicial ?? 0);

            // OJO: esto es tu lógica actual. Si el saldo debe ser "recaudado", puede ser al revés.
            return $saldoInicial - (float)$totalPagado;
        } catch (\Throwable $e) {
            Log::error('Error calcularSaldoMedioPago', [
                'msg' => $e->getMessage(),
                'medio_id' => $medio->id ?? null,
                'turno_id' => $this->turno?->id,
            ]);

            return 0;
        }
    }

    /* =========================================================
     * ABRIR TURNO
     * =======================================================*/
    public function abrir(): void
    {
        $this->validateOnly('base_inicial');

        try {
            $turnoAbierto = turnos_caja::turnoAbiertoGlobal();

            if ($turnoAbierto) {
                $abiertoPor = $turnoAbierto->abiertoPor?->name ?? 'otro usuario';

                $this->toast(
                    'error',
                    "Ya existe una caja abierta (Turno #{$turnoAbierto->id}) abierta por {$abiertoPor}. Debe cerrarse antes de abrir una nueva.",
                    8000
                );
                return;
            }

            $this->turno = turnos_caja::create([
                'user_id'        => Auth::id(),
                'abierto_por_id' => Auth::id(),
                'fecha_inicio'   => now(),
                'base_inicial'   => $this->base_inicial,
                'estado'         => 'abierto',
                'resumen'        => [],
            ]);

            $this->turno->load(['abiertoPor:id,name', 'cerradoPor:id,name']);

            $this->toast('success', 'Caja abierta correctamente.', 6000);

            $this->refrescarResumenes();
            $this->actualizarInforme();
        } catch (\Throwable $e) {
            Log::error('Error al abrir turno de caja', [
                'msg' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => Auth::id(),
            ]);

            $this->toast('error', 'No se pudo abrir la caja.', 8000);
        }
    }

    public ?int $turnoDetalleRetirosId = null;

public function verRetirosTurno(int $turnoId): void
{
    try {
        if ($this->turnoDetalleRetirosId === $turnoId && $this->mostrarTablaRetiros) {
            $this->turnoDetalleRetirosId = null;
            $this->mostrarTablaRetiros = false;
            $this->retirosDetalle = [];
            return;
        }

        $this->turnoDetalleRetirosId = $turnoId;
        $this->mostrarTablaRetiros = true;

        $this->retirosDetalle = CajaMovimiento::query()
            ->with('user:id,name')
            ->where('turno_id', $turnoId)
            ->where('tipo', 'RETIRO')
            ->latest('id')
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'fecha' => optional($item->created_at)?->format('Y-m-d H:i:s'),
                    'usuario' => $item->user?->name ?? '—',
                    'monto' => (float) $item->monto,
                    'motivo' => $item->motivo ?? '—',
                ];
            })
            ->toArray();
    } catch (\Throwable $e) {
        Log::error('Error al cargar retiros del turno del histórico', [
            'msg' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
            'turno_id' => $turnoId,
            'user_id' => Auth::id(),
        ]);

        $this->turnoDetalleRetirosId = null;
        $this->mostrarTablaRetiros = false;
        $this->retirosDetalle = [];

        $this->toast('error', 'No se pudo cargar el detalle de retiros.', 8000);
    }
}
    /* =========================================================
     * MOVIMIENTOS MANUALES
     * =======================================================*/
    public function agregarMovimiento(): void
    {
        try {
            if (!$this->turno || $this->turno->estaCerrado()) {
                $this->toast('error', 'No hay turno abierto.', 6000);
                return;
            }

            $this->validate([
                'tipo_mov' => 'required|in:INGRESO,RETIRO,DEVOLUCION',
                'monto'    => 'required|numeric|min:0.01',
                'motivo'   => 'nullable|string|max:255',
            ]);

            DB::transaction(function () {
                CajaMovimiento::create([
                    'turno_id' => $this->turno->id,
                    'user_id'  => Auth::id(),
                    'tipo'     => $this->tipo_mov,
                    'monto'    => $this->monto,
                    'motivo'   => $this->motivo,
                ]);

                if ($this->tipo_mov === 'INGRESO') {
                    $this->turno->increment('ingresos_efectivo', $this->monto);
                } elseif ($this->tipo_mov === 'RETIRO') {
                    $this->turno->increment('retiros_efectivo', $this->monto);
                } elseif ($this->tipo_mov === 'DEVOLUCION') {
                    $this->turno->increment('devoluciones', $this->monto);
                }

                $this->turno->refresh();
            });

            $this->monto = null;
            $this->motivo = null;

            $this->toast('success', 'Movimiento registrado.', 6000);

            $this->refrescarResumenes();
            $this->actualizarInforme();

            if ($this->mostrarTablaRetiros) {
                $this->cargarRetirosDetalle();
            }
        } catch (\Illuminate\Validation\ValidationException $ve) {
            $primer = $ve->validator->errors()->first() ?? 'Revisa los campos marcados.';
            $this->toast('error', $primer, 9000);

            Log::warning('TurnoCaja validation agregarMovimiento', [
                'errors' => $ve->validator->errors()->toArray()
            ]);

            return;
        } catch (\Throwable $e) {
            Log::error('Error al registrar movimiento de caja', [
                'msg' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => Auth::id(),
                'turno_id' => $this->turno?->id,
            ]);

            $this->toast('error', 'Ocurrió un error al registrar el movimiento.', 8000);
        }
    }
    private function cargarRetirosDetalle(): void
    {
        if (!$this->turno) {
            $this->retirosDetalle = [];
            return;
        }

        $this->retirosDetalle = CajaMovimiento::query()
            ->with('user:id,name')
            ->where('turno_id', $this->turno->id)
            ->where('tipo', 'RETIRO')
            ->latest('id')
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'fecha' => optional($item->created_at)?->format('Y-m-d H:i:s'),
                    'usuario' => $item->user?->name ?? '—',
                    'monto' => (float) $item->monto,
                    'motivo' => $item->motivo ?? '—',
                ];
            })
            ->toArray();
    }
    /* =========================================================
     * CERRAR TURNO
     * =======================================================*/
    public function cerrar(): void
    {
        try {
            if (!$this->turno || $this->turno->estaCerrado()) {
                $this->toast('error', 'No hay turno abierto.', 6000);
                return;
            }

            DB::transaction(function () {
                $pagos = FacturaPago::where('turno_id', $this->turno->id)->get();

                $byTipo  = $pagos->groupBy('medio_tipo')->map->sum('monto')->toArray();
                $byMedio = $pagos->groupBy('medio_codigo')->map(function ($g) {
                    return [
                        'codigo' => $g->first()->medio_codigo,
                        'tipo'   => $g->first()->medio_tipo,
                        'nombre' => $g->first()->medio_codigo,
                        'total'  => (float) $g->sum('monto'),
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
                    'cerrado_por_id'         => Auth::id(),
                    'total_ventas'           => $totalVentas,
                    'ventas_efectivo'        => (float) ($byTipo['EFECTIVO'] ?? 0),
                    'ventas_debito'          => (float) ($byTipo['DEBITO'] ?? 0),
                    'ventas_credito_tarjeta' => (float) ($byTipo['CREDITO'] ?? 0),
                    'ventas_transferencias'  => (float) ($byTipo['TRANSFERENCIA'] ?? 0),
                    'ventas_a_credito'       => (float) ($byTipo['CREDITO_CLIENTE'] ?? 0),
                    'resumen'                => $resumen,
                ]);
            });

            $this->toast('success', 'Turno cerrado.', 6000);

            $this->turno    = null;
            $this->resumen  = [];
            $this->porTipo  = [];
            $this->porMedio = [];

            $this->actualizarInforme();
        } catch (\Throwable $e) {
            Log::error('Error al cerrar turno de caja', [
                'msg' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => Auth::id(),
                'turno_id' => $this->turno?->id,
            ]);

            $this->toast('error', 'No se pudo cerrar el turno de caja.', 8000);
        }
    }

    /* =========================================================
     * RESUMEN DEL TURNO ACTUAL
     * =======================================================*/
    private function refrescarResumenes(): void
    {
        $this->resumen = $this->porTipo = $this->porMedio = [];

        if (!$this->turno) return;

        try {
            $tipoColCandidates   = ['medio_tipo', 'tipo', 'tipo_medio', 'metodo', 'forma_pago'];
            $codigoColCandidates = ['medio_codigo', 'codigo', 'medio', 'metodo_codigo', 'referencia', 'ref'];

            $tipoCol   = collect($tipoColCandidates)->first(fn($c) => Schema::hasColumn('factura_pagos', $c));
            $codigoCol = collect($codigoColCandidates)->first(fn($c) => Schema::hasColumn('factura_pagos', $c));

            $wrap     = fn(string $c) => DB::getQueryGrammar()->wrap($c);
            $bindings = [];

            $tipoExpr = $tipoCol ? $wrap($tipoCol) : '?';
            if (!$tipoCol) $bindings[] = 'OTRO';

            $codigoExpr = $codigoCol ? $wrap($codigoCol) : '?';
            if (!$codigoCol) $bindings[] = '-';

            $pagos = FacturaPago::query()
                ->selectRaw("monto, {$tipoExpr} AS medio_tipo, {$codigoExpr} AS medio_codigo", $bindings)
                ->where('turno_id', $this->turno->id)
                ->get();

            $totalVentas = (float) $pagos->sum('monto'); // ✅ todos los medios (antes estabas sumando solo efectivo)

            $this->resumen = [
                'base_inicial'       => (float) $this->turno->base_inicial,
                'total_ventas'       => $totalVentas,
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
            Log::error('Error al refrescar resúmenes de turno', [
                'msg' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'turno_id' => $this->turno?->id,
            ]);

            $this->toast('error', 'No se pudo refrescar el resumen del turno.', 8000);
        }
    }

    /* =========================================================
     * INFORME HISTÓRICO POR RANGO
     * =======================================================*/
    public function actualizarInforme(): void
    {
        try {
            $this->validate([
                'filtro_desde' => 'nullable|date',
                'filtro_hasta' => 'nullable|date|after_or_equal:filtro_desde',
            ]);

            $query = turnos_caja::query()
                ->with(['abiertoPor:id,name', 'cerradoPor:id,name']);

            if ($this->filtro_desde) {
                $query->whereDate('fecha_inicio', '>=', $this->filtro_desde);
            }

            if ($this->filtro_hasta) {
                $query->whereDate('fecha_inicio', '<=', $this->filtro_hasta);
            }

            $turnos = $query
                ->orderByDesc('fecha_inicio')
                ->get();

            $this->turnosInforme = $turnos;

            $this->mediosActivos = MedioPagos::query()
                ->where('activo', true)
                ->orderBy('nombre')
                ->get(['id', 'nombre'])
                ->toArray();

            $turnoIds = $turnos->pluck('id')->all();

            if (empty($turnoIds)) {
                $this->mapMediosPorTurno = [];
                $this->totalesInforme = [
                    'conteo'         => 0,
                    'total_base'     => 0,
                    'total_ventas'   => 0,
                    'total_efectivo' => 0,
                    'total_ingresos' => 0,
                    'total_retiros'  => 0,
                    'total_devol'    => 0,
                ];
                return;
            }

            $rows = FacturaPago::query()
                ->selectRaw('turno_id, medio_pago_id, SUM(monto) as total')
                ->whereIn('turno_id', $turnoIds)
                ->whereNotNull('medio_pago_id')
                ->groupBy('turno_id', 'medio_pago_id')
                ->get();

            $map = [];
            foreach ($rows as $r) {
                $map[$r->turno_id][$r->medio_pago_id] = (float) $r->total;
            }
            $this->mapMediosPorTurno = $map;

            $this->totalesInforme = [
                'conteo'         => $turnos->count(),
                'total_base'     => (float) $turnos->sum('base_inicial'),
                'total_ventas'   => (float) $turnos->sum('total_ventas'),
                'total_efectivo' => (float) $turnos->sum('ventas_efectivo'),
                'total_ingresos' => (float) $turnos->sum('ingresos_efectivo'),
                'total_retiros'  => (float) $turnos->sum('retiros_efectivo'),
                'total_devol'    => (float) $turnos->sum('devoluciones'),
            ];
        } catch (\Illuminate\Validation\ValidationException $ve) {
            $primer = $ve->validator->errors()->first() ?? 'Revisa los campos marcados.';
            $this->toast('error', $primer, 9000);
            Log::warning('TurnoCaja validation actualizarInforme', [
                'errors' => $ve->validator->errors()->toArray()
            ]);
            return;
        } catch (\Throwable $e) {
            Log::error('Error al actualizar informe', [
                'msg' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => Auth::id(),
            ]);

            $this->toast('error', 'No se pudo generar el informe.', 8000);
        }
    }
}
