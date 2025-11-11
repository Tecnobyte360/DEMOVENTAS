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

    public ?Empresa $empresa = null;

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

    // Imágenes Base64
    public ?string $logo_b64 = null;
    public ?string $logo_dark_b64 = null;
    public ?string $favicon_b64 = null;

    // Tema PDF
    public array $theme = [];

    public string $q = '';
    public int $perPage = 10;
    public ?string $ok = null;

    public function mount(): void
    {
        $this->theme = $this->defaultTheme();

        if ($empresa = Empresa::query()->first()) {
            $this->empresa = $empresa;
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
            'email'  => ['nullable','email','max:255'],
            'sitio_web' => ['nullable','url','max:255'],
            'theme.*' => ['nullable','string','max:64'],
        ];
    }

    public function updatingQ() { $this->resetPage(); }

    public function createNew(): void
    {
        $this->resetForm();
        $this->empresa = null;
        $this->ok = null;
    }

    public function edit(int $id): void
    {
        try {
            $empresa = Empresa::findOrFail($id);
            $this->empresa = $empresa;
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
            // merge defaults para evitar nulls
            $this->theme = array_replace($this->defaultTheme(), $this->theme ?? []);

            $this->validate();

            $empresa = $this->empresa ?? new Empresa();
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

            if ($this->logo_b64)
                $empresa->logo_path = $this->storeBase64Image($this->logo_b64, 'logos', 'logo');
            if ($this->logo_dark_b64)
                $empresa->logo_dark_path = $this->storeBase64Image($this->logo_dark_b64, 'logos', 'logo-dark');
            if ($this->favicon_b64)
                $empresa->favicon_path = $this->storeBase64Image($this->favicon_b64, 'favicons', 'favicon');

            $empresa->save();
            $this->empresa = $empresa;
            $this->ok = 'Configuración guardada correctamente.';
            $this->resetUploads();

        } catch (Throwable $e) {
            $this->handleException($e, 'No se pudo guardar la configuración.');
        }
    }

    private function storeBase64Image(string $dataUrl, string $folder, string $prefix): string
    {
        [$meta, $encoded] = explode(';base64,', $dataUrl, 2);
        $mime = str_replace('data:', '', $meta);
        $ext = match ($mime) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            'image/x-icon','image/vnd.microsoft.icon' => 'ico',
            default => 'png',
        };
        $binary = base64_decode($encoded);
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
        $this->resetUploads();
    }

    private function resetForm(): void
    {
        $this->reset([
            'nombre','nit','email','telefono','sitio_web','direccion',
            'is_activa','color_primario','color_secundario'
        ]);
        $this->is_activa = true;
        $this->theme = $this->defaultTheme();
        $this->resetUploads();
    }

    private function resetUploads(): void
    {
        $this->reset(['logo_b64','logo_dark_b64','favicon_b64']);
    }

    public function render()
    {
        $rows = Empresa::query()
            ->when($this->q, fn($q) =>
                $q->where('nombre','like',"%{$this->q}%")
                  ->orWhere('nit','like',"%{$this->q}%")
                  ->orWhere('email','like',"%{$this->q}%")
                  ->orWhere('telefono','like',"%{$this->q}%")
            )
            ->latest('id')
            ->paginate($this->perPage);

        return view('livewire.configuracion-empresas.empresas', compact('rows'));
    }

    private function handleException(Throwable $e, string $msg): void
    {
        Log::error($msg, ['exception' => $e]);
        $this->addError('general', $msg);
    }
}
