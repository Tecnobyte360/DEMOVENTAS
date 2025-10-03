<?php

namespace App\Livewire\Finanzas;

use Livewire\Component;
use App\Models\InventarioRuta\GastoRuta;
use App\Models\Ruta\Ruta;
use App\Models\Finanzas\TipoGasto;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Masmerise\Toaster\PendingToast;

class GastosEmpresa extends Component
{
    public $ruta_id = null;
    public $tipo_gasto_id;
    public $monto;
    public $observacion;

    public $filtroTipo = 'todos'; 

    public $rutas = [];
    public $tiposGasto = [];
    public $gastos = [];

    protected $rules = [
        'ruta_id'        => 'nullable|exists:rutas,id',
        'tipo_gasto_id'  => 'required|exists:tipos_gasto,id',
        'monto'          => 'required|numeric|min:0.01',
        'observacion'    => 'nullable|string|max:255',
    ];

    protected $messages = [
        'ruta_id.exists'        => 'La ruta seleccionada no es válida.',
        'tipo_gasto_id.required' => 'Debe seleccionar el tipo de gasto.',
        'tipo_gasto_id.exists'  => 'El tipo de gasto seleccionado no es válido.',
        'monto.required'        => 'Debe ingresar el monto.',
        'monto.min'             => 'El monto debe ser mayor que cero.',
    ];

   
public function mount()
{
    $this->rutas = Ruta::orderBy('ruta')->get();
    $this->tiposGasto = TipoGasto::orderBy('nombre')->get();
    $this->loadGastos();
}

    public function guardarGasto()
    {
        $this->validate();

        try {
            GastoRuta::create([
                'ruta_id'        => $this->ruta_id,
                'user_id'        => Auth::id(),
                'tipo_gasto_id'  => $this->tipo_gasto_id,
                'monto'          => $this->monto,
                'observacion'    => $this->observacion,
            ]);

            $this->reset(['ruta_id', 'tipo_gasto_id', 'monto', 'observacion']);
            $this->loadGastos();

            PendingToast::create()
                ->success()
                ->message('Gasto registrado exitosamente.')
                ->duration(6000);
        } catch (\Exception $e) {
            Log::error('Error al registrar gasto: ' . $e->getMessage());

            PendingToast::create()
                ->error()
                ->message('Ocurrió un error al registrar el gasto.')
                ->duration(8000);
        }
    }

    public function loadGastos()
    {
        $this->gastos = GastoRuta::with(['ruta', 'tipoGasto'])
            ->latest()
            ->take(100)
            ->get();
    }

    public function getGastosFiltradosProperty()
    {
        return collect($this->gastos)->filter(function ($gasto) {
            if ($this->filtroTipo === 'ruta') {
                return $gasto->ruta_id !== null;
            } elseif ($this->filtroTipo === 'admin') {
                return $gasto->ruta_id === null;
            }
            return true;
        });
    }

    public function render()
    {
        return view('livewire.finanzas.gastos-empresa', [
            'gastosFiltrados' => $this->gastosFiltrados,
        ]);
    }
}
