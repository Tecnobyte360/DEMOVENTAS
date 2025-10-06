<?php

namespace App\Livewire\Productos;

use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Log;

use App\Models\bodegas;
use App\Models\Productos\Producto;
use App\Models\Categorias\Subcategoria;
use App\Models\Impuestos\Impuesto as ImpuestoModel;
use App\Models\CuentasContables\PlanCuentas;
use App\Models\Productos\ProductoCuentaTipo;
use App\Models\UnidadesMedida;
use Masmerise\Toaster\PendingToast;

class Productos extends Component
{
    use WithFileUploads;

    /** Listas */
    public $productos, $subcategorias, $bodegas, $impuestos, $unidades;

    /** Catálogos dinámicos para cuentas */
    public $tiposCuenta;
    public $cuentasPUC;

    /** Selección de cuentas por tipo_id: [tipo_id => plan_cuentas_id] */
    public array $cuentasPorTipo = [];

    /** Campos producto */
    public $nombre, $descripcion, $costo, $precio, $activo = true, $subcategoria_id;
    public ?int $impuesto_id = null;

    /** Unidad de medida + imagen */
    public ?int $unidad_medida_id = null;
    public $imagen = null; // archivo Livewire

    /** Movimiento contable según (ARTICULO | SUBCATEGORIA) */
    public string $mov_contable_segun = Producto::MOV_SEGUN_ARTICULO;

    /** Estado edición / búsqueda */
    public $producto_id, $isEdit = false, $search = '';

    /** Stocks por bodega */
    public $bodegaSeleccionada = '';
    public $stockMinimo = 0, $stockMaximo = null;
    public $stocksPorBodega = [];

    /** Stock global opcional */
    public $stockMinimoGlobal, $stockMaximoGlobal;

    /** Flags UI */
    public $mostrarBodegas = [];
    public $erroresFormulario = false;

    /** Modal de cuentas */
    public bool $showCuentasModal = false;

    public function mount()
    {
        $this->productos     = collect();
        $this->subcategorias = Subcategoria::where('activo', true)->orderBy('nombre')->get();
        $this->bodegas       = bodegas::where('activo', true)->orderBy('nombre')->get();

        $this->impuestos = ImpuestoModel::with('tipo')
            ->where('activo', true)
            ->orderBy('prioridad')->orderBy('nombre')
            ->get();

        $this->tiposCuenta = ProductoCuentaTipo::activos()
            ->orderBy('orden')->orderBy('id')
            ->get(['id','codigo','nombre','obligatorio','orden']);

        // Solo cuentas imputables de nivel 5
        $this->cuentasPUC = PlanCuentas::imputables()
            ->where('nivel', 5)
            ->ordenCodigo()
            ->get(['id','codigo','nombre']);

        // Catálogo de unidades de medida
        $this->unidades = UnidadesMedida::where('activo', true)
            ->orderBy('nombre')
            ->get(['id','nombre','simbolo','codigo']);

        // Inicializa selección vacía por tipo
        $this->cuentasPorTipo = [];
        foreach ($this->tiposCuenta as $t) {
            $this->cuentasPorTipo[$t->id] = null;
        }

        $this->mov_contable_segun = Producto::MOV_SEGUN_ARTICULO;
    }

    public function render()
    {
        $query = Producto::with([
            'subcategoria',
            'bodegas',
            'impuesto',
            'cuentas.tipo',
            'cuentas.cuentaPUC',
            'unidadMedida',
        ]);

        if ($this->search) {
            $query->where('nombre', 'like', '%' . $this->search . '%');
        }

        $this->productos = $query->get();

        // Preview en vivo para fila editada (precio/iva)
        if ($this->isEdit && $this->producto_id) {
            $this->productos = $this->productos->map(function ($p) {
                if ($p->id === (int) $this->producto_id) {
                    $p->precio = (float) ($this->precio ?? $p->precio);
                    if ($this->impuestoSeleccionado) {
                        $p->setRelation('impuesto', $this->impuestoSeleccionado);
                    }
                }
                return $p;
            });
        }

        return view('livewire.productos.productos', [
            'impuestos'   => $this->impuestos,
            'tiposCuenta' => $this->tiposCuenta,
            'cuentasPUC'  => $this->cuentasPUC,
            'unidades'    => $this->unidades,
        ]);
    }

    /** ===== Modal Cuentas ===== */
    public function abrirModalCuentas(): void
    {
        if (empty($this->cuentasPorTipo)) {
            foreach ($this->tiposCuenta as $t) $this->cuentasPorTipo[$t->id] = null;
        }
        $this->showCuentasModal = true;
    }

    public function cerrarModalCuentas(): void
    {
        $this->showCuentasModal = false;
    }

    /** Cambia ARTICULO|SUBCATEGORIA desde el modal */
    public function setMovContableSegun(string $valor): void
    {
        $valor = strtoupper($valor);
        if (!in_array($valor, [Producto::MOV_SEGUN_ARTICULO, Producto::MOV_SEGUN_SUBCATEGORIA], true)) return;

        $this->mov_contable_segun = $valor;

        if ($this->mov_contable_segun === Producto::MOV_SEGUN_SUBCATEGORIA) {
            foreach ($this->tiposCuenta as $t) {
                $this->cuentasPorTipo[$t->id] = null;
            }
        }
    }

    /** Guarda lo del modal (si aplica) */
    public function guardarModalCuentas(): void
    {
        if ($this->mov_contable_segun === Producto::MOV_SEGUN_SUBCATEGORIA) {
            $this->showCuentasModal = false;
            PendingToast::create()->success()->message('Configuración guardada (según subcategoría).')->duration(3000);
            return;
        }

        $this->validate($this->reglasCuentas());

        if ($this->isEdit && $this->producto_id) {
            $producto = Producto::findOrFail($this->producto_id);
            foreach ($this->cuentasPorTipo as $tipoId => $pucId) {
                if (!$pucId) continue;
                $producto->cuentas()->updateOrCreate(
                    ['tipo_id' => (int)$tipoId],
                    ['plan_cuentas_id' => (int)$pucId]
                );
            }
        }

        $this->showCuentasModal = false;
        PendingToast::create()->success()->message('Cuentas configuradas.')->duration(3000);
    }

    /** ===== Bodegas UI ===== */
    public function agregarBodega()
    {
        if (!$this->bodegaSeleccionada) {
            PendingToast::create()->error()->message('Selecciona una bodega primero.')->duration(4000);
            return;
        }
        $this->stocksPorBodega[$this->bodegaSeleccionada] = [
            'stock_minimo' => $this->stockMinimo,
            'stock_maximo' => $this->stockMaximo,
        ];
        $this->bodegaSeleccionada = '';
        $this->stockMinimo = 0;
        $this->stockMaximo = null;
    }

    public function eliminarBodega($id) { unset($this->stocksPorBodega[$id]); }

    /* =======================================
     * CONVERTIR IMAGEN A BASE64
     * ======================================= */
    private function toBase64($uploadedFile): ?string
    {
        if (!$uploadedFile) return null;
        $mime = $uploadedFile->getMimeType() ?: 'image/jpeg';
        $data = file_get_contents($uploadedFile->getRealPath());
        return 'data:' . $mime . ';base64,' . base64_encode($data);
    }

    /** ===== Store ===== */
    public function store()
    {
        try {
            $this->erroresFormulario = false;

            $this->validate(array_merge([
                'nombre'              => 'required|string|max:255',
                'descripcion'         => 'nullable|string|max:500',
                'subcategoria_id'     => 'required|exists:subcategorias,id',
                'precio'              => 'required|numeric|min:0',
                'costo'               => 'nullable|numeric|min:0',
                'activo'              => 'required|boolean',
                'impuesto_id'         => 'nullable|exists:impuestos,id',
                'unidad_medida_id'    => 'nullable|exists:unidades_medida,id',
               'imagen' => 'nullable|mimes:jpg,jpeg,png,webp,gif,svg,bmp|max:5120',
                'stockMinimoGlobal'   => 'nullable|integer|min:0',
                'stockMaximoGlobal'   => 'nullable|integer|min:0|gte:stockMinimoGlobal',
                'mov_contable_segun'  => 'required|in:' . Producto::MOV_SEGUN_ARTICULO . ',' . Producto::MOV_SEGUN_SUBCATEGORIA,
            ], $this->reglasCuentas()));

            if (Producto::where('nombre', $this->nombre)->exists()) {
                $this->addError('nombre', 'Ya existe un producto registrado con este nombre.');
                $this->erroresFormulario = true;
                return;
            }

            $this->aplicarStockGlobalSiExiste();

            // 🔥 Convertir a Base64 (sin usar storage)
            $imagenBase64 = $this->imagen ? $this->toBase64($this->imagen) : null;

            $producto = Producto::create([
                'nombre'              => $this->nombre,
                'descripcion'         => $this->descripcion,
                'precio'              => $this->precio,
                'costo'               => $this->costo ?? 0,
                'stock'               => 0,
                'stock_minimo'        => 0,
                'stock_maximo'        => null,
                'activo'              => $this->activo,
                'subcategoria_id'     => $this->subcategoria_id,
                'impuesto_id'         => $this->impuesto_id,
                'unidad_medida_id'    => $this->unidad_medida_id,
                'imagen_path'         => $imagenBase64, // << Base64 en BD
                'mov_contable_segun'  => $this->mov_contable_segun,
            ]);

            if ($this->mov_contable_segun === Producto::MOV_SEGUN_ARTICULO) {
                foreach ($this->cuentasPorTipo as $tipoId => $pucId) {
                    if (!$pucId) continue;
                    $producto->cuentas()->updateOrCreate(
                        ['tipo_id' => (int)$tipoId],
                        ['plan_cuentas_id' => (int)$pucId]
                    );
                }
            }

            foreach ($this->stocksPorBodega as $bodegaId => $stockData) {
                $producto->bodegas()->attach($bodegaId, [
                    'stock'        => 0,
                    'stock_minimo' => $stockData['stock_minimo'] ?? 0,
                    'stock_maximo' => $stockData['stock_maximo'] ?? null,
                ]);
            }

            $this->resetInput();
            PendingToast::create()->success()->message('Producto creado exitosamente.')->duration(5000);

        } catch (\Throwable $e) {
            Log::error('Error al guardar producto', ['message' => $e->getMessage()]);
            PendingToast::create()->error()->message('Error al guardar el producto: ' . $e->getMessage())->duration(9000);
        }
    }

    /** ===== Update ===== */
   public function update()
{
    try {
        $this->validate(array_merge([
            'nombre'              => 'required|string|max:255',
            'subcategoria_id'     => 'required|exists:subcategorias,id',
            'precio'              => 'required|numeric|min:0',
            'costo'               => 'nullable|numeric|min:0',
            'impuesto_id'         => 'nullable|exists:impuestos,id',
            'unidad_medida_id'    => 'nullable|exists:unidades_medida,id',
            'imagen' => 'nullable|mimes:jpg,jpeg,png,webp,gif,svg,bmp|max:5120',
            'mov_contable_segun'  => 'required|in:' . \App\Models\Productos\Producto::MOV_SEGUN_ARTICULO . ',' . \App\Models\Productos\Producto::MOV_SEGUN_SUBCATEGORIA,
        ], $this->reglasCuentas()));

        $this->aplicarStockGlobalSiExiste();

        $producto = \App\Models\Productos\Producto::findOrFail($this->producto_id);

        $data = [
            'nombre'              => $this->nombre,
            'descripcion'         => $this->descripcion,
            'precio'              => $this->precio,
            'costo'               => $this->costo ?? 0,
            'activo'              => $this->activo,
            'subcategoria_id'     => $this->subcategoria_id,
            'impuesto_id'         => $this->impuesto_id,
            'unidad_medida_id'    => $this->unidad_medida_id,
            'mov_contable_segun'  => $this->mov_contable_segun,
        ];

        // Si sube nueva imagen, reemplaza por Base64 (sin tocar disco)
        if ($this->imagen) {
            $data['imagen_path'] = $this->toBase64($this->imagen);
        }

        $producto->update($data);

        if ($this->mov_contable_segun === \App\Models\Productos\Producto::MOV_SEGUN_ARTICULO) {
            foreach ($this->cuentasPorTipo as $tipoId => $pucId) {
                if (!$pucId) continue;
                $producto->cuentas()->updateOrCreate(
                    ['tipo_id' => (int)$tipoId],
                    ['plan_cuentas_id' => (int)$pucId]
                );
            }
        } else {
            $producto->cuentas()->delete();
        }

        foreach ($this->stocksPorBodega as $bodegaId => $stockData) {
            $producto->bodegas()->syncWithoutDetaching([
                $bodegaId => [
                    'stock_minimo' => $stockData['stock_minimo'] ?? 0,
                    'stock_maximo' => $stockData['stock_maximo'] ?? null,
                ]
            ]);
        }

        $this->resetInput();
        \Masmerise\Toaster\PendingToast::create()
            ->success()
            ->message('Producto actualizado exitosamente.')
            ->duration(5000);

    } catch (\Throwable $e) {
        // Log completo para depurar
        \Illuminate\Support\Facades\Log::error('Error al actualizar producto', [
            'id'    => $this->producto_id,
            'msg'   => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);

        // Mensaje amigable y con detalle útil (limitado)
        $detalle = config('app.debug')
            ? $this->prettyException($e)
            : 'Ocurrió un error inesperado.';

        \Masmerise\Toaster\PendingToast::create()
            ->error()
            ->message('Error al actualizar el producto: '.$detalle)
            ->duration(12000);
    }
}

/**
 * Devuelve un mensaje legible según el tipo de excepción
 * (validación, QueryException de MySQL/SQL Server, etc.).
 */
private function prettyException(\Throwable $e): string
{
    // Validación
    if ($e instanceof \Illuminate\Validation\ValidationException) {
        return 'Corrige los campos marcados y vuelve a intentar.';
    }

    // Errores de BD: intenta extraer detalle del driver (PDO)
    if ($e instanceof \Illuminate\Database\QueryException) {
        $driverMsg = $e->errorInfo[2] ?? null;          // suele traer el detalle útil
        $msg = $driverMsg ?: $e->getMessage();
        return \Illuminate\Support\Str::limit($msg, 220);
    }

    // Si la excepción tiene "previous" (envoltorios)
    if ($e->getPrevious()) {
        return \Illuminate\Support\Str::limit($e->getPrevious()->getMessage(), 220);
    }

    // Fallback
    return \Illuminate\Support\Str::limit($e->getMessage(), 220);
}


    /** ===== Edit ===== */
    public function edit($id)
    {
        try {
            $producto = Producto::with(['bodegas','cuentas'])->findOrFail($id);

            $this->producto_id        = $producto->id;
            $this->nombre             = $producto->nombre;
            $this->descripcion        = $producto->descripcion;
            $this->precio             = $producto->precio;
            $this->costo              = $producto->costo;
            $this->activo             = (bool) $producto->activo;
            $this->subcategoria_id    = $producto->subcategoria_id;
            $this->impuesto_id        = $producto->impuesto_id;
            $this->unidad_medida_id   = $producto->unidad_medida_id;
            $this->imagen             = null; // no precargar archivos
            $this->mov_contable_segun = $producto->mov_contable_segun ?? Producto::MOV_SEGUN_ARTICULO;
            $this->isEdit             = true;

            $this->cuentasPorTipo = [];
            foreach ($this->tiposCuenta as $t) $this->cuentasPorTipo[$t->id] = null;
            foreach ($producto->cuentas as $pc) {
                $this->cuentasPorTipo[$pc->tipo_id] = $pc->plan_cuentas_id;
            }

            $this->stocksPorBodega = [];
            foreach ($producto->bodegas as $bodega) {
                $this->stocksPorBodega[$bodega->id] = [
                    'stock_minimo' => $bodega->pivot->stock_minimo,
                    'stock_maximo' => $bodega->pivot->stock_maximo,
                ];
            }
        } catch (\Throwable $e) {
            Log::error('Error al cargar producto para edición', ['id'=>$id, 'message'=>$e->getMessage()]);
            PendingToast::create()->error()->message('Error al cargar el producto.')->duration(7000);
        }
    }

    /** ===== Validación por campo ===== */
    public function updated($propertyName)
    {
        $this->validateOnly($propertyName, array_merge([
            'nombre'              => 'required|string|max:255',
            'descripcion'         => 'nullable|string|max:500',
            'subcategoria_id'     => 'required|exists:subcategorias,id',
            'precio'              => 'required|numeric|min:0',
            'costo'               => 'nullable|numeric|min:0',
            'activo'              => 'required|boolean',
            'impuesto_id'         => 'nullable|exists:impuestos,id',
            'unidad_medida_id'    => 'nullable|exists:unidades_medida,id',
            'imagen'              => 'nullable|image|max:2048',
            'stockMinimoGlobal'   => 'nullable|integer|min:0',
            'stockMaximoGlobal'   => 'nullable|integer|min:0|gte:stockMinimoGlobal',
            'mov_contable_segun'  => 'required|in:' . Producto::MOV_SEGUN_ARTICULO . ',' . Producto::MOV_SEGUN_SUBCATEGORIA,
        ], $this->reglasCuentas()));
    }

    /** ===== Helpers ===== */

    private function reglasCuentas(): array
    {
        if ($this->mov_contable_segun !== Producto::MOV_SEGUN_ARTICULO) {
            return [];
        }
        $rules = [];
        foreach ($this->tiposCuenta as $t) {
            $rules["cuentasPorTipo.{$t->id}"] =
                ($t->obligatorio ? 'required' : 'nullable') . '|exists:plan_cuentas,id';
        }
        return $rules;
    }

    private function aplicarStockGlobalSiExiste()
    {
        if (!is_null($this->stockMinimoGlobal) || !is_null($this->stockMaximoGlobal)) {
            foreach ($this->bodegas as $bodega) {
                $this->stocksPorBodega[$bodega->id]['stock_minimo'] = $this->stockMinimoGlobal ?? 0;
                $this->stocksPorBodega[$bodega->id]['stock_maximo'] = $this->stockMaximoGlobal ?? null;
            }
        }
    }

    private function resetInput()
    {
        $this->reset([
            'nombre','descripcion','precio','costo','activo','subcategoria_id','impuesto_id',
            'unidad_medida_id','imagen',
            'producto_id','isEdit','bodegaSeleccionada','stockMinimo','stockMaximo',
            'stocksPorBodega','stockMinimoGlobal','stockMaximoGlobal',
        ]);

        $this->mov_contable_segun = Producto::MOV_SEGUN_ARTICULO;

        $this->cuentasPorTipo = [];
        foreach ($this->tiposCuenta as $t) $this->cuentasPorTipo[$t->id] = null;
    }

    /** Computados */
    public function getImpuestoSeleccionadoProperty()
    {
        if (!$this->impuesto_id || !$this->impuestos) return null;
        return $this->impuestos->firstWhere('id', (int) $this->impuesto_id);
    }

    public function getDebeIngresarCuentasProperty(): bool
    {
        return $this->mov_contable_segun === Producto::MOV_SEGUN_ARTICULO;
    }

    public function getPrecioConIvaTmpProperty(): float
    {
        $base = (float) ($this->precio ?? 0);
        $imp  = $this->impuestoSeleccionado;

        if (!$imp) return round($base, 2);
        if (!is_null($imp->porcentaje)) return round($base * (1 + ((float)$imp->porcentaje / 100)), 2);
        if (!is_null($imp->monto_fijo)) return round($base + (float)$imp->monto_fijo, 2);
        return round($base, 2);
    }
}
