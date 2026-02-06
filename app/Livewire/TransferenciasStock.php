<?php

namespace App\Livewire;

use App\Models\Bodega;
use App\Models\Productos\Producto;
use App\Models\Productos\ProductoBodega;
use App\Services\Inventario\TransferenciaStockService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;
use Throwable;

class TransferenciasStock extends Component
{
    use WithPagination;

    protected $paginationTheme = 'tailwind';

    /** Listas */
    public $productos;
    public $bodegas;

    /** Formulario */
    public ?int $producto_id = null;
    public ?int $bodega_origen_id = null;
    public ?int $bodega_destino_id = null;
    public $cantidad = 0;
    public ?string $observacion = null;

    /** UI Helpers */
    public ?float $stock_origen = null;
    public bool $disabledTransferir = true;

    /** =========================
     *  FILTROS TABLA
     *  ========================= */
    public string $f_buscar = '';
    public string $f_bodega = '';
    public string $f_desde  = '';
    public string $f_hasta  = '';

    public function mount(): void
    {
        $this->productos = Producto::orderBy('nombre')->get(['id', 'nombre']);
        $this->bodegas   = Bodega::where('activo', true)->orderBy('nombre')->get(['id', 'nombre']);

        $this->recalcularStockOrigen();
        $this->recalcularDisabled();
    }

    protected function rules(): array
    {
        return [
            'producto_id'       => ['required', 'integer', 'exists:productos,id'],
            'bodega_origen_id'  => ['required', 'integer', 'exists:bodegas,id'],
            'bodega_destino_id' => ['required', 'integer', 'different:bodega_origen_id', 'exists:bodegas,id'],
            'cantidad'          => ['required', 'numeric', 'min:0.000001'],
            'observacion'       => ['nullable', 'string', 'max:500'],
        ];
    }

    protected array $messages = [
        'producto_id.required'         => 'Debes seleccionar un producto.',
        'bodega_origen_id.required'    => 'Debes seleccionar la bodega origen.',
        'bodega_destino_id.required'   => 'Debes seleccionar la bodega destino.',
        'bodega_destino_id.different'  => 'La bodega destino debe ser diferente a la bodega origen.',
        'cantidad.required'            => 'Debes ingresar una cantidad.',
        'cantidad.min'                 => 'La cantidad debe ser mayor que 0.',
    ];

    public function updated($property): void
    {
        // Form
        if (in_array($property, ['producto_id', 'bodega_origen_id', 'cantidad', 'bodega_destino_id'], true)) {
            $this->resetErrorBag('cantidad');
            $this->recalcularStockOrigen();
            $this->recalcularDisabled();
        }

        // Filtros tabla
        if (in_array($property, ['f_buscar', 'f_bodega', 'f_desde', 'f_hasta'], true)) {
            $this->resetPage();
        }
    }

    private function recalcularStockOrigen(): void
    {
        $this->stock_origen = null;

        if (!$this->producto_id || !$this->bodega_origen_id) return;

        $row = ProductoBodega::query()
            ->where('producto_id', $this->producto_id)
            ->where('bodega_id', $this->bodega_origen_id)
            ->first();

        $this->stock_origen = $row ? (float) ($row->stock ?? 0) : 0.0;
    }

    private function recalcularDisabled(): void
    {
        $this->disabledTransferir = true;

        if (!$this->producto_id) return;
        if (!$this->bodega_origen_id) return;
        if (!$this->bodega_destino_id) return;
        if ((int) $this->bodega_origen_id === (int) $this->bodega_destino_id) return;

        $cant = (float) $this->cantidad;
        if ($cant <= 0) return;

        if (!is_null($this->stock_origen) && $cant > (float) $this->stock_origen) return;

        $this->disabledTransferir = false;
    }

    public function transferir(TransferenciaStockService $svc): void
    {
        $this->validate();

        $cant = (float) $this->cantidad;

        if (!is_null($this->stock_origen) && $cant > (float) $this->stock_origen) {
            $this->addError('cantidad', 'La cantidad supera el stock disponible en la bodega origen.');
            $this->dispatch('toast', type: 'error', message: 'Stock insuficiente en bodega origen.');
            $this->recalcularDisabled();
            return;
        }

        try {
            $res = $svc->transferir(
                (int) $this->producto_id,
                (int) $this->bodega_origen_id,
                (int) $this->bodega_destino_id,
                $cant,
                Auth::id(),
                $this->observacion
            );

            $this->dispatch('toast', type: 'success', message: "Transferencia OK. CPU: {$res['cpu']} | Total: {$res['costo_total']}");

            // reset form
            $this->reset(['producto_id', 'bodega_origen_id', 'bodega_destino_id', 'cantidad', 'observacion']);
            $this->resetErrorBag();

            // recalcula helpers (deja UI coherente)
            $this->recalcularStockOrigen();
            $this->recalcularDisabled();

            // refresca histórico
            $this->resetPage();

        } catch (Throwable $e) {
            report($e);
            $this->dispatch('toast', type: 'error', message: $e->getMessage() ?: 'Error al transferir.');
        }
    }

    public function limpiarFiltrosTabla(): void
    {
        $this->reset(['f_buscar', 'f_bodega', 'f_desde', 'f_hasta']);
        $this->resetPage();
    }

    public function render()
    {
        $q = DB::table('transferencias_stock as ts')
            ->join('productos as p', 'p.id', '=', 'ts.producto_id')
            ->join('bodegas as bo', 'bo.id', '=', 'ts.bodega_origen_id')
            ->join('bodegas as bd', 'bd.id', '=', 'ts.bodega_destino_id')
            ->select([
                'ts.id',
                'ts.created_at',
                'ts.cantidad',
                'ts.costo_unitario',
                'ts.costo_total',
                'ts.observacion',
                'p.nombre as producto',
                'bo.nombre as bodega_origen',
                'bd.nombre as bodega_destino',
                'ts.user_id',
            ])
            ->orderByDesc('ts.id');

        if (trim($this->f_buscar) !== '') {
            $term = trim($this->f_buscar);
            $q->where('p.nombre', 'like', "%{$term}%");
        }

        if ($this->f_bodega !== '') {
            $bid = (int) $this->f_bodega;
            $q->where(function ($qq) use ($bid) {
                $qq->where('ts.bodega_origen_id', $bid)
                   ->orWhere('ts.bodega_destino_id', $bid);
            });
        }

        if ($this->f_desde !== '') $q->whereDate('ts.created_at', '>=', $this->f_desde);
        if ($this->f_hasta !== '') $q->whereDate('ts.created_at', '<=', $this->f_hasta);

        return view('livewire.transferencias-stock', [
            'transferencias' => $q->paginate(10),
        ]);
    }
}
