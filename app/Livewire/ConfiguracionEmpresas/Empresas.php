<?php

namespace App\Livewire\ConfiguracionEmpresas;

use App\Models\ConfiguracionEmpresas\Empresa;
use Illuminate\Support\Facades\Log;
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

    // Diagnóstico para la vista (opcional)
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

            // Tamaños generosos por si suben logos grandes
            'logo'      => ['nullable','image','mimes:png,jpg,jpeg,webp','max:10240'], // 10MB
            'logo_dark' => ['nullable','image','mimes:png,jpg,jpeg,webp','max:10240'],
            'favicon'   => ['nullable','mimes:png,jpg,jpeg,webp,ico','max:5120'],      // 5MB
        ];
    }

    protected function messages(): array
    {
        return [
            'logo.image' => 'El logo debe ser una imagen válida.',
            'logo.mimes' => 'Formatos permitidos: png, jpg, jpeg, webp.',
            'logo.max'   => 'El logo supera el tamaño máximo permitido (10 MB).',

            'logo_dark.image' => 'El logo oscuro debe ser una imagen válida.',
            'logo_dark.mimes' => 'Formatos permitidos: png, jpg, jpeg, webp.',
            'logo_dark.max'   => 'El logo oscuro supera el tamaño máximo permitido (10 MB).',

            'favicon.mimes' => 'El favicon debe ser png, jpg, jpeg, webp o ico.',
            'favicon.max'   => 'El favicon supera el tamaño máximo permitido (5 MB).',
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
        // (Opcional) Diagnóstico simple
        $this->uploadDiagnostics = $this->diagnoseUploads();
        if (!empty($this->uploadDiagnostics['errors'])) {
            $this->addError('logo', 'Falló la subida: revisa el diagnóstico.');
            session()->flash('ok', null);
            return;
        }

        // Validación
        $this->validate();

        if ($this->usar_gradiente && (!$this->color_primario || !$this->color_secundario)) {
            $this->addError('color_primario', 'Debes definir color primario y secundario para el gradiente.');
            $this->addError('color_secundario', 'Debes definir color primario y secundario para el gradiente.');
            return;
        }

        // Guardar datos base
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
        $empresa->save();

        // Convertir a Base64 y guardar en los mismos campos *_path
        try {
            if ($this->logo) {
                $this->putBase64($empresa, 'logo_path', $this->logo);
            }
            if ($this->logo_dark) {
                $this->putBase64($empresa, 'logo_dark_path', $this->logo_dark);
            }
            if ($this->favicon) {
                $this->putBase64($empresa, 'favicon_path', $this->favicon);
            }
        } catch (\Throwable $e) {
            Log::error('Error convirtiendo imágenes a Base64', [
                'empresa_id' => $this->empresa?->id,
                'error'      => $e->getMessage(),
            ]);
            $this->addError('logo', 'Error al procesar imagen: '.$e->getMessage());
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

        // Como ya no hay archivos físicos, solo limpiamos columnas o eliminamos registro.
        $empresa->delete();

        if ($this->empresa?->id === $id) {
            $this->createNew();
        }

        session()->flash('ok', 'Empresa eliminada.');
        $this->resetPage();
    }

    /** Convierte un UploadedFile a Data URL Base64 y lo guarda en $empresa->$attr */
    private function putBase64(Empresa $empresa, string $attr, $uploadedFile): void
    {
        // Livewire UploadedFile
        $mimeType = $uploadedFile->getMimeType() ?: 'application/octet-stream';
        $contents = file_get_contents($uploadedFile->getRealPath());
        if ($contents === false) {
            throw new \RuntimeException('No se pudo leer el archivo subido.');
        }
        $empresa->$attr = 'data:'.$mimeType.';base64,'.base64_encode($contents);
    }

    /** Diagnóstico simple (sin tocar filesystem ya que no lo usamos) */
    private function diagnoseUploads(): array
    {
        $errors = [];
        $info = [];

        $info['php_limits'] = [
            'uploadMax' => ini_get('upload_max_filesize'),
            'postMax'   => ini_get('post_max_size'),
            'memory'    => ini_get('memory_limit'),
        ];

        $tmp = storage_path('app/livewire-tmp');
        if (!is_dir($tmp) || !is_writable($tmp)) {
            $errors[] = "Carpeta temporal no disponible o sin permisos: {$tmp}";
        } else {
            $info['tmp_ok'] = $tmp;
        }

        foreach (['logo','logo_dark','favicon'] as $f) {
            if ($this->$f) {
                $info["{$f}_size_kb"] = round($this->$f->getSize() / 1024).' KB';
                $info["{$f}_mime"]    = $this->$f->getMimeType();
            }
        }

        return compact('errors','info');
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

        return view('livewire.configuracion-empresas.empresas', compact('rows'));
    }
}
