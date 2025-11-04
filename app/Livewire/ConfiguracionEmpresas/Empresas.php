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

    // Formulario base
    public string $nombre = '';
    public ?string $nit = null;
    public ?string $email = null;
    public ?string $telefono = null;
    public ?string $sitio_web = null;
    public ?string $direccion = null;

    public bool $is_activa = true;

    // Imágenes (Base64)
    public ?string $logo_b64 = null;
    public ?string $logo_dark_b64 = null;
    public ?string $favicon_b64 = null;

    // 🎨 Tema PDF — solo los colores reales del pdf_theme
    public array $theme = [
        'primary'   => '#7666AB',
        'base'      => '#FFFFFF',
        'ink'       => '#2B2B2B',
        'muted'     => '#4D4D4D',
        'border'    => '#E6E6E6',
        'theadBg'   => '#7666AB',
        'theadText' => '#FFFFFF',
        'stripe'    => '#F6F5FB',
        'grandBg'   => '#473C7B',
        'grandTx'   => '#FFFFFF',
        'wmColor'   => 'rgba(118, 102, 171, .06)',
    ];

    public string $q = '';
    public int $perPage = 10;

    public function mount(): void
    {
        if ($empresa = Empresa::query()->first()) {
            $this->empresa = $empresa;
            $this->fillFromModel($empresa);
        }
    }

    protected function rules(): array
    {
        return [
            'nombre'           => ['required','string','max:255'],
            'nit'              => ['nullable','string','max:50'],
            'email'            => ['nullable','email','max:255'],
            'telefono'         => ['nullable','string','max:50'],
            'sitio_web'        => ['nullable','url','max:255'],
            'direccion'        => ['nullable','string','max:255'],
            'is_activa'        => ['boolean'],

            'logo_b64'        => ['nullable','string','starts_with:data:image/'],
            'logo_dark_b64'   => ['nullable','string','starts_with:data:image/'],
            'favicon_b64'     => ['nullable','string','starts_with:data:image/'],

            // 🎨 Validar pdf_theme (sin defaults)
            'theme.primary'   => ['required','string','max:32'],
            'theme.base'      => ['required','string','max:32'],
            'theme.ink'       => ['required','string','max:32'],
            'theme.muted'     => ['required','string','max:32'],
            'theme.border'    => ['required','string','max:32'],
            'theme.theadBg'   => ['required','string','max:32'],
            'theme.theadText' => ['required','string','max:32'],
            'theme.stripe'    => ['required','string','max:32'],
            'theme.grandBg'   => ['required','string','max:32'],
            'theme.grandTx'   => ['required','string','max:32'],
            'theme.wmColor'   => ['required','string','max:64'],
        ];
    }

    public function updatingQ() { $this->resetPage(); }
    public function updatingPerPage() { $this->resetPage(); }

    public function createNew(): void
    {
        $this->resetForm();
        $this->empresa = null;
    }

    public function edit(int $id): void
    {
        try {
            $empresa = Empresa::findOrFail($id);
            $this->empresa = $empresa;
            $this->fillFromModel($empresa);
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
            $this->validate();

            $empresa = $this->empresa ?? new Empresa();
            $empresa->fill([
                'nombre'      => $this->nombre,
                'nit'         => $this->nit,
                'email'       => $this->email,
                'telefono'    => $this->telefono,
                'sitio_web'   => $this->sitio_web,
                'direccion'   => $this->direccion,
                'is_activa'   => $this->is_activa,
                'pdf_theme'   => $this->theme, // Solo se guarda lo que el usuario definió
            ]);

            // Imágenes
            if ($this->logo_b64)      $empresa->logo_path = $this->logo_b64;
            if ($this->logo_dark_b64) $empresa->logo_dark_path = $this->logo_dark_b64;
            if ($this->favicon_b64)   $empresa->favicon_path = $this->favicon_b64;

            $empresa->save();
            $this->empresa = $empresa;

            session()->flash('ok', 'Configuración guardada correctamente.');
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

    private function fillFromModel(Empresa $m): void
    {
        $this->fill([
            'nombre'     => $m->nombre,
            'nit'        => $m->nit,
            'email'      => $m->email,
            'telefono'   => $m->telefono,
            'sitio_web'  => $m->sitio_web,
            'direccion'  => $m->direccion,
            'is_activa'  => (bool) $m->is_activa,
        ]);

        // Carga el theme exactamente como está en BD
        $this->theme = (array) $m->pdf_theme;

        $this->resetUploads();
    }

    private function resetForm(): void
    {
        $this->reset([
            'nombre','nit','email','telefono','sitio_web','direccion',
            'is_activa','theme'
        ]);
        $this->is_activa = true;
        $this->theme = [
            'primary'   => '#7666AB',
            'base'      => '#FFFFFF',
            'ink'       => '#2B2B2B',
            'muted'     => '#4D4D4D',
            'border'    => '#E6E6E6',
            'theadBg'   => '#7666AB',
            'theadText' => '#FFFFFF',
            'stripe'    => '#F6F5FB',
            'grandBg'   => '#473C7B',
            'grandTx'   => '#FFFFFF',
            'wmColor'   => 'rgba(118, 102, 171, .06)',
        ];
        $this->resetUploads();
    }

    private function resetUploads(): void
    {
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
            $this->handleException($e, 'No se pudieron cargar las empresas.');
            $rows = collect([])->paginate($this->perPage);
            return view('livewire.configuracion-empresas.empresas', compact('rows'));
        }
    }

    private function handleException(Throwable $e, string $userMessage): void
    {
        Log::error($userMessage, [
            'component' => static::class,
            'empresa_id' => $this->empresa->id ?? null,
            'exception' => get_class($e),
            'message' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);

        $this->addError('general', $userMessage);
    }
}
