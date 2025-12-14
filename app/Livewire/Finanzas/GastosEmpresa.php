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

// ✅ para sacar la cuenta del medio de pago EFECTIVO
use App\Models\MediosPago\MedioPagos;

// ✅ PUC (para restar saldo en la cuenta)
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
        'tipo_gasto_id'         => 'required|exists:tipos_gasto,id',
        'concepto_documento_id' => 'required|exists:conceptos_documentos,id',
        'serie_id'              => 'required|exists:series,id',
        'monto'                 => 'required|numeric|min:0.01',
        'observacion'           => 'nullable|string|max:255',
    ];

    protected $messages = [
        'tipo_gasto_id.required'         => 'Debe seleccionar el tipo de gasto.',
        'concepto_documento_id.required' => 'Debe seleccionar el concepto contable.',
        'serie_id.required'              => 'Debe seleccionar la serie.',
        'monto.required'                 => 'Debe ingresar el monto.',
        'monto.min'                      => 'El monto debe ser mayor que cero.',
    ];

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
            Log::error('GASTOS_EMPRESA mount error', ['msg' => $e->getMessage()]);
            $this->rutas = $this->tiposGasto = $this->conceptosContables = $this->series = $this->gastos = [];
            PendingToast::create()->error()->message('No se pudo cargar Gastos Empresa (catálogos).')->duration(8000);
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
            Log::error('GASTOS_EMPRESA reloadSeries error', ['msg' => $e->getMessage()]);
            $this->series = [];
            $this->serie_id = null;
            PendingToast::create()->warning()->message('No se pudieron cargar las series de Gastos.')->duration(8000);
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
            Log::error('GASTOS_EMPRESA previewSiguiente error', ['msg' => $e->getMessage()]);
            return '';
        }
    }

    private function repartirPorcentaje(float $total, array $rows): array
    {
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
    }

    /**
     * ✅ Cuenta EFECTIVO (plan_cuentas_id) asociada al medio de pago "efectivo".
     */
    private function cuentaEfectivoId(): ?int
    {
        $mp = MedioPagos::query()
            ->with('cuenta') // relación hacia MedioPagoCuenta (o como la tengas)
            ->whereRaw('LOWER(codigo) = ?', ['Efectivo'])
            ->first();

        return $mp?->cuenta?->plan_cuentas_id ? (int)$mp->cuenta->plan_cuentas_id : null;
    }

    /**
     * ✅ Trae cuentas DÉBITO del concepto.
     * ✅ El CRÉDITO SIEMPRE será EFECTIVO (para que siempre salga de caja/efectivo).
     */
    private function resolverCuentasDebitoYCreditoEfectivo(int $conceptoId, int $cuentaEfectivoId): array
    {
        $concepto = ConceptoDocumento::findOrFail($conceptoId);

        $rows = ConceptoDocumentoCuenta::query()
            ->where('concepto_documento_id', $concepto->id)
            ->orderByDesc('prioridad')
            ->get(['plan_cuenta_id','naturaleza','porcentaje','prioridad']);

        $deb = $rows->where('naturaleza', 'debito')->values();

        if ($deb->isEmpty()) {
            throw new \RuntimeException('El concepto no tiene cuentas en DÉBITO configuradas.');
        }

        $debRows = $deb->map(fn($r) => [
            'cuenta_id' => (int)$r->plan_cuenta_id,
            'pct'       => $r->porcentaje !== null ? (float)$r->porcentaje : null,
        ])->toArray();

        // ✅ crédito siempre efectivo 100%
        $creRows = [[ 'cuenta_id' => (int)$cuentaEfectivoId, 'pct' => 100 ]];

        return [$concepto, $debRows, $creRows];
    }

    public function guardarGasto(): void
    {
        try {
            $this->validate();

            DB::transaction(function () {

                $serie  = SerieModel::findOrFail($this->serie_id);
                $numero = $serie->tomarConsecutivo();

                $total = round((float)$this->monto, 2);
                if ($total <= 0) {
                    throw new \RuntimeException('Monto inválido.');
                }

                // ✅ 1) Crear gasto
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

                // ✅ 2) Caja (retiro)
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

                // ✅ 3) Cuenta EFECTIVO obligatoria
                $cuentaEfectivoId = $this->cuentaEfectivoId();
                if (!$cuentaEfectivoId) {
                    throw new \RuntimeException('No hay cuenta EFECTIVO configurada en Medios de Pago.');
                }

                // ✅ 4) Asiento + movimientos (crédito SIEMPRE efectivo)
                [$concepto, $debRows, $creRows] = $this->resolverCuentasDebitoYCreditoEfectivo(
                    (int)$this->concepto_documento_id,
                    (int)$cuentaEfectivoId
                );

                $debSplit = $this->repartirPorcentaje($total, $debRows);
                $creSplit = $this->repartirPorcentaje($total, $creRows); // (será 100% efectivo)

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

                // Débitos (según concepto)
                foreach ($debSplit as $l) {
                    if (empty($l['cuenta_id'])) {
                        throw new \RuntimeException('Cuenta débito inválida (NULL). Revisa ConceptoDocumentoCuenta.');
                    }

                    Movimiento::create([
                        'asiento_id'  => $asiento->id,
                        'cuenta_id'   => (int)$l['cuenta_id'], // ✅ tu BD exige cuenta_id
                        'debito'      => (float)$l['valor'],
                        'credito'     => 0,
                        'descripcion' => $gasto->observacion ?: 'Gasto (débito)',
                    ]);
                }

                // Créditos (SIEMPRE EFECTIVO)
                foreach ($creSplit as $l) {
                    if (empty($l['cuenta_id'])) {
                        throw new \RuntimeException('Cuenta crédito inválida (NULL). Revisa MedioPago EFECTIVO.');
                    }

                    Movimiento::create([
                        'asiento_id'  => $asiento->id,
                        'cuenta_id'   => (int)$l['cuenta_id'],
                        'debito'      => 0,
                        'credito'     => (float)$l['valor'],
                        'descripcion' => 'Salida por gasto (EFECTIVO)',
                    ]);
                }

                // ✅ 5) Descontar saldo del PUC (EFECTIVO)
                // COALESCE funciona en SQL Server y evita NULL - valor = NULL
                PlanCuentas::where('id', (int)$cuentaEfectivoId)
                    ->update([
                        'saldo' => DB::raw('COALESCE(saldo,0) - '.$total)
                    ]);

                // Enlazar gasto -> asiento
                $gasto->update(['asiento_id' => $asiento->id]);

            }, 3);

            $this->reset(['ruta_id','tipo_gasto_id','concepto_documento_id','monto','observacion']);
            $this->loadGastos();

            PendingToast::create()
                ->success()
                ->message('Gasto registrado. Se generó asiento y se descontó del EFECTIVO.')
                ->duration(6000);

        } catch (\Illuminate\Validation\ValidationException $ve) {
            PendingToast::create()->error()->message('Revisa los campos marcados.')->duration(6000);
            throw $ve;

        } catch (\Throwable $e) {
            Log::error('GASTOS_EMPRESA guardarGasto error', [
                'msg' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            PendingToast::create()->error()->message('Error: '.$e->getMessage())->duration(9000);
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
            Log::error('GASTOS_EMPRESA loadGastos error', ['msg' => $e->getMessage()]);
            $this->gastos = [];
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
            Log::error('GASTOS_EMPRESA getGastosFiltradosProperty error', ['msg' => $e->getMessage()]);
            return collect([]);
        }
    }

    public function render()
    {
        return view('livewire.finanzas.gastos-empresa', [
            'gastosFiltrados' => $this->gastosFiltrados,
        ]);
    }
}
