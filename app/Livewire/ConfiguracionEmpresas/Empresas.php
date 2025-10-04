<?php

namespace App\Livewire\ConfiguracionEmpresas;

use App\Models\ConfiguracionEmpresas\Empresa;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class Empresas extends Component
{
    use WithFileUploads, WithPagination;

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

    // Uploads
    public $logo;
    public $logo_dark;
    public $favicon;

    // Diagnóstico (para mostrar en la vista)
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

            // 2 MB (ajusta si necesitas)
            'logo'             => ['nullable','image','mimes:png,jpg,jpeg,webp','max:2048'],
            'logo_dark'        => ['nullable','image','mimes:png,jpg,jpeg,webp','max:2048'],
            'favicon'          => ['nullable','mimes:png,jpg,jpeg,webp,ico','max:1024'],
        ];
    }

    protected function messages(): array
    {
        return [
            'logo.image' => 'El logo debe ser una imagen válida.',
            'logo.mimes' => 'Formatos permitidos: png, jpg, jpeg, webp.',
            'logo.max'   => 'El logo supera el tamaño máximo permitido (2 MB).',

            'logo_dark.image' => 'El logo oscuro debe ser una imagen válida.',
            'logo_dark.mimes' => 'Formatos permitidos: png, jpg, jpeg, webp.',
            'logo_dark.max'   => 'El logo oscuro supera el tamaño máximo permitido (2 MB).',

            'favicon.mimes' => 'El favicon debe ser png, jpg, jpeg, webp o ico.',
            'favicon.max'   => 'El favicon supera el tamaño máximo permitido (1 MB).',
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
        $model = Empresa::findOrFail($id);
        $this->empresa = $model;
        $this->fillFromModel($model);
        $this->uploadDiagnostics = [];
    }

    public function cancel(): void
    {
        $this->createNew();
    }

    public function save(): void
    {
        // 1) Diagnóstico previo a cualquier guardado
        $this->uploadDiagnostics = $this->diagnoseUploads();

        if (!empty($this->uploadDiagnostics['errors'])) {
            // Muestra un error arriba y no continúa
            $this->addError('logo', 'Falló la subida: revisa el diagnóstico.');
            session()->flash('ok', null);
            return;
        }

        // 2) Validación de datos
        $this->validate();

        // Si van a usar gradiente, exige ambos colores
        if ($this->usar_gradiente && (!$this->color_primario || !$this->color_secundario)) {
            $this->addError('color_primario', 'Debes definir color primario y secundario para el gradiente.');
            $this->addError('color_secundario', 'Debes definir color primario y secundario para el gradiente.');
            return;
        }

        // 3) Guardado
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

        // 4) Reemplazo de archivos con manejo de errores
        try {
            if ($this->logo) {
                $this->replaceFile($empresa, 'logo_path', $this->logo, 'empresas/logos');
            }
            if ($this->logo_dark) {
                $this->replaceFile($empresa, 'logo_dark_path', $this->logo_dark, 'empresas/logos');
            }
            if ($this->favicon) {
                $this->replaceFile($empresa, 'favicon_path', $this->favicon, 'empresas/favicons');
            }
        } catch (\Throwable $e) {
            Log::error('Error subiendo archivos de empresa', [
                'empresa_id' => $this->empresa->id ?? null,
                'error'      => $e->getMessage(),
                'trace'      => $e->getTraceAsString(),
            ]);
            $this->addError('logo', 'Error al guardar archivos: '.$e->getMessage());
            return;
        }

        $empresa->save();
        $this->empresa = $empresa;

        session()->flash('ok', 'Configuración de empresa guardada correctamente.');
        $this->resetUploads();
    }

    public function delete(int $id): void
    {
        $empresa = Empresa::findOrFail($id);

        foreach (['logo_path','logo_dark_path','favicon_path'] as $attr) {
            if ($empresa->$attr && Storage::disk('public')->exists($empresa->$attr)) {
                Storage::disk('public')->delete($empresa->$attr);
            }
        }
        $empresa->delete();

        if ($this->empresa?->id === $id) {
            $this->createNew();
        }

        session()->flash('ok', 'Empresa eliminada.');
        $this->resetPage();
    }

    /** Guardado con try/catch y verificación previa del disco */
    private function replaceFile(Empresa $empresa, string $attr, $uploadedFile, string $dir): void
    {
        // Asegura que el disco public está disponible
        if (! Storage::disk('public')) {
            throw new \RuntimeException('Disco "public" no disponible.');
        }

        // Borra anterior si existe
        if ($empresa->$attr && Storage::disk('public')->exists($empresa->$attr)) {
            Storage::disk('public')->delete($empresa->$attr);
        }

        // Guarda
        $path = $uploadedFile->store($dir, 'public');
        if (! $path) {
            throw new \RuntimeException('No se pudo guardar el archivo en el disco public.');
        }

        $empresa->$attr = $path;
    }

    /** Diagnóstico de entorno de subidas */
    private function diagnoseUploads(): array
    {
        $errors = [];
        $warnings = [];
        $info = [];

        // 1) php.ini limits
        $uploadMax = ini_get('upload_max_filesize');
        $postMax   = ini_get('post_max_size');
        $memory    = ini_get('memory_limit');

        $info['php_limits'] = compact('uploadMax','postMax','memory');

        // 2) carpeta tmp de Livewire
        $tmp = storage_path('app/livewire-tmp');
        if (! is_dir($tmp)) {
            $errors[] = "No existe la carpeta temporal de Livewire: {$tmp}";
        } elseif (! is_writable($tmp)) {
            $errors[] = "La carpeta temporal de Livewire no tiene permisos de escritura: {$tmp}";
        } else {
            $info['tmp_ok'] = $tmp;
        }

        // 3) permisos de storage y cache
        $storage = storage_path();
        $cache   = storage_path('bootstrap/cache');
        if (! is_writable(storage_path('app'))) {
            $errors[] = 'El directorio storage/app no es escribible.';
        }
        if (is_dir($cache) && ! is_writable($cache)) {
            $warnings[] = 'bootstrap/cache no es escribible (recomendado ug+rwx).';
        }

        // 4) disco public
        try {
            $visibility = Storage::disk('public')->getVisibility('/') ?? 'desconocido';
            $info['disk_public'] = "OK (visibilidad: {$visibility})";
        } catch (\Throwable $e) {
            $errors[] = 'Disco "public" inaccesible: '.$e->getMessage();
        }

        // 5) tamaños de archivos seleccionados
        foreach (['logo','logo_dark','favicon'] as $field) {
            if ($this->$field) {
                try {
                    $sizeKb = round($this->$field->getSize() / 1024);
                    $mime   = $this->$field->getMimeType();
                    $info["{$field}_size_kb"] = $sizeKb.' KB';
                    $info["{$field}_mime"]    = $mime;
                } catch (\Throwable $e) {
                    $warnings[] = "No se pudo leer metadata de {$field}: ".$e->getMessage();
                }
            }
        }

        return compact('errors','warnings','info');
    }

    private function normalizeHex(?string $hex): ?string
    {
        if (!$hex) return null;
        $hex = ltrim($hex, '#');
        return '#'.strtolower($hex);
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
        $this->reset(['logo','logo_dark','favicon']);
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

        return view('livewire.configuracion-empresas.empresas',compact('rows'));
    }
}
