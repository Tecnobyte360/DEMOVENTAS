<?php

namespace App\Livewire\Inventario;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Inventario\EntradaMercancia;
use App\Models\Inventario\EntradaDetalle;
use App\Models\Productos\Producto;
use App\Models\SocioNegocio\SocioNegocio;
use App\Models\bodegas;
use Illuminate\Support\Facades\DB;
use Masmerise\Toaster\PendingToast;
use Carbon\Carbon;

class EntradasMercancia extends Component
{
    use WithPagination;

    protected $paginationTheme = 'tailwind';

    public $productos, $bodegas, $socios;

    // Encabezado
    public $fecha_contabilizacion, $socio_negocio_id, $lista_precio, $observaciones;

    // Detalle
    public $entradas = []; // filas: producto_id, producto_nombre, descripcion, cantidad, bodega_id, precio_unitario

    // UX (recuerda últimos valores)
    public $ultimo_bodega_id = null;
    public $ultimo_precio = null;

    // Modal detalle (si lo usas)
    public $detalleEntrada;
    public $isLoading = false;
    public $mostrarDetalle = false;

    // ========= Filtros =========
    public $filtro_desde = null; // YYYY-MM-DD (usar wire:model.defer)
    public $filtro_hasta = null; // YYYY-MM-DD (usar wire:model.defer)

    // ========= Fila expandible (detalle inline) =========
    public $filaAbiertaId = null;
    /** @var array<int, \Illuminate\Support\Collection> */
    public $detallesPorEntrada = [];

    // ========= Selección múltiple =========
    public $showMulti = false;
    public $multi_buscar = '';
    /** @var array [producto_id => ['cantidad'=>1,'precio'=>null,'bodega_id'=>null]] */
    public $multi_items = [];
    public $multi_bodega_id = null;
    public $multi_precio = null;

    public function mount()
    {
        $this->productos = Producto::where('activo', true)->orderBy('nombre')->get();
        $this->bodegas   = bodegas::where('activo', true)->orderBy('nombre')->get();
        $this->socios    = SocioNegocio::orderBy('razon_social')->get();

        // Fila inicial del detalle
        $this->entradas = [[
            'producto_id'     => null,
            'producto_nombre' => '',
            'descripcion'     => '',
            'cantidad'        => 1,
            'bodega_id'       => null,
            'precio_unitario' => null,
        ]];
    }

    public function render()
    {
        // Normaliza rango (si el usuario invierte fechas, se corrige)
        $desde = $this->filtro_desde ? Carbon::parse($this->filtro_desde)->startOfDay() : null;
        $hasta = $this->filtro_hasta ? Carbon::parse($this->filtro_hasta)->endOfDay()   : null;

        if ($desde && $hasta && $hasta->lt($desde)) {
            [$desde, $hasta] = [$hasta->copy()->startOfDay(), $desde->copy()->endOfDay()];
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
        ]);
    }

    /* ========= Acciones Filtros ========= */

    // Llamado por el botón "Buscar"
    public function buscar(): void
    {
        // Aplica los valores defer y vuelve a la página 1
        $this->resetPage();
        $this->cerrarFila();
    }

    // Llamado por el botón "Limpiar"
   public function limpiarFiltros()
{
    $this->filtro_desde = null;
    $this->filtro_hasta = null;
    $this->filtroAplicado = false;
    $this->resetPage();
}

    /* ========= Edición rápida ========= */

    public function agregarFila()
    {
        $this->entradas[] = [
            'producto_id'     => null,
            'producto_nombre' => '',
            'descripcion'     => '',
            'cantidad'        => 1,
            'bodega_id'       => $this->ultimo_bodega_id,
            'precio_unitario' => $this->ultimo_precio,
        ];
    }

    public function eliminarFila($index)
    {
        if (!isset($this->entradas[$index])) return;
        unset($this->entradas[$index]);
        $this->entradas = array_values($this->entradas);
    }

    public function actualizarProductoDesdeNombre($index)
    {
        $nombre = $this->entradas[$index]['producto_nombre'] ?? null;

        if (!$nombre) {
            $this->entradas[$index]['producto_id'] = null;
            $this->entradas[$index]['descripcion'] = '';
            $this->entradas[$index]['precio_unitario'] = $this->entradas[$index]['precio_unitario'] ?? 0;
            return;
        }

        $producto = Producto::where('nombre', $nombre)->first();

        if ($producto) {
            $this->entradas[$index]['producto_id'] = $producto->id;
            $this->entradas[$index]['descripcion'] = $producto->descripcion ?? $producto->nombre;

            $ultimaEntrada = EntradaDetalle::where('producto_id', $producto->id)
                ->latest('created_at')->first();

            $this->entradas[$index]['precio_unitario'] = $ultimaEntrada
                ? $ultimaEntrada->precio_unitario
                : ($this->entradas[$index]['precio_unitario'] ?? 0);
        } else {
            $this->entradas[$index]['producto_id'] = null;
            $this->entradas[$index]['descripcion'] = '';
            $this->entradas[$index]['precio_unitario'] = 0;
        }
    }

    public function updatedEntradas()
    {
        foreach ($this->entradas as $i => $fila) {
            if (!empty($fila['producto_id'])) {
                $p = Producto::find($fila['producto_id']);
                if ($p) $this->entradas[$i]['descripcion'] = $p->descripcion ?? $p->nombre;
            }
        }
    }

    public function incrementCantidad($i)
    {
        $this->entradas[$i]['cantidad'] = max(1, (int)($this->entradas[$i]['cantidad'] ?? 0) + 1);
    }

    public function decrementCantidad($i)
    {
        $this->entradas[$i]['cantidad'] = max(1, (int)($this->entradas[$i]['cantidad'] ?? 0) - 1);
    }

    public function recordarUltimos($i)
    {
        $this->ultimo_bodega_id = $this->entradas[$i]['bodega_id'] ?? $this->ultimo_bodega_id;
        if (isset($this->entradas[$i]['precio_unitario']) && $this->entradas[$i]['precio_unitario'] !== null) {
            $this->ultimo_precio = $this->entradas[$i]['precio_unitario'];
        }
    }

    public function quickAddLinea($productoNombre, $cantidad, $bodegaId, $precio)
    {
        $productoNombre = trim((string)$productoNombre);
        $cantidad = max(1, (int)$cantidad);
        $bodegaId = (int)$bodegaId;
        $precio   = ($precio === '' || $precio === null) ? null : (float)$precio;

        if (!$productoNombre || !$cantidad || !$bodegaId) return;

        $p = Producto::where('activo', true)->where('nombre', $productoNombre)->first();

        $this->entradas[] = [
            'producto_id'     => $p->id ?? null,
            'producto_nombre' => $productoNombre,
            'descripcion'     => $p->descripcion ?? '',
            'cantidad'        => $cantidad,
            'bodega_id'       => $bodegaId,
            'precio_unitario' => $precio,
        ];

        $this->ultimo_bodega_id = $bodegaId;
        if ($precio !== null) $this->ultimo_precio = $precio;
    }

    /* ========= Selección múltiple ========= */

    public function abrirMulti()
    {
        $this->showMulti = true;
        $this->multi_buscar = '';
        $this->multi_items = [];
        $this->multi_bodega_id = $this->ultimo_bodega_id;
        $this->multi_precio    = $this->ultimo_precio;
    }

    public function cerrarMulti()
    {
        $this->showMulti = false;
    }

    public function toggleMultiProducto($productoId)
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

    public function setCantidadMulti($productoId, $cantidad)
    {
        if (!isset($this->multi_items[$productoId])) return;
        $this->multi_items[$productoId]['cantidad'] = max(1, (int)$cantidad);
    }

    public function incCantidadMulti($productoId)
    {
        if (!isset($this->multi_items[$productoId])) return;
        $this->multi_items[$productoId]['cantidad'] = max(1, ((int)$this->multi_items[$productoId]['cantidad']) + 1);
    }

    public function decCantidadMulti($productoId)
    {
        if (!isset($this->multi_items[$productoId])) return;
        $this->multi_items[$productoId]['cantidad'] = max(1, ((int)$this->multi_items[$productoId]['cantidad']) - 1);
    }

    public function aplicarPorLote($campo)
    {
        if (!in_array($campo, ['bodega_id', 'precio'])) return;

        foreach ($this->multi_items as $id => $data) {
            if ($campo === 'bodega_id') {
                $this->multi_items[$id]['bodega_id'] = $this->multi_bodega_id;
            } else {
                $this->multi_items[$id]['precio'] = $this->multi_precio;
            }
        }
    }

    public function agregarSeleccionados()
    {
        if (empty($this->multi_items)) return;

        foreach ($this->multi_items as $productoId => $data) {
            $p = $this->productos->firstWhere('id', (int)$productoId);
            if (!$p) continue;

            $bodega = $data['bodega_id'] ?: $this->ultimo_bodega_id;
            $precio = $data['precio'];

            $this->entradas[] = [
                'producto_id'     => $p->id,
                'producto_nombre' => $p->nombre,
                'descripcion'     => $p->descripcion ?? '',
                'cantidad'        => max(1, (int)$data['cantidad']),
                'bodega_id'       => $bodega,
                'precio_unitario' => $precio === null ? null : (float)$precio,
            ];

            if ($bodega) $this->ultimo_bodega_id = $bodega;
            if ($precio !== null) $this->ultimo_precio = $precio;
        }

        $this->cerrarMulti();
        PendingToast::create()->success()->message('Productos añadidos al detalle.')->duration(3000);
    }

    /* ========= Guardado ========= */

    public function crearEntrada()
    {
        $this->validate([
            'fecha_contabilizacion'        => 'required|date',
            'socio_negocio_id'             => 'required|exists:socio_negocios,id',
            'entradas'                     => 'required|array|min:1',
            'entradas.*.producto_id'       => 'required|exists:productos,id',
            'entradas.*.bodega_id'         => 'required|exists:bodegas,id',
            'entradas.*.cantidad'          => 'required|numeric|min:1',
            'entradas.*.precio_unitario'   => 'nullable|numeric|min:0',
        ], [
            'socio_negocio_id.required' => 'Seleccione un socio.',
        ]);

        DB::beginTransaction();
        $this->isLoading = true;

        try {
            $entrada = EntradaMercancia::create([
                'socio_negocio_id'      => $this->socio_negocio_id,
                'fecha_contabilizacion' => $this->fecha_contabilizacion,
                'lista_precio'          => $this->lista_precio,
                'observaciones'         => $this->observaciones,
            ]);

            foreach ($this->entradas as $prod) {
                EntradaDetalle::create([
                    'entrada_mercancia_id' => $entrada->id,
                    'producto_id'          => $prod['producto_id'],
                    'bodega_id'            => $prod['bodega_id'],
                    'cantidad'             => $prod['cantidad'],
                    'precio_unitario'      => $prod['precio_unitario'] ?? 0,
                ]);

                // stock por bodega (pivot)
                $producto = Producto::find($prod['producto_id']);
                if ($producto) {
                    $pivot = $producto->bodegas()->where('bodegas.id', $prod['bodega_id'])->first();

                    if ($pivot) {
                        $producto->bodegas()->updateExistingPivot($prod['bodega_id'], [
                            'stock' => DB::raw('stock + ' . (int)$prod['cantidad'])
                        ]);
                    } else {
                        $producto->bodegas()->attach($prod['bodega_id'], [
                            'stock'        => (int)$prod['cantidad'],
                            'stock_minimo' => 0,
                            'stock_maximo' => null,
                        ]);
                    }

                    $nuevoStock = $producto->bodegas()->sum('producto_bodega.stock');
                    $producto->update(['stock' => $nuevoStock]);
                }
            }

            DB::commit();

            $this->reset([
                'fecha_contabilizacion',
                'socio_negocio_id',
                'lista_precio',
                'observaciones',
                'entradas',
                'ultimo_bodega_id',
                'ultimo_precio',
            ]);

            // fila limpia
            $this->entradas = [[
                'producto_id'     => null,
                'producto_nombre' => '',
                'descripcion'     => '',
                'cantidad'        => 1,
                'bodega_id'       => null,
                'precio_unitario' => null,
            ]];

            // refrescar a la primera página para ver el registro nuevo
            $this->resetPage();

            PendingToast::create()->success()->message('Entrada registrada exitosamente.')->duration(5000);
        } catch (\Throwable $e) {
            DB::rollBack();
            PendingToast::create()->error()->message('Error al guardar: ' . $e->getMessage())->duration(8000);
        } finally {
            $this->isLoading = false;
        }
    }

    public function cancelarEntrada()
    {
        $this->reset([
            'fecha_contabilizacion',
            'socio_negocio_id',
            'lista_precio',
            'observaciones',
            'entradas',
            'ultimo_bodega_id',
            'ultimo_precio'
        ]);

        $this->entradas = [[
            'producto_id'     => null,
            'producto_nombre' => '',
            'descripcion'     => '',
            'cantidad'        => 1,
            'bodega_id'       => null,
            'precio_unitario' => null,
        ]];

        $this->cerrarFila();
    }

    /* ========= Modal detalle (si aún lo usas en otra vista) ========= */
    public function verDetalle($entradaId)
    {
        $entrada = EntradaMercancia::with('detalles.producto', 'detalles.bodega', 'socioNegocio')->find($entradaId);

        if ($entrada) {
            $this->detalleEntrada = $entrada;
            $this->mostrarDetalle = true;
            $this->dispatch('abrirModalDetalle');
        } else {
            $this->detalleEntrada = null;
            $this->mostrarDetalle = false;
            PendingToast::create()->error()->message('Entrada no encontrada.')->duration(5000);
        }
    }

    /* ========= Fila expandible (detalle inline) ========= */
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
  
public $filtroAplicado = false;
public function aplicarFiltros()
{
    $this->filtroAplicado = true;
    $this->resetPage();
}

}
