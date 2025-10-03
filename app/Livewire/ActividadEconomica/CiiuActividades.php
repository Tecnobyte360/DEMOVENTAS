<?php

namespace App\Livewire\ActividadEconomica;

use App\Models\CiiuActividad;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;
use Throwable;

class CiiuActividades extends Component
{
    use WithPagination;

    protected $paginationTheme = 'tailwind';

    // Estado UI
    public bool $showModal = false;

    // Filtros / formulario
    public string $search = '';
    public ?int $actividadId = null;
    public string $codigo = '';
    public string $descripcion = '';

    // Mantener búsqueda y página en la URL
    protected array $queryString = [
        'search' => ['except' => ''],
        'page'   => ['except' => 1],
    ];

    // Reglas y mensajes
    protected function rules(): array
    {
        return [
            'codigo'      => ['required','string','max:10', Rule::unique('ciiu_actividades','codigo')->ignore($this->actividadId)],
            'descripcion' => ['required','string','max:255'],
        ];
    }

    protected array $messages = [
        'codigo.required'      => 'El código es obligatorio.',
        'codigo.max'           => 'El código no debe superar 10 caracteres.',
        'codigo.unique'        => 'Ya existe una actividad con ese código.',
        'descripcion.required' => 'La descripción es obligatoria.',
        'descripcion.max'      => 'La descripción no debe superar 255 caracteres.',
    ];

    public function render()
    {
        $actividades = CiiuActividad::query()
            ->search($this->search)
            ->orderBy('codigo')
            ->paginate(10);

        return view('livewire.actividad-economica.ciiu-actividades', compact('actividades'));
    }

    /** Resetear paginación cuando cambia la búsqueda */
    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    /** Abrir modal para crear */
    public function create(): void
    {
        $this->resetForm();
        $this->showModal = true;
    }

    /** Abrir modal para editar */
    public function edit(int $id): void
    {
        $a = CiiuActividad::findOrFail($id);

        $this->actividadId = $a->id;
        $this->codigo      = $a->codigo;       // se normaliza al guardar vía mutador
        $this->descripcion = $a->descripcion;

        $this->showModal = true;
    }

    /** Guardar (crear/editar) con try/catch + transacción */
    public function save(): void
    {
        $this->validate();

        try {
            DB::transaction(function () {
                CiiuActividad::updateOrCreate(
                    ['id' => $this->actividadId],
                    [
                        'codigo'      => $this->codigo,       // mutador hará trim + strtoupper
                        'descripcion' => trim($this->descripcion),
                    ]
                );
            });

            session()->flash('message', 'Actividad guardada correctamente.');
            $this->showModal = false;
            $this->resetForm();
            $this->resetPage();
        } catch (Throwable $e) {
            Log::error('Error guardando CIIU', [
                'actividadId' => $this->actividadId,
                'payload'     => ['codigo' => $this->codigo, 'descripcion' => $this->descripcion],
                'error'       => $e->getMessage(),
            ]);

            $this->addError('general', 'Ocurrió un error guardando la actividad. Intenta nuevamente.');
        }
    }

    /** Eliminar con manejo de página */
    public function delete(int $id): void
    {
        try {
            DB::transaction(function () use ($id) {
                CiiuActividad::findOrFail($id)->delete();
            });

            session()->flash('message', 'Actividad eliminada correctamente.');

            // Si nos quedamos sin registros en la página actual, retroceder una
            $perPage = 10;
            $total   = CiiuActividad::search($this->search)->count();
            $maxPage = max(1, (int) ceil($total / $perPage));
            if ($this->page > $maxPage) {
                $this->gotoPage($maxPage);
            }
        } catch (Throwable $e) {
            Log::error('Error eliminando CIIU', ['id' => $id, 'error' => $e->getMessage()]);
            $this->addError('general', 'No fue posible eliminar la actividad.');
        }
    }

    /** Cerrar modal manualmente */
    public function closeModal(): void
    {
        $this->showModal = false;
        $this->resetForm();
    }

    /** Limpiar formulario */
    private function resetForm(): void
    {
        $this->reset(['actividadId','codigo','descripcion']);
        $this->resetValidation();
    }
}
