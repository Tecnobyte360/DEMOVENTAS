<?php

namespace App\Livewire\Bodegas;

use App\Models\bodegas;
use Livewire\Component;

class Edit extends Component
{
    public $bodegaId, $nombre, $ubicacion, $activo;

    // Escuchar el evento "cargarBodega" desde el componente padre
    protected $listeners = ['cargarBodega' => 'loadBodegaData'];

    public function mount($bodegaId = null)
    {
        if ($bodegaId) {
            $this->loadBodegaData($bodegaId);
        }
    }

    public function loadBodegaData($bodegaId)
    {
        if (!$bodegaId) return;

        $this->bodegaId = $bodegaId;
        $bodega = bodegas::find($bodegaId);

        if ($bodega) {
            $this->nombre = $bodega->nombre;
            $this->ubicacion = $bodega->ubicacion;
            $this->activo = $bodega->activo;
        }
    }

    public function save()
    {
        $this->validate([
            'nombre' => 'required|string|max:255',
            'ubicacion' => 'required|string|max:255',
            'activo' => 'boolean',
        ]);

        bodegas::updateOrCreate(
            ['id' => $this->bodegaId],
            [
                'nombre' => $this->nombre,
                'ubicacion' => $this->ubicacion,
                'activo' => $this->activo,
            ]
        );

        // Emitir evento para actualizar la lista de bodegas en el componente padre
        $this->dispatch('bodegaUpdated');

        // Cerrar el modal y resetear valores
        $this->resetInput();
        $this->dispatch('closeEditModal');
    }

    public function resetInput()
    {
        $this->nombre = '';
        $this->ubicacion = '';
        $this->activo = 1;
        $this->bodegaId = null;
    }

    public function render()
    {
        return view('livewire.bodegas.edit');
    }
}
