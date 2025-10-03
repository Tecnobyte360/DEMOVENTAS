<?php

namespace App\Livewire\Vehiculos;

use Livewire\Component;
use App\Models\Vehiculo\Vehiculo as VehiculoModel;
use Masmerise\Toaster\PendingToast;
use Illuminate\Support\Facades\Log;
use Masmerise\Toaster\Toaster;

class Vehiculo extends Component
{
    public $vehiculos;

    public $placa;
    public $modelo;
    public $marca;
    public $vehiculo_id;
    public $isEdit = false;
    public bool $estado_activo = true;

    public function mount()
    {
        $this->cargarVehiculos();
    }

    public function cargarVehiculos()
    {
        $this->vehiculos = VehiculoModel::latest()->get();
    }

   public function guardarVehiculo()
{
    $this->resetErrorBag();

    try {
        $this->validate([
            'placa' => 'required|unique:vehiculos,placa,' . $this->vehiculo_id,
            'modelo' => 'required|string|max:255',
            'marca' => 'nullable|string|max:255',
        ]);

        VehiculoModel::updateOrCreate(
            ['id' => $this->vehiculo_id],
            [
                'placa' => $this->placa,
                'modelo' => $this->modelo,
                'marca' => $this->marca,
                'estado' => $this->estado_activo ? 'activo' : 'inactivo',
            ]
        );

        $this->resetFormulario();
        $this->cargarVehiculos();

        PendingToast::create()
            ->success()
            ->message($this->isEdit ? 'Vehículo actualizado correctamente.' : 'Vehículo registrado exitosamente.')
            ->duration(5000);
    } catch (\Throwable $e) {
        PendingToast::create()
            ->error()
            ->message('Error al registrar el vehículo: ' . $e->getMessage())
            ->duration(8000);
    }
}

public function editar($id)
{
    $vehiculo = VehiculoModel::findOrFail($id);
    $this->vehiculo_id = $vehiculo->id;
    $this->placa = $vehiculo->placa;
    $this->modelo = $vehiculo->modelo;
    $this->marca = $vehiculo->marca;
    $this->estado_activo = $vehiculo->estado === 'activo'; 
    $this->isEdit = true;
}


    public function eliminar($id)
    {
        VehiculoModel::findOrFail($id)->delete();
        $this->cargarVehiculos();
        $this->dispatch('vehiculo-eliminado', ['mensaje' => 'Vehículo eliminado.']);
    }

    public function resetFormulario()
    {
        $this->reset(['vehiculo_id', 'placa', 'modelo', 'marca', 'isEdit']);
        $this->resetErrorBag();
    }

    public function toggleEstado($id)
    {
        $vehiculo = VehiculoModel::findOrFail($id);
        $vehiculo->estado = $vehiculo->estado === 'activo' ? 'inactivo' : 'activo';
        $vehiculo->save();
        $this->cargarVehiculos();
    }

    public function render()
    {
        return view('livewire.vehiculos.vehiculo');
    }
}
