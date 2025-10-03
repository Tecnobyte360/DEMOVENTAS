<?php

namespace App\Livewire\Bodegas;

use Livewire\Component;
use App\Models\Bodegas;
use Illuminate\Validation\Rule;
use Illuminate\Database\QueryException;
use Throwable;

class Create extends Component
{
    public ?int $bodega_id = null;

    public string $nombre = '';
    public string $ubicacion = '';
    public bool   $activo = true;

    public string $mensaje = '';
    public string $tipoMensaje = 'success';

    /* ========== Validación ========== */
    protected function rules(): array
    {
        return [
            'nombre' => [
                'required',
                'string',
                'max:120',
                Rule::unique('bodegas', 'nombre')->ignore($this->bodega_id),
            ],
            'ubicacion' => ['required', 'string', 'max:255'],
            'activo'    => ['boolean'],
        ];
    }

    protected array $messages = [
        'nombre.required'    => 'El nombre de la bodega es obligatorio.',
        'nombre.unique'      => 'Ya existe una bodega con ese nombre.',
        'ubicacion.required' => 'La ubicación es obligatoria.',
    ];

    protected array $validationAttributes = [
        'nombre'    => 'nombre',
        'ubicacion' => 'ubicación',
        'activo'    => 'activo',
    ];

    /* ========== Ciclo de vida ========== */
    public function mount(?int $bodegaId = null): void
    {
        if ($bodegaId) {
            $this->bodega_id = $bodegaId;
            $this->cargarBodega($bodegaId);
        }
    }

    public function cargarBodega(int $id): void
    {
        if ($b = Bodegas::find($id)) {
            $this->nombre    = (string) $b->nombre;
            $this->ubicacion = (string) $b->ubicacion;
            $this->activo    = (bool) $b->activo;
        }
    }

    /* Validación campo a campo (opcional pero útil) */
    public function updated($name): void
    {
        $this->validateOnly($name);
    }

    /* ========== Acciones ========== */
    public function guardar(): void
    {
        // Normaliza entradas
        $this->nombre    = trim($this->nombre);
        $this->ubicacion = trim($this->ubicacion);

        $data = $this->validate();

        try {
            if ($this->bodega_id) {
                $bodega = Bodegas::findOrFail($this->bodega_id);
                $bodega->update($data);

                $this->mensaje = '✅ Bodega actualizada exitosamente.';
                $this->tipoMensaje = 'success';

                // evento Livewire para refrescar lista en el padre
                $this->dispatch('bodegaActualizada', id: $bodega->id);
            } else {
                $bodega = Bodegas::create($data);

                $this->mensaje = '✅ Bodega creada exitosamente.';
                $this->tipoMensaje = 'success';

                $this->dispatch('bodegaCreada', id: $bodega->id);
            }

            // Opcional: Toast genérico en el layout
            $this->dispatch('toast', type: 'success', message: $this->mensaje);

            // Limpia formulario
            $this->resetForm();

            // Cierra modal (ambas opciones sirven según tu contenedor)
            $this->dispatch('cerrarModal');            // evento Livewire
            $this->dispatchBrowserEvent('cerrar-modal-bodega'); // browser event

        } catch (QueryException $e) {
            $this->mensaje = '❌ Error en la base de datos.';
            $this->tipoMensaje = 'error';
            $this->dispatch('toast', type: 'error', message: $this->mensaje);
        } catch (Throwable $e) {
            $this->mensaje = '⚠ Ocurrió un error inesperado.';
            $this->tipoMensaje = 'warning';
            $this->dispatch('toast', type: 'warning', message: $this->mensaje);
        }
    }

    public function cancelar(): void
    {
        $this->resetForm();
        $this->dispatch('cerrarModal');
    }

    private function resetForm(): void
    {
        $this->reset(['bodega_id', 'nombre', 'ubicacion', 'activo']);
        $this->activo = true;
        // Mantén los mensajes visibles tras guardar; si no los quieres, descomenta:
        // $this->mensaje = ''; $this->tipoMensaje = 'success';
    }

    public function render()
    {
        return view('livewire.bodegas.create');
    }
}
