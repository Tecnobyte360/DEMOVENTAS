<?php

namespace App\Livewire\Inventario;

use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Masmerise\Toaster\PendingToast;

use App\Models\Bodega;
use App\Models\Categorias\SubcategoriaCuenta;
use App\Models\CuentasContables\PlanCuentas;
use App\Models\Serie\Serie;
use App\Models\SocioNegocio\SocioNegocio;
use App\Models\Productos\Producto;
use App\Models\Inventario\EntradaMercancia;
use App\Models\Productos\ProductoCuenta;
use App\Services\EntradaMercanciaService;

class EntradasMercancia extends Component
{
    use WithPagination;

    protected string $paginationTheme = 'tailwind';

    // Cabecera
    public ?int $entrada_id = null;
    public ?int $serie_id = null;
    public ?int $socio_negocio_id = null;
    public ?int $concepto_documento_id = null;
    public string $concepto_rol = 'inventario';
    public string $fecha_contabilizacion = '';
    public ?string $observaciones = null;
    public string $moneda = 'COP';

    // Detalle
    /** @var array<int,array{producto_id?:?int,bodega_id?:?int,descripcion?:?string,cantidad?:float,precio_unitario?:float,cuenta_str?:string}> */
    public array $entradas = [];

    // UI multi-selección
    public bool $showMulti = false;
    public string $multi_buscar = '';
    public ?int $multi_bodega_id = null;
    public ?float $multi_precio = null;
    public array $multi_items = []; // [producto_id => ['cantidad'=>X,'bodega_id'=>Y,'precio'=>Z]]

    // Catálogos
    public $productos;
    public $bodegas;
    public $series;
    public $socios;
    public $conceptos;

    protected $rules = [
        'fecha_contabilizacion'      => 'required|date',
        'socio_negocio_id'           => 'nullable|integer|exists:socio_negocios,id',
        'concepto_documento_id'      => 'required|integer|exists:conceptos_documentos,id',
        'concepto_rol'               => 'required|string|in:inventario,gasto,costo,ingreso,gasto_devolucion,ingreso_devolucion',
        'serie_id'                   => 'nullable|integer|exists:series,id',
        'entradas'                   => 'required|array|min:1',
        'entradas.*.producto_id'     => 'required|integer|exists:productos,id',
        'entradas.*.bodega_id'       => 'required|integer|exists:bodegas,id',
        'entradas.*.cantidad'        => 'required|numeric|min:1',
        'entradas.*.precio_unitario' => 'required|numeric|min:0',
        'entradas.*.descripcion'     => 'nullable|string|max:255',
    ];

    protected array $messages = [
        'concepto_documento_id.required' => 'Selecciona un concepto.',
        'entradas.min'                   => 'Debes agregar al menos una línea.',
    ];

    public function mount(?int $id = null): void
    {
        $this->fecha_contabilizacion = now()->toDateString();

        // Productos: necesitamos mov_contable_segun y subcategoria_id para el resolver
      $this->productos = Producto::where('activo', 1)
    ->orderBy('nombre')
    ->take(800)
    ->get(['id','nombre','mov_contable_segun','subcategoria_id']);

        $this->bodegas = Bodega::orderBy('nombre')->get(['id','nombre']);

        $this->socios = SocioNegocio::proveedores()
            ->orderBy('razon_social')
            ->take(500)
            ->get(['id','razon_social']);

        // Conceptos (sin tocar cuenta_str en UI)
        $this->conceptos = \App\Models\Conceptos\ConceptoDocumento::with([
                'cuentas'      => fn ($q) => $q->orderBy('prioridad'),
                'cuentas.plan' => fn ($q) => $q->select('id','codigo','nombre'),
            ])
            ->where('activo', 1)
            ->where('tipo', 'entrada')
            ->orderBy('nombre')
            ->get(['id','codigo','nombre','tipo','activo']);

        // Series para ENTRADA_MERCANCIA
        $tipo = \App\Models\TiposDocumento\TipoDocumento::where('codigo','ENTRADA_MERCANCIA')->first();

        $this->series = \App\Models\Serie\Serie::with('tipo')
            ->when($tipo, fn ($q) => $q->where('tipo_documento_id', $tipo->id))
            ->activa()
            ->orderBy('nombre')
            ->get(['id','nombre','prefijo','desde','hasta','proximo','longitud','es_default','tipo_documento_id']);

        $default = $this->series->firstWhere('es_default', 1) ?? $this->series->first();
        $this->serie_id = $default?->id;

        if ($id) {
            $this->cargar($id);
        } else {
            $this->agregarFila();
        }
    }

    public function render()
    {
        return view('livewire.inventario.entradas-mercancia', [
            'productos' => $this->productos,
            'bodegas'   => $this->bodegas,
            'series'    => $this->series,
            'socios'    => $this->socios,
            'conceptos' => $this->conceptos,
        ]);
    }

    /* ===================== UI Helpers ===================== */

    public function getProximoPreviewProperty(): ?string
    {
        try {
            $s = $this->serie_id ? Serie::find($this->serie_id) : null;
            if (!$s) return null;
            $n   = max((int) $s->proximo, (int) $s->desde);
            $len = $s->longitud ?? 6;
            $num = str_pad((string) $n, $len, '0', STR_PAD_LEFT);
            return ($s->prefijo ? "{$s->prefijo}-" : '') . $num;
        } catch (\Throwable) {
            return null;
        }
    }

    public function agregarFila(): void
    {
        $this->entradas[] = [
            'producto_id'     => null,
            'bodega_id'       => null,
            'descripcion'     => null,
            'cantidad'        => 1,
            'precio_unitario' => 0.00,
            'cuenta_str'      => '',
        ];
        $this->dispatch('$refresh');
    }

    public function eliminarFila(int $i): void
    {
        if (!isset($this->entradas[$i])) return;
        array_splice($this->entradas, $i, 1);
        $this->dispatch('$refresh');
    }

    /** Sincroniza descripción y (solo) cuenta por PRODUCTO */
    public function syncEntradasDescripcion(): void
    {
        foreach ($this->entradas as $i => &$l) {
            if (!empty($l['producto_id']) && empty($l['descripcion'])) {
                $p = $this->productos->firstWhere('id', (int) $l['producto_id']);
                if ($p) $l['descripcion'] = $p->nombre;
            }
            $this->actualizarCuentaStr($i);
        }
        unset($l);
        $this->dispatch('$refresh');
    }

    public function recordarUltimos(int $i): void
    {
        // si se borra el producto, limpia la cuenta
        if (isset($this->entradas[$i]) && empty($this->entradas[$i]['producto_id'])) {
            $this->entradas[$i]['cuenta_str'] = '';
        }
        $this->dispatch('$refresh');
    }

    /** NO tocar cuenta_str cuando cambia Concepto o Rol (solo refrescar UI) */
   public function updatedConceptoDocumentoId(): void
{
    // No tocar cuenta si no hay producto seleccionado
    foreach (array_keys($this->entradas) as $i) {
        if (!($this->entradas[$i]['producto_id'] ?? null)) {
            $this->entradas[$i]['cuenta_str'] = '';
        } else {
            $this->actualizarCuentaStr((int)$i);
        }
    }
}

public function updatedConceptoRol(): void
{
    foreach (array_keys($this->entradas) as $i) {
        if (!($this->entradas[$i]['producto_id'] ?? null)) {
            $this->entradas[$i]['cuenta_str'] = '';
        } else {
            $this->actualizarCuentaStr((int)$i);
        }
    }
}

    /** Llena cuenta_str SOLO por producto (ARTICULO/SUBCATEGORIA); sin fallback al concepto en UI */
   private function actualizarCuentaStr(int $i): void
{
    if (!isset($this->entradas[$i])) return;

    $this->entradas[$i]['cuenta_str'] = '';

    $pid = (int)($this->entradas[$i]['producto_id'] ?? 0);
    if ($pid <= 0) {
        // Sin producto: jamás rellenes por concepto
        return;
    }

    // Buscar el producto en el catálogo ya cargado
    $p = $this->productos->firstWhere('id', $pid);

    // 1) ARTICULO -> cuenta en producto_cuentas (tipo inventario)
    if ($p && strtoupper((string)$p->mov_contable_segun) === 'ARTICULO') {
        $pc = ProductoCuenta::query()
            ->with('cuentaPUC:id,codigo,nombre')
            ->where('producto_id', $pid)
            ->where('tipo_id', (int)config('conta.tipo_inventario_id', 1))
            ->first();

        if ($pc && $pc->cuentaPUC) {
            $this->entradas[$i]['cuenta_str'] = "{$pc->cuentaPUC->codigo} — {$pc->cuentaPUC->nombre}";
            return;
        }
    }

    // 2) SUBCATEGORIA -> cuenta en subcategoria_cuentas (tipo inventario)
    if ($p && strtoupper((string)$p->mov_contable_segun) === 'SUBCATEGORIA' && $p->subcategoria_id) {
        $sc = SubcategoriaCuenta::query()
            ->with('cuentaPUC:id,codigo,nombre')
            ->where('subcategoria_id', (int)$p->subcategoria_id)
            ->where('tipo_id', (int)config('conta.tipo_inventario_id', 1))
            ->first();

        if ($sc && $sc->cuentaPUC) {
            $this->entradas[$i]['cuenta_str'] = "{$sc->cuentaPUC->codigo} — {$sc->cuentaPUC->nombre}";
            return;
        }
    }

    // 3) Fallback: CONCEPTO (primera por rol; si no, por prioridad)
    if ($this->concepto_documento_id) {
        $c = $this->conceptos->firstWhere('id', (int)$this->concepto_documento_id);
        if ($c) {
            $match = collect($c->cuentas ?? [])->firstWhere('rol', $this->concepto_rol)
                  ?? collect($c->cuentas ?? [])->sortBy('prioridad')->first();

            if ($match?->plan_cuenta_id) {
                $puc = PlanCuentas::find($match->plan_cuenta_id);
                if ($puc) {
                    $this->entradas[$i]['cuenta_str'] = "{$puc->codigo} — {$puc->nombre}";
                }
            }
        }
    }
}


    /* ===================== Persistencia ===================== */

    private function payload(): array
    {
        return [
            'id'                    => $this->entrada_id,
            'serie_id'              => $this->serie_id,
            'fecha_contabilizacion' => $this->fecha_contabilizacion,
            'socio_negocio_id'      => $this->socio_negocio_id,
            'concepto_documento_id' => $this->concepto_documento_id,
            'observaciones'         => $this->observaciones,
            'moneda'                => $this->moneda,
            'lineas'                => array_map(function ($l) {
                return [
                    'producto_id'     => $l['producto_id'] ?? null,
                    'bodega_id'       => $l['bodega_id'] ?? null,
                    'descripcion'     => $l['descripcion'] ?? null,
                    'cantidad'        => (float)($l['cantidad'] ?? 0),
                    'precio_unitario' => (float)($l['precio_unitario'] ?? 0),
                ];
            }, $this->entradas),
        ];
    }

    private function validarConToast(): bool
    {
        try {
            $this->validate($this->rules);
            return true;
        } catch (ValidationException $e) {
            $first = collect($e->validator->errors()->all())->first() ?: 'Revisa los campos obligatorios.';
            PendingToast::create()->error()->message($first)->duration(9000);
            return false;
        }
    }

    public function crearEntrada(): void
    {
        try {
            if (!$this->validarConToast()) return;
            $e = EntradaMercanciaService::guardarBorrador($this->payload());
            $this->entrada_id = $e->id;

            PendingToast::create()->success()->message('Entrada guardada en borrador.')->duration(5000);
            $this->dispatch('refrescar-lista-entradas');
        } catch (\Throwable $e) {
            Log::error('ENTRADA::guardar error', ['msg' => $e->getMessage()]);
            PendingToast::create()->error()->message(config('app.debug') ? $e->getMessage() : 'No se pudo guardar.')->duration(9000);
        }
    }

    public function emitirEntrada(): void
    {
        try {
            if (!$this->entrada_id) {
                $tmp = EntradaMercanciaService::guardarBorrador($this->payload());
                $this->entrada_id = $tmp->id;
            }

            $e = EntradaMercancia::with('detalles')->findOrFail($this->entrada_id);
            // ⇨ sin pasar $this->concepto_rol; la contrapartida se toma del concepto
            EntradaMercanciaService::emitir($e);

            PendingToast::create()->success()->message('Entrada emitida correctamente.')->duration(6000);
            $this->resetFormulario();
            $this->dispatch('refrescar-lista-entradas');
        } catch (\Throwable $ex) {
            Log::error('ENTRADA::emitir error', ['msg' => $ex->getMessage()]);
            PendingToast::create()->error()->message($ex->getMessage())->duration(12000);
        }
    }

    public function cancelarEntrada(): void
    {
        $this->resetFormulario();
        PendingToast::create()->info()->message('Formulario reiniciado.')->duration(4000);
    }

    private function resetFormulario(): void
    {
        $this->entrada_id = null;
        $this->socio_negocio_id = null;
        $this->concepto_documento_id = null;
        $this->concepto_rol = 'inventario';
        $this->fecha_contabilizacion = now()->toDateString();
        $this->observaciones = null;
        $this->moneda = 'COP';
        $this->entradas = [];
        $this->agregarFila();
        $this->dispatch('$refresh');
    }

    private function cargar(int $id): void
    {
        $e = EntradaMercancia::with('detalles')->findOrFail($id);
        $this->entrada_id = $e->id;
        $this->serie_id = $e->serie_id;
        $this->socio_negocio_id = $e->socio_negocio_id;
        $this->concepto_documento_id = $e->concepto_documento_id;
        $this->fecha_contabilizacion = (string) ($e->fecha_contabilizacion ?? $e->fecha ?? now()->toDateString());
        $this->observaciones = $e->observaciones;
        $this->moneda = $e->moneda ?? 'COP';

        $this->entradas = $e->detalles->map(fn ($d) => [
            'producto_id'     => $d->producto_id,
            'bodega_id'       => $d->bodega_id,
            'descripcion'     => $d->descripcion,
            'cantidad'        => (float) $d->cantidad,
            'precio_unitario' => (float) $d->precio_unitario,
            'cuenta_str'      => '',
        ])->toArray();

        foreach (array_keys($this->entradas) as $i) $this->actualizarCuentaStr((int) $i);

        $this->dispatch('$refresh');
    }

    /* ============= Selección múltiple ============= */

    public function abrirMulti(): void { $this->showMulti = true; }
    public function cerrarMulti(): void
    {
        $this->showMulti = false;
        $this->multi_items = [];
        $this->multi_buscar = '';
        $this->multi_bodega_id = null;
        $this->multi_precio = null;
    }
    public function toggleMultiProducto(int $productoId): void
    {
        if (isset($this->multi_items[$productoId])) unset($this->multi_items[$productoId]);
        else $this->multi_items[$productoId] = ['cantidad'=>1,'bodega_id'=>null,'precio'=>0.0];
        $this->dispatch('$refresh');
    }
    public function incCantidadMulti(int $productoId): void
    {
        if (!isset($this->multi_items[$productoId])) return;
        $this->multi_items[$productoId]['cantidad'] = max(1, (int)$this->multi_items[$productoId]['cantidad'] + 1);
        $this->dispatch('$refresh');
    }
    public function decCantidadMulti(int $productoId): void
    {
        if (!isset($this->multi_items[$productoId])) return;
        $this->multi_items[$productoId]['cantidad'] = max(1, (int)$this->multi_items[$productoId]['cantidad'] - 1);
        $this->dispatch('$refresh');
    }
    public function aplicarPorLote(string $campo): void
    {
        foreach ($this->multi_items as $pid => &$cfg) {
            if ($campo === 'bodega_id' && $this->multi_bodega_id) $cfg['bodega_id'] = (int)$this->multi_bodega_id;
            if ($campo === 'precio' && is_numeric($this->multi_precio)) $cfg['precio'] = (float)$this->multi_precio;
        }
        unset($cfg);
        $this->dispatch('$refresh');
    }
    public function agregarSeleccionados(): void
    {
        foreach ($this->multi_items as $pid => $cfg) {
            $p = $this->productos->firstWhere('id', (int)$pid);
            $this->entradas[] = [
                'producto_id'     => (int)$pid,
                'bodega_id'       => $cfg['bodega_id'] ?? null,
                'descripcion'     => $p?->nombre,
                'cantidad'        => max(1, (int)($cfg['cantidad'] ?? 1)),
                'precio_unitario' => max(0.0, (float)($cfg['precio'] ?? 0)),
                'cuenta_str'      => '',
            ];
        }
        foreach (array_keys($this->entradas) as $i) $this->actualizarCuentaStr((int)$i);
        $this->cerrarMulti();
        $this->dispatch('$refresh');
    }
}
