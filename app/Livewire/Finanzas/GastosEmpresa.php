<?php

namespace App\Livewire\Finanzas;

use Livewire\Component;
use App\Models\InventarioRuta\GastoRuta;
use App\Models\Ruta\Ruta;
use App\Models\Finanzas\TipoGasto;
use App\Models\Conceptos\ConceptoDocumento;
use App\Models\TurnosCaja\CajaMovimiento;
use App\Models\TurnosCaja\turnos_caja;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Masmerise\Toaster\PendingToast;

class GastosEmpresa extends Component
{
    /* ========= Campos del formulario ========= */
    public ?int $ruta_id = null;
    public ?int $tipo_gasto_id = null;
    public ?int $concepto_documento_id = null;
    public $monto;
    public ?string $observacion = null;

    /* ========= Filtro de vista ========= */
    public string $filtroTipo = 'todos'; // todos | ruta | admin

    /* ========= Catálogos ========= */
    public $rutas = [];
    public $tiposGasto = [];
    public $conceptosContables = [];

    /* ========= Historial (colección en memoria) ========= */
    public $gastos = [];

    protected $rules = [
        'ruta_id'               => 'nullable|exists:rutas,id',
        'tipo_gasto_id'         => 'required|exists:tipos_gasto,id',
        'concepto_documento_id' => 'required|exists:conceptos_documentos,id',
        'monto'                 => 'required|numeric|min:0.01',
        'observacion'           => 'nullable|string|max:255',
    ];

    protected $messages = [
        'ruta_id.exists'                 => 'La ruta seleccionada no es válida.',
        'tipo_gasto_id.required'         => 'Debe seleccionar el tipo de gasto.',
        'tipo_gasto_id.exists'           => 'El tipo de gasto seleccionado no es válido.',
        'concepto_documento_id.required' => 'Debe seleccionar el concepto contable.',
        'concepto_documento_id.exists'   => 'El concepto contable seleccionado no es válido.',
        'monto.required'                 => 'Debe ingresar el monto.',
        'monto.min'                      => 'El monto debe ser mayor que cero.',
    ];

    public function mount(): void
    {
        // Catálogos
        $this->rutas      = Ruta::orderBy('ruta')->get();
        $this->tiposGasto = TipoGasto::orderBy('nombre')->get();

        // Conceptos contables disponibles para gastos
        $this->conceptosContables = ConceptoDocumento::query()
            ->where('activo', 1)
            // si quieres solo para gastos, descomenta:
            // ->where('tipo', 'salida')
            ->orderBy('nombre')
            ->get();

        $this->loadGastos();
    }

    /* ========= Acciones ========= */

  public function guardarGasto(): void
{
    $this->validate();

    try {
        DB::transaction(function () {
            // 1) Guardar el gasto como ya lo hacías
            $gasto = GastoRuta::create([
                'ruta_id'               => $this->ruta_id,
                'user_id'               => Auth::id(),
                'tipo_gasto_id'         => $this->tipo_gasto_id,
                'concepto_documento_id' => $this->concepto_documento_id,
                'monto'                 => $this->monto,
                'observacion'           => $this->observacion,
            ]);

            // 2) Buscar turno de caja ABIERTO del usuario
            $turno = turnos_caja::turnoAbiertoDe(Auth::id());

            // Opcional: si quieres que solo algunos tipos afecten caja,
            // aquí puedes validar algo del TipoGasto:
            // if (!$gasto->tipoGasto?->afecta_caja) return;

            if ($turno) {
                // 3) Crear el movimiento de caja tipo RETIRO
                CajaMovimiento::create([
                    'turno_id' => $turno->id,
                    'user_id'  => Auth::id(),
                    'tipo'     => 'RETIRO',
                    'monto'    => $this->monto,
                    'motivo'   => sprintf(
                        'Gasto: %s - %s',
                        $gasto->tipoGasto->nombre ?? 'N/A',
                        $gasto->conceptoDocumento->nombre ?? 'N/A'
                    ),
                ]);

                // 4) Actualizar retiros del turno
                $turno->increment('retiros_efectivo', $this->monto);
                $turno->refresh();
            }
        });

        // 5) Limpiar formulario
        $this->reset(['ruta_id','tipo_gasto_id','concepto_documento_id','monto','observacion']);

        // 6) Recargar lista para la tabla de gastos
        $this->loadGastos();

        // Mensajes
        session()->flash('message', 'Gasto registrado exitosamente.');

        PendingToast::create()
            ->success()
            ->message('Gasto registrado y afectó caja (retiro) si había turno abierto.')
            ->duration(6000)
            ->push();

        // 🔹 Si quieres refrescar también el componente de TurnoCaja
        // puedes emitir un evento y escucharlo allá:
        // $this->dispatch('turno-caja-refrescar');

    } catch (\Throwable $e) {
        Log::error('Error al registrar gasto: '.$e->getMessage(), [
            'ruta_id'               => $this->ruta_id,
            'tipo_gasto_id'         => $this->tipo_gasto_id,
            'concepto_documento_id' => $this->concepto_documento_id,
            'monto'                 => $this->monto,
            'observacion'           => $this->observacion,
        ]);

        session()->flash('error', 'Ocurrió un error al registrar el gasto.');

        PendingToast::create()
            ->error()
            ->message('Ocurrió un error al registrar el gasto.')
            ->duration(8000)
            ->push();
    }
}

    private function resetFormulario(): void
    {
        $this->reset([
            'ruta_id',
            'tipo_gasto_id',
            'concepto_documento_id',
            'monto',
            'observacion',
        ]);
    }

    public function loadGastos(): void
    {
        $this->gastos = GastoRuta::with(['ruta', 'tipoGasto', 'conceptoDocumento'])
            ->latest()
            ->take(100)
            ->get();
    }

    /* ========= Computado para la tabla ========= */

    public function getGastosFiltradosProperty()
    {
        return collect($this->gastos)->filter(function ($gasto) {
            if ($this->filtroTipo === 'ruta') {
                return $gasto->ruta_id !== null;
            }

            if ($this->filtroTipo === 'admin') {
                return $gasto->ruta_id === null;
            }

            return true; // todos
        });
    }

    /* ========= Render ========= */

    public function render()
    {
        return view('livewire.finanzas.gastos-empresa', [
            'gastosFiltrados' => $this->gastosFiltrados,
        ]);
    }
}
