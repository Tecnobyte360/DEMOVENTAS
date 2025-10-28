<?php

namespace App\Livewire\TipoDocumentos;

use App\Models\TiposDocumento\TipoDocumento;
use Livewire\Component;
use Livewire\WithPagination;

use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Masmerise\Toaster\PendingToast;
use Illuminate\Pagination\LengthAwarePaginator;

class Tiposdocumento extends Component
{
    use WithPagination;

    protected $paginationTheme = 'tailwind';

    // 🔎 Filtros y paginación
    public string $search = '';
    public int $perPage = 10;

    // 🧾 Campos del formulario
    public ?int $tipo_id = null;
    public string $codigo = '';
    public string $nombre = '';
    public ?string $modulo = null;
    public string $config_json = '{}';

    public bool $showModal = false;

    protected $queryString = ['search', 'perPage'];

    protected function rules(): array
    {
        return [
            'codigo' => [
                'required',
                'string',
                'max:40',
                Rule::unique('tipo_documentos', 'codigo')->ignore($this->tipo_id),
            ],
            'nombre' => ['required', 'string', 'max:120'],
            'modulo' => ['nullable', 'string', 'max:60'],
            'config_json' => ['nullable', 'string'],
        ];
    }

    public function render()
    {
        try {
            $items = TipoDocumento::query()
                ->when($this->search, function ($q) {
                    $s = '%' . trim($this->search) . '%';
                    $q->where(fn($w) =>
                        $w->where('nombre', 'like', $s)
                          ->orWhere('codigo', 'like', $s)
                          ->orWhere('modulo', 'like', $s)
                    );
                })
                ->orderByDesc('id')
                ->paginate($this->perPage);

            return view('livewire.tipo-documentos.tiposdocumento', compact('items'));
        } catch (\Throwable $e) {
            report($e);

            PendingToast::create()->error()->message('No se pudo cargar el listado.')->duration(6000);

            $items = new LengthAwarePaginator(collect([]), 0, $this->perPage);
            return view('livewire.tipo-documentos.tiposdocumento', compact('items'));
        }
    }


    public function create(): void
    {
        $this->resetForm();
        $this->showModal = true;
    }

  
    public function edit(int $id): void
    {
        try {
            $m = TipoDocumento::findOrFail($id);

            $this->fill([
                'tipo_id' => $m->id,
                'codigo' => $m->codigo,
                'nombre' => $m->nombre,
                'modulo' => $m->modulo,
                'config_json' => json_encode($m->config ?? [], JSON_PRETTY_PRINT),
            ]);

            $this->showModal = true;
        } catch (\Throwable $e) {
            report($e);
            PendingToast::create()->error()->message('No se pudo cargar el registro.')->duration(6000);
        }
    }


    public function save(): void
    {
        try {
            $this->validate();

            $config = json_decode($this->config_json, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('El campo "Config" no es un JSON válido.');
            }

            TipoDocumento::updateOrCreate(
                ['id' => $this->tipo_id],
                [
                    'codigo' => $this->codigo,
                    'nombre' => $this->nombre,
                    'modulo' => $this->modulo,
                    'config' => $config,
                ]
            );

            PendingToast::create()->success()->message('Tipo de documento guardado correctamente.')->duration(4000);

            $this->showModal = false;
            $this->resetForm();
        } catch (\Illuminate\Validation\ValidationException $ve) {
            throw $ve;
        } catch (\Throwable $e) {
            report($e);
            PendingToast::create()->error()->message('Error al guardar el registro.')->duration(6000);
        }
    }

 
    public function delete(int $id): void
    {
        try {
            TipoDocumento::findOrFail($id)->delete();
            PendingToast::create()->success()->message('Tipo de documento eliminado.')->duration(4000);
        } catch (\Throwable $e) {
            report($e);
            PendingToast::create()->error()->message('No se pudo eliminar el registro.')->duration(6000);
        }
    }

    private function resetForm(): void
    {
        $this->tipo_id = null;
        $this->codigo = '';
        $this->nombre = '';
        $this->modulo = '';
        $this->config_json = '{}';
    }
}
