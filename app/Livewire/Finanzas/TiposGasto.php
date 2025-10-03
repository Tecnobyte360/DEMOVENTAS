<?php

namespace App\Livewire\Finanzas;

use App\Models\Finanzas\TipoGasto;
use Livewire\Component;

use Masmerise\Toaster\PendingToast;

class TiposGasto extends Component
{
    public $tipos = [];

    public $tipoId;
    public $nombre;
    public $descripcion;

    public $modoEdicion = false;

    protected $rules = [
        'nombre' => 'required|string|max:100|unique:tipos_gasto,nombre',
        'descripcion' => 'nullable|string|max:255',
    ];

    public function mount()
    {
        $this->loadTipos();
    }

    public function loadTipos()
    {
        $this->tipos = TipoGasto::orderBy('nombre')->get();
    }

    public function guardar()
    {
        $this->validate();

        try {
            TipoGasto::create([
                'nombre' => $this->nombre,
                'descripcion' => $this->descripcion,
            ]);

            $this->resetForm();
            $this->loadTipos();

            PendingToast::create()
                ->success()
                ->message('Tipo de gasto registrado correctamente.')
                ->duration(4000);
        } catch (\Exception $e) {
            PendingToast::create()
                ->error()
                ->message('Ocurrió un error al registrar el tipo de gasto.')
                ->duration(8000);
        }
    }

    public function editar($id)
    {
        $tipo = TipoGasto::findOrFail($id);

        $this->tipoId = $tipo->id;
        $this->nombre = $tipo->nombre;
        $this->descripcion = $tipo->descripcion;
        $this->modoEdicion = true;
    }

    public function actualizar()
    {
        $this->validate([
            'nombre' => 'required|string|max:100|unique:tipos_gasto,nombre,' . $this->tipoId,
            'descripcion' => 'nullable|string|max:255',
        ]);

        try {
            $tipo = TipoGasto::findOrFail($this->tipoId);
            $tipo->update([
                'nombre' => $this->nombre,
                'descripcion' => $this->descripcion,
            ]);

            $this->resetForm();
            $this->loadTipos();

            PendingToast::create()
                ->success()
                ->message('Tipo de gasto actualizado exitosamente.')
                ->duration(4000);
        } catch (\Exception $e) {
            PendingToast::create()
                ->error()
                ->message('Ocurrió un error al actualizar el tipo de gasto.')
                ->duration(8000);
        }
    }

    public function eliminar($id)
    {
        try {
            TipoGasto::findOrFail($id)->delete();
            $this->loadTipos();

            PendingToast::create()
                ->success()
                ->message('Tipo de gasto eliminado.')
                ->duration(4000);
        } catch (\Exception $e) {
            PendingToast::create()
                ->error()
                ->message('No se pudo eliminar el tipo de gasto.')
                ->duration(8000);
        }
    }

    public function cancelar()
    {
        $this->resetForm();
    }

    private function resetForm()
    {
        $this->reset(['tipoId', 'nombre', 'descripcion', 'modoEdicion']);
        $this->resetValidation();
    }

    public function render()
    {
        return view('livewire.finanzas.tipos-gasto');
    }
}
