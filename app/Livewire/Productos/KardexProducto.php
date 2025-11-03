<?php

namespace App\Livewire\Productos;

use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Str;
use Carbon\Carbon;

use App\Models\Productos\Producto;
use App\Models\Bodega;
use App\Models\Movimiento\ProductoCostoMovimiento;

class KardexProducto extends Component
{
    use WithPagination;

    protected string $paginationTheme = 'tailwind';

    /** ==== Filtros ==== */
    public ?int $producto_id = null;
    public ?int $bodega_id   = null;
    public ?string $desde    = null;
    public ?string $hasta    = null;
    public string $buscarDoc = '';
    public int $perPage      = 25;

    /** ==== Catálogos ==== */
    public $productos;
    public $bodegas;

    protected $queryString = [
        'producto_id' => ['except' => null],
        'bodega_id'   => ['except' => null],
        'desde'       => ['except' => null],
        'hasta'       => ['except' => null],
        'buscarDoc'   => ['except' => ''],
        'perPage'     => ['except' => 25],
    ];

    public function mount(?int $productoId = null): void
    {
        $this->productos = Producto::orderBy('nombre')->get(['id','nombre']);
        $this->bodegas   = Bodega::orderBy('nombre')->get(['id','nombre']);

        $this->producto_id = $productoId ?: $this->producto_id;
        $this->desde = $this->desde ?: Carbon::now()->subDays(90)->toDateString();
        $this->hasta = $this->hasta ?: Carbon::now()->toDateString();
    }

    public function updating($prop): void
    {
        $this->resetPage();
    }

    public function render()
    {
        // Sanitiza perPage
        $this->perPage = in_array((int)$this->perPage, [10,25,50,100], true) ? (int)$this->perPage : 25;

        $filas = ProductoCostoMovimiento::query()
            ->with('tipoDocumento:id,codigo,nombre')
            ->when($this->producto_id, fn($q) => $q->where('producto_id', $this->producto_id))
            ->when($this->bodega_id,   fn($q) => $q->where('bodega_id', $this->bodega_id))
            ->when($this->desde,       fn($q) => $q->where('fecha', '>=', Carbon::parse($this->desde)->startOfDay()))
            ->when($this->hasta,       fn($q) => $q->where('fecha', '<=', Carbon::parse($this->hasta)->endOfDay()))
            ->when(trim($this->buscarDoc) !== '', function ($q) {
                $txt = '%' . (string) Str::of($this->buscarDoc)->trim() . '%';
                $q->where(function ($x) use ($txt) {
                    $x->orWhere('doc_id', 'like', $txt)
                      ->orWhere('ref', 'like', $txt)
                      ->orWhereHas('tipoDocumento', fn($qq) =>
                          $qq->where('nombre','like',$txt)->orWhere('codigo','like',$txt)
                      );
                });
            })
            ->orderBy('fecha')
            ->orderBy('id')
            ->paginate($this->perPage);

        // 🔽 CORRECCIÓN: usar campo 'tipo' en lugar del signo de cantidad
        $bodegasIndex = $this->bodegas->keyBy('id');
        $items = collect($filas->items())->map(function ($m) use ($bodegasIndex) {
            // Siempre usa valor absoluto de cantidad
            $cant = abs((float) ($m->cantidad ?? 0));
            
            // Lee el campo 'tipo' de la base de datos
            $tipo = strtoupper(trim((string) ($m->tipo ?? '')));

            // ✅ Determinar entrada/salida según el campo 'tipo'
            $entrada = null;
            $salida  = null;

            if (in_array($tipo, ['ENTRADA', 'COMPRA'], true)) {
                $entrada = $cant;
            } elseif (in_array($tipo, ['SALIDA', 'VENTA'], true)) {
                $salida = $cant;
            } else {
                // Fallback: si no hay tipo, usar el signo de cantidad original
                if ((float)($m->cantidad ?? 0) > 0) {
                    $entrada = $cant;
                } else {
                    $salida = $cant;
                }
            }

            // Construir texto del documento
            $tipoDoc = $m->tipoDocumento?->codigo ?: $m->tipoDocumento?->nombre;
            $docTxt  = trim(($tipoDoc ? $tipoDoc.' ' : '') . ($m->doc_id ? '#'.$m->doc_id : ''));
            if ($m->ref) $docTxt .= ' ('.$m->ref.')';
            $docTxt = $docTxt ?: '—';

            return [
                'id'                    => $m->id,
                'fecha'                 => $m->fecha instanceof Carbon ? $m->fecha->format('Y-m-d H:i') : (string) $m->fecha,
                'bodega'                => $bodegasIndex[$m->bodega_id]->nombre ?? '—',
                'doc'                   => $docTxt,
                'entrada'               => $entrada,
                'salida'                => $salida,
                'costo_unit_mov'        => (float) ($m->costo_unit_mov ?? $m->costo_unitario ?? 0),
                'costo_prom_nuevo'      => (float) ($m->costo_prom_nuevo ?? 0),
                'ultimo_costo_nuevo'    => (float) ($m->ultimo_costo_nuevo ?? 0),
                'metodo_costeo'         => $m->metodo_costeo ?? 'PROMEDIO',
                'tipo_evento'           => $m->tipo_evento ?? $tipo,
            ];
        });

        return view('livewire.productos.kardex-producto', [
            'filas'  => $filas,
            'items'  => $items,
        ]);
    }
}