<?php

namespace App\Livewire\ConfiguracionEmpresas;

use App\Models\ConfiguracionEmpresas\Empresa;
use Illuminate\Support\Facades\Log;
use Livewire\Component;
use Livewire\WithPagination;
use Throwable;

class Empresas extends Component
{
    use WithPagination;

    protected $paginationTheme = 'tailwind';

    public ?Empresa $empresa = null;

    // Formulario
    public string $nombre = '';
    public ?string $nit = null;
    public ?string $email = null;
    public ?string $telefono = null;
    public ?string $sitio_web = null;
    public ?string $direccion = null;

    public ?string $color_primario = null;
    public ?string $color_secundario = null;
    public bool $usar_gradiente = false;
    public ?int $grad_angle = 135;
    public bool $is_activa = true;

    // Recibidos desde el Blade como DataURL Base64
    public ?string $logo_b64 = null;
    public ?string $logo_dark_b64 = null;
    public ?string $favicon_b64 = null;

    // (opcional) diagnóstico
    public array $uploadDiagnostics = [];

    public string $q = '';
    public int $perPage = 10;

    protected function rules(): array
    {
        return [
            'nombre'           => ['required','string','max:255'],
            'nit'              => ['nullable','string','max:50'],
            'email'            => ['nullable','email','max:255'],
            'telefono'         => ['nullable','string','max:50'],
            'sitio_web'        => ['nullable','url','max:255'],
            'direccion'        => ['nullable','string','max:255'],

            'color_primario'   => ['nullable','regex:/^#?[0-9A-Fa-f]{6}$/'],
            'color_secundario' => ['nullable','regex:/^#?[0-9A-Fa-f]{6}$/'],

            'usar_gradiente'   => ['boolean'],
            'grad_angle'       => ['nullable','integer','min:0','max:360'],
            'is_activa'        => ['boolean'],

            // Validación simple de DataURL base64
            'logo_b64'        => ['nullable','string','starts_with:data:image/'],
            'logo_dark_b64'   => ['nullable','string','starts_with:data:image/'],
            'favicon_b64'     => ['nullable','string','starts_with:data:image/'],
        ];
    }

    public function updatingQ(){ $this->resetPage(); }
    public function updatingPerPage(){ $this->resetPage(); }

    public function createNew(): void
    {
        $this->resetForm();
        $this->empresa = null;
        $this->uploadDiagnostics = [];
    }

    public function edit(int $id): void
    {
        try {
            $model = Empresa::findOrFail($id);
            $this->empresa = $model;
            $this->fillFromModel($model);
            $this->uploadDiagnostics = [];
        } catch (Throwable $e) {
            $this->handleException($e, 'No se pudo cargar la empresa seleccionada.');
        }
    }

    public function cancel(): void
    {
        $this->createNew();
    }

    public function save(): void
    {
        try {
            // Valida campos (deja que arroje ValidationException si algo falla)
            $this->validate();

            if ($this->usar_gradiente && (!$this->color_primario || !$this->color_secundario)) {
                $this->addError('color_primario', 'Debes definir color primario y secundario para el gradiente.');
                $this->addError('color_secundario', 'Debes definir color primario y secundario para el gradiente.');
                return;
            }

            // Guarda datos base
            $empresa = $this->empresa ?? new Empresa();
            $empresa->fill([
                'nombre'           => $this->nombre,
                'nit'              => $this->nit,
                'email'            => $this->email,
                'telefono'         => $this->telefono,
                'sitio_web'        => $this->sitio_web,
                'direccion'        => $this->direccion,
                'color_primario'   => $this->normalizeHex($this->color_primario),
                'color_secundario' => $this->normalizeHex($this->color_secundario),
                'usar_gradiente'   => $this->usar_gradiente,
                'grad_angle'       => $this->grad_angle ?? 135,
                'is_activa'        => $this->is_activa,
            ]);

            // Si llegaron nuevas imágenes en base64, se guardan en los mismos campos *_path
            if ($this->logo_b64) {
                $empresa->logo_path = $this->logo_b64;
            }
            if ($this->logo_dark_b64) {
                $empresa->logo_dark_path = $this->logo_dark_b64;
            }
            if ($this->favicon_b64) {
                $empresa->favicon_path = $this->favicon_b64;
            }

            $empresa->save();
            $this->empresa = $empresa;

            session()->flash('ok', 'Configuración de empresa guardada correctamente.');
            $this->resetUploads();
        } catch (Throwable $e) {
            $this->handleException($e, 'No se pudo guardar la configuración de la empresa.');
        }
    }

    public function delete(int $id): void
    {
        try {
            $empresa = Empresa::findOrFail($id);
            $empresa->delete();

            if ($this->empresa?->id === $id) {
                $this->createNew();
            }

            session()->flash('ok', 'Empresa eliminada.');
            $this->resetPage();
        } catch (Throwable $e) {
            $this->handleException($e, 'No se pudo eliminar la empresa.');
        }
    }

    private function normalizeHex(?string $hex): ?string
    {
        return $hex ? '#'.strtolower(ltrim($hex, '#')) : null;
    }

    private function fillFromModel(Empresa $m): void
    {
        $this->fill([
            'nombre'           => $m->nombre,
            'nit'              => $m->nit,
            'email'            => $m->email,
            'telefono'         => $m->telefono,
            'sitio_web'        => $m->sitio_web,
            'direccion'        => $m->direccion,
            'color_primario'   => $m->color_primario,
            'color_secundario' => $m->color_secundario,
            'usar_gradiente'   => (bool) ($m->usar_gradiente ?? false),
            'grad_angle'       => (int)  ($m->grad_angle ?? 135),
            'is_activa'        => (bool) ($m->is_activa ?? true),
        ]);

        $this->resetUploads();
    }

    private function resetForm(): void
    {
        $this->reset([
            'nombre','nit','email','telefono','sitio_web','direccion',
            'color_primario','color_secundario','usar_gradiente','grad_angle','is_activa',
        ]);
        $this->is_activa = true;
        $this->grad_angle = 135;
        $this->resetUploads();
    }

    private function resetUploads(): void
    {
        // Limpia las 3 cadenas Base64
        $this->reset(['logo_b64','logo_dark_b64','favicon_b64']);
    }

    public function render()
    {
        try {
            $rows = Empresa::query()
                ->when($this->q !== '', function($q){
                    $q->where(function($sub){
                        $sub->where('nombre','like',"%{$this->q}%")
                            ->orWhere('nit','like',"%{$this->q}%")
                            ->orWhere('email','like',"%{$this->q}%")
                            ->orWhere('telefono','like',"%{$this->q}%");
                    });
                })
                ->latest('id')
                ->paginate($this->perPage);

            return view('livewire.configuracion-empresas.empresas', compact('rows'));
        } catch (Throwable $e) {
            // Si algo muy raro falla en render, registra y muestra una lista vacía
            $this->handleException($e, 'No se pudieron cargar las empresas.');
            $rows = collect([])->paginate($this->perPage);
            return view('livewire.configuracion-empresas.empresas', compact('rows'));
        }
    }

    /**
     * Manejo centralizado de excepciones: log + error amigable.
     */
    private function handleException(Throwable $e, string $userMessage): void
    {
        Log::error($userMessage, [
            'component' => static::class,
            'empresa_id' => $this->empresa->id ?? null,
            'exception' => get_class($e),
            'message' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);

        // Muestra en UI (puedes cambiar a session()->flash('error', ...) si prefieres un alert global)
        $this->addError('general', $userMessage);
    }
}
