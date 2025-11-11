<?php

namespace App\Livewire\ConfiguracionEmpresas;

use App\Models\ConfiguracionEmpresas\Empresa;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithPagination;
use Throwable;

class Empresas extends Component
{
    use WithPagination;

    protected $paginationTheme = 'tailwind';

    /** Guardamos sólo el ID para evitar problemas de (de)hidratación */
    public ?int $empresa_id = null;

    // Datos base
    public string $nombre = '';
    public ?string $nit = null;
    public ?string $email = null;
    public ?string $telefono = null;
    public ?string $sitio_web = null;
    public ?string $direccion = null;
    public bool $is_activa = true;
    public ?string $color_primario = null;
    public ?string $color_secundario = null;

    // Imágenes Base64 (nuevas subidas)
    public ?string $logo_b64 = null;
    public ?string $logo_dark_b64 = null;
    public ?string $favicon_b64 = null;

    // Rutas actuales (para previsualizar en edición)
    public ?string $logo_actual = null;
    public ?string $logo_dark_actual = null;
    public ?string $favicon_actual = null;

    // Tema PDF + extras UI
    public array $theme = [];
    public bool $usar_gradiente = false;
    public int $grad_angle = 0;

    // Filtros/estado UI
    public string $q = '';
    public int $perPage = 10;
    public ?string $ok = null;

    public function mount(): void
    {
        $this->theme = $this->defaultTheme();

        if ($empresa = Empresa::query()->first()) {
            $this->empresa_id = $empresa->id;
            $this->fillFromModel($empresa);
        }
    }

    private function defaultTheme(): array
    {
        return [
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
    }

    protected function rules(): array
    {
        return [
            'nombre' => ['required','string','max:255'],
            'nit' => ['nullable','string','max:50'],
            'email'  => ['nullable','email','max:255'],
            'telefono' => ['nullable','string','max:50'],
            'sitio_web' => ['nullable','url','max:255'],
            'direccion' => ['nullable','string','max:255'],
            'is_activa' => ['boolean'],

            'color_primario' => ['nullable','string','max:32'],
            'color_secundario' => ['nullable','string','max:32'],

            'theme.*' => ['nullable','string','max:64'],

            'logo_b64' => ['nullable','string'],
            'logo_dark_b64' => ['nullable','string'],
            'favicon_b64' => ['nullable','string'],
        ];
    }

    public function updatingQ() { $this->resetPage(); }
    public function updatingPerPage() { $this->resetPage(); }

    public function createNew(): void
    {
        $this->resetForm();
        $this->empresa_id = null;
        $this->ok = null;
    }

    public function edit(int $id): void
    {
        try {
            $empresa = Empresa::findOrFail($id);
            $this->empresa_id = $empresa->id;
            $this->fillFromModel($empresa);
            $this->ok = null;
        } catch (Throwable $e) {
            $this->handleException($e, 'No se pudo cargar la empresa.');
        }
    }

    public function cancel(): void
    {
        $this->createNew();
    }

    public function save(): void
    {
        try {
            // Merge defaults por si theme viene parcial
            $this->theme = array_replace($this->defaultTheme(), $this->theme ?? []);

            $this->validate();

            // SIEMPRE re-hidratar desde BD por ID (clave del fix!)
            $empresa = $this->empresa_id
                ? Empresa::findOrFail($this->empresa_id)
                : new Empresa();

            $empresa->fill([
                'nombre'          => $this->nombre,
                'nit'             => $this->nit,
                'email'           => $this->email,
                'telefono'        => $this->telefono,
                'sitio_web'       => $this->sitio_web,
                'direccion'       => $this->direccion,
                'is_activa'       => $this->is_activa,
                'color_primario'  => $this->color_primario,
                'color_secundario'=> $this->color_secundario,
                'pdf_theme'       => $this->theme,
            ]);

            // Guardar imágenes sólo si se subieron nuevas
            if ($this->logo_b64) {
                $empresa->logo_path = $this->storeBase64Image($this->logo_b64, 'logos', 'logo');
            }
            if ($this->logo_dark_b64) {
                $empresa->logo_dark_path = $this->storeBase64Image($this->logo_dark_b64, 'logos', 'logo-dark');
            }
            if ($this->favicon_b64) {
                $empresa->favicon_path = $this->storeBase64Image($this->favicon_b64, 'favicons', 'favicon');
            }

            $empresa->save();
            $this->empresa_id = $empresa->id;

            // Refrescar “actuales” para la vista
            $this->logo_actual   = $this->toPublicUrl($empresa->logo_path);
            $this->logo_dark_actual = $this->toPublicUrl($empresa->logo_dark_path);
            $this->favicon_actual   = $this->toPublicUrl($empresa->favicon_path);

            $this->ok = 'Configuración guardada correctamente.';
            $this->resetUploads();

        } catch (Throwable $e) {
            $this->handleException($e, 'No se pudo guardar la configuración.');
        }
    }

    public function delete(int $id): void
    {
        try {
            $empresa = Empresa::findOrFail($id);
            $empresa->delete();

            if ($this->empresa_id === $id) {
                $this->createNew();
            }
            $this->ok = 'Empresa eliminada.';
            $this->resetPage();
        } catch (Throwable $e) {
            $this->handleException($e, 'No se pudo eliminar la empresa.');
        }
    }

    private function storeBase64Image(string $dataUrl, string $folder, string $prefix): string
    {
        if (!str_contains($dataUrl, ';base64,')) {
            throw new \RuntimeException('Imagen inválida.');
        }
        [$meta, $encoded] = explode(';base64,', $dataUrl, 2);
        $mime = str_replace('data:', '', $meta);
        $ext = match ($mime) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            'image/svg+xml' => 'svg',
            'image/x-icon', 'image/vnd.microsoft.icon' => 'ico',
            default => 'png',
        };

        $binary = base64_decode($encoded);
        if ($binary === false) {
            throw new \RuntimeException('No se pudo decodificar la imagen.');
        }

        $path = "empresas/{$folder}/{$prefix}-".uniqid().".{$ext}";
        Storage::disk('public')->put($path, $binary);
        return $path;
    }

    private function fillFromModel(Empresa $m): void
    {
        $this->fill([
            'nombre'          => $m->nombre,
            'nit'             => $m->nit,
            'email'           => $m->email,
            'telefono'        => $m->telefono,
            'sitio_web'       => $m->sitio_web,
            'direccion'       => $m->direccion,
            'is_activa'       => (bool) $m->is_activa,
            'color_primario'  => $m->color_primario,
            'color_secundario'=> $m->color_secundario,
        ]);

        $this->theme = array_replace($this->defaultTheme(), (array) $m->pdf_theme);

        // Para mostrar logos actuales en la UI
        $this->logo_actual      = $this->toPublicUrl($m->logo_path);
        $this->logo_dark_actual = $this->toPublicUrl($m->logo_dark_path);
        $this->favicon_actual   = $this->toPublicUrl($m->favicon_path);

        $this->resetUploads();
    }

    private function resetForm(): void
    {
        $this->reset([
            'nombre','nit','email','telefono','sitio_web','direccion',
            'is_activa','color_primario','color_secundario','logo_actual','logo_dark_actual','favicon_actual'
        ]);
        $this->is_activa = true;
        $this->theme = $this->defaultTheme();
        $this->usar_gradiente = false;
        $this->grad_angle = 0;
        $this->resetUploads();
    }

    private function resetUploads(): void
    {
        $this->reset(['logo_b64','logo_dark_b64','favicon_b64']);
    }

    private function toPublicUrl(?string $path): ?string
    {
        if (!$path) return null;
        if (str_starts_with($path, 'data:image/')) return $path;
        return asset('storage/' . $path);
    }

    public function render()
    {
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
    }

    private function handleException(Throwable $e, string $userMessage): void
    {
        Log::error($userMessage, [
            'component' => static::class,
            'empresa_id' => $this->empresa_id,
            'exception' => get_class($e),
            'message' => $e->getMessage(),
        ]);

        $this->addError('general', $userMessage);
    }
}
