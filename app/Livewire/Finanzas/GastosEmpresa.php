<?php

namespace App\Livewire\Finanzas;

use Livewire\Component;
use App\Models\InventarioRuta\GastoRuta;
use App\Models\Ruta\Ruta;
use App\Models\Finanzas\TipoGasto;
use App\Models\Conceptos\ConceptoDocumento;
use App\Models\Conceptos\ConceptoDocumentoCuenta;
use App\Models\TurnosCaja\CajaMovimiento;
use App\Models\TurnosCaja\turnos_caja;
use App\Models\Serie\Serie as SerieModel;
use App\Models\TiposDocumento\TipoDocumento;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Masmerise\Toaster\PendingToast;

use App\Models\Asiento\Asiento;
use App\Models\Movimiento\Movimiento;

use App\Models\MediosPago\MedioPagos;
use App\Models\CuentasContables\PlanCuentas;

class GastosEmpresa extends Component
{
    public ?int $ruta_id = null;
    public ?int $tipo_gasto_id = null;
    public ?int $concepto_documento_id = null;
    public ?int $serie_id = null;
    public $monto = null;
    public ?string $observacion = null;

    public array $series = [];
    public string $filtroTipo = 'todos';

    public $rutas = [];
    public $tiposGasto = [];
    public $conceptosContables = [];
    public $gastos = [];

    protected $rules = [
        'ruta_id'               => 'nullable|exists:rutas,id',
        // 'tipo_gasto_id'         => 'required|exists:tipos_gasto,id',
        'concepto_documento_id' => 'required|exists:conceptos_documentos,id',
        'serie_id'              => 'required|exists:series,id',
        'monto'                 => 'required|numeric|min:0.01',
        'observacion'           => 'nullable|string|max:255',
    ];

    protected $messages = [
        // 'tipo_gasto_id.required'         => 'Debe seleccionar el tipo de gasto.',
        'concepto_documento_id.required' => 'Debe seleccionar el concepto contable.',
        'serie_id.required'              => 'Debe seleccionar la serie.',
        'monto.required'                 => 'Debe ingresar el monto.',
        'monto.numeric'                  => 'El monto debe ser numérico.',
        'monto.min'                      => 'El monto debe ser mayor que cero.',
    ];

    /**
     * ✅ Manejo centralizado de errores (captura TODO).
     */
    private function handleException(
        string $context,
        \Throwable $e,
        ?string $toastMsg = null,
        string $toastType = 'error',
        int $duration = 9000
    ): void {
        try {
            Log::error("GASTOS_EMPRESA {$context}", [
                'msg'   => $e->getMessage(),
                'class' => get_class($e),
                'file'  => $e->getFile(),
                'line'  => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'user'  => Auth::id(),
                'payload' => [
                    'ruta_id' => $this->ruta_id,
                    'tipo_gasto_id' => $this->tipo_gasto_id,
                    'concepto_documento_id' => $this->concepto_documento_id,
                    'serie_id' => $this->serie_id,
                    'monto' => $this->monto,
                    'observacion' => $this->observacion,
                ],
            ]);

            $message = $toastMsg ?: ('Error: ' . $e->getMessage());

            $toast = PendingToast::create();
            if ($toastType === 'warning') $toast->warning();
            elseif ($toastType === 'success') $toast->success();
            else $toast->error();

            $toast->message($message)->duration($duration);
        } catch (\Throwable $inner) {
            Log::error("GASTOS_EMPRESA handleException FAILED", [
                'msg' => $inner->getMessage(),
                'original_context' => $context,
                'original_error' => $e->getMessage(),
            ]);
        }
    }

    public function mount(): void
    {
        try {
            $this->rutas      = Ruta::orderBy('ruta')->get();
            $this->tiposGasto = TipoGasto::orderBy('nombre')->get();

            $this->conceptosContables = ConceptoDocumento::query()
                ->where('activo', 1)
                ->orderBy('nombre')
                ->get();

            $this->reloadSeries();
            $this->loadGastos();
        } catch (\Throwable $e) {
            $this->rutas = $this->tiposGasto = $this->conceptosContables = $this->series = $this->gastos = [];
            $this->serie_id = null;
            $this->handleException('mount error', $e, 'No se pudo cargar Gastos Empresa (catálogos).');
        }
    }

    private function reloadSeries(): void
    {
        try {
            $tipoId = (int) (TipoDocumento::whereRaw('LOWER(codigo) = ?', ['gastos'])->value('id') ?? 0);

            $query = SerieModel::query();

            if (method_exists(SerieModel::class, 'scopeActiva')) $query->activa();
            else $query->where('activa', true);

            if ($tipoId > 0) $query->where('tipo_documento_id', $tipoId);

            $rows = $query
                ->orderByDesc('es_default')
                ->orderBy('nombre')
                ->get(['id','nombre','prefijo','proximo','desde','hasta','longitud','es_default']);

            $this->series = $rows->map(fn($s) => [
                'id'        => (int)$s->id,
                'nombre'    => (string)$s->nombre,
                'prefijo'   => (string)($s->prefijo ?? ''),
                'proximo'   => (int)($s->proximo ?? 0),
                'desde'     => (int)($s->desde ?? 0),
                'hasta'     => (int)($s->hasta ?? 0),
                'longitud'  => (int)($s->longitud ?? 6),
                'default'   => (bool)$s->es_default,
            ])->toArray();

            if (!$this->serie_id) {
                $default = collect($this->series)->firstWhere('default', true);
                $this->serie_id = $default['id'] ?? (collect($this->series)->first()['id'] ?? null);
            }
        } catch (\Throwable $e) {
            $this->series = [];
            $this->serie_id = null;
            $this->handleException('reloadSeries error', $e, 'No se pudieron cargar las series de Gastos.', 'warning', 8000);
        }
    }

    public function previewSiguiente(?int $id = null): string
    {
        try {
            $serieId = $id ?: $this->serie_id;
            if (!$serieId) return '';

            $s = SerieModel::find($serieId);
            if (!$s) return '';

            $long = (int) ($s->longitud ?: 6);
            $n    = max((int)$s->proximo, (int)$s->desde);
            $n    = min($n, (int)$s->hasta);

            $num = str_pad((string)$n, $long, '0', STR_PAD_LEFT);
            return ($s->prefijo ? "{$s->prefijo}-" : '') . $num;
        } catch (\Throwable $e) {
            $this->handleException('previewSiguiente error', $e, null, 'warning', 8000);
            return '';
        }
    }

    private function repartirPorcentaje(float $total, array $rows): array
    {
        try {
            if ($total <= 0) throw new \RuntimeException('Total inválido para repartir.');
            if (count($rows) < 1) throw new \RuntimeException('No hay cuentas para repartir porcentaje.');

            if (count($rows) === 1 && (!isset($rows[0]['pct']) || (float)$rows[0]['pct'] <= 0)) {
                return [[ 'cuenta_id' => (int)$rows[0]['cuenta_id'], 'valor' => round($total, 2) ]];
            }

            $sum = array_sum(array_map(fn($r) => (float)($r['pct'] ?? 0), $rows));
            if ($sum <= 0) {
                return [[ 'cuenta_id' => (int)$rows[0]['cuenta_id'], 'valor' => round($total, 2) ]];
            }

            $asignado = 0.0;
            $out = [];

            foreach ($rows as $r) {
                $pct = (float)($r['pct'] ?? 0);
                $valor = round(($total * $pct) / $sum, 2);
                $asignado += $valor;
                $out[] = ['cuenta_id' => (int)$r['cuenta_id'], 'valor' => $valor];
            }

            $diff = round($total - $asignado, 2);
            if (abs($diff) >= 0.01 && count($out) > 0) {
                $out[count($out) - 1]['valor'] = round($out[count($out) - 1]['valor'] + $diff, 2);
            }

            return $out;
        } catch (\Throwable $e) {
            $this->handleException('repartirPorcentaje error', $e);
            throw $e;
        }
    }

    private function cuentaEfectivoId(): ?int
    {
        try {
            $mp = MedioPagos::query()
                ->with('cuenta')
                ->whereRaw('LOWER(codigo) = ?', ['efectivo'])
                ->first();

            return $mp?->cuenta?->plan_cuentas_id ? (int)$mp->cuenta->plan_cuentas_id : null;
        } catch (\Throwable $e) {
            $this->handleException('cuentaEfectivoId error', $e);
            return null;
        }
    }

    private function resolverCuentasDebitoYCreditoEfectivo(int $conceptoId, int $cuentaEfectivoId): array
    {
        try {
            if ($cuentaEfectivoId <= 0) throw new \RuntimeException('Cuenta EFECTIVO inválida.');

            $concepto = ConceptoDocumento::findOrFail($conceptoId);

            $rows = ConceptoDocumentoCuenta::query()
                ->where('concepto_documento_id', $concepto->id)
                ->orderByDesc('prioridad')
                ->get(['plan_cuenta_id','naturaleza','porcentaje','prioridad']);

            $deb = $rows->where('naturaleza', 'debito')->values();
            if ($deb->isEmpty()) throw new \RuntimeException('El concepto no tiene cuentas en DÉBITO configuradas.');

            $debRows = $deb->map(fn($r) => [
                'cuenta_id' => (int)$r->plan_cuenta_id,
                'pct'       => $r->porcentaje !== null ? (float)$r->porcentaje : null,
            ])->toArray();

            $creRows = [[ 'cuenta_id' => (int)$cuentaEfectivoId, 'pct' => 100 ]];

            return [$concepto, $debRows, $creRows];
        } catch (\Throwable $e) {
            $this->handleException('resolverCuentasDebitoYCreditoEfectivo error', $e);
            throw $e;
        }
    }

    public function guardarGasto(): void
    {
        try {
            $this->validate();

            DB::transaction(function () {
                try {
                    $serie  = SerieModel::findOrFail($this->serie_id);
                    $numero = $serie->tomarConsecutivo();

                    $total = round((float)$this->monto, 2);
                    if ($total <= 0) throw new \RuntimeException('Monto inválido.');

                    // 1) Crear gasto
                    $gasto = GastoRuta::create([
                        'serie_id'              => (int)$serie->id,
                        'numero'                => (int)$numero,
                        'prefijo'               => $serie->prefijo,
                        'ruta_id'               => $this->ruta_id,
                        'user_id'               => Auth::id(),
                        'tipo_gasto_id'         => $this->tipo_gasto_id,
                        'concepto_documento_id' => $this->concepto_documento_id,
                        'monto'                 => $total,
                        'observacion'           => $this->observacion,
                    ]);

                    // 2) Caja retiro
                    $turno = turnos_caja::turnoAbiertoDe(Auth::id());
                    if ($turno) {
                        $long   = (int)($serie->longitud ?? 6);
                        $consec = str_pad((string)$gasto->numero, $long, '0', STR_PAD_LEFT);
                        $doc    = ($gasto->prefijo ? $gasto->prefijo.'-' : '') . $consec;

                        $movCaja = CajaMovimiento::create([
                            'turno_id' => $turno->id,
                            'user_id'  => Auth::id(),
                            'tipo'     => 'RETIRO',
                            'monto'    => $total,
                            'motivo'   => sprintf(
                                'Gasto %s: %s - %s',
                                $doc,
                                $gasto->tipoGasto->nombre ?? 'N/A',
                                $gasto->conceptoDocumento->nombre ?? 'N/A'
                            ),
                        ]);

                        $gasto->update(['caja_movimiento_id' => $movCaja->id]);
                        $turno->increment('retiros_efectivo', $total);
                    }

                    // 3) EFECTIVO
                    $cuentaEfectivoId = $this->cuentaEfectivoId();
                    if (!$cuentaEfectivoId) throw new \RuntimeException('No hay cuenta EFECTIVO configurada en Medios de Pago.');

                    // 4) Asiento + movimientos
                    [$concepto, $debRows, $creRows] = $this->resolverCuentasDebitoYCreditoEfectivo(
                        (int)$this->concepto_documento_id,
                        (int)$cuentaEfectivoId
                    );

                    $debSplit = $this->repartirPorcentaje($total, $debRows);
                    $creSplit = $this->repartirPorcentaje($total, $creRows);

                    $asiento = Asiento::create([
                        'fecha'       => now()->toDateString(),
                        'glosa'       => 'Gasto: '.($concepto->nombre ?? 'N/A').' | '.($gasto->observacion ?? ''),
                        'origen'      => 'gasto',
                        'origen_id'   => $gasto->id,
                        'moneda'      => 'COP',
                        'total_debe'  => $total,
                        'total_haber' => $total,
                        'tercero_id'  => null,
                    ]);

                    foreach ($debSplit as $l) {
                        if (empty($l['cuenta_id'])) throw new \RuntimeException('Cuenta débito inválida (NULL). Revisa ConceptoDocumentoCuenta.');

                        Movimiento::create([
                            'asiento_id'  => $asiento->id,
                            'cuenta_id'   => (int)$l['cuenta_id'],
                            'debito'      => (float)$l['valor'],
                            'credito'     => 0,
                            'descripcion' => $gasto->observacion ?: 'Gasto (débito)',
                        ]);
                    }

                    foreach ($creSplit as $l) {
                        if (empty($l['cuenta_id'])) throw new \RuntimeException('Cuenta crédito inválida (NULL). Revisa MedioPago EFECTIVO.');

                        Movimiento::create([
                            'asiento_id'  => $asiento->id,
                            'cuenta_id'   => (int)$l['cuenta_id'],
                            'debito'      => 0,
                            'credito'     => (float)$l['valor'],
                            'descripcion' => 'Salida por gasto (EFECTIVO)',
                        ]);
                    }

                    // 5) Descontar saldo EFECTIVO (SQL Server / MySQL OK con COALESCE)
                    PlanCuentas::where('id', (int)$cuentaEfectivoId)
                        ->update([
                            'saldo' => DB::raw('COALESCE(saldo,0) - '.$total)
                        ]);

                    $gasto->update(['asiento_id' => $asiento->id]);

                } catch (\Throwable $e) {
                    $this->handleException('guardarGasto TX error', $e);
                    throw $e; // rollback
                }
            }, 3);

            $this->reset(['ruta_id','tipo_gasto_id','concepto_documento_id','monto','observacion']);
            $this->loadGastos();

            PendingToast::create()
                ->success()
                ->message('Gasto registrado. Se generó asiento y se descontó del EFECTIVO.')
                ->duration(6000);

        } catch (\Illuminate\Validation\ValidationException $ve) {
            // ✅ AQUÍ ESTÁ LA CLAVE: mostrar el error real
            $primer = $ve->validator->errors()->first() ?? 'Revisa los campos marcados.';

            PendingToast::create()
                ->error()
                ->message($primer)
                ->duration(9000);

            Log::warning('GASTOS_EMPRESA validation', [
                'errors' => $ve->validator->errors()->toArray(),
                'payload' => [
                    'ruta_id' => $this->ruta_id,
                    'tipo_gasto_id' => $this->tipo_gasto_id,
                    'concepto_documento_id' => $this->concepto_documento_id,
                    'serie_id' => $this->serie_id,
                    'monto' => $this->monto,
                    'observacion' => $this->observacion,
                ],
            ]);

            // ❌ NO relanzamos, para que no quede genérico
            return;

        } catch (\Throwable $e) {
            $this->handleException('guardarGasto error', $e);
        }
    }

    public function loadGastos(): void
    {
        try {
            $this->gastos = GastoRuta::with(['ruta', 'tipoGasto', 'conceptoDocumento'])
                ->latest()
                ->take(100)
                ->get();
        } catch (\Throwable $e) {
            $this->gastos = [];
            $this->handleException('loadGastos error', $e, 'No se pudieron cargar los gastos.', 'warning', 8000);
        }
    }

    public function getGastosFiltradosProperty()
    {
        try {
            return collect($this->gastos)->filter(function ($gasto) {
                if ($this->filtroTipo === 'ruta')  return $gasto->ruta_id !== null;
                if ($this->filtroTipo === 'admin') return $gasto->ruta_id === null;
                return true;
            });
        } catch (\Throwable $e) {
            $this->handleException('getGastosFiltradosProperty error', $e, 'Error filtrando gastos.', 'warning', 8000);
            return collect([]);
        }
    }

    public function render()
    {
        try {
            return view('livewire.finanzas.gastos-empresa', [
                'gastosFiltrados' => $this->gastosFiltrados,
            ]);
        } catch (\Throwable $e) {
            $this->handleException('render error', $e, 'Error renderizando la vista.', 'error', 9000);

            return view('livewire.finanzas.gastos-empresa', [
                'gastosFiltrados' => collect([]),
            ]);
        }
    }
}
