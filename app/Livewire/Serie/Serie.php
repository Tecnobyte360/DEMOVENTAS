<?php

namespace App\Livewire\Serie;

use App\Models\Serie\Serie as SerieSerie; // alias del modelo
use App\Models\TiposDocumento\TipoDocumento;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Masmerise\Toaster\PendingToast;
use Illuminate\Pagination\LengthAwarePaginator;

class Serie extends Component
{
    use WithPagination;

    protected $paginationTheme = 'tailwind';

    // Listado / filtros
    public string $search = '';
    public int $perPage = 10;

    // Form
    public ?int $serie_id = null;
    public int  $tipo_documento_id = 0;   // <- reemplaza 'documento'
    public bool $es_default = false;

    public string $nombre = '';
    public string $prefijo = '';
    public int    $rango_desde = 1;
    public int    $rango_hasta = 999999;
    public int    $proximo_ui = 1;
    public int    $longitud   = 6;
    public string $resolucion = '';
    public ?string $fecha_inicio = null;
    public ?string $fecha_fin    = null;
    public bool   $activo = true;

    public bool $showModal = false;

    /** opciones para el select */
    public array $tipos = [];

    protected $queryString = ['search', 'perPage'];

   public function mount(): void
{
    $this->tipos = TipoDocumento::orderBy('nombre')
        ->get(['id','nombre','codigo'])
        ->map(fn($t)=>['id'=>$t->id,'nombre'=>$t->nombre,'codigo'=>$t->codigo])
        ->toArray();

    $this->tipo_documento_id = (int) (
        TipoDocumento::where('codigo','factura')->value('id')
        ?? TipoDocumento::orderBy('id')->value('id')  
        ?? 0
    );
}

    protected function rules(): array
{
    return [
        'tipo_documento_id' => ['required', 'integer', 'exists:tipo_documentos,id'],
        'es_default'        => ['boolean'],

        'nombre'  => ['required', 'string', 'max:120'],
        'prefijo' => [
            'nullable',
            'string',
            'max:10',
            Rule::unique('series', 'prefijo')
                ->where(function ($q) {
                    $q->where('nombre', trim($this->nombre))
                      ->whereNotNull('prefijo')
                      ->where('prefijo', '<>', '');
                })
                ->ignore($this->serie_id),
        ],

        'rango_desde'  => ['required', 'integer', 'min:1'],
        'rango_hasta'  => ['required', 'integer', 'gte:rango_desde'],
        'proximo_ui'   => ['required', 'integer', 'min:1'],
        'longitud'     => ['required', 'integer', 'min:1', 'max:12'],

        'resolucion'   => ['nullable', 'string', 'max:120'],
        'fecha_inicio' => ['nullable', 'date'],
        'fecha_fin'    => ['nullable', 'date', 'after_or_equal:fecha_inicio'],

        'activo'       => ['boolean'],
    ];
}

    public function render()
    {
        try {
            $items = SerieSerie::query()
                ->with('tipo')
                ->when(trim($this->search) !== '', function ($q) {
                    $s = '%' . trim($this->search) . '%';
                    $q->where(fn($w) => $w->where('nombre', 'like', $s)
                        ->orWhere('prefijo', 'like', $s)
                        ->orWhere('resolucion', 'like', $s)
                        ->orWhereHas('tipo', fn($t)=>$t->where('nombre','like',$s)->orWhere('codigo','like',$s))
                    );
                })
                ->orderByDesc('id')
                ->paginate($this->perPage);

            return view('livewire.serie.serie', compact('items'));
        } catch (\Throwable $e) {
            report($e);

            PendingToast::create()
                ->error()
                ->message(config('app.debug')
                    ? ('No se pudo cargar el listado de series: ' . $e->getMessage())
                    : 'No se pudo cargar el listado de series.')
                ->duration(8000);

            $items = new LengthAwarePaginator(
                collect([]), 0, $this->perPage,
                request()->integer('page', 1),
                ['path'=>request()->url(), 'query'=>request()->query()]
            );
            return view('livewire.serie.serie', compact('items'));
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
            $m = SerieSerie::with('tipo')->findOrFail($id);

            $this->fill([
                'serie_id'          => $m->id,
                'tipo_documento_id' => (int) $m->tipo_documento_id,
                'es_default'        => (bool) $m->es_default,

                'nombre'       => (string) $m->nombre,
                'prefijo'      => (string) $m->prefijo,
                'rango_desde'  => (int) $m->desde,
                'rango_hasta'  => (int) $m->hasta,
                'proximo_ui'   => (int) $m->proximo,
                'longitud'     => (int) ($m->longitud ?? 6),
                'resolucion'   => (string) ($m->resolucion ?? ''),
                'fecha_inicio' => optional($m->vigente_desde)?->toDateString(),
                'fecha_fin'    => optional($m->vigente_hasta)?->toDateString(),
                'activo'       => (bool) $m->activa,
            ]);

            $this->showModal = true;
        } catch (\Throwable $e) {
            report($e);
            PendingToast::create()->error()->message('No se pudo cargar la serie seleccionada.')->duration(6000);
        }
    }

    // App/Livewire/Serie/Serie.php

public function save(): void
{
    try {
        $this->validate();
        $this->validateBusinessRules();

        DB::transaction(function () {
            $current = $this->serie_id
                ? SerieSerie::whereKey($this->serie_id)->lockForUpdate()->first()
                : null;

            $desde = (int) $this->rango_desde;
            $hasta = (int) $this->rango_hasta;

            // ⚠️ SOLO cuando editas una serie existente
            $maxUsado = $current
                ? \App\Models\Factura\Factura::where('serie_id', $current->id)->max('numero')
                : null;

            $minPermitido = $maxUsado ? ((int)$maxUsado + 1) : $desde;

            $proximo = max((int)$this->proximo_ui, $minPermitido);
            $proximo = min($proximo, $hasta);

            if ($this->es_default) {
                SerieSerie::where('tipo_documento_id', $this->tipo_documento_id)
                    ->when($this->serie_id, fn($q) => $q->where('id', '<>', $this->serie_id))
                    ->update(['es_default' => false]);
            }

            $serie = SerieSerie::updateOrCreate(
                ['id' => $this->serie_id],
                [
                    'tipo_documento_id' => $this->tipo_documento_id,
                    'es_default'        => (bool)$this->es_default,
                    'nombre'            => $this->nombre,
                    'prefijo'           => $this->prefijo,
                    'desde'             => $desde,
                    'hasta'             => $hasta,
                    'proximo'           => $proximo,
                    'longitud'          => (int)$this->longitud,
                    'resolucion'        => $this->resolucion,
                    'vigente_desde'     => $this->fecha_inicio,
                    'vigente_hasta'     => $this->fecha_fin,
                    'activa'            => (bool)$this->activo,
                ]
            );

            $this->serie_id = $serie->id;
        }, 3);

        $this->showModal = false;
        $this->resetForm();
        PendingToast::create()->success()->message('Serie guardada correctamente.')->duration(4500);
    } catch (\Illuminate\Validation\ValidationException $ve) {
        PendingToast::create()->error()->message('Revisa los campos marcados.')->duration(6000);
        throw $ve;
    } catch (\Throwable $e) {
        report($e);
        $text = $e->getMessage();
        $msg = 'No se pudo guardar la serie.';
        if (str_contains($text, 'IX_series_un_default_por_tipo') || str_contains($text, 'default_unique_key')) {
            $msg = 'Solo puede existir una serie "Default" por tipo de documento.';
        }
        if (str_contains(strtolower($text), 'unique') && str_contains($text, 'prefijo')) {
            $msg = 'Ya existe una serie con ese Prefijo y Nombre.';
        }
        PendingToast::create()->error()->message($msg)->duration(8000);
    }
}

    public function toggleActivo(int $id): void
    {
        try {
            $m = SerieSerie::findOrFail($id);
            $m->activa = ! $m->activa;
            $m->save();

            PendingToast::create()->info()->message($m->activa ? 'Serie activada.' : 'Serie desactivada.')->duration(4000);
        } catch (\Throwable $e) {
            report($e);
            PendingToast::create()->error()->message('No se pudo cambiar el estado.')->duration(6000);
        }
    }

    public function delete(int $id): void
    {
        try {
            $m = SerieSerie::findOrFail($id);
$tieneDocs = \App\Models\Factura\Factura::where('serie_id', $id)->exists();
            if ($tieneDocs) {
                PendingToast::create()->warning()->message('No puedes eliminar una serie con documentos emitidos.')->duration(7000);
                return;
            }

            $m->delete();
            PendingToast::create()->success()->message('Serie eliminada.')->duration(4500);
        } catch (\Throwable $e) {
            report($e);
            PendingToast::create()->error()->message('No se pudo eliminar la serie.')->duration(7000);
        }
    }

    public function previewSiguiente(?int $id = null): string
    {
        try {
            $long = (int) ($this->longitud ?: 6);

            if ($id) {
                $s = SerieSerie::find($id);
                if (!$s) return '';
                $n = max((int)$s->proximo, (int)$s->desde);
                $num = str_pad((string)$n, $long, '0', STR_PAD_LEFT);
                return ($s->prefijo ? "{$s->prefijo}-" : '') . $num;
            }

            $n = max((int) ($this->proximo_ui ?: $this->rango_desde), $this->rango_desde);
            $n = min($n, (int)$this->rango_hasta);
            $num = str_pad((string)$n, $long, '0', STR_PAD_LEFT);

            return ($this->prefijo ? "{$this->prefijo}-" : '') . $num;
        } catch (\Throwable $e) {
            report($e);
            return '';
        }
    }

    protected function validateBusinessRules(): void
    {
        if ($this->rango_desde < 1) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'rango_desde' => 'El valor mínimo permitido es 1.',
            ]);
        }

        if ($this->rango_hasta < $this->rango_desde) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'rango_hasta' => 'Debe ser mayor o igual que "Desde".',
            ]);
        }

        if ($this->proximo_ui < $this->rango_desde || $this->proximo_ui > $this->rango_hasta) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'proximo_ui' => 'El próximo debe estar dentro del rango definido.',
            ]);
        }

        if ($this->es_default && !$this->activo) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'es_default' => 'Una serie "Default" debe estar activa.',
            ]);
        }

        if ($this->serie_id) {
         $maxUsado = \App\Models\Factura\Factura::where('serie_id', $this->serie_id)->max('numero');

            if ($maxUsado && (int)$this->rango_hasta < (int)$maxUsado) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'rango_hasta' => 'El valor "Hasta" no puede ser menor que el último número emitido (' . $maxUsado . ').',
                ]);
            }
        }
    }

    private function resetForm(): void
    {
        $this->serie_id   = null;
        $this->tipo_documento_id = (int) (TipoDocumento::where('codigo','factura')->value('id') ?? 0);
        $this->es_default = false;

        $this->nombre = '';
        $this->prefijo = '';
        $this->rango_desde = 1;
        $this->rango_hasta = 999999;
        $this->proximo_ui  = 1;
        $this->longitud    = 6;
        $this->resolucion  = '';
        $this->fecha_inicio = null;
        $this->fecha_fin    = null;
        $this->activo = true;
    }
}
