<?php

namespace App\Livewire\Inventario;

use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Masmerise\Toaster\PendingToast;

use App\Models\Bodega;
use App\Models\Conceptos\ConceptoDocumento;
use App\Models\Inventario\EntradaMercancia;
use App\Models\Inventario\EntradaDetalle;
use App\Models\Movimiento\ProductoCostoMovimiento;
use App\Models\Productos\Producto;
use App\Models\SocioNegocio\SocioNegocio;
use App\Models\Serie\Serie;
use App\Models\TiposDocumento\TipoDocumento;
use App\Services\EntradaMercanciaService;

class EntradasMercancia extends Component
{
    use WithPagination;

    /** Tailwind para la paginación */
    protected string $paginationTheme = 'tailwind';

    /** ===== Catálogos ===== */
    /** @var \Illuminate\Support\Collection<int,\App\Models\Productos\Producto> */
    public $productos;
    /** @var \Illuminate\Support\Collection<int,\App\Models\Bodega> */
    public $bodegas;
    /** @var \Illuminate\Support\Collection<int,\App\Models\SocioNegocio\SocioNegocio> */
    public $socios;
    /** @var \Illuminate\Support\Collection<int,\App\Models\Conceptos\ConceptoDocumento> */
    public $conceptos;

    /** ===== Encabezado ===== */
    public ?string $fecha_contabilizacion = null;
    public ?int    $socio_negocio_id     = null;
    public ?string $lista_precio         = null;
    public ?string $observaciones        = null;

    /** Concepto de la entrada */
    public ?int $concepto_documento_id = null;   // seleccionado en el formulario
    public string $concepto_rol = 'inventario';  // rol a usar en el pivot para escoger cuenta

    /** ===== Serie / Numeración ===== */
    public string $documento = 'ENTRADA_MERCANCIA';
    public ?Serie $serieDefault = null;
    public ?int   $serie_id = null;

    /**
     * ===== Detalle =====
     * Cada fila: producto_id, producto_nombre, descripcion, cantidad, bodega_id, precio_unitario, cuenta_id, cuenta_str
     * @var array<int, array<string,mixed>>
     */
    public array $entradas = [];

    /** UX: recordar últimos */
    public ?int    $ultimo_bodega_id = null;
    public ?float  $ultimo_precio    = null;

    /** Modal / fila expandible (listado) */
    public $detalleEntrada = null;
    public bool $isLoading = false;
    public bool $mostrarDetalle = false;

    /** ===== Filtros (listado) ===== */
    public ?string $filtro_desde = null; // YYYY-MM-DD
    public ?string $filtro_hasta = null; // YYYY-MM-DD
    public bool    $filtroAplicado = false;

    /** ===== Fila expandible (detalle inline) ===== */
    public ?int $filaAbiertaId = null;
    /** @var array<int, \Illuminate\Support\Collection> */
    public array $detallesPorEntrada = [];

    /** ===== Selección múltiple ===== */
    public bool   $showMulti = false;
    public string $multi_buscar = '';
    /** @var array<int,array{cantidad:int,precio:float|null,bodega_id:int|null}> */
    public array  $multi_items = [];
    public ?int   $multi_bodega_id = null;
    public ?float $multi_precio    = null;

    /* ==========================================
     |  Ciclo de vida
     ========================================== */
    public function mount(): void
    {
        // Catálogos
        $this->productos = Producto::with([
            'cuentaInventario:id,codigo,nombre',
            'cuentaCompra:id,codigo,nombre',
        ])->where('activo', true)
          ->orderBy('nombre')
          ->get();

        $this->bodegas = Bodega::where('activo', true)
            ->orderBy('nombre')->get();

        $this->socios = SocioNegocio::orderBy('razon_social')->get();

        // Conceptos tipo ENTRADA activos
        $this->conceptos = ConceptoDocumento::query()
            ->where('tipo', 'entrada')
            ->where('activo', true)
            ->orderBy('nombre')
            ->get(['id','codigo','nombre']);

        // Fecha/Serie por defecto
        $this->fecha_contabilizacion = now()->toDateString();

        $this->documento    = $this->detectarCodigoDocumento() ?? 'ENTRADA_MERCANCIA';
        $this->serieDefault = Serie::defaultParaCodigo($this->documento);
        $this->serie_id     = $this->serieDefault?->id;

        // Fila inicial
        $this->entradas = [[
            'producto_id'     => null,
            'producto_nombre' => '',
            'descripcion'     => '',
            'cantidad'        => 1,
            'bodega_id'       => null,
            'precio_unitario' => null,
            'cuenta_id'       => null,
            'cuenta_str'      => '',
        ]];
    }

    private function detectarCodigoDocumento(): ?string
    {
        $tipo = TipoDocumento::whereRaw('LOWER(codigo)=LOWER(?)', ['ENTRADA_MERCANCIA'])->first();
        return $tipo ? (string)$tipo->codigo : 'ENTRADA_MERCANCIA';
    }

    public function render()
    {
        // Normalizar rango
        $desde = $this->filtro_desde ? Carbon::parse($this->filtro_desde)->startOfDay() : null;
        $hasta = $this->filtro_hasta ? Carbon::parse($this->filtro_hasta)->endOfDay()   : null;
        if ($desde && $hasta && $hasta->lt($desde)) {
            [$desde, $hasta] = [$hasta->copy()->startOfDay(), $desde->copy()->endOfDay()];
        }

        // Series activas del tipo
        $series = Serie::query()
            ->with('tipo')
            ->activa()
            ->when($this->documento !== '', fn($q) =>
                $q->whereHas('tipo', fn($t) => $t->where('codigo', $this->documento))
            )
            ->orderBy('nombre')
            ->get(['id','nombre','prefijo','desde','hasta','proximo','longitud','es_default','tipo_documento_id']);

        if (!$this->serie_id) {
            $this->serie_id = $this->serieDefault?->id
                ?? optional($series->firstWhere('es_default', true))->id
                ?? optional($series->first())->id;
        }

        $entradasPaginadas = EntradaMercancia::with('socioNegocio')
            ->when($desde && $hasta, fn($q) => $q->whereBetween('fecha_contabilizacion', [$desde, $hasta]))
            ->when($desde && !$hasta, fn($q) => $q->where('fecha_contabilizacion', '>=', $desde))
            ->when(!$desde && $hasta, fn($q) => $q->where('fecha_contabilizacion', '<=', $hasta))
            ->orderByDesc('fecha_contabilizacion')
            ->orderByDesc('id')
            ->paginate(10);

        return view('livewire.inventario.entradas-mercancia', [
            'entradasMercancia' => $entradasPaginadas,
            'series'            => $series,
            'serieDefault'      => $this->serieDefault,
        ]);
    }

    /* ==========================================
     |  Validación
     ========================================== */
    protected function rules(): array
    {
        return [
            'fecha_contabilizacion'        => ['required','date'],
            'socio_negocio_id'             => ['required','exists:socio_negocios,id'],
            'concepto_documento_id'        => ['required','exists:conceptos_documentos,id'],
            'serie_id'                     => ['nullable','integer','exists:series,id'],
            'entradas'                     => ['required','array','min:1'],
            'entradas.*.producto_id'       => ['required','exists:productos,id'],
            'entradas.*.bodega_id'         => ['required','exists:bodegas,id'],
            'entradas.*.cantidad'          => ['required','numeric','min:1'],
            'entradas.*.precio_unitario'   => ['nullable','numeric','min:0'],
        ];
    }

    protected array $messages = [
        'socio_negocio_id.required'      => 'Seleccione un socio.',
        'concepto_documento_id.required' => 'Seleccione un concepto.',
        'entradas.required'              => 'Agregue al menos una línea.',
    ];

    /* ==========================================
     |  Helpers de cuentas
     ========================================== */
    private function resolverCuentaDeProducto(?Producto $p): array
    {
        if (!$p) return ['cuenta_id' => null, 'cuenta_str' => ''];

        if ($p->relationLoaded('cuentaInventario') ? $p->cuentaInventario : $p->cuentaInventario()->first()) {
            $c = $p->cuentaInventario;
            return ['cuenta_id' => $c->id ?? null, 'cuenta_str' => trim(($c->codigo ?? '').' - '.($c->nombre ?? ''))];
        }

        if ($p->relationLoaded('cuentaCompra') ? $p->cuentaCompra : $p->cuentaCompra()->first()) {
            $c = $p->cuentaCompra;
            return ['cuenta_id' => $c->id ?? null, 'cuenta_str' => trim(($c->codigo ?? '').' - '.($c->nombre ?? ''))];
        }

        if (!empty($p->cuenta_inventario)) return ['cuenta_id' => null, 'cuenta_str' => (string)$p->cuenta_inventario];
        if (!empty($p->cuenta_compra))     return ['cuenta_id' => null, 'cuenta_str' => (string)$p->cuenta_compra];

        return ['cuenta_id' => null, 'cuenta_str' => ''];
    }

    /** Cuenta desde el concepto según rol (usa pivot concepto_documento_cuenta) */
    private function resolverCuentaDesdeConcepto(?int $conceptoId, string $rol = 'inventario'): array
    {
        if (!$conceptoId) {
            return ['cuenta_id' => null, 'cuenta_str' => ''];
        }

        $concepto = ConceptoDocumento::with([
            'cuentas' => function ($q) use ($rol) {
                $q->wherePivot('rol', $rol)
                  ->orderByPivot('prioridad', 'asc');
            }
        ])->find($conceptoId);

        if (!$concepto) {
            return ['cuenta_id' => null, 'cuenta_str' => ''];
        }

        $cuenta = $concepto->cuentas->first();  // por prioridad
        if (!$cuenta) {
            return ['cuenta_id' => null, 'cuenta_str' => ''];
        }

        $label = trim(($cuenta->codigo ?? '').' — '.($cuenta->nombre ?? ''));
        return ['cuenta_id' => (int)$cuenta->id, 'cuenta_str' => $label];
    }

    /* ==========================================
     |  Reacción al cambio de concepto
     ========================================== */
    public function updatedConceptoDocumentoId(): void
    {
        // Aplica solo a líneas SIN cuenta (no forzar)
        $this->aplicarConceptoEnLineas(false, $this->concepto_rol);
    }

    /**
     * Aplica la cuenta del concepto a las líneas (rol configurable).
     */
    public function aplicarConceptoEnLineas(bool $forzar = false, string $rol = 'inventario'): void
    {
        if (!$this->concepto_documento_id) return;

        $cuenta = $this->resolverCuentaDesdeConcepto($this->concepto_documento_id, $rol);

        foreach ($this->entradas as $i => $fila) {
            $tiene = !empty($fila['cuenta_id']) || !empty($fila['cuenta_str']);
            if ($forzar || !$tiene) {
                $this->entradas[$i]['cuenta_id']  = $cuenta['cuenta_id'];
                $this->entradas[$i]['cuenta_str'] = $cuenta['cuenta_str'];
            }
        }
    }

    /* ==========================================
     |  Edición rápida del detalle
     ========================================== */
    public function agregarFila(): void
    {
        // La nueva fila hereda la cuenta del concepto si existe
        $cuenta = $this->resolverCuentaDesdeConcepto($this->concepto_documento_id, $this->concepto_rol);

        $this->entradas[] = [
            'producto_id'     => null,
            'producto_nombre' => '',
            'descripcion'     => '',
            'cantidad'        => 1,
            'bodega_id'       => $this->ultimo_bodega_id,
            'precio_unitario' => $this->ultimo_precio,
            'cuenta_id'       => $cuenta['cuenta_id'],
            'cuenta_str'      => $cuenta['cuenta_str'],
        ];
    }

    public function eliminarFila(int $index): void
    {
        if (!isset($this->entradas[$index])) return;
        unset($this->entradas[$index]);
        $this->entradas = array_values($this->entradas);
    }

    public function actualizarProductoDesdeNombre(int $index): void
    {
        $nombre = $this->entradas[$index]['producto_nombre'] ?? null;

        if (!$nombre) {
            $this->entradas[$index]['producto_id']     = null;
            $this->entradas[$index]['descripcion']     = '';
            $this->entradas[$index]['precio_unitario'] = $this->entradas[$index]['precio_unitario'] ?? 0;
            $this->entradas[$index]['cuenta_id']       = null;
            $this->entradas[$index]['cuenta_str']      = '';
            return;
        }

        $producto = $this->productos->firstWhere('nombre', $nombre);

        if ($producto) {
            $this->entradas[$index]['producto_id'] = $producto->id;
            $this->entradas[$index]['descripcion'] = $producto->descripcion ?? $producto->nombre;

            // Proponer costo promedio (prioridad) por bodega si ya está elegida
            $bid   = $this->entradas[$index]['bodega_id'] ?? null;
            $costo = $this->ultimoCostoPromedio($producto->id, $bid ? (int)$bid : null);

            if ($costo !== null) {
                $this->entradas[$index]['precio_unitario'] = $costo;
            } else {
                // Fallback: último precio usado en una entrada anterior
                $ultimaEntrada = EntradaDetalle::where('producto_id', $producto->id)
                    ->latest('created_at')->first();

                $this->entradas[$index]['precio_unitario'] = $ultimaEntrada
                    ? (float)$ultimaEntrada->precio_unitario
                    : (float)($this->entradas[$index]['precio_unitario'] ?? 0);
            }

            // 1) Cuenta desde producto
            $cuenta = $this->resolverCuentaDeProducto($producto);

            // 2) Si no hay en producto → cuenta desde concepto (rol)
            if (!$cuenta['cuenta_id'] && $this->concepto_documento_id) {
                $cuenta = $this->resolverCuentaDesdeConcepto($this->concepto_documento_id, $this->concepto_rol);
            }

            $this->entradas[$index]['cuenta_id']  = $cuenta['cuenta_id'];
            $this->entradas[$index]['cuenta_str'] = $cuenta['cuenta_str'];
        } else {
            $this->entradas[$index]['producto_id']     = null;
            $this->entradas[$index]['descripcion']     = '';
            $this->entradas[$index]['precio_unitario'] = 0;
            $this->entradas[$index]['cuenta_id']       = null;
            $this->entradas[$index]['cuenta_str']      = '';
        }
    }

    public function updatedEntradas($value, $name = null): void
    {
        if ($name) {
            if (preg_match('/^entradas\.(\d+)\.(producto_id|bodega_id)$/', $name, $m)) {
                $index = (int) $m[1];
                // Al cambiar producto o bodega, propone costo promedio
                $this->setPrecioDesdeCostoPromedio($index);
            }
        }
        $this->syncEntradasDescripcion();
    }

    public function syncEntradasDescripcion(): void
    {
        foreach ($this->entradas as $i => $fila) {
            if (!empty($fila['producto_id'])) {
                $p = $this->productos->firstWhere('id', (int)$fila['producto_id'])
                   ?? Producto::find($fila['producto_id']);

                if ($p) {
                    $this->entradas[$i]['descripcion'] = $p->descripcion ?? $p->nombre;

                    // Si aún no tiene precio o está en 0, propone costo promedio
                    if (empty($this->entradas[$i]['precio_unitario']) || (float)$this->entradas[$i]['precio_unitario'] <= 0) {
                        $bid   = $this->entradas[$i]['bodega_id'] ?? null;
                        $costo = $this->ultimoCostoPromedio($p->id, $bid ? (int)$bid : null);
                        if ($costo !== null) {
                            $this->entradas[$i]['precio_unitario'] = $costo;
                        }
                    }

                    // 1) Producto
                    $cuenta = $this->resolverCuentaDeProducto($p);

                    // 2) Si no hay → Concepto (rol)
                    if (!$cuenta['cuenta_id'] && $this->concepto_documento_id) {
                        $cuenta = $this->resolverCuentaDesdeConcepto($this->concepto_documento_id, $this->concepto_rol);
                    }

                    $this->entradas[$i]['cuenta_id']  = $cuenta['cuenta_id'];
                    $this->entradas[$i]['cuenta_str'] = $cuenta['cuenta_str'];
                }
            } else {
                if ($this->concepto_documento_id && empty($fila['cuenta_id']) && empty($fila['cuenta_str'])) {
                    $cuenta = $this->resolverCuentaDesdeConcepto($this->concepto_documento_id, $this->concepto_rol);
                    $this->entradas[$i]['cuenta_id']  = $cuenta['cuenta_id'];
                    $this->entradas[$i]['cuenta_str'] = $cuenta['cuenta_str'];
                }
            }
        }
    }

    public function incrementCantidad(int $i): void
    {
        $this->entradas[$i]['cantidad'] = max(1, (int)($this->entradas[$i]['cantidad'] ?? 0) + 1);
    }

    public function decrementCantidad(int $i): void
    {
        $this->entradas[$i]['cantidad'] = max(1, (int)($this->entradas[$i]['cantidad'] ?? 0) - 1);
    }

    public function recordarUltimos(int $i): void
    {
        $this->ultimo_bodega_id = $this->entradas[$i]['bodega_id'] ?? $this->ultimo_bodega_id;
        if (isset($this->entradas[$i]['precio_unitario']) && $this->entradas[$i]['precio_unitario'] !== null) {
            $this->ultimo_precio = (float) $this->entradas[$i]['precio_unitario'];
        }
    }

    public function quickAddLinea(string $productoNombre, int $cantidad, int $bodegaId, $precio): void
    {
        $productoNombre = trim($productoNombre);
        $cantidad = max(1, (int)$cantidad);
        $bodegaId = (int)$bodegaId;
        $precio   = ($precio === '' || $precio === null) ? null : (float)$precio;

        if (!$productoNombre || !$cantidad || !$bodegaId) return;

        $p = $this->productos->firstWhere('nombre', $productoNombre)
           ?? Producto::where('activo', true)->where('nombre', $productoNombre)->first();

        // Si no viene precio, usar costo promedio por bodega
        if ($precio === null || $precio <= 0) {
            $precio = $this->ultimoCostoPromedio($p?->id ?? 0, $bodegaId) ?? null;
        }

        $cuenta = $this->resolverCuentaDeProducto($p);
        if (!$cuenta['cuenta_id'] && $this->concepto_documento_id) {
            $cuenta = $this->resolverCuentaDesdeConcepto($this->concepto_documento_id, $this->concepto_rol);
        }

        $this->entradas[] = [
            'producto_id'     => $p->id ?? null,
            'producto_nombre' => $productoNombre,
            'descripcion'     => $p->descripcion ?? '',
            'cantidad'        => $cantidad,
            'bodega_id'       => $bodegaId,
            'precio_unitario' => $precio,
            'cuenta_id'       => $cuenta['cuenta_id'],
            'cuenta_str'      => $cuenta['cuenta_str'],
        ];

        $this->ultimo_bodega_id = $bodegaId;
        if ($precio !== null) $this->ultimo_precio = $precio;
    }

    public function toggleMultiProducto(int $productoId): void
    {
        if (isset($this->multi_items[$productoId])) {
            unset($this->multi_items[$productoId]);
        } else {
            $this->multi_items[$productoId] = [
                'cantidad'   => 1,
                'precio'     => $this->multi_precio,
                'bodega_id'  => $this->multi_bodega_id,
            ];
        }
    }

    public function setCantidadMulti(int $productoId, int $cantidad): void
    {
        if (!isset($this->multi_items[$productoId])) return;
        $this->multi_items[$productoId]['cantidad'] = max(1, (int)$cantidad);
    }

    public function incCantidadMulti(int $productoId): void
    {
        if (!isset($this->multi_items[$productoId])) return;
        $this->multi_items[$productoId]['cantidad'] =
            max(1, ((int)$this->multi_items[$productoId]['cantidad']) + 1);
    }

    public function decCantidadMulti(int $productoId): void
    {
        if (!isset($this->multi_items[$productoId])) return;
        $this->multi_items[$productoId]['cantidad'] =
            max(1, ((int)$this->multi_items[$productoId]['cantidad']) - 1);
    }

    public function aplicarPorLote(string $campo): void
    {
        if (!in_array($campo, ['bodega_id', 'precio'], true)) return;

        foreach ($this->multi_items as $id => $data) {
            if ($campo === 'bodega_id') {
                $this->multi_items[$id]['bodega_id'] = $this->multi_bodega_id;
            } else {
                $this->multi_items[$id]['precio'] = $this->multi_precio;
            }
        }
    }

    public function agregarSeleccionados(): void
    {
        if (empty($this->multi_items)) return;

        foreach ($this->multi_items as $productoId => $data) {
            $p = $this->productos->firstWhere('id', (int)$productoId) ?? Producto::find($productoId);
            if (!$p) continue;

            $bodega = $data['bodega_id'] ?: $this->ultimo_bodega_id;
            $precio = $data['precio'];

            // Si no hay precio en el lote, usar costo promedio
            if ($precio === null || (float)$precio <= 0) {
                $precio = $this->ultimoCostoPromedio($p->id, $bodega ? (int)$bodega : null);
            }

            $cuenta = $this->resolverCuentaDeProducto($p);
            if (!$cuenta['cuenta_id'] && $this->concepto_documento_id) {
                $cuenta = $this->resolverCuentaDesdeConcepto($this->concepto_documento_id, $this->concepto_rol);
            }

            $this->entradas[] = [
                'producto_id'     => $p->id,
                'producto_nombre' => $p->nombre,
                'descripcion'     => $p->descripcion ?? '',
                'cantidad'        => max(1, (int)$data['cantidad']),
                'bodega_id'       => $bodega,
                'precio_unitario' => $precio === null ? null : (float)$precio,
                'cuenta_id'       => $cuenta['cuenta_id'],
                'cuenta_str'      => $cuenta['cuenta_str'],
            ];

            if ($bodega) $this->ultimo_bodega_id = $bodega;
            if ($precio !== null) $this->ultimo_precio = (float)$precio;
        }

        $this->cerrarMulti();
        PendingToast::create()->success()->message('Productos añadidos al detalle.')->duration(3000);
    }

    /* ==========================================
     |  Guardado / Flujo
     ========================================== */
    /** Guarda como borrador (no mueve stock ni kardex) */
    public function crearEntrada(): void
    {
        $this->validate();

        DB::beginTransaction();
        $this->isLoading = true;

        try {
            $entrada = EntradaMercancia::create([
                'socio_negocio_id'      => $this->socio_negocio_id,
                'concepto_documento_id' => $this->concepto_documento_id,
                'fecha_contabilizacion' => $this->fecha_contabilizacion,
                'lista_precio'          => $this->lista_precio,
                'observaciones'         => $this->observaciones,
                'estado'                => 'borrador',
            ]);

            foreach ($this->entradas as $prod) {
                EntradaDetalle::create([
                    'entrada_mercancia_id' => $entrada->id,
                    'producto_id'          => $prod['producto_id'],
                    'bodega_id'            => $prod['bodega_id'],
                    'cantidad'             => $prod['cantidad'],
                    'precio_unitario'      => (float)($prod['precio_unitario'] ?? 0),
                ]);
            }

            DB::commit();

            $this->cancelarEntrada();
            $this->resetPage();
            PendingToast::create()->success()->message('Entrada guardada como borrador.')->duration(5000);
        } catch (\Throwable $e) {
            DB::rollBack();
            PendingToast::create()->error()->message('Error al guardar: '.$e->getMessage())->duration(8000);
        } finally {
            $this->isLoading = false;
        }
    }

    /** Emite la entrada (asigna serie/número, mueve stock, costo promedio y kardex) */
    public function emitirEntrada(int $entradaId): void
    {
        try {
            $e = EntradaMercancia::with('detalles.producto.bodegas')->findOrFail($entradaId);

            if ($e->estado === 'emitida') {
                PendingToast::create()->info()->message('Esta entrada ya está emitida.')->duration(4000);
                return;
            }

            // Serie default del documento
            $this->documento    = $this->detectarCodigoDocumento() ?? 'ENTRADA_MERCANCIA';
            $this->serieDefault = Serie::defaultParaCodigo($this->documento);

            if (!$this->serieDefault) {
                throw new \RuntimeException('No hay serie default activa para ENTRADA_MERCANCIA.');
            }

            DB::transaction(function () use ($e) {
                // Numeración
                $n = $this->serieDefault->tomarConsecutivo();

                $e->update([
                    'serie_id' => $this->serieDefault->id,
                    'prefijo'  => $this->serieDefault->prefijo,
                    'numero'   => $n,
                    'estado'   => 'emitida',
                ]);

                // Movimiento de inventario y kardex
                EntradaMercanciaService::emitir($e);

                // Recalcular stock global del producto (opcional)
                foreach ($e->detalles as $d) {
                    $total = $d->producto->bodegas()->sum('producto_bodega.stock');
                    $d->producto->update(['stock' => $total]);
                }
            }, 3);

            PendingToast::create()->success()->message('Entrada emitida y Kardex actualizado.')->duration(5000);
            $this->resetPage();
        } catch (\Throwable $e) {
            PendingToast::create()->error()->message('No se pudo emitir: '.$e->getMessage())->duration(9000);
        }
    }

    /** Reversa una entrada emitida */
    public function revertirEntrada(int $entradaId): void
    {
        try {
            $e = EntradaMercancia::with('detalles.producto.bodegas')->findOrFail($entradaId);

            if ($e->estado !== 'emitida') {
                PendingToast::create()->info()->message('Solo puedes revertir entradas emitidas.')->duration(5000);
                return;
            }

            EntradaMercanciaService::revertir($e);

            foreach ($e->detalles as $d) {
                $total = $d->producto->bodegas()->sum('producto_bodega.stock');
                $d->producto->update(['stock' => $total]);
            }

            PendingToast::create()->success()->message('Entrada revertida correctamente.')->duration(5000);
            $this->resetPage();
        } catch (\Throwable $e) {
            PendingToast::create()->error()->message('No se pudo revertir: '.$e->getMessage())->duration(9000);
        }
    }

    /** Limpia el formulario */
    public function cancelarEntrada(): void
    {
        $this->reset([
            'fecha_contabilizacion',
            'socio_negocio_id',
            'lista_precio',
            'observaciones',
            'entradas',
            'ultimo_bodega_id',
            'ultimo_precio',
            'serie_id',
            'concepto_documento_id',
        ]);

        $this->fecha_contabilizacion = now()->toDateString();

        // Serie default nuevamente
        $this->documento    = $this->detectarCodigoDocumento() ?? 'ENTRADA_MERCANCIA';
        $this->serieDefault = Serie::defaultParaCodigo($this->documento);
        $this->serie_id     = $this->serieDefault?->id;

        // Primera fila vacía (sin cuenta)
        $this->entradas = [[
            'producto_id'     => null,
            'producto_nombre' => '',
            'descripcion'     => '',
            'cantidad'        => 1,
            'bodega_id'       => null,
            'precio_unitario' => null,
            'cuenta_id'       => null,
            'cuenta_str'      => '',
        ]];

        $this->cerrarFila();
    }

    /* ==========================================
     |  Fila expandible (detalle inline)
     ========================================== */
    public function cerrarFila(): void
    {
        $this->filaAbiertaId = null;
    }

    public function toggleDetalleFila(int $entradaId): void
    {
        if ($this->filaAbiertaId === $entradaId) {
            $this->filaAbiertaId = null;
            return;
        }

        $this->filaAbiertaId = $entradaId;

        if (!isset($this->detallesPorEntrada[$entradaId])) {
            $this->detallesPorEntrada[$entradaId] =
                EntradaDetalle::with(['producto', 'bodega'])
                    ->where('entrada_mercancia_id', $entradaId)
                    ->get();
        }
    }

    /* ==========================================
     |  UI Helpers
     ========================================== */
    public function getProximoPreviewProperty(): ?string
    {
        try {
            $s = $this->serie_id ? Serie::find($this->serie_id) : $this->serieDefault;
            if (!$s) return null;

            $n   = max((int)$s->proximo, (int)$s->desde);
            $len = (int) ($s->longitud ?? 6);
            $num = str_pad((string)$n, $len, '0', STR_PAD_LEFT);

            return ($s->prefijo ? "{$s->prefijo}-" : '') . $num;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /* ==========================================
     |  Costos promedio
     ========================================== */

    /**
     * Devuelve el último costo_prom_nuevo para el producto (opcional por bodega).
     * Si no hay registro, retorna null.
     */
    private function ultimoCostoPromedio(int $productoId, ?int $bodegaId = null): ?float
    {
        if (!$productoId) return null;

        $q = ProductoCostoMovimiento::query()
            ->where('producto_id', $productoId)
            ->when($bodegaId, fn($qq) => $qq->where('bodega_id', $bodegaId))
            ->orderByDesc('fecha')
            ->orderByDesc('id');

        $row = $q->first(['costo_prom_nuevo']);

        return $row?->costo_prom_nuevo !== null
            ? (float) $row->costo_prom_nuevo
            : null;
    }

    /** Asigna precio_unitario desde el costo promedio para la fila $i */
    private function setPrecioDesdeCostoPromedio(int $i): void
    {
        if (!isset($this->entradas[$i])) return;

        $pid = (int)($this->entradas[$i]['producto_id'] ?? 0);
        $bid = $this->entradas[$i]['bodega_id'] ?? null;

        if (!$pid) return;

        $costo = $this->ultimoCostoPromedio($pid, $bid ? (int)$bid : null);

        // Si existe costo, lo asignamos (si prefieres forzar siempre, quita la condición)
        if ($costo !== null && ((float)($this->entradas[$i]['precio_unitario'] ?? 0) <= 0)) {
            $this->entradas[$i]['precio_unitario'] = $costo;
        }
    }
}
