<?php

namespace App\Livewire\Cotizaciones;

use Livewire\Component;
use Livewire\Attributes\On;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

use App\Models\SocioNegocio\SocioNegocio;
use App\Models\Productos\Producto;
use App\Models\Bodega;

// ✅ alias del modelo para evitar choque con el nombre del componente
use App\Models\cotizaciones\cotizacione as CotizacionModel;
use Illuminate\Support\Facades\Schema;
use Maatwebsite\Excel\Validators\ValidationException;
use Masmerise\Toaster\PendingToast;

class Cotizacion extends Component
{
    // ✅ Livewire 2: no tipar como Model (ni como ?Cotizacion)
    public $cotizacion = null;

    public ?int $socio_negocio_id = null;
    public string $fecha = '';
    public ?string $vencimiento = null;        // ✅ tu columna real
    public ?string $lista_precio = null;       // ✅ tu columna real
    public ?string $terminos_pago = null;      // ✅ tu columna real
    public ?string $notas = null;
    public string $estado = 'borrador';

    public array $lineas = [];

    public bool $habilitarActualizar = false;
    private ?string $originalHash = null;

    protected $rules = [
        'socio_negocio_id'              => 'required|integer|exists:socio_negocios,id',
        'fecha'                         => 'required|date',
        'vencimiento'                   => 'nullable|date',

        'lineas'                        => 'required|array|min:1',
        'lineas.*.producto_id'          => 'required|integer|exists:productos,id',
        'lineas.*.bodega_id'            => 'nullable|integer|exists:bodegas,id',
        'lineas.*.cantidad'             => 'required|numeric|min:1',
        'lineas.*.precio_unitario'      => 'required|numeric|min:0',
        'lineas.*.descuento_pct'        => 'required|numeric|min:0|max:100',
        'lineas.*.impuesto_pct'         => 'required|numeric|min:0|max:100',
        'lineas.*.importe'              => 'required|numeric|min:0',
    ];

    protected array $validationAttributes = [
        'socio_negocio_id' => 'cliente',
        'vencimiento'      => 'vencimiento',
        'terminos_pago'    => 'términos de pago',
        'lineas'           => 'líneas',
        'lineas.*.producto_id'     => 'producto',
        'lineas.*.bodega_id'       => 'bodega',
        'lineas.*.cantidad'        => 'cantidad',
        'lineas.*.precio_unitario' => 'precio unitario',
        'lineas.*.descuento_pct'   => 'descuento (%)',
        'lineas.*.impuesto_pct'    => 'impuesto (%)',
        'lineas.*.importe'         => 'importe',
    ];

    #[On('abrir-cotizacion')]
    public function abrir(int $id): void
    {
        $this->cargarCotizacion($id);
    }

    public function mount(?int $id = null): void
    {
        try {
            $this->fecha = now()->toDateString();
            $this->vencimiento = now()->addDays(7)->toDateString();

            if ($id) {
                $this->cargarCotizacion($id);
            } else {
                $this->addLinea();
                $this->terminos_pago = 'Cotización sujeta a disponibilidad. Precios no incluyen transporte (si aplica).';
            }

            $this->takeSnapshot();
        } catch (\Throwable $e) {
            report($e);
            PendingToast::create()->error()->message('No se pudo inicializar la cotización.')->duration(7000);
        }
    }

    public function render()
    {
        $clientes = SocioNegocio::clientes()
            ->orderBy('razon_social')
            ->take(200)
            ->get();

        // ✅ SQL Server: NO pedir columnas que no existen (precio_venta)
        $productos = Producto::where('activo', 1)
            ->orderBy('nombre')
            ->take(300)
            ->get(['id', 'nombre', 'precio']);

        $bodegas = Bodega::query()
            ->when(Schema::hasColumn('bodegas', 'activo'), fn($q) => $q->where('activo', 1))
            ->orderBy('nombre')
            ->get(['id', 'nombre']);

        return view('livewire.cotizaciones.cotizacion', compact('clientes', 'productos', 'bodegas'));
    }

    /* =========================
     * Helpers líneas
     * ========================= */
    private function normalizeLinea(array &$l): void
    {
        // Detectar valores vacíos sin forzar conversión inmediata
        $cantRaw   = $l['cantidad'] ?? null;
        $precioRaw = $l['precio_unitario'] ?? null;
        $descRaw   = $l['descuento_pct'] ?? 0;
        $ivaRaw    = $l['impuesto_pct'] ?? 0;

        // Si están vacíos, mantener null (no forzar valores)
        $cant   = ($cantRaw === '' || $cantRaw === null) ? null : (float)$cantRaw;
        $precio = ($precioRaw === '' || $precioRaw === null) ? null : (float)$precioRaw;
        $desc   = ($descRaw === '' || $descRaw === null) ? 0 : (float)$descRaw;
        $iva    = ($ivaRaw === '' || $ivaRaw === null) ? 0 : (float)$ivaRaw;

        // Normalizar SOLO si hay valor
        $l['cantidad']        = is_null($cant) ? null : round(max(0, $cant), 3);
        $l['precio_unitario'] = is_null($precio) ? null : round(max(0, $precio), 2);
        $l['descuento_pct']   = min(100.0, max(0.0, round($desc, 3)));
        $l['impuesto_pct']    = min(100.0, max(0.0, round($iva, 3)));

        // Calcular importe SOLO si cantidad y precio existen
        if (is_null($l['cantidad']) || is_null($l['precio_unitario'])) {
            $l['importe'] = 0;
            return;
        }

        $base = ($l['cantidad'] * $l['precio_unitario'])
            * (1 - $l['descuento_pct'] / 100);

        $l['importe'] = round(max(0, $base), 2);
    }

    public function addLinea(): void
    {
        $l = [
            'producto_id'     => null,
            'bodega_id'       => null,
            'cantidad'        => null,
            'precio_unitario' => 0,
            'descuento_pct'   => 0,
            'impuesto_pct'    => 0,
            'importe'         => 0,
        ];

        $this->normalizeLinea($l);
        $this->lineas[] = $l;

        $this->markDirtyIfNeeded();
        $this->dispatch('$refresh');
    }

    public function removeLinea(int $i): void
    {
        if (!isset($this->lineas[$i])) return;

        array_splice($this->lineas, $i, 1);

        if (count($this->lineas) === 0) {
            $this->addLinea(); // mantener 1 fila mínima
        }

        $this->markDirtyIfNeeded();
        $this->dispatch('$refresh');
    }

    public function updated($name, $value): void
    {
        // producto cambiado
        if (preg_match('/^lineas\.(\d+)\.producto_id$/', $name, $m)) {
            $i = (int)$m[1];
            $this->setProducto($i, $value);

            $this->resetErrorBag();
            $this->resetValidation();

            $this->markDirtyIfNeeded();
            $this->dispatch('$refresh');
            return;
        }

        // recalcular al cambiar números
        if (preg_match('/^lineas\.(\d+)\.(cantidad|precio_unitario|descuento_pct|impuesto_pct)$/', $name, $m)) {
            $i = (int)$m[1];
            if (isset($this->lineas[$i])) {
                $this->normalizeLinea($this->lineas[$i]);
                $this->markDirtyIfNeeded();
                $this->dispatch('$refresh');
            }
            return;
        }

        // cualquier cambio en cabecera
        $this->markDirtyIfNeeded();
    }

    public function setProducto(int $i, $id): void
    {
        if (!isset($this->lineas[$i])) return;

        $prodId = $id ? (int)$id : null;
        $this->lineas[$i]['producto_id'] = $prodId;

        if (!$prodId) {
            $this->lineas[$i]['precio_unitario'] = 0;
            $this->lineas[$i]['impuesto_pct'] = 0;
            $this->normalizeLinea($this->lineas[$i]);
            return;
        }

        $p = Producto::find($prodId);
        if (!$p) return;

        // ✅ SQL Server: usar SOLO columnas existentes
        $precioBase = (float)($p->precio ?? 0);
        $this->lineas[$i]['precio_unitario'] = $precioBase;

        // si tu impuesto viene de otro lado, lo asignas aquí
        $this->normalizeLinea($this->lineas[$i]);
    }

    /* =========================
     * Totales (según tu modelo)
     * subtotal = suma(importe)
     * impuestos = suma(importe * impuesto_pct)
     * ========================= */
    public function getSubtotalProperty(): float
    {
        return round(collect($this->lineas)->sum(fn($l) => (float)($l['importe'] ?? 0)), 2);
    }

    public function getImpuestosTotalProperty(): float
    {
        $imp = 0.0;

        foreach ($this->lineas as $l) {
            $base = (float)($l['importe'] ?? 0); // base sin IVA
            $iva  = min(100, max(0, (float)($l['impuesto_pct'] ?? 0)));
            $imp += $base * $iva / 100;
        }

        return round($imp, 2);
    }

    public function getTotalProperty(): float
    {
        return round($this->subtotal + $this->impuestosTotal, 2);
    }

    /* =========================
     * Guardar / Actualizar
     * ========================= */
    private function validarConToast(): bool
    {
        try {
            $this->validate($this->rules, [], $this->validationAttributes);
            return true;
        } catch (ValidationException $e) {
            $first = collect($e->validator->errors()->all())->first() ?: 'Revisa los campos obligatorios.';
            PendingToast::create()->error()->message($first)->duration(9000);
            return false;
        }
    }

    public function guardar(): void
    {
        try {
            if (!$this->validarConToast()) return;

            DB::transaction(function () {

                if (!$this->cotizacion) {
                    $this->cotizacion = new CotizacionModel();
                } else {
                    // si viene como array accidentalmente, recargar modelo
                    if (is_array($this->cotizacion) && !empty($this->cotizacion['id'])) {
                        $this->cotizacion = CotizacionModel::find((int)$this->cotizacion['id']);
                    }
                }

                $cab = [
                    'socio_negocio_id' => $this->socio_negocio_id,
                    'fecha'            => $this->fecha,
                    'vencimiento'      => $this->vencimiento,
                    'lista_precio'     => $this->lista_precio,
                    'terminos_pago'    => $this->terminos_pago,
                    'estado'           => 'borrador',
                    'notas'            => $this->notas,

                    'subtotal'         => $this->subtotal,
                    'impuestos'        => $this->impuestosTotal,
                    'total'            => $this->total,
                ];

                \Illuminate\Database\Eloquent\Model::unguarded(function () use ($cab) {
                    $this->cotizacion->forceFill($cab)->save();
                });

                // reemplazar detalles
                $this->cotizacion->detalles()->delete();

                $payload = [];
                foreach ($this->lineas as $l) {
                    $payload[] = [
                        'producto_id'     => $l['producto_id'] ?? null,
                        'bodega_id'       => $l['bodega_id'] ?? null,
                        'cantidad'        => (float)($l['cantidad'] ?? 1),
                        'precio_unitario' => (float)($l['precio_unitario'] ?? 0),
                        'descuento_pct'   => (float)($l['descuento_pct'] ?? 0),
                        'impuesto_pct'    => (float)($l['impuesto_pct'] ?? 0),
                        'importe'         => (float)($l['importe'] ?? 0),
                    ];
                }

                \Illuminate\Database\Eloquent\Model::unguarded(function () use ($payload) {
                    $this->cotizacion->detalles()->createMany($payload);
                });

                // ✅ recalcular totales según tu modelo (este método guarda internamente)
                $this->cotizacion->load('detalles');
                $this->cotizacion->recalcularTotales();

                $this->estado = (string)($this->cotizacion->estado ?? 'borrador');
            });

            $this->takeSnapshot();

            PendingToast::create()->success()
                ->message('Cotización guardada (ID: ' . $this->cotizacion->id . ').')
                ->duration(5000);

            $this->dispatch('refrescar-lista-cotizaciones');
        } catch (\Throwable $e) {
            Log::error('COTIZACION GUARDAR ERROR', ['msg' => $e->getMessage()]);
            PendingToast::create()->error()
                ->message(config('app.debug') ? $e->getMessage() : 'No se pudo guardar la cotización.')
                ->duration(9000);
        }
    }

    public function actualizar(): void
    {
        if (!$this->cotizacion || empty($this->cotizacion->id)) {
            $this->guardar();
            return;
        }

        $this->guardar();
        $this->takeSnapshot();
    }

    private function cargarCotizacion(int $id): void
    {
        $c = CotizacionModel::with('detalles')->findOrFail($id);
        $this->cotizacion = $c;

        $this->fill([
            'socio_negocio_id' => $c->socio_negocio_id,
            'fecha'            => $c->fecha,
            'vencimiento'      => $c->vencimiento,
            'lista_precio'     => $c->lista_precio,
            'terminos_pago'    => $c->terminos_pago,
            'notas'            => $c->notas,
            'estado'           => $c->estado ?? 'borrador',
        ]);

        $this->lineas = $c->detalles->map(function ($d) {
            return [
                'id'              => $d->id,
                'producto_id'     => $d->producto_id,
                'bodega_id'       => $d->bodega_id,
                'cantidad'        => (float)$d->cantidad,
                'precio_unitario' => (float)$d->precio_unitario,
                'descuento_pct'   => (float)$d->descuento_pct,
                'impuesto_pct'    => (float)$d->impuesto_pct,
                'importe'         => (float)$d->importe,
            ];
        })->toArray();

        foreach ($this->lineas as &$l) {
            $this->normalizeLinea($l);
        }

        $this->resetErrorBag();
        $this->resetValidation();
        $this->takeSnapshot();
    }

    /* =========================
     * Snapshot (habilitarActualizar)
     * ========================= */
    private function computeHash(): string
    {
        $payload = [
            'socio_negocio_id' => (int)($this->socio_negocio_id ?? 0),
            'fecha'            => (string)$this->fecha,
            'vencimiento'      => (string)($this->vencimiento ?? ''),
            'lista_precio'     => (string)($this->lista_precio ?? ''),
            'terminos_pago'    => (string)($this->terminos_pago ?? ''),
            'notas'            => (string)($this->notas ?? ''),
            'estado'           => (string)($this->estado ?? 'borrador'),
            'lineas' => array_values(array_map(function ($l) {
                return [
                    'producto_id'     => (int)($l['producto_id'] ?? 0),
                    'bodega_id'       => (int)($l['bodega_id'] ?? 0),
                    'cantidad'        => round((float)($l['cantidad'] ?? 0), 3),
                    'precio_unitario' => round((float)($l['precio_unitario'] ?? 0), 2),
                    'descuento_pct'   => round((float)($l['descuento_pct'] ?? 0), 3),
                    'impuesto_pct'    => round((float)($l['impuesto_pct'] ?? 0), 3),
                    'importe'         => round((float)($l['importe'] ?? 0), 2),
                ];
            }, $this->lineas ?? [])),
        ];

        return hash('sha256', json_encode($payload));
    }

    private function takeSnapshot(): void
    {
        $this->originalHash = $this->computeHash();
        $this->habilitarActualizar = false;
    }

    private function markDirtyIfNeeded(): void
    {
        $this->habilitarActualizar = $this->computeHash() !== ($this->originalHash ?? '');
    }
}
