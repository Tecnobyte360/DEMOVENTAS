<?php

namespace App\Livewire\Productos;

use App\Models\Bodega;
use Livewire\Component;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

use App\Models\Productos\Producto;
use App\Models\Categorias\Subcategoria;
use App\Models\Impuestos\Impuesto as ImpuestoModel;
use App\Models\CuentasContables\PlanCuentas;
use App\Models\Productos\ProductoCuentaTipo;
use App\Models\UnidadesMedida;
use Masmerise\Toaster\PendingToast;

class Productos extends Component
{
    /** ===== Listas ===== */
    public $productos, $subcategorias, $bodegas, $impuestos, $unidades;

    /** Catálogos dinámicos para cuentas */
    public $tiposCuenta;
    public $cuentasPUC;
    public bool $es_inventariable = true;
    /** Selección de cuentas por tipo_id: [tipo_id => plan_cuentas_id] */
    public array $cuentasPorTipo = [];

    /** Campos producto */
    public $nombre, $descripcion, $costo, $precio, $activo = true, $subcategoria_id;
    public ?int $impuesto_id = null;

    /** Unidad de medida + imagen (como Base64) */
    public ?int $unidad_medida_id = null;
    public ?string $imagen_base64 = null; // data URL base64

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
        $this->bodegas       = Bodega::where('activo', true)->orderBy('nombre')->get();
        $this->es_inventariable = true;
        $this->impuestos = ImpuestoModel::with('tipo')
            ->where('activo', true)
            ->orderBy('prioridad')->orderBy('nombre')
            ->get();

        $this->tiposCuenta = ProductoCuentaTipo::activos()
            ->orderBy('orden')->orderBy('id')
            ->get(['id', 'codigo', 'nombre', 'obligatorio', 'orden']);

        $this->cuentasPUC = PlanCuentas::imputables()
            ->where('nivel', 5)
            ->ordenCodigo()
            ->get(['id', 'codigo', 'nombre']);

        $this->unidades = UnidadesMedida::where('activo', true)
            ->orderBy('nombre')
            ->get(['id', 'nombre', 'simbolo', 'codigo']);

        foreach ($this->tiposCuenta as $t) {
            $this->cuentasPorTipo[$t->id] = null;
        }

        $this->mov_contable_segun = Producto::MOV_SEGUN_ARTICULO;
    }

  public function render()
{
    $query = Producto::with([
        'subcategoria',
        'bodegas',           // importante para costos por bodega
        'impuesto',
        'cuentas.tipo',
        'cuentas.cuentaPUC',
        'unidadMedida',
    ]);

    if ($this->search) {
        $query->where('nombre', 'like', '%' . $this->search . '%');
    }

    // Cargar productos
    $this->productos = $query->get()->map(function (Producto $p) {
        // Adjuntamos costos por bodega y promedio global
        $p->setAttribute('costos_por_bodega', $p->costosPorBodega());
        $p->setAttribute('costo_promedio_global', $p->costo_promedio_global);
        return $p;
    });

    // Preview de precio + IVA mientras editas
    if ($this->isEdit && $this->producto_id) {
        $this->productos = $this->productos->map(function ($p) {
            if ($p->id === (int)$this->producto_id) {
                $p->precio = (float)($this->precio ?? $p->precio);
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


    /* ========================= EDIT ========================= */
    public function edit(int $id): void
    {
        try {
            $producto = Producto::with(['bodegas', 'cuentas'])->findOrFail($id);

            $this->producto_id        = $producto->id;
            $this->nombre             = $producto->nombre;
            $this->descripcion        = $producto->descripcion;
            $this->precio             = $producto->precio;
            $this->costo              = $producto->costo;
            $this->activo             = (bool) $producto->activo;
            $this->subcategoria_id    = $producto->subcategoria_id;
            $this->impuesto_id        = $producto->impuesto_id;
            $this->unidad_medida_id   = $producto->unidad_medida_id;
            $this->imagen_base64      = null; // no precargamos la dataURL (ya se muestra desde BD)
            $this->mov_contable_segun = $producto->mov_contable_segun ?? Producto::MOV_SEGUN_ARTICULO;
            $this->isEdit             = true;

            // Cuentas actuales
            $this->cuentasPorTipo = [];
            foreach ($this->tiposCuenta as $t) $this->cuentasPorTipo[$t->id] = null;
            foreach ($producto->cuentas as $pc) {
                $this->cuentasPorTipo[$pc->tipo_id] = $pc->plan_cuentas_id;
            }

            // Stocks por bodega
            $this->stocksPorBodega = [];
            foreach ($producto->bodegas as $bodega) {
                $this->stocksPorBodega[$bodega->id] = [
                    'stock_minimo' => $bodega->pivot->stock_minimo,
                    'stock_maximo' => $bodega->pivot->stock_maximo,
                ];
            }
        } catch (\Throwable $e) {
            Log::error('Error al cargar producto para edición', ['id' => $id, 'msg' => $e->getMessage()]);
            PendingToast::create()->error()->message('Error al cargar el producto.')->duration(7000);
        }
    }


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

    public function setMovContableSegun(string $valor): void
    {
        $valor = strtoupper($valor);
        if (!in_array($valor, [Producto::MOV_SEGUN_ARTICULO, Producto::MOV_SEGUN_SUBCATEGORIA], true)) return;

        $this->mov_contable_segun = $valor;
        if ($valor === Producto::MOV_SEGUN_SUBCATEGORIA) {
            foreach ($this->tiposCuenta as $t) $this->cuentasPorTipo[$t->id] = null;
        }
    }

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
        if (!$this->es_inventariable) {
            PendingToast::create()->error()->message('Los servicios no gestionan stock por bodega.')->duration(4000);
            return;
        }
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
    public function eliminarBodega($id)
    {
        unset($this->stocksPorBodega[$id]);
    }

    /* =======================================
     * Validar imagen Base64 (sin storage)
     * ======================================= */
    private function validarDataUrl(?string $dataUrl, int $maxMB = 5): void
    {
        if (!$dataUrl) return;

        if (!preg_match('#^data:(image/(png|jpe?g|webp|gif|bmp|svg\+xml));base64,#i', $dataUrl)) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'imagen_base64' => 'Formato de imagen no permitido.',
            ]);
        }

        [$meta, $payload] = explode(',', $dataUrl, 2);
        $sizeBytes = (int) (strlen($payload) * 3 / 4); // aproximado
        $maxBytes  = $maxMB * 1024 * 1024;

        if ($sizeBytes > $maxBytes) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'imagen_base64' => "La imagen supera los {$maxMB} MB.",
            ]);
        }
    }

    /* ========================= Store ========================= */
    public function store()
    {
        try {
            $this->erroresFormulario = false;

            $this->validate(array_merge([
                'nombre'             => 'required|string|max:255',
                'descripcion'        => 'nullable|string|max:500',
                'subcategoria_id'    => 'required|exists:subcategorias,id',
                'precio'             => 'required|numeric|min:0',
                'costo'              => 'nullable|numeric|min:0',
                'activo'             => 'required|boolean',
                'impuesto_id'        => 'nullable|exists:impuestos,id',
                'unidad_medida_id'   => 'nullable|exists:unidades_medida,id',
                'stockMinimoGlobal'  => 'nullable|integer|min:0',
                'stockMaximoGlobal'  => 'nullable|integer|min:0|gte:stockMinimoGlobal',
                'mov_contable_segun' => 'required|in:' . Producto::MOV_SEGUN_ARTICULO . ',' . Producto::MOV_SEGUN_SUBCATEGORIA,
            ], $this->reglasCuentas()));

            // Validar imagen Base64 (si viene)
            $this->validarDataUrl($this->imagen_base64, 5);

            if (Producto::where('nombre', $this->nombre)->exists()) {
                $this->addError('nombre', 'Ya existe un producto registrado con este nombre.');
                $this->erroresFormulario = true;
                return;
            }

            $this->aplicarStockGlobalSiExiste();

            $producto = Producto::create([
                'nombre'             => $this->nombre,
                'descripcion'        => $this->descripcion,
                'precio'             => $this->precio,
                'costo'              => $this->costo ?? 0,
                'stock'              => 0,
                'stock_minimo'       => 0,
                'stock_maximo'       => null,
                'activo'             => $this->activo,
                'subcategoria_id'    => $this->subcategoria_id,
                'impuesto_id'        => $this->impuesto_id,
                'unidad_medida_id'   => $this->unidad_medida_id,
                'imagen_path'        => $this->imagen_base64,
                'mov_contable_segun' => $this->mov_contable_segun,
                'es_inventariable'   => $this->es_inventariable,
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

            if ($this->es_inventariable) {
                foreach ($this->stocksPorBodega as $bodegaId => $stockData) {
                    $producto->bodegas()->attach($bodegaId, [
                        'stock'        => 0,
                        'stock_minimo' => $stockData['stock_minimo'] ?? 0,
                        'stock_maximo' => $stockData['stock_maximo'] ?? null,
                    ]);
                }
            }

            $this->resetInput();
            PendingToast::create()->success()->message('Producto creado exitosamente.')->duration(5000);
        } catch (\Throwable $e) {
            Log::error('Error al guardar producto', ['message' => $e->getMessage()]);
            PendingToast::create()->error()->message('Error al guardar el producto: ' . Str::limit($e->getMessage(), 220))->duration(9000);
        }
    }

    /* ========================= Update ========================= */
    public function update()
    {
        try {
            $this->validate(array_merge([
                'nombre'             => 'required|string|max:255',
                'subcategoria_id'    => 'required|exists:subcategorias,id',
                'precio'             => 'required|numeric|min:0',
                'costo'              => 'nullable|numeric|min:0',
                'impuesto_id'        => 'nullable|exists:impuestos,id',
                'unidad_medida_id'   => 'nullable|exists:unidades_medida,id',
                'mov_contable_segun' => 'required|in:' . Producto::MOV_SEGUN_ARTICULO . ',' . Producto::MOV_SEGUN_SUBCATEGORIA,
            ], $this->reglasCuentas()));

            if (!empty($this->imagen_base64)) {
                $this->validarDataUrl($this->imagen_base64, 5);
            }

            $this->aplicarStockGlobalSiExiste();

            $producto = Producto::findOrFail($this->producto_id);

            $data = [
                'nombre'             => $this->nombre,
                'descripcion'        => $this->descripcion,
                'precio'             => $this->precio,
                'costo'              => $this->costo ?? 0,
                'activo'             => $this->activo,
                'subcategoria_id'    => $this->subcategoria_id,
                'impuesto_id'        => $this->impuesto_id,
                'unidad_medida_id'   => $this->unidad_medida_id,
                'mov_contable_segun' => $this->mov_contable_segun,
                'es_inventariable'   => $this->es_inventariable,
            ];

            if (!empty($this->imagen_base64)) {
                $data['imagen_path'] = $this->imagen_base64;
            }

            $producto->update($data);

            if ($this->mov_contable_segun === Producto::MOV_SEGUN_ARTICULO) {
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
            PendingToast::create()->success()->message('Producto actualizado exitosamente.')->duration(5000);
        } catch (\Throwable $e) {
            Log::error('Error al actualizar producto', [
                'id' => $this->producto_id,
                'msg' => $e->getMessage(),
            ]);
            PendingToast::create()->error()->message('Error al actualizar el producto: ' . Str::limit($e->getMessage(), 220))->duration(12000);
        }
    }

    /** ===== Validación por campo ===== */
    public function updated($propertyName)
    {
        $this->validateOnly($propertyName, array_merge([
            'nombre'             => 'required|string|max:255',
            'descripcion'        => 'nullable|string|max:500',
            'subcategoria_id'    => 'required|exists:subcategorias,id',
            'precio'             => 'required|numeric|min:0',
            'costo'              => 'nullable|numeric|min:0',
            'activo'             => 'required|boolean',
            'impuesto_id'        => 'nullable|exists:impuestos,id',
            'unidad_medida_id'   => 'nullable|exists:unidades_medida,id',
            'stockMinimoGlobal'  => 'nullable|integer|min:0',
            'stockMaximoGlobal'  => 'nullable|integer|min:0|gte:stockMinimoGlobal',
            'mov_contable_segun' => 'required|in:' . Producto::MOV_SEGUN_ARTICULO . ',' . Producto::MOV_SEGUN_SUBCATEGORIA,
        ], $this->reglasCuentas()));
    }

    /** ===== Helpers ===== */
    private function reglasCuentas(): array
    {
        if ($this->mov_contable_segun !== Producto::MOV_SEGUN_ARTICULO) return [];
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
            'nombre',
            'descripcion',
            'precio',
            'costo',
            'activo',
            'subcategoria_id',
            'impuesto_id',
            'unidad_medida_id',
            'imagen_base64',
            'producto_id',
            'isEdit',
            'bodegaSeleccionada',
            'stockMinimo',
            'stockMaximo',
            'stocksPorBodega',
            'stockMinimoGlobal',
            'stockMaximoGlobal',
            'es_inventariable',
        ]);

        $this->mov_contable_segun = Producto::MOV_SEGUN_ARTICULO;

        $this->cuentasPorTipo = [];
        foreach ($this->tiposCuenta as $t) $this->cuentasPorTipo[$t->id] = null;
    }

    /** Computados */
    public function getImpuestoSeleccionadoProperty()
    {
        if (!$this->impuesto_id || !$this->impuestos) return null;
        return $this->impuestos->firstWhere('id', (int)$this->impuesto_id);
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

    public function costosPorBodega(): array
    {
        $this->loadMissing('bodegas');

        return $this->bodegas
            ->mapWithKeys(function ($b) {
                return [
                    $b->id => [
                        'bodega'         => $b->nombre,
                        'ultimo_costo'   => is_null($b->pivot->ultimo_costo) ? null : (float) $b->pivot->ultimo_costo,
                        'costo_promedio' => is_null($b->pivot->costo_promedio) ? null : (float) $b->pivot->costo_promedio,
                        'stock'          => (float) ($b->pivot->stock ?? 0),
                    ],
                ];
            })
            ->all();
    }
    public function getCostoPromedioGlobalAttribute(): ?float
    {
        if (!$this->relationLoaded('bodegas')) return null;

        $totalStock = $this->bodegas->sum(fn($b) => (float) ($b->pivot->stock ?? 0));

        // Si no hay stock, promedio simple de los costos_promedio existentes
        if ($totalStock <= 0) {
            $vals = $this->bodegas
                ->pluck('pivot.costo_promedio')
                ->filter(fn($v) => !is_null($v))
                ->map(fn($v) => (float) $v);

            return $vals->isEmpty() ? null : round($vals->avg(), 6);
        }

        // Promedio ponderado por stock
        $sum = $this->bodegas->sum(function ($b) {
            $stock = (float) ($b->pivot->stock ?? 0);
            $cpu   = (float) ($b->pivot->costo_promedio ?? 0);
            return $stock * $cpu;
        });

        return round($sum / max($totalStock, 1), 6);
    }
}
